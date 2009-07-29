<?php require_once('templates/header.php'); ?>

<form method="post" action="edit.php" enctype="multipart/form-data">

<table class="edit-table">
  <tr>
    <td>Name:</td>
    <td><input type="text" name="cn[]" value="<?= $user_data['cn'][0] ?>" /></td>
  </tr>

  <tr>
    <td>Other E-mail Address(es):</td>
    <td id="email-aliases">
    <?php for ($i = 0; $i < $user_data['emailalias']['count']; $i++) { ?>
      <div>
      <input type="text" name="emailAlias[]" value="<?= $user_data['emailalias'][$i] ?>"/><a href="#" class="remove-link">Remove e-mail</a>
      </div>
    <?php } ?>
    <a id="email-alias-add" href="#">Add e-mail</a><br />
    </td>
  </tr>

  <tr>
    <td>Manager:</td>
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
        echo "<option value=\"{$managerlist[$j]['dn']}\" $selected>{$managerlist[$j]['cn'][0]}</option>";
      }
      ?>
      </select>
    </td>
  </tr>

  <tr>
    <td>Office City:</td>
    <td>
      <select id="office-city-select" name="office_city">
        <option value=""></option>
        <?php 
        foreach ($office_cities as $oc ) { 
          $selected = ($oc == $city) ? ' selected="selected"' : '';
          echo "<option value=\"$oc\"$selected>$oc</option>";
        }
        ?>
      </select>
      <input id="office-city-text" style="display: none;" type="text" name="office_city_name" value="<?= $city_name ?>" />
    </td>
  </tr>

  <tr>
    <td>Office Country:</td>
    <td>
      <select id="office_country_select" name="office_country">
        <option value=""></option>
        <?php 
        foreach($country_codes as $country_name => $code) {
          $selected = ($code == $country) ? 'selected="selected"' : '';
          print '<option '. $selected . ' value="' . htmlentities($code) . '">'. htmlentities($country_name) . '</option>';
        }
        ?>
      </select>
    </td>
  </tr>

  <tr>
    <td>Title:</td>
    <td><input type="text" name="title[]" value="<?= $user_data['title'][0] ?>"/></td>
  </tr>

  <tr>
    <td>Employee Status:</td>
    <td>
    <?php
      print_status_edit($user_data['employeetype'][0], array_key_exists('ismanager', $user_data) && $user_data['ismanager'][0], $is_admin);
    ?>
    </td>
  </tr>

  <tr>
    <td>Extension:</td>
    <td><input type="text" name="telephoneNumber[]" value="<?= $user_data['telephonenumber'][0] ?>"/></td>
  </tr>

  <tr>
    <td>Phone Number(s):</td>
    <td id="phone-numbers">
    <?php for ($i = 0; $i < $user_data['mobile']['count']; $i++) { ?>
      <div>
      <input type="text" name="mobile[]" value="<?= $user_data['mobile'][$i] ?>"/><a href="#" class="remove-link">Remove number</a>
      </div>
    <? } ?>
    <a id="phone-number-add" href="#">Add number</a><br />
    </td>
  </tr>

  <tr>
    <td>IM Account(s):</td>
    <td id="im-accounts">
    <?php for ($i = 0; $i < $user_data['im']['count']; $i++) { ?>
     <div>
     <input type="text" name="im[]" value="<?= $user_data['im'][$i] ?>"/><a href="#" class="remove-link">Remove account</a>
     </div>
    <? } ?>
    <a id="im-add" href="#">Add account</a><br />
    </td>
  </tr>

  <tr>
    <td>Bugzilla Email:</td>
    <td><input type="text" name="bugzillaEmail[]" value="<?= $user_data['bugzillaemail'][0] ?>"/></td>
  </tr>

  <tr>
    <td>I work on:</td>
    <td><textarea cols="40" rows="5" name="description[]"><?= $user_data['description'][0] ?></textarea></td>
  </tr>

  <tr>
    <td>Other:</td>
    <td><textarea cols="40" rows="5" name="other[]"><?= $user_data['other'][0] ?></textarea></td>
  </tr>

  <tr>
    <td>Current Photo:</td>
    <td><img src="pic.php?type=thumb&mail=<?= $user_data['mail'][0] ?>"/></td>
  </tr>

  <tr>
    <td>Upload new photo:</td>
    <td><input type="file" name="jpegPhoto" /></td>
  </tr>
</table>
<input type="hidden" name="edit_mail" value="<?= $edit_user ?>" />
<input type="submit" value="Save Changes" />

</form>

<?php require_once('templates/footer.php'); ?>
