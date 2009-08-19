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
    <td>
      <select id="office-city-select" name="office_city">
        <option value=""></option>
        <?php 
        foreach ($office_cities as $oc ) { 
          $selected = ($oc == $city) ? ' selected="selected"' : '';
          $oc = escape($oc);
          echo "<option value=\"$oc\"$selected>$oc</option>";
        }
        ?>
      </select>
      <input id="office-city-text" style="display: none;" type="text" name="office_city_name" value="<?= escape($city_name) ?>" />
    </td>
  </tr>

  <tr>
    <td><label>Office Country</label></td>
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
    <td><label>Title</label></td>
    <td><input type="text" name="title[]" value="<?= escape($user_data['title'][0]) ?>"/></td>
  </tr>

  <tr>
    <td><label>Employee Status</label></td>
    <td>
    <?php
      print_status_edit($user_data['employeetype'][0], array_key_exists('ismanager', $user_data) && $user_data['ismanager'][0], $is_admin);
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
    <td><input type="text" name="bugzillaEmail[]" value="<?= escape($user_data['bugzillaemail'][0]) ?>"/></td>
  </tr>

  <tr>
    <td><label>I work on</label></td>
    <td>
      <textarea cols="40" rows="5" name="description[]"><?= $user_data['description'][0] ?></textarea><br />
      <em class="description">Links in <a href="http://en.wikipedia.org/wiki/Help:Wikitext_examples">wiki markup</a> style supported.</em>
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
        <input id="photo-upload" type="file" name="jpegPhoto" /><br />
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

<script src="js/autocomplete.js" type="text/javascript"></script>
<script type="text/javascript">
var countryMap = {
  'Mountain View': 'US',
  'Auckland': 'NZ',
  'Beijing': 'CN',
  'Denmark': 'DK',
  'Paris': 'FR',
  'Toronto': 'CA',
  'Tokyo': 'JP' 
};

$(document).observe("keypress", function(e) {
  if ((e.charCode || e.keyCode) == 47) { // KEY_SLASH
    $("text").focus();
    e.stop();
  }
});

$(document).observe("dom:loaded", function() {

  $("phonebook-search").observe("submit", function(e) {
    e.stop();
    window.location = "./#search/" + $F("text");
  });

  $("edit-entry").addClassName("selected").removeAttribute("href");

  $("office-city-select").observe("change", function(e) {
    var city = $F("office-city-select");
    $("office-city-text")[city == "Other" ? "show" : "hide"]();
    if (countryMap[city]) {
      $("office-country-select").value = countryMap[city];
    }
  });
  
  var remover = function(e) {
    e.element().up().remove();
    e.stop();
  };
  var adder = function(name, title) {
    title = "Remove " + title;
    return function(e) {
      var div = new Element("div");
      var input = new Element("input", {type: "text", name: name});
      var a = new Element("a", {href: '#', title: title});
      div.insert(input).insert(a);
      a.observe("click", remover).addClassName("remove-link");
      e.element().insert({before: div}); e.stop();
      input.focus();
    };
  };

  $("email-alias-add").observe("click", adder("emailAlias[]", "e-mail"));
  $("phone-number-add").observe("click", adder("mobile[]", "number"));
  $("im-add").observe("click", adder("im[]", "account"));

  $w("email-aliases phone-numbers im-accounts").map(function(x) {
    return $(x).descendants().find("input + a");
  }).flatten().compact().invoke("observe", "click", remover).each(function(x) {
    x.writeAttribute("title", x.innerHTML).update('');
  });

  var manager = new Element("input", {type: "text", id: "manager-text"});
  $("select-manager").hide().insert({before: manager});
  manager.value = $$("option[value='#{dn}']".interpolate({
    dn: $F("select-manager")
  }))[0].innerHTML;
  new Autocomplete(manager, {
    serviceUrl: "./search.php?format=autocomplete",
    minChars: 2,
    onSelect: function(value, data) {
      $("select-manager").value = data;
    }
  });

});
</script>

<?php require_once('templates/footer.php'); ?>
