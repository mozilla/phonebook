
BehaviorManager.register("centerVCard", {
  observer: function() { $$("div.vcard").invoke("verticallyCenter"); },
  enable: function() {
    Event.observe(Prototype.resizeTarget, "resize", this.observer);
  },
  disable: Prototype.emptyFunction
});

Object.extend(SearchManager, {
  startSearch: function() {
    BehaviorManager.disable("centerHeader") && 
      $("phonebook-search").removeClassName("large");
    $("phonebook-search").request({
      parameters: {format: "json"},
      onSuccess: function onSuccess(r) {
        $("text").releaseFocus();
        $("results").update('');
        var results = r.responseText.evalJSON().each(function(person) {
          var container = new Element("div").addClassName("photo-frame");
          var face = new Element("img", {
            src: person.picture + "&type=thumb", "class": "wall-photo"
          }).observe("click", function() {
            this.showPerson(person.dn);
          }.bind(this));
          var name = new Element("span").update(person.cn);
          container.insert(name).insert(face);
          if (person.employeetype.indexOf("DISABLED") != -1) {
            container.addClassName("disabled");
          }
          $("results").insert(container);
        }.bind(this));
        if (results.length == 0) {
          $("results").update(this.notFoundMessage);
        }
      }.bind(this)
    });
  },

  showPerson: function(dn) {
    $("phonebook-search").request({
      parameters: {format: "html", query: dn.dnToEmail()},
      onSuccess: function onSuccess(r) {
        $(document.body).addClassName("lightbox");
        var close = new Element("div").observe("click", function(e) {
          $(document.body).removeClassName("lightbox");
        }).addClassName("close-button").writeAttribute("title", "Close");
        $("overlay").update('').update(r.responseText).
                     down("div.vcard").verticallyCenter().
                     down("div.header").insert(close);
        $("overlay").down(".vcard p.manager a").observe("click", function() {
          $(document.body).removeClassName("lightbox");
        });
      }
    });
  }
});

BehaviorManager.enable("slashSearch");

$(document).observe("dom:loaded", function() {
  $("menu").down("a.wall").addClassName("selected");
  BehaviorManager.enable("centerVCard");
  
  $("overlay").observe("click", function(e) {
    if (e.element() == this) {
      $(document.body).removeClassName("lightbox");
    }
  });

  SearchManager.initialize();
});

