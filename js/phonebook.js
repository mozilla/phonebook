///
// page base
///

var Page = function() {
  $('#menu .' + this.id).addClass('selected');
};

Page.prototype.init = function() {
  // clear query button
  $('#text')
    .keyup(function(event) {
      var $this = $(this);
      if ($this.val().trim() === '') {
        $('#clear-button').hide();
      } else {
        $('#clear-button').show();
      }
      if (event.which === 27) {  // esc
        $('#clear-button').click();
      }
    });
  $('#clear-button')
    .hide()
    .mousedown(function() {
      $(this).addClass('active');
    })
    .mouseup(function() {
      $(this).removeClass('active');
    })
    .click(function() {
      $('#text').val('').keyup();
      $('#search').click();
    });
};

Page.prototype.searchQuery = function(loc) {
  // load search query from hash (legacy) or query string
  loc = loc || window.location;
  if (loc.hash.startsWith('#search/')) {
    return loc.hash.substring(8).trim();
  } else if (loc.search.startsWith('?search/')) {
    return loc.search.substring(8).trim();
  } else {
    return '';
  }
};

Page.prototype.showThrobber = function() {
  $('#throbber').css('visibility', 'visible');
};

Page.prototype.hideThrobber = function() {
  $('#throbber').css('visibility', 'hidden');
};

Page.prototype.linkifyCard = function($parent) {
  // change card links to js handlers
  var page = this;
  $parent.find('.manager a:not(.org-chart)').click(function(event) {
    event.preventDefault();
    $('#text').val(page.searchQuery(this)).keyup();
    $('#search').click();
  });
};

Page.prototype.errorResult = function($parent, jx, textStatus, errorThrown) {
  // display "error result" message in parent
  $parent.html(
    $('.error-result-template')
      .clone()
      .attr('class', 'error-result')
  );

  $parent.find('.reload-page').on('click', function () { window.location.reload(); });
  $('html').animate({ scrollTop: $parent.find('.error-result').offset().top - 2 });
};

Page.prototype.noResults = function($parent) {
  // display "no results" message in parent
  $parent.html(
    $('.no-results-template')
      .clone()
      .attr('class', 'no-results')
  );
};

Page.prototype.tooManyResults = function($parent, found, showing) {
  // display "too many results" message in parent
  $parent
    .prepend('<div id="search-limited">Showing only the first ' + showing + ' of ' + found + ' results.</div>');
};

//
// cards
//

function CardPage() {
  this.id = this.id || 'card';
  Page.call(this);
  if (!this.searchQuery()) {
    $('#phonebook-search').addClass('large');
  }
}
CardPage.prototype = Object.create(Page.prototype);
CardPage.prototype.constructor = CardPage;

CardPage.prototype.init = function() {
  Page.prototype.init.call(this);
  var query = this.searchQuery();
  if (query) {
    $('#text').val(query).keyup();
    $('#search').click();
  } else {
    $('#text').focus().select();
  }
};

CardPage.prototype.search = function(query) {
  var page = this;
  return new Promise(function(resolve, reject) {
    var url = './search.php?format=html&query=' + encodeURIComponent(query);
    $('#results').load(url, function(responseText, textStatus, jqXHR) {
      switch(textStatus) {
        case 'success':
        case 'notmodified':
          if (!$('#results').text()) {
            page.noResults($('#results'));
          } else {
            page.linkifyCard($('#results'));
          }
          break;
        default:
          page.errorResult($('#results'));
      }
      resolve();
    });
  });
};

CardPage.prototype.clear = function() {
  $('#results').empty();
  $('#search-limited').remove();
};

//
// faces
//

function WallPage() {
  this.id = this.id || 'wall';
  CardPage.call(this);
}
WallPage.prototype = Object.create(CardPage.prototype);
WallPage.prototype.constructor = WallPage;

WallPage.prototype.init = function() {
  CardPage.prototype.init.call(this);
  $('#overlay')
    .click(function() {
      $('body').removeClass('lightbox');
    });
};

