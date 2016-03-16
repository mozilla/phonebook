<?php


function output_pgpFingerprint($s) {
  $stripped_fingerprint = str_replace(" ", "", $s);
  $inner_text = '<a target="_blank" style="font-size: 12px;" href="http://gpg.mozilla.org/pks/lookup?search=0x' . $stripped_fingerprint . '&op=vindex">' . $stripped_fingerprint . '</a>';
  $return_string = '<div class="pgpfingerprint">';
  $return_string .= 'PGP Fingerprint: <span style="font-size: 12px;">' . $inner_text . '</span>';
  $return_string .= '</div>';
  return $return_string;
}
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
  if( is_array($s)){
    $s = $s[0];
  }
  return str_replace(":::", "/", $s);
}
function output_analytics_script(){
    $GA = <<<EOT

        <script type="text/javascript">
                var _gaq = _gaq || [];
                _gaq.push(['_setAccount', 'UA-35433268-20']);
                _gaq.push(['_trackPageview']);

                (function() {
                    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
                })();
        </script>

EOT;
    return $GA;
}
function output_html_im($im) {
  return '<ul class="im">'. output_html_listify($im) .'</ul>';
}

function output_html_mobile_linkify($s) {
  $s = htmlspecialchars($s);
  return "<li class=\"tel\"><a href=\"tel:$s\">$s</a></li>";
}

function output_html_mobile($l) {
  $l = array_map("wikilinks", $l);
  $l = array_map("output_html_mobile_linkify", $l);
  return '<ul class="telecommunications">' . implode($l) . '</ul>';
}

function output_html_description($s) {
  return '<div class="note">I work on: '. wikilinks($s) .'</div>';
}

function output_html_other($s) {
  return '<div class="other">'. wikilinks($s) .'</div>';
}

function output_html_dn_to_email($dn) {
  preg_match('/mail=(.+),o=/', $dn, $matches);
  return htmlspecialchars($matches[1]);
}

function output_html_manager($m) {
  $m["email"] = htmlspecialchars(output_html_dn_to_email($m["dn"]));
  $clean_cn = htmlspecialchars($m['cn']);
  return "<p class=\"manager\">Manager: <a href=\"#search/{$m['email']}\">{$clean_cn}</a><a class='org-chart' href='tree.php#search/{$m['email']}'>(Org)</a></p>";
}

function output_html_title($t) {
  return '<p class="title">'. htmlspecialchars($t) .'</p>';
}

function output_html_telephonenumber($s) {
    return " asfd, x$s";
}

function output_html_bugzillaemail($s) {
  $s = htmlspecialchars($s);
  return "<div class=\"bugmail\">Bugmail: <a title=\"Bugmail\" href=\"mailto:$s\">$s</a></div>";
}

