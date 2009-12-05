
if (!window.console) {
  window.console = {log: Prototype.emptyFunction};
}

// Implement onhashchange support
(function() {
  var hash = window.location.hash;
  var fire = function(str) {
    $(document).fire("hash:changed", { hash: str.substring(1) });
  };
  var pe = new PeriodicalExecuter(function() {
    var newHash = window.location.hash;
    if (newHash != hash) {
      fire(newHash);
      hash = newHash;
    }
  }, 1);
})();

Element.addMethods({
  verticallyCenter: function verticallyCenter(element, percentage) {
    element = $(element);
    if (!element.retrieve("originalMarginTop")) {
      element.store("originalMarginTop", element.getStyle("marginTop"));
    }
    var offset = $(document).viewport.getHeight() - element.getHeight();
    return element.setStyle({marginTop: (offset * (percentage || 0.4)) + "px"});
  },

  stopVerticallyCentering: function stopVerticallyCentering(element) {
    element = $(element);
    var original = element.getStorage().unset("originalMarginTop");
    return element.setStyle({marginTop: original});
  }
});

// Force Gecko to redraw the blinking cursor
Element.addMethods("input", {
  releaseFocus: function releaseFocus(element) {
    $w("blur focus blur").each(function(method) { $(element)[method](); });
    return element;
  }
});

Prototype.resizeTarget = document.onresize ? document : window;

Array.prototype.find = function find(selector) {
  return this.filter(function(x) {
    return x.match ? x.match(selector) : false;
  });
};

// Show / hide throbbers accoring to Ajax requests
(function() {
  function update() {
    var method = (Ajax.activeRequestCount == 0 ? "remove" : "add") + "ClassName";
    $("throbber")[method]("loading");
  }
  Ajax.Responders.register({
    onCreate:   update,
    onComplete: update
  });
})();

// Search keyword persistence between different views
$(document).observe("dom:loaded", function() {
  $$("#menu a.persist").each(function(a) {
    a.store("originalLink", a.readAttribute("href"));
  });
});
$(document).observe("hash:changed", function(e) {
  $$("#menu a.persist").each(function(a) {
    a.writeAttribute("href", a.retrieve("originalLink") + "#" + e.memo.hash);
  });
});

Function.prototype.lazify = function lazify() {
  var cache = {};
  return this.wrap(function(original, x) {
    return cache[x] ? cache[x] : (cache[x] = original(x));
  });
};

Number.emToPx = function emToPx(x) {
  var div = new Element("div").setStyle({
    position: "absolute", margin: "0", padding: "0", visibility: "none",
    width: x + "em", height: "9px", top: "-100000em", left: "-100000em"
  });
  $(document.body).insert(div);
  var px = div.getWidth();
  div.remove();
  return px;
}.lazify();

Number.prototype.emToPx = Number.emToPx.methodize();
String.prototype.toInt = window.parseInt.methodize();

// BehaviorManager allows encapsulation of event handlers that frequently need
// to be enabled or disabled due to change of state. Behaviors must be first
// registered with BehaviorManager.register().
var BehaviorManager = {
  behaviors: {},

  // `behaviors' is an object that must support three methods at minimum:
  // `enable', `disable', and `fire'.
  // * `enable' is called when a behavior is switched on.
  // * `disable' is called when a behavior is switched off.
  // * `fire' is called when the behavior must be executed immediately.
  //   If `fire' is not present, BehaviorManager assumes that a function called
  //   `observer' is present and calls it.
  // Although not enforced by BehaviorManager, it is customary to name the
  // event handler `observer".
  // The `behaviors' object must not manipulate `_enabled', as it is used
  // internally by BehaviorManager.
  register: function register(name, behaviors) {
    this.behaviors[name] = behaviors;
    this.behaviors[name]._enabled = false;
  },

  enable: function enable(behavior, immediatelyFire) {
    var name = behavior;
    behavior = this.behaviors[behavior];
    (behavior._enabled = true) && behavior.enable();
    if (immediatelyFire) {
      this.fire(name);
    }
  },

  // Returns false if the behavior has already been disabled, otherwise true.
  disable: function disable(behavior) {
    behavior = this.behaviors[behavior];
    if (behavior._enabled) {
      behavior.disable();
      behavior._enabled = false;
      return true;
    } else {
      return false;
    }
  },

  fire: function fire(behavior) {
    behavior = this.behaviors[behavior];
    (behavior.fire || behavior.observer).bind(behavior)();
  },

  update: function fire(behavior) {
    behavior = this.behaviors[behavior];
    if (behavior.update) {
      behavior.update();
    }
    return !!behavior.update;
  }
};

