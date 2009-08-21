<!DOCTYPE HTML>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>Mozilla Phonebook</title>
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" />
    <script src="js/prototype.js" type="text/javascript"></script>
    <script type="text/javascript">
    if (!window.console) {
      window.console = {};
      window.console.log = Prototype.emptyFunction;
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
      verticallyCenter: function(element, percentage) {
        element = $(element);
        if (!element.retrieve("originalMarginTop")) {
          element.store("originalMarginTop", element.getStyle("marginTop"));
        }
        var offset = $(document).viewport.getHeight() - element.getHeight();
        return element.setStyle({marginTop: (offset * (percentage || 0.4)) + "px"});
      },

      stopVerticallyCentering: function(element) {
        element = $(element);
        var original = element.getStorage().unset("originalMarginTop");
        return element.setStyle({marginTop: original});
      },

      searchify: function(element, results) {
        return $(element).writeAttribute({type: "search", results: results || 5});
      }
    });

    Element.addMethods("input", {
      releaseFocus: function(element) {
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

    String.prototype.dnToEmail = function() {
      var m = this.match(/mail=(\w+@mozilla.*),o=/);
      return (m ? m[1] : null);
    };

    Ajax.Responders.register({
      onCreate: function() { $("throbber").addClassName("loading"); },
      onComplete: function() { $("throbber").removeClassName("loading"); }
    });

    $(document).observe("dom:loaded", function() {
      $$("#menu a.persist").each(function(link) {
        link.store("originalLink", link.readAttribute("href"));
      });
    });
    $(document).observe("hash:changed", function(e) {
      $$("#menu a.persist").each(function(link) {
        link.writeAttribute("href", link.retrieve("originalLink") + "#" + e.memo.hash);
      });
    });
    </script>
  </head>

<body>

<div id="header">
  <form action="search.php" method="get" id="phonebook-search">
    <h1><a href="./">Phonebook</a></h1>
    <div id="search-region">
      <input type="hidden" name="format" value="html" />
      <input type="text" name="query" id="text" size="18" /><button type="submit" id="search">Search</button>
    </div>
    <div id="throbber"></div>
    <ul id="menu">
      <li><a class="card persist" href="./">Cards</a></li>
      <li><a class="wall persist" href="./wall.php">Facewall</a></li>
      <li><a class="tree" href="./tree.php">Org Chart</a></li>
      <li class="edit"><a class="edit" href="./edit.php" id="edit-entry">Edit My Entry</a></li>
    </ul>
  </form>
</div>

