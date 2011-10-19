
BehaviorManager.enable("slashSearch");

$(document).observe("dom:loaded", function() {
  $("edit-entry").addClassName("selected").removeAttribute("href");
  if (window.location.search.toQueryParams().edit_mail) {
    $("edit-entry").update("Edit Entry");
  }

  $("phonebook-search").observe("submit", function(e) {
    e.stop();
    window.location = "./#search/" + $F("text");
  });

  $("office-city-select").observe("change", function(e) {
    var city = $F(this),
        sel_option = $$('#office-city-select option[value='+city+']')[0],
        country = (city == 'Other') ? '' : sel_option.readAttribute('data-country');
    $("office-city-text")[city == "Other" ? "show" : "hide"]();
    $("office-country-select").value = country;
  });
  
  var remover = function(e) {
    e.element().up().remove();
    e.stop();
  };
  var cities = new Array();
  new Ajax.Request('office_cities.php', {
        method: 'get',
        onSuccess: function(transport) {
            var cities_json = transport.responseText.evalJSON();
            for (var i=0; i < cities_json.length; i++){
                cities.push(cities_json[i].office);
            }
        }
    });

  var office_adder = function(name, title) {
    title = "Remove " + title;
    return function(e) {
      var div = new Element("div");
      var select = new Element("select", {name: name});
      for(i=0; i < cities.length; i++){
        if (cities[i] != 'Other') {
            select.insert(new Option(cities[i], cities[i]));
        }
      } 
      var a = new Element("a", {href: '#', title: title});
      div.insert(select).insert(a);
      a.observe("click", remover).addClassName("remove-link");
      e.element().insert({before: div}); 
      e.stop();
      select.focus();
    };
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
  $("office-add").observe("click", office_adder("office_city[]", "office"));

  $w("office-cities").map(function(x) {
    return $(x).descendants().find("select + a");
  }).flatten().compact().invoke("observe", "click", remover).each(function(x) {
    x.writeAttribute("title", x.innerHTML).update('');
  });

  $w("email-aliases phone-numbers im-accounts").map(function(x) {
    return $(x).descendants().find("input + a");
  }).flatten().compact().invoke("observe", "click", remover).each(function(x) {
    x.writeAttribute("title", x.innerHTML).update('');
  });

  // Replace dumb combobox with an autocomplete textbox
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

