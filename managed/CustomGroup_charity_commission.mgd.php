<?php
declare(strict_types = 1);

use CRM_Charitylookup_ExtensionUtil as E;

/**
 * "Charity Commission" custom field set on Organisations.
 *
 * Registered number is entered by users; everything else is filled in by the
 * lookup and is shown read-only.
 */
$fields = [
  'registered_number' => [
    'label' => E::ts('Registered Charity Number'),
    'data_type' => 'String',
    'html_type' => 'Text',
    'text_length' => 20,
    'is_searchable' => TRUE,
    'help_post' => E::ts('Charity Commission (England & Wales) number. For a linked charity use the form 1234567-1.'),
  ],
  'charity_status' => [
    'label' => E::ts('Register Status'),
    'data_type' => 'String',
    'html_type' => 'Text',
    'text_length' => 64,
    'is_searchable' => TRUE,
  ],
  'charity_type' => [
    'label' => E::ts('Charity Type'),
    'data_type' => 'String',
    'html_type' => 'Text',
    'text_length' => 64,
    'is_searchable' => TRUE,
  ],
  'date_registered' => [
    'label' => E::ts('Date Registered'),
    'data_type' => 'Date',
    'html_type' => 'Select Date',
    'date_format' => 'dd/mm/yy',
  ],
  'date_removed' => [
    'label' => E::ts('Date Removed'),
    'data_type' => 'Date',
    'html_type' => 'Select Date',
    'date_format' => 'dd/mm/yy',
  ],
  'removal_reason' => [
    'label' => E::ts('Removal Reason'),
    'data_type' => 'String',
    'html_type' => 'Text',
  ],
  'company_number' => [
    'label' => E::ts('Company Number'),
    'data_type' => 'String',
    'html_type' => 'Text',
    'text_length' => 20,
    'is_searchable' => TRUE,
  ],
  'reporting_status' => [
    'label' => E::ts('Reporting Status'),
    'data_type' => 'String',
    'html_type' => 'Text',
    'text_length' => 64,
  ],
  'financial_year_end' => [
    'label' => E::ts('Latest Financial Year End'),
    'data_type' => 'Date',
    'html_type' => 'Select Date',
    'date_format' => 'dd/mm/yy',
  ],
  'latest_income' => [
    'label' => E::ts('Latest Income'),
    'data_type' => 'Money',
    'html_type' => 'Text',
    'is_searchable' => TRUE,
  ],
  'latest_expenditure' => [
    'label' => E::ts('Latest Expenditure'),
    'data_type' => 'Money',
    'html_type' => 'Text',
  ],
  'activities' => [
    'label' => E::ts('Activities'),
    'data_type' => 'Memo',
    'html_type' => 'TextArea',
    'attributes' => 'rows=4, cols=60',
  ],
  'trustees' => [
    'label' => E::ts('Trustees'),
    'data_type' => 'Memo',
    'html_type' => 'TextArea',
    'attributes' => 'rows=4, cols=60',
  ],
  'last_lookup' => [
    'label' => E::ts('Last Checked with Charity Commission'),
    'data_type' => 'Date',
    'html_type' => 'Select Date',
    'date_format' => 'dd/mm/yy',
    'time_format' => 2,
  ],
];

$entities = [
  [
    'name' => 'CustomGroup_charity_commission',
    'entity' => 'CustomGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'charity_commission',
        'title' => E::ts('Charity Commission'),
        'extends' => 'Organization',
        'style' => 'Inline',
        'collapse_display' => FALSE,
        'is_active' => TRUE,
        'is_public' => FALSE,
        'weight' => 1,
      ],
      'match' => ['name'],
    ],
  ],
];

$weight = 0;
foreach ($fields as $name => $values) {
  $weight += 10;
  $entities[] = [
    'name' => 'CustomGroup_charity_commission_CustomField_' . $name,
    'entity' => 'CustomField',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => $values + [
        'custom_group_id.name' => 'charity_commission',
        'name' => $name,
        'column_name' => $name,
        'weight' => $weight,
        'is_active' => TRUE,
        // Everything except the number itself comes from the register.
        'is_view' => $name !== 'registered_number',
      ],
      'match' => ['name', 'custom_group_id'],
    ],
  ];
}

return $entities;
