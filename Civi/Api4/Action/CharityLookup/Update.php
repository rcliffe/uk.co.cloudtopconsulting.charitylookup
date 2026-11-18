<?php
declare(strict_types = 1);

namespace Civi\Api4\Action\CharityLookup;

use Civi\Api4\Contact;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Charitylookup\Updater;

/**
 * Look organisations up on the Charity Commission register and update them.
 *
 * With `contactId`, updates that one organisation (optionally setting a new
 * `registeredNumber`). Without it, refreshes every organisation that has a
 * registered charity number - suitable for a scheduled job.
 *
 * @method int getContactId()
 * @method $this setContactId(int $contactId)
 * @method string getRegisteredNumber()
 * @method $this setRegisteredNumber(string $registeredNumber)
 * @method int getLimit()
 * @method $this setLimit(int $limit)
 */
class Update extends AbstractAction {

  /**
   * Organisation to update. Leave empty to refresh all organisations with a
   * registered charity number.
   *
   * @var int
   */
  protected $contactId;

  /**
   * Number to look up and store (single contact only). Defaults to the
   * number already saved on the contact.
   *
   * @var string
   */
  protected $registeredNumber;

  /**
   * When refreshing all, the maximum number of organisations to process
   * (least recently checked first). 0 = no limit.
   *
   * @var int
   */
  protected $limit = 0;

  public function _run(Result $result) {
    $updater = new Updater(NULL, $this->getCheckPermissions());

    if ($this->contactId) {
      $result[] = $updater->updateContact((int) $this->contactId, $this->registeredNumber ?: NULL);
      return;
    }

    $contactIds = Contact::get($this->getCheckPermissions())
      ->addSelect('id')
      ->addWhere('contact_type', '=', 'Organization')
      ->addWhere('is_deleted', '=', FALSE)
      ->addWhere(Updater::NUMBER_FIELD, 'IS NOT EMPTY')
      ->addOrderBy('charity_commission.last_lookup', 'ASC')
      ->setLimit((int) $this->limit)
      ->execute()->column('id');

    foreach ($contactIds as $contactId) {
      try {
        $result[] = $updater->updateContact((int) $contactId) + ['is_error' => FALSE];
      }
      catch (\CRM_Core_Exception $e) {
        $result[] = ['contact_id' => $contactId, 'is_error' => TRUE, 'error_message' => $e->getMessage()];
      }
    }
  }

}
