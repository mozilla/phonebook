
BehaviorManager.enable("slashSearch");
SearchManager.startSearch = function() {
  if (!$F("text").strip()) {
    window.location = "./";
  }
  BehaviorManager.disable("centerHeader") && 
                  $("phonebook-search").removeClassName("large");
  $("phonebook-search").request({onSuccess: function onSuccess(r) {
    $("results").update('').update(r.responseText ||
      '<div style="text-align: center; margin-top: 5em;">' + 
        '<img src="./img/ohnoes.jpg" />' + 
        '<h2>OH NOES! No ones were foundz.</h2>' +
      '</div>'
    );
    $("text").releaseFocus();
  }});
};

$(document).observe("dom:loaded", function() {
  $("menu").down("a.card").addClassName("selected");
  SearchManager.initialize();
});
