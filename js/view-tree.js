
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
  var cardcount = $("person").childElements("div.vcard").length;
  if (cardcount > 1) return;

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
  Tree.doNotFilter = true;
  e.element().match("a") && Tree.select(this);
  e.stop();
}.toBehavior(new Selector("div li.hr-node"), "click"));

BehaviorManager.register("treeNodeSelecting", function(e) {
  Tree.doNotFilter = true;
  Tree.select($(this).up().addClassName("selected"));
  e.stop();
}.toBehavior(new Selector("div li.hr-node span.title"), "click"));

BehaviorManager.register("clearFilterOnEsc", function(e) {
  if ((e.which || e.keyCode) == Event.KEY_ESC) {
    Tree.clearFilter();
  }
}.toBehavior("text", "keyup"));

BehaviorManager.register("clearFilterOnClick", {
  bound: false,

  onMouseDown: function(e) {
    e.findElement().addClassName("active");
  },
  onMouseUp: function(e) {
    e.findElement().removeClassName("active");
    this.fire();
  },
  onKeyUp: function(e) { this.update(); },

  update: function(e) {
    (function() {
      this.button[$F("text") == "" ? "hide" : "show"]();
    }).bind(this).defer();
  },

  enable: function() {
    if (!this.bound) {
      $w("onMouseDown onMouseUp onKeyUp").each(function(e) {
        this[e] = this[e].bind(this);
      }.bind(this));
      this.bound = true;
    }
    $("text").addClassName("with-clear-button");
    this.button = new Element("div", {id: "clear-button"}).update("Clear");
    this.container = $("text").wrap("div", {id: "text-wrapper"});
    this.container.insert(this.button.writeAttribute("title", "Clear"));
    this.button.observe("mousedown", this.onMouseDown).
                observe("mouseup", this.onMouseUp);
    $("text").observe("keyup", this.onKeyUp);
    this.onKeyUp();
  },

  disable: function() {
    $("text").removeClassName("with-clear-button");
    this.button.stopObserving("mousedown", this.onMouseDown).
                stopObserving("mouseup", this.onMouseUp).
                remove();
    this.container.insert({before: $("text").remove()}).remove();
    $("text").stopObserving("keyup", this.onKeyUp);
  },

  fire: function() {
    Tree.clearFilter();
    this.button.hide();
    $("text").focus();
  }
});

BehaviorManager.register("filterOnSubmit", function(e) {
  e.stop();
  $("text").blur();
  if (!$F("text").strip()) {
    $$("#orgchart li:not(.leaf)").invoke("expand");
    return;
  }
  window.location.hash = "search/" + $F("text");
}.toBehavior("phonebook-search", "submit"));

var Tree = {
  doNotFilter: false,
  selected: null,
  select: function(node) {
    this.selected && this.selected.removeClassName("selected");
    this.selected = $(node).addClassName("selected");
    window.location.hash = "search/" + this.selected.id.replace("-at-", '@');
  },

  clearFilter: function() {
    (function() { $("text").clear(); }).defer();
    this.stopFiltering();
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
        $("person").update(r.responseText || this.notFoundMessage).down(".vcard");
        BehaviorManager.fire("scrollSnap");
      }
    });
  },

  filter: function() {
    $("phonebook-search").request({
      parameters: {format: "json"},
      onSuccess: function onSuccess(r) {
        var people = r.responseText.evalJSON();
        var converter = this.dnToEmail || function(x) { return x.dnToEmail(); };
        people = people.pluck("dn").map(converter).compact();
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
        
        if (people.length > 0) {
          $$("#orgchart li:not(.leaf)").invoke("collapse");
          console.log("Post-collapsing");
          allowedToShow.flatten().uniq().find(":not(.leaf)").invoke("expand");
          console.log("Post-expansion");
          people.invoke("addClassName", "highlighted").first().scrollTo();
        } else {
          $("person").update(SearchManager.notFoundMessage || '');
        }
        $("orgchart").addClassName("filter-view");
      }.bind(this)
    });
  }
};

Object.extend(SearchManager, {
  enabledBehaviorsOnInit: ["filterOnSubmit"],
  onHashChange: function(e) {
    var query = e.memo.hash.replace("search/", '');
    console.log(query);
    $("text").value = query;
    if (query.include('@')) {
      Tree.showPerson.bind(this)(query);
      Tree.select($(query.replace('@', "-at-")));
    }
    if (!Tree.doNotFilter) {
      Tree.filter();
    } else {
      BehaviorManager.update("clearFilterOnClick");
      Tree.doNotFilter = false;
    }
  },

  onLoad: function() {
    var hash = window.location.hash;
    if (hash.startsWith("#search/")) {
      var search = hash.replace(/^#search\//, '');
      if (!search.strip()) { return; }
      $("text").value = search;
      $(document).fire("hash:changed", {hash: hash.substring(1)});
    }
  }
});

$(document).observe("dom:loaded", function() {
  $("search").update("Filter");
  $("menu").down("a.tree").addClassName("selected");
  BehaviorManager.enable("scrollSnap");
  BehaviorManager.enable("treeNodeToggling");
  BehaviorManager.enable("slashSearch");
  BehaviorManager.enable("clearFilterOnEsc");
  BehaviorManager.enable("clearFilterOnClick");

  SearchManager.initialize();
});

Object.extend(Tree, {
  dnToEmail: function(x) {
    var e = x.dnToEmail();
    return e ? $(e.replace('@', "-at-")) : null;
  }
});
