<?php require_once('templates/header.php'); ?>
<form id="edit" method="post" action="edit.php" enctype="multipart/form-data">
<div class="vcard">

<div class="header"><h2>Edit Phonebook Entry</h2></div>
<div class="body">
<table class="edit-table">
  <tr>
    <td><label>Name</label></td>
    <td><input type="text" name="cn[]" value="<?= escape($user_data['cn'][0]) ?>" /></td>
  </tr>

  <tr>
    <td><label>Other E-mail Address(es)</label></td>
    <td id="email-aliases">
    <?php for ($i = 0; $i < $user_data['emailalias']['count']; $i++) { ?>
      <div>
      <input type="text" name="emailAlias[]" value="<?= escape($user_data['emailalias'][$i]) ?>"/><a href="#" class="remove-link">Remove e-mail</a>
      </div>
    <?php } ?>
    <a id="email-alias-add" href="#">Add e-mail</a><br />
    </td>
  </tr>

  <tr>
    <td><label>Manager</label></td>
    <td>
      <select name="manager" id="select-manager">
      <?php
      echo '<option value=""></option>';
      for ($j = 0; $j < $managerlist["count"]; $j++) {
        if ($managerlist[$j]['dn'] == $user_data['manager'][0]) {
          $selected =' selected="selected"';
        } else {
          $selected = '';
        }
        echo '<option value="'. $managerlist[$j]['dn'] .'"'.
              $selected .'>'. escape($managerlist[$j]['cn'][0]) .'</option>';
      }
      ?>
      </select>
    </td>
  </tr>
  <tr>
    <td><label>Office City</label></td>
<?php 
    if(!is_array($user_data["physicaldeliveryofficename"])){
        $user_data["physicaldeliveryofficename"] = array($user_data["physicaldeliveryofficename"]);
    } 
      unset($user_data["physicaldeliveryofficename"]["count"]);
if (!empty($city) && !in_array($city, array_keys($office_cities))) {
  $city = "Other";
}
?>
    <td id='office-cities'>
<?php 
$office_city_display_style = "display: none;";
$primary_country = '';
$counter = 0;
$other_city_name = '';
    foreach ($user_data["physicaldeliveryofficename"] as $office_location){ 
    list($city, $country) = explode(":::", $office_location);
    if($counter == 0){
        $primary_country = $country;
    }
    $city_name = $city;
?>
      <div><select id="office-city-select" name="office_city[]">
        <?php
        foreach ($office_cities as $oc => $octry) {
          if ($counter > 0 && $oc == 'Other'){
              continue;
          }
          $selected = ($oc == $city) ? ' selected="selected"' : '';
          $oc = escape($oc);
          $octry = escape($octry);
          if (!in_array($city, array_keys($office_cities)) && $oc == "Other"){
            $office_city_display_style = "display: block;";
            $other_city_name = $city;
            echo "<option value=\"$oc\" data-country=\"{$octry}\" selected=\"selected\">$oc</option>";
          } else {
            echo "<option value=\"$oc\" data-country=\"{$octry}\" $selected>$oc</option>";

          }
        }
        ?>
            </select><?php ($counter > 0) ? print '<a href="#" class="remove-link"></a>' : ''; ?><br /></div>
<?php 
$counter++;    
} ?>
    <a id="office-add" href="#">Add Office</a><br />
      <input id="office-city-text" style="<?= $office_city_display_style ?>" type="text" name="office_city_name" value="<?= escape($other_city_name) ?>" />
    </td>
  </tr>

  <tr>
    <td><label>Office Country</label></td>
    <td>
      <select id="office-country-select" name="office_country">
        <option value=""></option>
        <?php
        foreach ($country_codes as $country_name => $code) {
          $selected = ($code == $primary_country) ? 'selected="selected"' : '';
          print '<option '. $selected . ' value="' . htmlentities($code) . '">'. htmlentities($country_name) . '</option>';
        }
        ?>
      </select>
    </td>
  </tr>

  <tr>
    <td><label>Desk Number</label></td>
    <td><input type="text" name="roomNumber[]" value="<?= escape($user_data['roomnumber'][0]) ?>"/></td>
  </tr>
  <tr>
    <td><label>Title</label></td>
    <td><input type="text" name="title[]" value="<?= escape($user_data['title'][0]) ?>"/></td>
  </tr>

  <tr>
    <td><label>Employee Status</label></td>
    <td>
    <?php
      function print_status_edit($status, $is_manager, $admin) {
        global $orgs, $emp_type;
        $status = $status == "DISABLED" ? array('D', 'D') : str_split($status);
        if (!empty($status[0])) {
          list($current_org, $current_emp_type) = $status;
        }
        if ($admin) {
          require "templates/_status.php";
        } else {
          if (isset($orgs[$current_org]) &&
              isset($emp_type[$current_emp_type])) {
            print $orgs[$current_org] .", ". $emp_type[$current_emp_type];
          } else {
            print "DISABLED";
          }
        }
      }

      print_status_edit($user_data['employeetype'][0],
                        isset($user_data["ismanager"]) && $user_data['ismanager'][0] && $user_data['ismanager'][0] != 'FALSE',
                        $is_admin);
    ?>
    </td>
  </tr>

  <tr>
    <td><label>Extension</label></td>
    <td><input type="text" name="telephoneNumber[]" value="<?= escape($user_data['telephonenumber'][0]) ?>"/></td>
  </tr>

  <tr>
    <td><label>Phone Number(s)</label></td>
    <td id="phone-numbers">
    <?php for ($i = 0; $i < $user_data['mobile']['count']; $i++) { ?>
      <div>
      <input type="text" name="mobile[]" value="<?= escape($user_data['mobile'][$i]) ?>"/><a href="#" class="remove-link">Remove number</a>
      </div>
    <? } ?>
    <a id="phone-number-add" href="#">Add number</a><br />
    </td>
  </tr>

  <tr>
    <td><label>B2G Number</label></td>
    <td><input type="text" name="b2gNumber[]" value="<?= escape($user_data['b2gnumber'][0]) ?>"/></td>
  </tr>
  <tr>
    <td><label>IM Account(s)</label></td>
    <td id="im-accounts">
    <?php for ($i = 0; $i < $user_data['im']['count']; $i++) { ?>
     <div>
     <input type="text" name="im[]" value="<?= escape($user_data['im'][$i]) ?>"/><a href="#" class="remove-link">Remove account</a>
     </div>
    <? } ?>
    <a id="im-add" href="#">Add account</a><br />
    </td>
  </tr>

  <tr>
    <td><label>Bugzilla Email</label></td>
    <td>
      <input type="text" name="bugzillaEmail[]" value="<?= escape($user_data['bugzillaemail'][0]) ?>"/><br />
      <em class="description">Your full Bugzilla email address with no extra cruft</em>
    </td>
  </tr>

  <tr>
    <td><label>I work on</label></td>
    <td>
      <textarea cols="40" rows="5" name="description[]"><?= $user_data['description'][0] ?></textarea><br />
      <em class="description">Links in <a href="http://en.wikipedia.org/wiki/Help:Wikitext_examples">wiki markup</a> style supported.</em>
    </td>
  </tr>
