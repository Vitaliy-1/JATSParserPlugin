/* Adding favicon */
$('link[rel="icon"]').prependTo('head');

/* Adding header */
$('<header></header>').prependTo('body');

$('div.pkp_site_name_wrapper').prependTo('header');

$('nav.navbar.navbar-default').appendTo('header');

/* Adding abstract */

$('div.article-abstract-text').prependTo('div.panel-body:first');
$ ('h2#abstract-title').appendTo('div.section.abstract');

/* Adding authors and affiliation */

$('ul.author-list').appendTo('div.authors-affil');
//$('div.article-meta span.affiliation').hide();

jQuery(function($){
  var $affiliations = $('div.article-meta span.affiliation');

  $(document.body).on('click', function(e){
    var $clickedElement = $(e.target);

    if ($clickedElement.is('div.article-meta span.name')) {
      $affiliations.not($clickedElement.next('div.article-meta span.affiliation')).hide();
      $clickedElement.next('div.article-meta span.affiliation').toggle();
      var target = $(this).next('div.article-meta span.affiliation');
      var pos = $clickedElement.position();
      var width = $clickedElement.outerWidth();
      $('div.article-meta span.name').next(target).css({
        position: "fixed",
        //top: pos.top + 23 + "px",
        //left: pos.left + "px",
        "text-align": "center",
        bottom: 0,
        left: 0,
        width: '100%'
      });
    } else {
      $affiliations.hide();
    }
  });
});




/* Adding article Title */

$('h1#artitle-title').prependTo('div.title-authors-etc');
$('h2#subtitle').insertAfter('h1#artitle-title')

/* Adding menu tabs. Order is important*/

$('<div class="navwraper col-lg-7 col-md-7 col-sm-12 col-xs-12"></div>').prependTo('div.grid-cell');
$('<nav class="navigational-tabs"></nav>').appendTo('div.navwraper.col-lg-7.col-md-7.col-sm-12.col-xs-12');
$('<ul class="nav nav-tabs" role="tablist" id="myTabs"></ul>').appendTo("nav.navigational-tabs");

$('<li role="presentation" class="active"></li>').appendTo('ul.nav.nav-tabs');
$('<li role="presentation" class="figures-data"></li>').appendTo('ul.nav.nav-tabs');
$('<li role="presentation" class="info"></li>').appendTo('ul.nav.nav-tabs');

$('a[href="#article"]').appendTo('ul#myTabs li.active:first');
$('a[href="#figuresdata"]').appendTo('ul#myTabs li.figures-data');
$('a[href="#infodata"]').appendTo('ul#myTabs li.info');

/* Adding open access and peer-reviewed labels */

$('p.license-access').appendTo('div.access-review');
$('p.reviewed').appendTo('div.access-review');

/* Adding dates submitted and published */

$('li.submitted').appendTo('ul.dates');
$('li.published').appendTo('ul.dates');

/* Bootstrap intra-article navigation */

$('<div class="fornav col-lg-5 col-md-5" role="complementary" id="navwrap"></div>').prependTo('div.row.tab-content')
$('<nav class="bs-docs-sidebar" id="myAffix"></nav>').appendTo('div.fornav.col-lg-5.col-md-5');
$("nav.bs-docs-sidebar").prepend("<ul class='nav nav-tabs bs-docs-sidenav' id='navblock' role='tablist'></ul>");

$("div.article-content h2").each(function(i) {
    var current = $(this);
    current.attr("id", "title" + i);
    $(".nav.bs-docs-sidenav").append("<li><a id='link" + i + "' href='#title" + i + "'class='" + current.prop("tagName") + "'>" +
        current.html() + "</a></li>");
});


$('#myAffix').affix({
  offset: {
    top: function () {
      return (this.top = $('#title0').outerWidth(true))
    },
    bottom: function () {
      return (this.bottom = $('h2.title:last').outerHeight(true))
    }
  }
});

/* Bootstrap tabs */

$('#myTabs a:first').click(function (e) {
  e.preventDefault()
  $(this).tab('show')
});

$('#myTabs li:eq(2) a').click(function (e) {
  e.preventDefault()
  $(this).tab('show')
});

$('#myTabs li:eq(1) a').click(function (e) {
  e.preventDefault()
  $(this).tab('show')
});

/* Clone figures for tabs */

$("div.row.tab-content").append("<div class='col-lg-7 col-md-7 col-sm-12 col-xs-12 tab-pane fade' id='figuresdata'></div>")


$("div.figure-wrap").each(function(){
  $(this).clone().appendTo("#figuresdata");
});



/* Clone author info fortabs*/

$("div.row.tab-content").append("<div class='col-lg-7 col-md-7 col-sm-12 col-xs-12 tab-pane fade' id='infodata'></div>")

$(".author-list").each(function(){
  $(this).clone().appendTo("#infodata");
});