WallPage.prototype.search = function(query) {
  var page = this;
  return new Promise(function(resolve, reject) {
    var url = 'search.php?format=json&query=' + encodeURIComponent(query);
    $.getJSON(url, function(searchResult) {
      var $results = $('#results');
      $results.empty();

      // no results
      if (searchResult.count === 0) {
        page.noResults($results);
        resolve();
        return;
      }

      // too many results
      if (searchResult.count > searchResult.users.length) {
        page.tooManyResults($results, searchResult.count, searchResult.users.length);
      }

      // show matches
      $.each(searchResult.users, function() {
        $results.append(
          $('<div class="photo-frame"></div>')
            .data('mail', this.mail)
            .append(
              $('<span></span>').text(this.cn)
            )
            .append(
              $('<img class="wall-photo">')
                .attr('src', 'pic.php?type=thumb&mail=' + encodeURIComponent(this.mail))
            )
            .click(page, page.showCard)
        );
      });

      resolve();
    }).fail(function(jx, textStatus, errorThrown) {
     page.errorResult($('#results'), jx, textStatus, errorThrown);
     resolve();
    });
  });
};

WallPage.prototype.showCard = function(event) {
  var page = event.data;
  var mail = $(this).data('mail');
  page.showThrobber();
  $.ajax({
    url: 'search.php?format=html&query=' + encodeURIComponent(mail),
    success: function(html) {
      $('body').addClass('lightbox');
      $('#overlay').html(html);
      $('#overlay .header')
        .append(
          $('<div class="close-button" title="Close">')
          .click(function() {
            $('#overlay').click();
          })
        );
    },
    error: function() {
      page.errorResult($('#overlay'));
      $('body').addClass('lightbox');
      page.hideThrobber();
    },
    complete: function() {
      page.hideThrobber();
    }
  });
};

//
// tree
//

function TreePage() {
  this.id = this.id || 'tree';
  Page.call(this);
}
TreePage.prototype = Object.create(Page.prototype);
TreePage.prototype.constructor = CardPage;

TreePage.prototype.init = function() {
  Page.prototype.init.call(this);
  var page = this;

  var query = this.searchQuery();
  if (query) {
    $('#text').val(query).keyup();
    $('#search').click();
  } else {
    $('#text').focus().select();
  }

  // clicking on a name -> show card
  $('.hr-link').click(function(event) {
    event.preventDefault();
    event.stopPropagation();
    var mail = $(this).attr('href').substring(8);
    page.showCard(mail);
  });

  // collapse / expand
  $('#orgchart li, #orphans li').click(function(event) {
    event.preventDefault();
    var $this = $(this);
    if ($this.hasClass('expanded')) {
      page.collapseNode($this);
    } else {
      page.expandNode($this);
    }
  });

  // stick visible card when scrolling
  $(window).scroll(function() {
    var $card = $('#person div.vcard');
    if ($card.length !== 1) {
      return;
    }
    if ($(window).scrollTop() > $('#orgchart').offset().top) {
      $card.addClass('snap-to-top');
    } else {
      $card.removeClass('snap-to-top');
    }
  });
};

TreePage.prototype.childNodes = function($parent) {
  // children are in an adjacent <ul>
  return $('#' + $parent.attr('id') + ' + ul');
};

TreePage.prototype.collapseNode = function($parent) {
  if ($parent.hasClass('collapsed')) {
    return;
  }
  var $children = this.childNodes($parent);
  if (!$children.length) {
    return;
  }
  $parent.removeClass('expanded').addClass('collapsed');
  $children.hide();
};

TreePage.prototype.expandNode = function($parent) {
  if ($parent.hasClass('expanded')) {
    return;
  }
  var $children = this.childNodes($parent);
  if (!$children.length) {
    return;
  }
  $parent.removeClass('collapsed').addClass('expanded');
  $children.show();
};

TreePage.prototype.expandAllNodes = function() {
  var page = this;
  $('#orgchart li.collapsed, #orphans li.collapsed').each(function() {
    page.expandNode($(this));
  });
};

TreePage.prototype.mailToID = function(mail) {
  return '#' + mail.replace('@', '-at-').replace('.', '_');
};

