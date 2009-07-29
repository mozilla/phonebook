<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" dir="ltr">
  <head>
    <title>Phonebook</title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <script src="./js/prototype.js" type="text/javascript"></script>
    <!--<script src="./js/jquery-1.3.2.min.js" type="text/javascript"></script>-->
    <link rel="stylesheet" type="text/css" href="./css/style.css"/>
    <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" /> 

    <script type='text/javascript'>
    // jQuery.noConflict();
    if (!window.console) {
      window.console = {};
      window.console.log = Prototype.emptyFunction;
    }

    // Convert a dn to an email address
    String.prototype.toEmail = function String_toEmail() {
      var m = this.match(/mail=(\w+@mozilla.*),o=/);
      return (m ? m[1] : null);
    };
    
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

    $(document).observe("keypress", function(e) {
      if ((e.charCode || e.keyCode) == 47) { // KEY_SLASH
        $("text").focus();
        e.stop();
      }
    });

    $(document).observe("dom:loaded", function() {
      Prototype.Browser.WebKit && $("text").writeAttribute({
        type: "search", results: 5
      }) && $("search").hide(); // Just this one special treatment, Safari

      var fillAndSearch = function() {
        $("text").value = window.location.hash.replace("#search/", '');
        startSearch();
      };

      if (window.location.hash.startsWith("#search/")) {
        fillAndSearch();
      } else {
        $("text").focus();
      }

      $(document).observe("hash:changed", fillAndSearch);

      $("phonebook-search").observe("submit", function(e) {
        window.location.hash = "#search/" + $F("text");
        startSearch(); e.stop();
      });
      
      var listify = function(a) {
        return "<ul>" + $A(a).map(function(x) {
          return "<li>" + x + "</li>";
        }).join('') + "</ul>";
      };
      var emailLinkify = function(s) {
        return '<a class="value" href="mailto:#{s}">#{s}</a>'.interpolate({s: s});
      };
      var processors = $H({
        "email": emailLinkify,
        "emailalias": emailLinkify.wrap(function(original, email) {
          email = Object.isString(email) ? [email] : $A(email);
          return ", " + email.map(original).join(", ");
        }),
        "employeetype": function(l) { return l.join(", "); },
        "im": listify,
        "mobile": listify.wrap(function(original, list) {
          return original(list).replace(/<li>/, '<li class="tel">');
        }),
        "description": function(s) {
          return '<p class="note">I work on: #{s}</p>'.interpolate({s: s});
        },
        "other": function(s) {
          return "<hr />" + s;
        },
        "manager": function(m) {
          return '<p class="manager">Manager: <a href="#search/#{email}">#{name}</a></p>'.interpolate({
            email: m.dn.toEmail(),
            name: m.cn
          });
        },
        "telephonenumber": function(x) { return "ext. " + x; }
      });

      function startSearch() {
        $("phonebook-search").request({onSuccess: function onSuccess(r) {
          $("results").update('');
          var results = r.responseText.evalJSON().each(function entryEach(e) {
            var code = process(e);
            var vcard = new Element("div", {"class":"vcard"}).update(code);
            $("results").insert(vcard);
          });
          $("text").blur();
          if (results.length == 0) {
            $("results").update(
              '<div style="text-align: center; margin-top: 5em;">' + 
                '<img src="./img/ohnoes.jpg" />' + 
                '<h2>OH NOES! No ones were foundz.</h2>' +
              '</div>'
            );
          }
        }});
      };

      function process(entry) {
        entry.email = [entry.dn.toEmail()];
        entry.picture = '<img class="photo" src="#{url}" alt="Photo of #{name}" />'.interpolate({
          url: entry.picture,
          name: entry.cn
        });
        console.log("JSON preprocessing done");

        processors.each(function processorEach(pair) {
          if (entry[pair.key]) 
            entry[pair.key] = pair.value(entry[pair.key]);
        });
        console.log("JSON postprocessing done");
        return template(entry);
      }

      function template(person) {
        return [
        '#{picture}<p class="fn">#{cn}</p>',
        '<p class="title">#{title}</p>',
        '<p class="employee-type">#{employeetype}</p>',
        '<div class="adr">#{telephonenumber} @ ',
          '<span class="locality">#{physicaldeliveryofficename}</span>',
        '</div>',
        '#{manager}',
        '<div class="telecommunications">',
          '#{mobile}',
          '<p class="email">',
            '#{email}#{emailalias}',
          '</p>',
          '#{im}',
        '</div>',
        '#{description}',
        '#{other}'
        ].join('').interpolate(person);
      }
    });
    </script>
  </head>

  <body>
    <div id="header">
      <form action="search.php" method="get" id="phonebook-search">
        <label for="text" id="phonebook-label">Phonebook</label>
        <input type="text" name="query" id="text" />
        <input type="submit" value="Search" id="search" />
        <ul id="links">
          <li><a href="./#search/*">Everyone</a></li>
          <li><a href="https://intranet.mozilla.org/">Intranet</a></li>
          <li><a href="https://intranet.mozilla.org/OfficeLocations">Office Locations</a></li>
          <li><a href="edit.php" id="edit-entry">Edit Entry</a></li>
        </ul>
      </form>
    </div>
    <div id="results">

    </div>
  </body>
</html>