// If o is a string, it will be treated as an id.
// If o is an element, make sure the DOM is loaded.
// If o is a Selector, it will be a live set of nodes that match the CSS selector.
Function.prototype.toBehavior = function toBehavior(o, event) {
  return {
    observer: this,
    update: function() { this.observer(); },
    enable: o instanceof Selector ? function() {
        o.findElements().uniq().invoke("observe", event, this.observer);
      } : function() { Event.observe(o, event, this.observer); },
    disable: o instanceof Selector ? function() {
      o.findElements().uniq().invoke("stopObserving", event, this.observer);
      } : function() { Event.stopObserving(o, event, this.observer); }
  };
};

BehaviorManager.register("slashSearch", function(e) {
  if (["input", "textarea"].include(e.findElement().tagName.toLowerCase())) {
    return;
  }
  if ((e.which || e.keyCode) == 47) { // KEY_SLASH
    $("text").focus(); e.stop();
  }
}.toBehavior(document, "keypress"));

BehaviorManager.register("submitOnEnter", function(e) {
  e.stop();
  window.location.hash = "#search/" + $F("text");
}.toBehavior("phonebook-search", "submit"));

BehaviorManager.register("centerHeader", {
  attached: false,

  fire: function fire() {
    this.observer.adjust = true;
    this.observer();
  },

  observer: function observer(e) {
    if (arguments.callee.adjust) { $("header").verticallyCenter(); }
  },

  enable: function enable() {
    this.observer.adjust = true;
    if (!this.attached) {
      Event.observe(Prototype.resizeTarget, "resize", this.observer);
      this.attached = true;
    }
  },

  disable: function disable() {
    this.observer.adjust = false;
    $("header").stopVerticallyCentering();
  }
});

// Normally, you should simply mix in a method called `startSearch' and then
// call SearchManager.initialize() when DOM is loaded. For advanced examples,
// take a look at tree.js, which overrides everything but `initialize'.
var SearchManager = {
  enabledBehaviorsOnInit: ["submitOnEnter"],
  initialize: function initialize() {
    $(document).observe("hash:changed", this.onHashChange.bind(this));
    this.onLoad();
    this.enabledBehaviorsOnInit.each(function(b) {
      BehaviorManager.enable(b);
    });
  },

  onHashChange: function onHashChange(e) {
    $("text").value = e.memo.hash.replace("search/", '');
    this.startSearch.bind(this)();
  },

  onLoad: function onLoad() {
    if (window.location.hash.startsWith("#search/")) {
      $(document).fire("hash:changed", {
        hash: window.location.hash.substring(1)
      });
    } else {
      $("phonebook-search").addClassName("large");
      BehaviorManager.enable("centerHeader", true);
      $("text").focus();
    }
  }
};

String.prototype.dnToEmail = function dnToEmail() {
  var m = this.match(/mail=(\w+@mozilla.*),o=/);
  return (m ? m[1] : null);
};

SearchManager.notFoundMessage =
  '<div style="text-align: center; margin-top: 5em;">' +
    '<img src="./img/ohnoes.jpg" />' +
    '<h2>OH NOES! No ones were foundz.</h2>' +
  '</div>';