TreePage.prototype.search = function(query) {
  var page = this;
  return new Promise(function(resolve, reject) {
    var url = 'search.php?format=json&query=' + encodeURIComponent(query);
    $('#orgchart').removeClass('filter-view');
    $('#person').empty();
    $.getJSON(url, function(searchResult) {
      // no results
      if (searchResult.count === 0) {
        page.clear();
        page.noResults($('#person'));
        resolve();
        return;
      }

      // too many results
      if (searchResult.count > searchResult.users.length) {
        page.tooManyResults($('#page'), searchResult.count, searchResult.users.length);
      }

      // highlight matches
      $('#orgchart').addClass('filter-view');
      $.each(searchResult.users, function() {
        var id = page.mailToID(this.mail);
        $(id).addClass('highlighted');
      });

      // collapse all non-highlighted nodes
      page.collapseAllNodes();

      // expand matches
      var $person = $('#orgchart li.highlighted');
      $person.each(function() {
        page.expandNode($(this));
      });

      // and bring into view
      $('html').animate({ scrollTop: $person.first().offset().top - 2 });

      // display the precise email match, if any
      $.each(searchResult.users, function() {
          if (this.mail == query) {
              page.showCard(this.mail);
          }
      });

      resolve();
    }).fail(function(jx, textStatus, errorThrown) {
     page.errorResult($('#person'), jx, textStatus, errorThrown);
     // bring error pane into view
     $('html').animate({ scrollTop: 0 });
     resolve();
    });
  });
};

TreePage.prototype.collapseAllNodes = function() {
  var page = this;
  page.expandAllNodes();
  $('#orgchart li:not(.leaf)').each(function() {
    var $parent = $(this);
    var $children = page.childNodes($parent);
    if ($children.find('.highlighted').length === 0) {
      page.collapseNode($parent);
    }
  });
};

TreePage.prototype.showCard = function(mail) {
  var page = this;
  page.deselectAllNodes();
  window.history.pushState({}, '',
    window.location.pathname + '?search/' + mail);

  var $person = $(page.mailToID(mail));
  $person.addClass('selected');
  $('html').animate({ scrollTop: $person.offset().top - 2 });
  $('#text').val(mail).keyup();

  page.showThrobber();
  $.ajax({
    url: 'search.php?format=html&exact_search=true&query=' + encodeURIComponent(mail),
    success: function(html) {
      $('#person').html(html);
      page.linkifyCard($('#person'));
      $(window).scroll();
    },
    complete: function() {
      page.hideThrobber();
    },
    error: function() {
      page.errorResult($('#person'));
      page.hideThrobber();
    }
  });
};

TreePage.prototype.deselectAllNodes = function() {
  $('#person').empty();
  $('#search-limited').remove();
  function reset(id) {
    $(id + ' li.selected').removeClass('selected');
  }
  reset('#orgchart');
  reset('#orphans');
}

TreePage.prototype.dehighlightAllNodes = function() {
  $('#orgchart').removeClass('filter-view');
  function reset(id) {
    $(id + ' li.highlighted').removeClass('highlighted');
  }
  reset('#orgchart');
  reset('#orphans');
}

TreePage.prototype.clear = function() {
  this.deselectAllNodes();
  this.dehighlightAllNodes();
  this.expandAllNodes();
  this.collapseNode($('#managerless'));
};

TreePage.prototype.linkifyCard = function($parent) {
  var page = this;
  // change card links to js handlers
  $parent.find('.manager a').click(function(event) {
    event.preventDefault();
    page.showCard(page.searchQuery(this));
  });
};

//
// edit
//

function EditPage() {
  this.id = this.id || 'edit';
  Page.call(this);
}
EditPage.prototype = Object.create(Page.prototype);
EditPage.prototype.constructor = Page;

