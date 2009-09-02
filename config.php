<?php

$tree_config = array(
  "ldap" => array(
    "search_base" => "o=com, dc=mozilla",
    "search_filter" => "mail=*",
    "search_attributes" => array(
      "cn", "manager", "title", "mail", "employeeType"
    )
  )
);

function tree_view_process_entry($person) {
  return array(
    "title" => !empty($person["title"][0]) ? $person["title"][0] : null,
    "name" => !empty($person["cn"][0]) ? $person["cn"][0] : null,
    "disabled" => isset($person["employeetype"]) ?
                    strpos($person["employeetype"][0], 'D') !== FALSE: 
                    FALSE
  );
}

function tree_view_roots() {
  return array(
    "mitchell@mozilla.com", "lilly@mozilla.com"
  );
}

function tree_view_item($email, $leaf=FALSE) {
  global $everyone;
  $email = htmlspecialchars($email);
  $id = str_replace('@', "-at-", $email);
  $name = htmlspecialchars($everyone[$email]["name"]);
  $title = htmlspecialchars($everyone[$email]["title"]);
  $leaf = $leaf ? " leaf" : '';
  $disabled = $everyone[$email]["disabled"] ? " disabled" : '';
  return "<li id=\"$id\" class=\"hr-node expanded$leaf$disabled\">".
           "<a href=\"#search/$email\" class=\"hr-link\">$name</a> ".
           "<span class=\"title\">$title</span>".
         "</li>";
}

