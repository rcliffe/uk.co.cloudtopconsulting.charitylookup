<?php
declare(strict_types = 1);

namespace Civi\Charitylookup;

use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\Phone;
use Civi\Api4\Website;
use CRM_Charitylookup_ExtensionUtil as E;

/**
 * Looks a charity up on the register and copies the details onto an
 * Organisation contact.
 */
class Updater {

  public const NUMBER_FIELD = 'charity_commission.registered_number';

  private Client $client;

  private bool $checkPermissions;

  public function __construct(?Client $client = NULL, bool $checkPermissions = TRUE) {
    $this->client = $client ?? new Client();
    $this->checkPermissions = $checkPermissions;
  }

  /**
   * @param int $contactId
   * @param string|null $registeredNumber
   *   Number to look up (and save). If NULL, the number already stored on the
   *   contact is used.
   *
   * @return array
   *   ['contact_id', 'registered_number', 'charity_name', 'updated' => [labels]]
   *
   * @throws \CRM_Core_Exception
   */
  public function updateContact(int $contactId, ?string $registeredNumber = NULL): array {
    $contact = Contact::get($this->checkPermissions)
      ->addSelect('id', 'contact_type', 'organization_name', self::NUMBER_FIELD)
      ->addWhere('id', '=', $contactId)
      ->execute()->first();
    if (!$contact) {
      throw new \CRM_Core_Exception(E::ts('Contact %1 not found.', [1 => $contactId]));
    }
    if ($contact['contact_type'] !== 'Organization') {
      throw new \CRM_Core_Exception(E::ts('Charity lookup only works for organisations.'));
    }
    $registeredNumber = trim((string) ($registeredNumber ?? $contact[self::NUMBER_FIELD] ?? ''));
    if ($registeredNumber === '') {
      throw new \CRM_Core_Exception(E::ts('This organisation has no registered charity number.'));
    }

    $details = $this->client->getAllCharityDetails($registeredNumber);
    try {
      $overview = $this->client->getCharityOverview($registeredNumber);
    }
    catch (\CRM_Core_Exception $e) {
      // Activities are a nice-to-have; don't fail the whole update.
      \Civi::log('charitylookup')->warning('Charity overview lookup failed for {number}: {message}', [
        'number' => $registeredNumber,
        'message' => $e->getMessage(),
      ]);
      $overview = [];
    }

    $updated = [];
    $values = $this->mapContactValues($details, $overview);
    $values[self::NUMBER_FIELD] = $this->formatNumber($details);
    if (\Civi::settings()->get('charitylookup_update_contact_name') && !empty($details['charity_name'])) {
      $values['organization_name'] = $details['charity_name'];
      $updated[] = E::ts('Organisation name');
    }
    Contact::update($this->checkPermissions)
      ->addWhere('id', '=', $contactId)
      ->setValues($values)
      ->execute();
    $updated[] = E::ts('Legal name and Charity Commission details');

    if (\Civi::settings()->get('charitylookup_update_contact_details')) {
      $updated = array_merge($updated, $this->updateLocationDetails($contactId, $details));
    }

    return [
      'contact_id' => $contactId,
      'registered_number' => $values[self::NUMBER_FIELD],
      'charity_name' => $details['charity_name'] ?? NULL,
      'updated' => $updated,
    ];
  }

  /**
   * Values for Contact.update (core + custom fields).
   */
  public function mapContactValues(array $details, array $overview = []): array {
    $status = $details['reg_status'] ?? NULL;
    $statusLabels = ['R' => E::ts('Registered'), 'RM' => E::ts('Removed')];

    $trustees = array_filter(array_map(
      fn($t) => trim((string) ($t['trustee_name'] ?? '')),
      $details['trustee_names'] ?? []
    ));

    $values = [
      'charity_commission.charity_status' => $statusLabels[$status] ?? $status,
      'charity_commission.charity_type' => $details['charity_type'] ?? NULL,
      'charity_commission.date_registered' => self::date($details['date_of_registration'] ?? NULL),
      'charity_commission.date_removed' => self::date($details['date_of_removal'] ?? NULL),
      'charity_commission.removal_reason' => $details['removal_reason'] ?? NULL,
      'charity_commission.company_number' => $details['charity_co_reg_number'] ?? NULL,
      'charity_commission.reporting_status' => $details['reporting_status'] ?? NULL,
      'charity_commission.financial_year_end' => self::date($details['latest_acc_fin_year_end_date'] ?? NULL),
      'charity_commission.latest_income' => $details['latest_income'] ?? NULL,
      'charity_commission.latest_expenditure' => $details['latest_expenditure'] ?? NULL,
      'charity_commission.trustees' => $trustees ? implode("\n", $trustees) : NULL,
      'charity_commission.last_lookup' => date('Y-m-d H:i:s'),
    ];
    if (array_key_exists('activities', $overview)) {
      $values['charity_commission.activities'] = $overview['activities'];
    }
    if (!empty($details['charity_name'])) {
      $values['legal_name'] = $details['charity_name'];
    }
    return $values;
  }

