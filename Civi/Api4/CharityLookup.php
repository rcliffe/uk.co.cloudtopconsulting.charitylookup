<?php
declare(strict_types = 1);

namespace Civi\Api4;

/**
 * Charity Commission (England & Wales) register lookups.
 *
 * @searchable none
 * @package Civi\Api4
 */
class CharityLookup extends Generic\AbstractEntity {

  /**
   * Fetch raw details for a registered number without saving anything.
   *
   * @param bool $checkPermissions
   * @return Action\CharityLookup\Fetch
   */
  public static function fetch($checkPermissions = TRUE) {
    return (new Action\CharityLookup\Fetch(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Look up organisations on the register and update them.
   *
   * @param bool $checkPermissions
   * @return Action\CharityLookup\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\CharityLookup\Update(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [];
    }))->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['access CiviCRM'],
      // Per-contact edit permission is enforced by the Contact API.
      'update' => ['edit all contacts'],
    ];
  }

}