function output_html_cn($s){
    return htmlspecialchars($s);

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
  require_once 'workday_location_map.php';
  $processors = array(
    "mail" => "output_html_email_linkify",
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
    "cn" => "output_html_cn",
    "pgpfingerprint" => "output_pgpFingerprint",
    "bugzillaemail" => "output_html_bugzillaemail"
  );

  $template = array('<div class="vcard">',
  '<div class="header"><h2 class="fn">#{cn}</h2>#{edit}</div>',
  '<div class="body">',
    '<div class="side">',
      '#{picture}',
      '#{vcard_export}',
    '</div>',
    '<div class="employee">',
      '#{title}',
      '<p class="employee-type">#{employeetype}</p>',
      '#{manager}',
    '</div>',
    '#{employeenumber}',
    '#{deptname}',
    '#{workdaycostcenter}',
    '#{locality}',
    '#{workdaylocation}',
    '#{roomnumber}',
    '#{mobile}',
    '<ul class="email">#{mail}#{emailalias}</ul>',
    '#{bugzillaemail}',
    '#{b2gnumber}',
    '#{githubprofile}',
    '#{im}',
    '#{pgpfingerprint}',
    '#{description}',
    '#{other}',
  '</div>',
  '<div class="footer">',
    '<span class="l"></span>',
    '<span class="m"></span>',
    '<span class="r"></span>',
  '</div>',
  '#{GA}</div>');
  $template = implode('', $template);

  header("Content-Type: text/html");
  foreach ($entries as &$entry) {
    if (isset($entry["picture"])) {
      $photo_name = output_html_cn($entry['cn']);
      $entry["picture"] = "<a class=\"photo\" href=\"{$entry['picture']}\" target=\"_blank\"><img class=\"photo\" src=\"{$entry['picture']}&amp;type=thumb\" alt=\"Photo of {$photo_name}\" /></a>";
    }
    if (isset($entry["roomnumber"])) {
        $entry["roomnumber"] = '<ul class="desk"><li>' . htmlspecialchars($entry['roomnumber']) . '</li></ul>';
    }

    /*
      Not everyone will have a pgpfingerprint. If it isn't a valid length, not set or blank, unset it
    */
    if (isset($entry["pgpfingerprint"]) && strlen(str_replace(" ","", $entry["pgpfingerprint"])) != 40) {
      unset($entry["pgpfingerprint"]);
    }
    
    if (isset($entry["workdaylocation"])) {
        if(isset($WORKDAY_LOCATION_MAP[$entry['workdaylocation']])){
            $workday_location = $WORKDAY_LOCATION_MAP[$entry['workdaylocation']];
        } else {
            $workday_location = $entry["workdaylocation"];

        }
        $entry["workdaylocation"] = '<ul><li>' . htmlspecialchars($workday_location) . '</li></ul>';
    }
    if (isset($entry["employeenumber"])) {
        $entry["employeenumber"] = '<ul class="employeenumber"><li>Employee ID: ' . htmlspecialchars($entry['employeenumber']) . '</li></ul>';
    }
    if (isset($entry["deptname"])) {
        $entry["deptname"] = '<ul class="team"><li>Team: ' . htmlspecialchars($entry['deptname']) . '</li></ul>';
    }
    if (isset($entry["workdaycostcenter"])) {
        $entry["workdaycostcenter"] = '<ul><li>Cost Center: ' . htmlspecialchars($entry['workdaycostcenter']) . '</li></ul>';
    }
    $entry["org_chart"] = ' <a class="org-chart" href="tree.php#search/'. $entry["mail"] .'">(Org)</a>';
    $entry["vcard_export"] = '<a class="vcard-export" href="./search.php?format=vcard&query='. $entry["mail"] .'">vCard</a>';
    $entry["edit"] = $is_admin ? ' <a href="edit.php?edit_mail='. escape($entry["mail"]) .'">(edit)</a>' : '';
    if (isset($entry["physicaldeliveryofficename"])) {
        if(is_array($entry["physicaldeliveryofficename"])){
            $entry["physicaldeliveryofficename"] = $entry["physicaldeliveryofficename"][0];
        }
      $entry["physicaldeliveryofficename"] = htmlspecialchars($entry["physicaldeliveryofficename"]);
      if(isset($entry["telephonenumber"]) && $entry["telephonenumber"] != ''){
        $entry["telephonenumber"] = ' Ext. ' . $entry['telephonenumber'];
      } else {
        $entry["telephonenumber"] = '';
      }
      $entry["locality"] = interpolate(implode('', array(
        '<ul class="adr"><li>',
          '<span class="locality">',
            '<a href="https://intranet.mozilla.org/OfficeLocations">#{physicaldeliveryofficename}</a>',
          '</span>',
          '#{telephonenumber}',
          '#{seating_chart}',
        '</li></ul>',
      )), $entry);
    }
    if (isset($entry["b2gnumber"])) {
        $entry["b2gnumber"] = '<ul class="b2gnumber"><li>B2G Number: ' . htmlspecialchars($entry['b2gnumber']) . '</li></ul>';
    }
    if (isset($entry["githubprofile"])) {
        $entry["githubprofile"] = '<ul class="githubProfile"><li>Github Profile: <a href="https://github.com/' . htmlspecialchars($entry['githubprofile']) . '">' . htmlspecialchars($entry['githubprofile']) . '</li></ul>';
    }
    foreach ($processors as $processor => $function) {
      if (isset($entry[$processor])) {
        $entry[$processor] = call_user_func($function, $entry[$processor]);
      }
    }
    $entry['GA'] = output_analytics_script();
    $interpolated_template = interpolate($template, $entry);
    print $interpolated_template;
  }

  die;
}