  /**
   * Address/phone/email/website. Only non-empty register values are written.
   *
   * @return string[] labels of what changed
   */
  private function updateLocationDetails(int $contactId, array $details): array {
    $locationTypeId = (int) \Civi::settings()->get('charitylookup_location_type_id')
      ?: (int) \CRM_Core_BAO_LocationType::getDefault()->id;
    $updated = [];

    $address = $this->mapAddress($details);
    if ($address) {
      $this->upsert(Address::class, $contactId, ['location_type_id' => $locationTypeId], $address);
      $updated[] = E::ts('Address');
    }
    if (!empty($details['phone'])) {
      $this->upsert(Phone::class, $contactId,
        ['location_type_id' => $locationTypeId, 'phone_type_id:name' => 'Phone'],
        ['phone' => trim($details['phone'])]);
      $updated[] = E::ts('Phone');
    }
    if (!empty($details['email'])) {
      $this->upsert(Email::class, $contactId,
        ['location_type_id' => $locationTypeId],
        ['email' => trim($details['email'])]);
      $updated[] = E::ts('Email');
    }
    if (!empty($details['web'])) {
      $url = trim($details['web']);
      if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
      }
      $exists = Website::get($this->checkPermissions)
        ->addWhere('contact_id', '=', $contactId)
        ->addWhere('url', '=', $url)
        ->selectRowCount()->execute()->countMatched();
      if (!$exists) {
        $this->upsert(Website::class, $contactId, ['website_type_id:name' => 'Main'], ['url' => $url]);
      }
      $updated[] = E::ts('Website');
    }
    return $updated;
  }

  /**
   * Update the first record matching $match for the contact, or create one.
   */
  private function upsert(string $entity, int $contactId, array $match, array $values): void {
    $get = $entity::get($this->checkPermissions)
      ->addSelect('id')
      ->addWhere('contact_id', '=', $contactId)
      ->addOrderBy('is_primary', 'DESC')
      ->addOrderBy('id')
      ->setLimit(1);
    if ($entity === Website::class) {
      // Website has no is_primary.
      $get->setOrderBy(['id' => 'ASC']);
    }
    foreach ($match as $field => $value) {
      $get->addWhere($field, '=', $value);
    }
    $existing = $get->execute()->first();
    if ($existing) {
      $entity::update($this->checkPermissions)
        ->addWhere('id', '=', $existing['id'])
        ->setValues($values)
        ->execute();
    }
    else {
      $entity::create($this->checkPermissions)
        ->setValues($values + $match + ['contact_id' => $contactId])
        ->execute();
    }
  }

  /**
   * Convert the register's five free-text address lines into CiviCRM fields.
   *
   * First line is the street, last line (if more than one) the town; any
   * lines in between become supplemental address lines.
   */
  public function mapAddress(array $details): array {
    $lines = [];
    foreach (['one', 'two', 'three', 'four', 'five'] as $n) {
      $line = trim((string) ($details["address_line_$n"] ?? ''));
      if ($line !== '') {
        $lines[] = $line;
      }
    }
    $postcode = trim((string) ($details['address_post_code'] ?? ''));
    if (!$lines && $postcode === '') {
      return [];
    }

    $address = [
      'street_address' => array_shift($lines),
      'city' => count($lines) ? array_pop($lines) : NULL,
      'supplemental_address_1' => NULL,
      'supplemental_address_2' => NULL,
      'supplemental_address_3' => NULL,
      'postal_code' => $postcode ?: NULL,
      'country_id:abbr' => 'GB',
    ];
    if (count($lines) > 3) {
      // Squash any overflow into the last supplemental line.
      $lines = array_merge(array_slice($lines, 0, 2), [implode(', ', array_slice($lines, 2))]);
    }
    foreach (array_values($lines) as $i => $line) {
      $address['supplemental_address_' . ($i + 1)] = $line;
    }
    return $address;
  }

  private function formatNumber(array $details): string {
    $number = (string) $details['reg_charity_number'];
    $suffix = (int) ($details['group_subsid_suffix'] ?? 0);
    return $suffix ? "$number-$suffix" : $number;
  }

  private static function date(?string $value): ?string {
    if (!$value) {
      return NULL;
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : NULL;
  }

}