<?php
    if(!isset($user_data['shirtsize'][0])){
        $user_shirt = '';
    } else {
        $user_shirt = $user_data['shirtsize'][0];
    }

?>
  <tr>
    <td><label>T-Shirt Size</label></td>
    <td>
    <select name="shirtsize">
    <option value="">Non Selected</option>
    <?php
        for($i=0;$i<count($shirt_sizes);$i++){
            echo "<option value=\"". $shirt_sizes[$i] . "\"";
            if($shirt_sizes[$i] == $user_shirt){
                echo " selected=\"selected\" ";
            }
            echo ">" . $shirt_sizes[$i] . "</option>";
        }
    ?>
    </select>
    </td>
  </tr>
  <tr>
    <td><label>Other</label></td>
    <td>
      <textarea cols="40" rows="5" name="other[]"><?= $user_data['other'][0] ?></textarea><br />
      <em class="description">Links in <a href="http://en.wikipedia.org/wiki/Help:Wikitext_examples">wiki markup</a> style supported.</em>
    </td>
  </tr>

  <tr>
    <td><label>Photo</label></td>
    <td>
        <img class="photo" src="pic.php?type=thumb&mail=<?= escape($user_data['mail'][0]) ?>"/>
        <label for="photo-upload">Upload new photo</label>
        <input id="photo-upload" type="file" name="jpegPhoto" accept="image/jpeg" /><br />
        <em class="description">Please upload your mugshot<br /></em>
        <em class="description">Only JPEG is supported</em>
    </td>
  </tr>

  <tr>
    <td></td>
    <td>
      <input type="hidden" name="edit_mail" value="<?= escape($edit_user) ?>" />
      <button type="submit">Save Changes</button>
    </td>
  </tr>
</table>
</form>
</div>
<div class="footer">
  <span class="l"></span>
  <span class="m"></span>
  <span class="r"></span>
</div>

</div>

<script type="text/javascript" src="js/autocomplete.js"></script>
<script type="text/javascript" src="js/edit.js"></script>

<?php require_once('templates/footer.php'); ?>
