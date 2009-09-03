
BehaviorManager.enable("slashSearch");
SearchManager.startSearch = function() {
  if (!$F("text").strip()) {
    return;
  }
  if (BehaviorManager.disable("centerHeader")) {
    $("phonebook-search").removeClassName("large");
  }
  $("phonebook-search").request({onSuccess: function onSuccess(r) {
    $("results").update('').update(r.responseText || this.notFoundMessage);
    $("text").releaseFocus();
  }.bind(this)});
};

$(document).observe("dom:loaded", function() {
  $("menu").down("a.card").addClassName("selected");
  SearchManager.initialize();
});

