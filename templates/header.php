<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
                      "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
    <title>Mozilla Corporation/Foundation Phonebook</title>
    <link href="css/style.css" rel="stylesheet" type="text/css">
    <script src="js/prototype.js" type="text/javascript"></script>
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

      $("edit-entry").removeAttribute("href");

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
        return $(x).down("input + a");
      }).compact().invoke("observe", "click", remover).each(function(x) {
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
  </head>
<body>

<div id="header">
  <form action="search.php" method="get" id="phonebook-search">
    <h1>Phonebook</h1>
    <div id="search-region">
      <input type="text" name="query" id="text" />
      <input type="submit" value="Search" id="search" />
    </div>
    <ul id="menu">
      <li><a href="./#search/*">Everyone</a></li>
      <li><a href="https://intranet.mozilla.org/">Intranet</a></li>
      <li><a href="https://intranet.mozilla.org/OfficeLocations">Offices</a></li>
      <li class="edit"><a href="edit.php" id="edit-entry">Edit My Entry</a></li>
    </ul>
  </form>
</div>
<hr />