EditPage.prototype.init = function() {
  Page.prototype.init.call(this);

  // init simple multi-value fields
  function initValueList(container, add, name, title) {
    $(container + ' .remove-link')
      .attr('title', 'Remove ' + title);
    $(add)
      .data({ name: name + '[]', title: title })
      .addClass('add-link');
  }
  initValueList('#email-aliases', '#email-alias-add', 'emailAlias', 'e-mail');
  initValueList('#phone-numbers', '#phone-number-add', 'mobile', 'number');
  initValueList('#im-accounts', '#im-add', 'im', 'account');
  $('.add-link').click(this, this.addValue);

  // init office
  $('#office-city-select')
    .change(function() {
      var selected = $(this).val();
      if (selected === 'Other') {
        $('#office-city-text').show();
      } else {
        $('#office-city-text').hide();
      }
    })
    .change();
  $('#office-cities .remove-link')
    .attr('title', 'Remove office');
  $('#office-add').click(this, this.addOffice);

  // removeValue works for both simple and office fields
  $('.remove-link').click(this.removeValue);

  // bugmail should be an undecorated email address
  $('#bmo')
    .keyup(function() {
      var email = $(this).val().trim();
      if (email === '') {
        $('#bmo-error').hide();
        return;
      }
      // as per https://html.spec.whatwg.org/multipage/forms.html#e-mail-state-(type=email)
      if (/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test(email)) {
        $('#bmo-error').hide();
      } else {
        $('#bmo-error').show();
      }
    })
    .change(function() {
      var $this = $(this);
      $this
        .val($this.val().trim())
        .keyup();
    })
    .change();
  $('#bmo-error').hover(
    function() {
      $('#bmo-blurb').css('color', 'red');
    },
    function() {
      $('#bmo-blurb').css('color', '');
    }
  );
};

EditPage.prototype.clear = function() {};

EditPage.prototype.search = function(query) {
  window.location = window.location.pathname.replace('edit.php', '?search/' + query);
  return new Promise(function() {});
};

EditPage.prototype.addValue = function(event) {
  event.preventDefault();
  var $this = $(this);
  var page = event.data;
  $('<div/>')
    .append(
      $('<input type="text">')
        .attr('name', $this.data('name'))
    )
    .append(
      $('<a href="#" class="remove-link">')
        .attr('title', 'Remove ' + $this.data('title'))
        .click(page.removeValue)
    )
    .insertBefore($this)
    .find('input')
    .focus();
};

EditPage.prototype.removeValue = function(event) {
  event.preventDefault();
  $(this)
    .parent('div')
    .remove();
};

EditPage.prototype.addOffice = function(event) {
  event.preventDefault();
  var $this = $(this);
  var page = event.data;

  var $offices = $('#office-city-select')
    .clone()
    .removeAttr('id');
  $offices
    .val($offices.find('option')[0].value)
    .find('option[value="Other"]')
      .remove();

  $('<div/>')
    .append($offices)
    .append(
      $('<a href="#" class="remove-link">')
        .attr('title', 'Remove office')
        .click(page.removeValue)
    )
    .insertBefore(this);
};

//
// initialisation
//

var pb_page;
var pageID = $('body').data('page');
if (pageID === 'wall') {
  pb_page = new WallPage();
} else if (pageID === 'tree') {
  pb_page = new TreePage();
} else if (pageID === 'edit') {
  pb_page = new EditPage();
} else {
  pb_page = new CardPage();
}

$(function() {
  $('#search').click(function(event) {
    event.preventDefault();

    var $text = $('#text');
    var filter = $text.val().trim();
    var queryString = filter === '' ? '' : '?search/' + filter;

    // update url
    window.history.pushState({}, '',
      window.location.pathname + queryString);

    // update other page links to include search filter
    $('#menu li a:not(.edit)').each(function() {
      this.href = this.pathname + queryString;
    });

    // don't rerun identical queries, except for the empty-string query
    if (filter !== '' && filter === $text.data('last')) {
      return;
    }
    $text.data('last', filter);

    pb_page.clear();

    // run search
    if (filter === '') {
      $('#text').focus().select();
    } else {
      $('#phonebook-search').removeClass('large');
      pb_page.showThrobber();
      pb_page.search(filter).then(pb_page.hideThrobber);
    }
  });

  pb_page.init();
});
