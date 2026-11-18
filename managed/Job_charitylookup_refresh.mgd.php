<?php
declare(strict_types = 1);

use CRM_Charitylookup_ExtensionUtil as E;

return [
  [
    'name' => 'Job_charitylookup_refresh',
    'entity' => 'Job',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'charitylookup_refresh',
        'description' => E::ts('Refresh organisations from the Charity Commission register (least recently checked first).'),
        'run_frequency' => 'Daily',
        'api_entity' => 'CharityLookup',
        'api_action' => 'update',
        'parameters' => "version=4\nlimit=50",
        'is_active' => FALSE,
        'domain_id' => 'current_domain',
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
