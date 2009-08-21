<?php
require_once "init.php";

$search = ldap_search(
  $ldapconn,
  "o=com, dc=mozilla",
  "mail=*",
  array("cn", "manager", "title", "mail", "employeeType")
);
$data = ldap_get_entries($ldapconn, $search);

$people = array();
$orphans = array();
$everyone = array();

foreach ($data as $person) {
  $mail = $person['mail'][0];
  $everyone[$mail] = array(
    "title" => !empty($person["title"][0]) ? $person["title"][0] : null,
    "name" => !empty($person["cn"][0]) ? $person["cn"][0] : null,
    "disabled" => isset($person["employeetype"]) ?
                    strpos($person["employeetype"][0], 'D') !== FALSE: 
                    FALSE
  );

  // If a user has a manager, try to find their place in the tree.
  if (!empty($person["manager"][0])) {
    $manager = explode(',', $person["manager"][0]);
    $manager = explode('=', $manager[0]);
    $manager = $manager[1];
    
    if (empty($people[$manager])) {
      $people[$manager] = array($mail);
    } else {
      $people[$manager][] = $mail;
    }
  } elseif (!empty($mail) && 
            strpos("lilly@mozilla.com", $mail) === FALSE && 
            strpos("mitchell@mozilla.com", $mail) === FALSE) {
    // Person is an orphan.
    $orphans[] = $mail;
  }
}

function item($email, $leaf=FALSE) {
  global $everyone;
  $email = htmlspecialchars($email);
  $id = str_replace('@', "-at-", $email);
  $name = htmlspecialchars($everyone[$email]["name"]);
  $title = htmlspecialchars($everyone[$email]["title"]);
  $leaf = $leaf ? " leaf" : '';
  $disabled = $everyone[$email]["disabled"] ? " disabled" : '';
  return "<li id=\"$id\" class=\"hr-node expanded$leaf$disabled\">".
           "<a href=\"#search/$email\" class=\"hr-link\">$name</a> ".
           "<span class=\"title\">$title</span>".
         "</li>";
}


function make_tree($level, $root, $nodes=null) {
  global $people;
  global $everyone;

  print "\n". item($root, ($nodes == null));

  if (is_array($nodes)) {
    print "\n<ul>";
    foreach ($nodes as $node) {
      if (!empty($people[$node])) {
        make_tree($level + 1, $node, $people[$node]);
      } else {
        make_tree($level + 1, $node);
      }
    }
    print "\n</ul>";
  }
  
  print "\n</li>";
}

require_once "templates/header.php";
?>

<div id="page">

<div id="orgchart" class="tree">
<ul>
<?= make_tree(0, 'mitchell@mozilla.com'); ?>
<?= make_tree(0, 'lilly@mozilla.com', $people['lilly@mozilla.com']); ?>
</ul>
</div>
<br />
<div id="orphans" class="tree">
<ul>
  <li>People who need to set their manager</li>
  <ul>
<?php
foreach ($orphans as $orphan) {
print "\n". item($orphan, TRUE);
}
?>
  </ul>
</ul>
</div>

<div id="person">
</div>

</div>

<script type="text/javascript">
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

$(document).observe("dom:loaded", function() {
  $("search").update("Filter");
  $("menu").down("a.tree").addClassName("selected");
  var region = $("person");

  $(document).observe("scroll", function() {
    var card = region.down("div.vcard");
    var tree = $("orgchart");
    if (!card || !tree) { return; }
    if (!card.retrieve("originalTop")) {
      card.store("originalTop", card.getStyle("marginTop"));
    }
    var refTop = tree.viewportOffset().top;
    card[refTop >= 5 ? "removeClassName" : "addClassName"]("snap-to-top");
  });

  $$("div li.hr-node").invoke("observe", "click", function(e) {
    !e.element().match("a") && $(this).toggleTree();
    select(this);
    e.stop();
  });

  $$("div li.hr-node span.title").invoke("observe", "click", function(e) {
    select($(this).up().addClassName("selected"));
    e.stop();
  });

  $(document).observe("keypress", function(e) {
    if ((e.which || e.keyCode) == 47) { // KEY_SLASH
      $("text").focus(); e.stop();
    }
  });

  $("text").observe("keypress", function(e) {
    if ((e.which || e.keyCode) == Event.KEY_ESC) {
      (function() { $(this).clear(); }).bind(this).defer();
      stopFiltering();
    }
    if ((e.which || e.keyCode) == 47) {
      e.stop();
    }
  });

  $("phonebook-search").observe("submit", function(e) {
    e.stop();
    stopFiltering();
    $("text").blur();
    if (!$F("text").strip()) {
      $$("#orgchart li:not(.leaf)").invoke("expand");
      return;
    }
    // window.location.hash = "#search/" + $F("text");
    filter();
  });

  function filter() {
    $("phonebook-search").request({
      parameters: {format: "json"},
      onSuccess: function onSuccess(r) {
        var people = r.responseText.evalJSON().pluck("dn").map(function(x) {
          var m = x.match(/mail=(\w+@mozilla.*),o=/);
          return m ? $(m[1].replace('@', "-at-")) : null;
        }).compact();
        people.sort(function(a, b) {
          return a.cumulativeOffset().top - b.cumulativeOffset().top;
        });
        console.log("Post-eval");
        
        var allowedToShow = people.map(function(x) {
          console.log("  " + x.id);
          var rootwards = x.ancestors().find("ul").compact().invoke("previous", "li");
          console.log("  Post rootwards");
          var leafwards = [];//x.next();
          console.log("  Post leafwards");
          // leafwards = leafwards && leafwards.match("ul") ? leafwards.select("li") : [];
          console.log("  Post leafing");
          return [x].concat(rootwards).concat(leafwards).compact();
        });
        
        $$("#orgchart li:not(.leaf)").invoke("collapse");
        console.log("Post-collapsing");
        allowedToShow.flatten().uniq().find(":not(.leaf)").invoke("expand");
        console.log("Post-expansion");
        people.invoke("addClassName", "highlighted").first().scrollTo();
        $("orgchart").addClassName("filter-view");
        // allowedToShow.each(function(x) console.log(x));
      }
    });
  }

  function stopFiltering() {
    $("orgchart").removeClassName("filter-view");
    $$("#orgchart li.highlighted").invoke("removeClassName", "highlighted");
    $$("#orgchart li:not(.leaf)").invoke("expand");
  }

  var selected = null;
  function select(node) {
    selected && selected.removeClassName("selected");
    selected = $(node).addClassName("selected");
    window.location.hash = "#search/" + selected.id.replace("-at-", '@');
  }
  
  $(document).observe("hash:changed", function(e) {
    showPerson(e.memo.hash.replace("search/", ''));
  });
  var hash = window.location.hash;
  if (hash.startsWith("#search/")) {
    $(document).fire("hash:changed", {hash: hash.substring(1)});
    var search = $("text").value = hash.replace(/^#search\//, '');
    if (!search.strip()) { return; }
    filter();
  }

  function showPerson(email) {
    new Ajax.Request("search.php", {
      method: "get",
      parameters: {query: email, format: "html"},
      onSuccess: function(r) {
        $("person").update(r.responseText).down(".vcard");
      }
    });
  }
});
</script>

<?php require_once "templates/footer.php"; ?>
