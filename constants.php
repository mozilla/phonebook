<?php
$orgs = array (
  'C' => "Mozilla Corporation",
  'F' => "Mozilla Foundation",
  'J' => "Mozilla Japan",
  'M' => "Mozilla Messaging",
  'O' => "Mozilla Online",
  'D' => "DISABLED"
);

$emp_type = array (
  'E' => "Employee",
  'C' => "Contractor",
  'I' => "Intern",
  'D' => "DISABLED"
);

$editable_fields = array(
  'cn', 'title', 'telephoneNumber', 'mobile', 'description',
  'other', 'im', 'mail', 'emailAlias', 'physicalDeliveryOfficeName',
  'employeeType', 'bugzillaEmail', 'shirtsize', 'b2gNumber', 'roomNumber', 'githubProfile', 'workdayTimezone'
);

$deleteable_fields = array(
  'emailAlias', 'mobile', 'im',
);

$office_cities = array(
    'Mountain View' => 'US', 
    'San Francisco' => 'US',
    'Auckland' => 'NZ',
    'Beijing' => 'CN',
    'Copenhagen' => 'DK',
    'London' => 'GB',
    'Paris' => 'FR',
    'Tokyo' => 'JP',
    'Toronto' => 'CA',
    'Vancouver' => 'CA',
    'Taipei' => 'TW',
    'Portland' => 'US',
    'Boston' => 'US',
    'Berlin' => 'DE',
    'Other' => 'Other'
);

$shirt_sizes = array(
   'XS (M)',
   'S (M)',
   'M (M)',
   'L (M)',
   'XL (M)',
   'XXL (M)',
   'XXXL (M)',
   'XS (F)',
   'S (MF)',
   'M (F)',
   'L (F)',
   'XL (F)',
   'XXL (F)',
   'XXXL (F)'
);

// Valid output formats. Must correspond to an output-{something}.inc file.
$output_formats = array(
    'html',
    'json',
    'vcard',
);
$MONKEY_FREE_ARRAY = array('cn');
