
Element.addMethods("li", {
  childTree: function(element) {
    var next = $(element).next();
    return (next && next.match("ul")) ? next : undefined;
  },

  collapse: function(element) {
    var child = $(element).childTree();
    child && child.hide();
    return $(element).addClassName("collapsed").removeClassName("expanded");
  },

  expand: function(element) {
    var child = $(element).childTree();
    child && child.show();
    return $(element).addClassName("expanded").removeClassName("collapsed");
  },

  collapsed: function(element) {
    return $(element).hasClassName("collapsed");
  },

  expanded: function(element) {
    return !$(element).collapsed();
  },

  toggleTree: function(element) {
    element = $(element);
    return element[element.collapsed() ? "expand" : "collapse"]();
  }
});

BehaviorManager.register("scrollSnap", function() {
  var card = $("person").down("div.vcard");
  var tree = $("orgchart");
  if (!card || !tree) { return; }
  if (!card.retrieve("originalTop")) {
    card.store("originalTop", card.getStyle("marginTop"));
  }
  var refTop = tree.viewportOffset().top;
  card[refTop >= 5 ? "removeClassName" : "addClassName"]("snap-to-top");
}.toBehavior(document, "scroll"));

BehaviorManager.register("treeNodeToggling", function(e) {
  !e.element().match("a") && $(this).toggleTree();
  Tree.select(this);
  e.stop();
}.toBehavior(new Selector("div li.hr-node"), "click"));

BehaviorManager.register("treeNodeSelecting", function(e) {
  Tree.select($(this).up().addClassName("selected"));
  e.stop();
}.toBehavior(new Selector("div li.hr-node span.title"), "click"));

BehaviorManager.register("stopFilteringOnEsc", function(e) {
  console.log(e.which || e.keyCode);
  if ((e.which || e.keyCode) == Event.KEY_ESC) {
    (function() { $(this).clear(); }).bind(this).defer();
    Tree.stopFiltering();
  }
}.toBehavior("text", "keyup"));

BehaviorManager.register("filterOnSubmit", function(e) {
  e.stop();
  Tree.stopFiltering();
  $("text").blur();
  if (!$F("text").strip()) {
    $$("#orgchart li:not(.leaf)").invoke("expand");
    return;
  }
  Tree.filter();
}.toBehavior("phonebook-search", "submit"));

var Tree = {
  selected: null,
  select: function(node, changeHash) {
    this.selected && this.selected.removeClassName("selected");
    this.selected = $(node).addClassName("selected");
    window.location.hash = "#search/" + this.selected.id.replace("-at-", '@');
  },

  stopFiltering: function() {
    $("orgchart").removeClassName("filter-view");
    $$("#orgchart li.highlighted").invoke("removeClassName", "highlighted");
    $$("#orgchart li:not(.leaf)").invoke("expand");
  },

  showPerson: function(email) {
    new Ajax.Request("search.php", {
      method: "get",
      parameters: {query: email, format: "html"},
      onSuccess: function(r) {
        $("person").update(r.responseText).down(".vcard");
        BehaviorManager.fire("scrollSnap");
      }
    });
  },

  filter: function() {
    $("phonebook-search").request({
      parameters: {format: "json"},
      onSuccess: function onSuccess(r) {
        var people = r.responseText.evalJSON();
        people = people.pluck("dn").map(this.dnToEmail).compact();
        people.sort(function(a, b) {
          return a.cumulativeOffset().top - b.cumulativeOffset().top;
        });
        console.log("Post-eval");
        
        var allowedToShow = people.map(function(x) {
          console.log("  " + x.id);
          var rootwards = x.ancestors().find("ul").compact().invoke("previous", "li");
          console.log("  Post rootwards");
          var leafwards = [];
          console.log("  Post leafwards");
          console.log("  Post leafing");
          return [x].concat(rootwards).concat(leafwards).compact();
        });
        
        $$("#orgchart li:not(.leaf)").invoke("collapse");
        console.log("Post-collapsing");
        allowedToShow.flatten().uniq().find(":not(.leaf)").invoke("expand");
        console.log("Post-expansion");
        people.invoke("addClassName", "highlighted").first().scrollTo();
        $("orgchart").addClassName("filter-view");
      }.bind(this)
    });
  }
};

Object.extend(SearchManager, {
  enabledBehaviorsOnInit: ["filterOnSubmit"],
  onHashChange: function(e) {
    var email = e.memo.hash.replace("search/", '');
    Tree.showPerson(email);
    Tree.select($(email.replace('@', "-at-")));
  },

  onLoad: function() {
    var hash = window.location.hash;
    if (hash.startsWith("#search/")) {
      $(document).fire("hash:changed", {hash: hash.substring(1)});
      var search = $("text").value = hash.replace(/^#search\//, '');
      if (!search.strip()) { return; }
      Tree.filter();
    }
  }
});

$(document).observe("dom:loaded", function() {
  $("search").update("Filter");
  $("menu").down("a.tree").addClassName("selected");

  BehaviorManager.enable("scrollSnap");
  BehaviorManager.enable("treeNodeToggling");
  BehaviorManager.enable("slashSearch");
  BehaviorManager.enable("stopFilteringOnEsc");

  SearchManager.initialize();
});

Object.extend(Tree, {
  dnToEmail: function(x) {
    var m = x.match(/mail=(\w+@mozilla.*),o=/);
    return m ? $(m[1].replace('@', "-at-")) : null;
  }
});
