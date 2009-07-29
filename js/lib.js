var i = 0;
var countryMap = {
  'Mountain View': 'US',
  'Auckland': 'NZ',
  'Beijing': 'CN',
  'Denmark': 'DK',
  'Paris': 'FR',
  'Toronto': 'CA',
  'Tokyo': 'JP'
};


function addPhoneNumberInput() {
  $('#phone_number_add').before($('<div id="phone_number_js' + i + '"><input type="text" name="mobile[]" /> <a href="#" onclick="removePhoneNumber(\'js'+i+'\')">Remove number</a></div>'));
  i++;
}
function removePhoneNumber(id) {
  $('#phone_number_'+ id).remove();
}
function addEmailAliasInput() {
  $('#email_alias_add').before($('<div id="email_alias_js' + i + '"><input type="text" name="emailAlias[]" /> <a href="#" onclick="removeEmailAlias(\'js'+i+'\')">Remove e-mail</a></div>'));
  i++;
}
function removeEmailAlias(id) {
  $('#email_alias_'+id).remove();
}
function addIMInput() {
  $('#im_add').before($('<div id="im_js' + i + '"><input type="text" name="im[]" /> <a href="#" onclick="removeIM(\'js'+i+'\')">Remove account</a></div>'));
  i++;
}
function removeIM(id) {
  $('#im_'+id).remove();
}
function officeCityEventHandler(e) {
  checkOfficeCitySelect($('#office_city_select'));
  if (countryMap[$('#office_city_select').val()]) {
    $('#office_country_select').val(countryMap[$('#office_city_select').val()]);
  }
}
function checkOfficeCitySelect(select_elm) {
  if (select_elm.val() == 'Other') {
    $('#office_city_text').show();
  } else {
    $('#office_city_text').hide();
  }
}