for (var i = 0; i < 100; i++) {
$("div.author-institution div[data-id=" + i + "]").clone().insertAfter("div#infodata [data-aff*=" + i + "]");
}
$("div#infodata a").each(function(i) {
  $(this).replaceWith("<h3 class='info-auth-heading'>" + $(this).text() + "</h3>");
});


/* Hide inra-navigation on figures click */

$("a[href='#figuresdata']").click(function() {
  $("#myAffix").css("visibility","hidden");
});

$("a[href='#article']").click(function() {
  $("#myAffix").css("visibility","visible");
});

$("a[href='#infodata']").click(function() {
  $("#myAffix").css("visibility","hidden");
});
/* Reference pop-ups */


$(function () {
  $('[class="ref-tip btn btn-info"]').popover()
});


$('[class="ref-tip btn btn-info"]').attr("data-placement", "top");
$('[class="ref-tip btn btn-info"]').attr("tabindex", "0");
$('[class="ref-tip btn btn-info"]').attr("role", "button");
$('[class="ref-tip btn btn-info"]').attr("data-trigger", "hover");

var refAuth = jQuery.makeArray(document.getElementsByClassName("ref-auth"));
var refTitle = jQuery.makeArray(document.getElementsByClassName("ref-title"));
var refSource = jQuery.makeArray(document.getElementsByClassName("ref-source"));
var refInfo = jQuery.makeArray(document.getElementsByClassName("ref-info"));
//var refFullText = jQuery.makeArray(document.getElementsByClassName("ref-full"));

for (var i = 0; i < refAuth.length; i++) {
	if (refTitle[i] != null) {
  		refTitle[i] = refTitle[i].innerHTML;
    }
}

for (var i = 0; i < refAuth.length; i++) {
	if (refAuth[i] != null && refSource[i] != null) {
	    output = [refAuth[i].innerHTML + ' ' + refSource[i].innerHTML];
	    for (var s = 0; s < output.length; s++) {
	    	$("[rid='bib" + (i + 1) + "']").attr("data-content", output);
	    }
    }
};

for (var i = 0; i < refTitle.length; i++) {
      var p = i + 1;
    $("[rid='bib" + p + "']").attr("title", refTitle[i]);
};

/* Tables */

$('table').attr("class", "table table-striped table-bordered");


/* Collapseble menu. Disable on desktop */

function refresh() {
   ww = $(window).width();
   var w =  ww<limit ? (location.reload(true)) :  ( ww>limit ? (location.reload(true)) : ww=limit );
}

(function (p) {
  if ($(window).width() <= 974) {
    $("div.article-text").attr({
      id: "accordion",
      role: "tablist",
      "aria-multiselectable": "true",
      class: "article-text panel-group"
    });

    $("div.front").attr("class", "front panel panel-default");

    $("div.panwrap").each(function(i) {
    $(this).attr("class", "panwrap panel panel-default")
    });

    $("h2.title").each(function() {
    $(this).attr("class", "title panel-title collapsed")
    });

    $("div.section").each(function(i) {
    $(this).attr("href", "#collapse" + i)
    });

    $("div.forpan").each(function(i) {
    $(this).attr("id", "collapse" + i)
      if (i==0) {
        $(this).attr("class", "forpan panel-collapse collapse in")
        } else {$(this).attr("class", "forpan panel-collapse collapse")}
      });


    $("div.section").each(function(i) {
      if (i==0) {
        $(this).attr("aria-expanded", "true")
        } else {$(this).attr("aria-expanded", "false")}
      });

    $("div.section").each(function(i) {
      $(this).attr({
        id: "s" + i,
        role: "button",
        "data-toggle": "collapse",
        "data-parent": "#accordion",
    });
      if (i==0) {
        $(this).attr("class", "section abstract panel-heading")
        } else {$(this).attr("class", "section panel-heading")}
    });

  } else {return false}
})(jQuery); //end of if

var ww = $(window).width();
var limit = 974;

var tOut;
$(window).resize(function() {
    var resW = $(window).width();
    clearTimeout(tOut);
    if ( (ww>limit && resW<limit) || (ww<limit && resW>limit) ) {
        tOut = setTimeout(refresh, 0);
    }
});


/* Scroll to head of section */

(function($) {
    $.fn.goTo = function() {
        $('html, body').animate({
            scrollTop: $(this).offset().top + 'px'
        }, 'fast');

    }
})(jQuery);


var collapseNumber = jQuery.makeArray(document.getElementsByClassName("panel panel-default"));
for (var i = 0; i < collapseNumber.length; i++) {
  $('#collapse' + i).on('shown.bs.collapse', function () {
     $('#s' + $('.panel-collapse').index(this)).goTo();
   });
};



/* Copyright notice append to infodata */

$('div.item-copyright').appendTo('#infodata');
