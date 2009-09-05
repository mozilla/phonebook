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
  'cn', 'title', 'telephoneNumber', 'mobile', 'description', 'manager',
  'other', 'im', 'mail', 'emailAlias', 'physicalDeliveryOfficeName',
  'employeeType', 'isManager', 'bugzillaEmail'
);

$office_cities = array(
  'Mountain View', 'Auckland', 'Beijing', 'Denmark', 'Paris',
  'Toronto','Tokyo', 'Other'
);

$protocol = isset($_SERVER["HTTPS"]) ? "https://" : "http://";
define("BASEPATH", $protocol . $_SERVER["HTTP_HOST"] . dirname($_SERVER["REQUEST_URI"]) .'/');

