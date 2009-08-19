<?php require_once('templates/header.php'); ?>
<div id="results">
</div>

<script type="text/javascript">
Function.prototype.lazify = function() {
  var cache = {};
  return this.wrap(function(original, x) {
    return cache[x] ? cache[x] : (cache[x] = original(x));
  });
};

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

(function() {
  var cards = 2;
  Event.observe(Prototype.resizeTarget, "resize", function() {
    var c = Math.floor(document.viewport.getWidth() / Number.emToPx(1) / 31);
    if (cards != c && c != 0) {
      $("results").setStyle({width: ((cards = c) * 31) + "em"});
    }
  });
})();

$(document).observe("keypress", function(e) {
  if ((e.charCode || e.keyCode) == 47) { // KEY_SLASH
    $("text").focus(); e.stop();
  }
});

$(document).observe("dom:loaded", function() {
  // Just this one special treatment, Safari
  Prototype.Browser.WebKit && $("text").searchify() && $("search").hide(); 

  $(document).observe("hash:changed", function() {
    $("text").value = window.location.hash.replace("#search/", '');
    startSearch();
  });

  $("phonebook-search").observe("submit", function(e) {
    e.stop();
    window.location.hash = "#search/" + $F("text");
  });

  var stoppedResizing = false;
  if (window.location.hash.startsWith("#search/")) {
    $(document).fire("hash:changed", {hash: window.location.hash.substring(1)});
  } else {
    $("phonebook-search").addClassName("large");
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
    if (!$F("text").strip()) {
      window.location = "./";
    }
    if (!stoppedResizing) {
      centralize.stop = true;
      stoppedResizing = true;
      $("header").stopVerticallyCentering();
      $("phonebook-search").removeClassName("large");
    }
    $("phonebook-search").request({onSuccess: function onSuccess(r) {
      $("results").update('').update(r.responseText ||
        '<div style="text-align: center; margin-top: 5em;">' + 
          '<img src="./img/ohnoes.jpg" />' + 
          '<h2>OH NOES! No ones were foundz.</h2>' +
        '</div>'
      );
      $("text").releaseFocus();
    }});
  }
});
</script>
<?php require_once('templates/footer.php'); ?>
