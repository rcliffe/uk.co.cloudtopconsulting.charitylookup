<?php
declare(strict_types = 1);

use CRM_Charitylookup_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_charitylookup_settings',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Charity Commission Lookup'),
        'name' => 'charitylookup_settings',
        'url' => 'civicrm/admin/setting/charitylookup?reset=1',
        'icon' => NULL,
        'permission' => 'administer CiviCRM',
        'permission_operator' => '',
        'parent_id.name' => 'System Settings',
        'is_active' => TRUE,
        'domain_id' => 'current_domain',
      ],
      'match' => ['domain_id', 'name'],
    ],
  ],
];
