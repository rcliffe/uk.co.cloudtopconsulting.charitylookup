<?php
declare(strict_types = 1);

namespace Civi\Api4\Action\CharityLookup;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Charitylookup\Client;

/**
 * Return the register entry for a charity number (nothing is saved).
 *
 * @method string getRegisteredNumber()
 * @method $this setRegisteredNumber(string $registeredNumber)
 */
class Fetch extends AbstractAction {

  /**
   * Registered charity number, optionally with suffix e.g. "1234567-1".
   *
   * @var string
   * @required
   */
  protected $registeredNumber;

  public function _run(Result $result) {
    $client = new Client();
    $details = $client->getAllCharityDetails((string) $this->registeredNumber);
    try {
      $details['activities'] = $client->getCharityOverview((string) $this->registeredNumber)['activities'] ?? NULL;
    }
    catch (\CRM_Core_Exception $e) {
      $details['activities'] = NULL;
    }
    $result[] = $details;
  }

}
