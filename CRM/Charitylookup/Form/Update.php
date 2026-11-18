<?php
declare(strict_types = 1);

use Civi\Api4\Contact;
use Civi\Charitylookup\Updater;
use CRM_Charitylookup_ExtensionUtil as E;

/**
 * "Update from Charity Commission" popup for an organisation.
 */
class CRM_Charitylookup_Form_Update extends CRM_Core_Form {

  protected int $contactId;

  public function preProcess(): void {
    $this->contactId = (int) CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    if (!CRM_Contact_BAO_Contact_Permission::allow($this->contactId, CRM_Core_Permission::EDIT)) {
      CRM_Core_Error::statusBounce(E::ts('You do not have permission to edit this contact.'));
    }
    $contact = Contact::get()
      ->addSelect('display_name', 'contact_type', Updater::NUMBER_FIELD, 'charity_commission.last_lookup')
      ->addWhere('id', '=', $this->contactId)
      ->execute()->first();
    if (!$contact || $contact['contact_type'] !== 'Organization') {
      CRM_Core_Error::statusBounce(E::ts('Charity lookup only works for organisations.'));
    }
    $this->setTitle(E::ts('Charity Commission lookup: %1', [1 => $contact['display_name']]));
    $this->assign('lastLookup', $contact['charity_commission.last_lookup'] ?? NULL);
    $this->assign('hasApiKey', (bool) Civi::settings()->get('charitylookup_api_key'));
    $this->assign('isAdmin', CRM_Core_Permission::check('administer CiviCRM'));
    $this->assign('settingsUrl', CRM_Utils_System::url('civicrm/admin/setting/charitylookup', 'reset=1'));
    $this->_defaults = ['registered_number' => $contact[Updater::NUMBER_FIELD] ?? ''];
  }

  public function buildQuickForm(): void {
    $this->add('text', 'registered_number', E::ts('Registered charity number'), ['size' => 12, 'maxlength' => 20], TRUE);
    $this->addFormRule([__CLASS__, 'formRule']);
    $this->addButtons([
      ['type' => 'submit', 'name' => E::ts('Look up and update'), 'icon' => 'fa-search', 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => E::ts('Cancel')],
    ]);
  }

  public function setDefaultValues(): array {
    return $this->_defaults;
  }

  public static function formRule(array $values): array|bool {
    try {
      \Civi\Charitylookup\Client::parseNumber((string) ($values['registered_number'] ?? ''));
    }
    catch (CRM_Core_Exception $e) {
      return ['registered_number' => $e->getMessage()];
    }
    return TRUE;
  }

  public function postProcess(): void {
    $number = trim((string) $this->getSubmittedValue('registered_number'));
    $summaryUrl = CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'cid' => $this->contactId]);
    try {
      $result = (new Updater())->updateContact($this->contactId, $number);
      CRM_Core_Session::setStatus(
        E::ts('Updated from the register entry for %1 (%2): %3.', [
          1 => $result['charity_name'],
          2 => $result['registered_number'],
          3 => implode(', ', $result['updated']),
        ]),
        E::ts('Charity Commission'),
        'success'
      );
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), E::ts('Charity Commission lookup failed'), 'error');
    }
    CRM_Core_Session::singleton()->replaceUserContext($summaryUrl);
  }

}
