<?php
require_once('templates/header.php'); ?>
<div id="results">
</div>

<div id="overlay">
</div>

<script type="text/javascript">
Function.prototype.lazify = function() {
  var cache = {};
  return this.wrap(function(original, x) {
    return cache[x] ? cache[x] : (cache[x] = original(x));
  });
};

String.toInt = parseInt.methodize();

Number.emToPx = function(x) {
  var div = new Element("div").setStyle({
    position: "absolute", margin: "0", padding: "0", visibility: "none",
    width: x + "em", height: "9px", top: "-100000em", left: "-100000em"
  });
  $(document.body).insert(div);
  var px = div.getWidth();
  div.remove();
  return px;
}.lazify();

$(document).observe("keypress", function(e) {
  if ((e.charCode || e.keyCode) == 47) { // KEY_SLASH
    $("text").focus(); e.stop();
  }
});

$(document).observe("dom:loaded", function() {
  $("phonebook-search").addClassName("large");

  var adjustLayout = function() {
    var img = $$("img.wall-photo");
    img = img.length ? img[0] : null;
    var width = 140;
    if (img) {
      width = img.width +
        $w("paddingLeft marginLeft paddingRight marginRight").map(function(x) {
          return img.getStyle(x).replace("px", '').toInt();
        }).inject(function(x, y) { return x + y; });
    } else {
      width += Number.emToPx(5);
    }
    var photos = 4;
    Event.observe(Prototype.resizeTarget, "resize", readjust);
    function readjust() {
      var c = Math.floor(document.viewport.getWidth() / width);
      console.log(c);
      if (photos != c && c != 0) {
        $("results").setStyle({width: ((photos = c) * width) + "px"});
      }
    }
  };
  adjustLayout();
  $("results").setStyle({width: "880px"});
  $("menu").down("a.wall").addClassName("selected");

  $(document).observe("hash:changed", function(e) {
    $("text").value = e.memo.hash.replace("search/", '');
    startSearch();
  });

  $("phonebook-search").observe("submit", function(e) {
    window.location.hash = "#search/" + $F("text");
    e.stop();
  });
  
  $("overlay").observe("click", function(e) {
    if (e.element() == this) {
      $(document.body).removeClassName("lightbox");
    }
  });
  Event.observe(Prototype.resizeTarget, "resize", function() {
    $$("div.vcard").invoke("verticallyCenter");
  });

  var stoppedResizing = false;
  if (window.location.hash.startsWith("#search/")) {
    $(document).fire("hash:changed", {hash: window.location.hash.substring(1)});
  } else {
    $("header").verticallyCenter();
    Event.observe(Prototype.resizeTarget, "resize", centralize);
    $("text").focus();
  }

  function centralize() {
    if (!arguments.callee.stop) {
      $("header").verticallyCenter();
    }
  }
  
  function startSearch() {
    if (!stoppedResizing) {
      centralize.stop = true;
      stoppedResizing = true;
      $("header").stopVerticallyCentering();
      $("phonebook-search").removeClassName("large");
    }
    $("phonebook-search").request({
      parameters: {format: "json"},
      onSuccess: function onSuccess(r) {
        $("text").releaseFocus();
        $("results").update('');
        r.responseText.evalJSON().each(function(person) {
          var container = new Element("div").addClassName("photo-frame");
          var face = new Element("img", {
            src: person.picture + "&type=thumb", "class": "wall-photo"
          }).observe("click", function() { showPerson(person.dn); });
          var name = new Element("span").update(person.cn);
          container.insert(name).insert(face);
          if (person.employeetype.indexOf("DISABLED") != -1) {
            container.addClassName("disabled");
          }
          $("results").insert(container);
        });
        adjustLayout();
      }
    });
  }

  function showPerson(dn) {
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
</script>

<?php require_once('templates/footer.php'); ?>
