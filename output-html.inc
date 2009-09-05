<?php

function output_html_listify($a, $sanitize=TRUE) {
  $s = "";
  for ($i = 0; $i < count($a); $i++) {
    if ($sanitize) {
      $a[$i] = htmlspecialchars($a[$i]);
    }
    $s .= "<li>{$a[$i]}</li>";
  }
  return $s;
}

function output_html_email_linkify($s) {
  $s = htmlspecialchars($s, ENT_QUOTES);
  return "<li><a class=\"value\" href=\"mailto:$s\">$s</a></li>";
}

function output_html_emailalias($o) {
  $o = !is_array($o) ? array($o) : $o;
  return implode('', array_map("output_html_email_linkify", $o));
}

function output_html_employeetype($l) {
  $l[0] = '<span class="org">'. $l[0] .'</span>';
  return implode(", ", $l);
}

function output_html_location($s) {
  return str_replace(":::", "/", $s);
}

function output_html_im($im) {
  return '<ul class="im">'. output_html_listify($im) .'</ul>';
}

function output_html_mobile($l) {
  return '<ul class="telecommunications">'. 
         str_replace("<li>", '<li class="tel">', 
                    output_html_listify(array_map("wikilinks", $l))) .
         '</ul>';
}

function output_html_description($s) {
  return '<div class="note">I work on: '. wikilinks($s) .'</div>';
}

function output_html_other($s) {
  return '<div class="other">'. wikilinks($s) .'</div>';
}

function output_html_dn_to_email($dn) {
  preg_match('/mail=([a-z]+@mozilla.*),o=/', $dn, $matches);
  return $matches[1];
}

function output_html_manager($m) {
  $m["email"] = output_html_dn_to_email($m["dn"]);
  return "<p class=\"manager\">Manager: <a href=\"#search/{$m['email']}\">{$m['cn']}</a></p>";
}

function output_html_title($t) {
  return '<p class="title">'. htmlspecialchars($t) .'</p>';
}

function output_html_telephonenumber($s) { return " , x$s"; }

function output_html_bugzillaemail($s) {
  return "<a class=\"bugmail\" title=\"Bugmail\" href=\"mailto:$s\">Bugmail</a>";
}

function interpolate($s, $o, $pattern="/(^|.|\\r|\\n)(#\\{(.*?)\\})/") {
  $clone = $s;
  $matches = array();
  while (preg_match($pattern, $clone, $x)) {
    $matches[] = $x;
    $clone = str_replace($x[2], '', $clone);
  }
  foreach ($matches as $m) {
    list($escape_char, $placeholder, $key) = array($m[1], $m[2], $m[3]);
    if ($escape_char == '\\') {
      continue;
    }
    $s = str_replace($placeholder, (isset($o[$key]) ? (string)$o[$key] : ''), $s);
  }
  return $s;
}

function output_html($entries, $is_admin=FALSE) {
  $processors = array(
    "email" => "output_html_email_linkify",
    "emailalias" => "output_html_emailalias",
    "employeetype" => "output_html_employeetype",
    "physicaldeliveryofficename" => "output_html_location",
    "im" => "output_html_im",
    "mobile" => "output_html_mobile",
    "description" => "output_html_description",
    "other" => "output_html_other",
    "manager" => "output_html_manager",
    "title" => "output_html_title",
    "telephonenumber" => "output_html_telephonenumber",
    "bugzillaemail" => "output_html_bugzillaemail"
  );

  $template = array('<div class="vcard">',
  '<div class="header"><h2 class="fn">#{cn}</h2>#{edit}</div>',
  '<div class="body">',
    '<div class="side">',
      '#{picture}',
      '#{vcard_export} #{bugzillaemail}',
    '</div>',
    '<div class="employee">',
      '#{title}',
      '<p class="employee-type">#{employeetype}</p>',
      '#{org_chart}',
      '#{manager}',
    '</div>',
    '<ul class="adr"><li>',
      '<span class="locality">',
        '<a href="https://intranet.mozilla.org/OfficeLocations">#{physicaldeliveryofficename}</a>', 
      '</span>',
      '#{telephonenumber}',
      '#{seating_chart}',
    '</li></ul>',
    '#{mobile}',
    '<ul class="email">#{email}#{emailalias}</ul>',
    '#{im}',
    '#{description}',
    '#{other}',
  '</div>',
  '<div class="footer">',
    '<span class="l"></span>',
    '<span class="m"></span>',
    '<span class="r"></span>',
  '</div>',
  '</div>');
  $template = implode('', $template);
  
  header("Content-Type: text/html");
  foreach ($entries as &$entry) {
    $entry["email"] = output_html_dn_to_email($entry["dn"]);
    $entry["picture"] = "<a class=\"photo\" href=\"{$entry['picture']}\" target=\"_blank\"><img class=\"photo\" src=\"{$entry['picture']}&amp;type=thumb\" alt=\"Photo of {$entry['cn']}\" /></a>";
    $entry["org_chart"] = ' <a class="org-chart" href="tree.php#search/'. $entry["email"] .'">(Org)</a>';
    $entry["vcard_export"] = '<a class="vcard-export" href="./search.php?format=vcard&query='. $entry["email"] .'">vCard</a>';
    $entry["edit"] = $is_admin ? ' <a href="edit.php?edit_mail='. escape($entry["email"]) .'">(edit)</a>' : '';
    foreach ($processors as $processor => $function) {
      if (isset($entry[$processor])) {
        $entry[$processor] = call_user_func($function, $entry[$processor]);
      }
    }
    print interpolate($template, $entry);
  }
  
  die;
}

