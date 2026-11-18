<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'charitylookup.civix.php';
// phpcs:enable

use CRM_Charitylookup_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function charitylookup_civicrm_config(\CRM_Core_Config $config): void {
  _charitylookup_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function charitylookup_civicrm_install(): void {
  _charitylookup_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function charitylookup_civicrm_enable(): void {
  _charitylookup_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_summaryActions().
 *
 * Adds "Charity Commission lookup" to the Actions menu on organisations.
 */
function charitylookup_civicrm_summaryActions(&$actions, $contactID): void {
  if (!$contactID || !CRM_Contact_BAO_Contact_Permission::allow((int) $contactID, CRM_Core_Permission::EDIT)) {
    return;
  }
  $contactType = CRM_Contact_BAO_Contact::getContactType($contactID);
  if ($contactType !== 'Organization') {
    return;
  }
  $actions['otherActions']['charitylookup'] = [
    'title' => E::ts('Charity Commission lookup'),
    'description' => E::ts('Update this organisation from the Register of Charities'),
    'weight' => 75,
    'href' => CRM_Utils_System::url('civicrm/charitylookup/update', 'reset=1'),
    'ref' => 'charitylookup-update',
    'class' => 'crm-popup',
    'icon' => 'crm-i fa-building-columns',
  ];
}
