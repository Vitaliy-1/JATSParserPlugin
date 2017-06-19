/* tranfering abstract to article block */


/* remove unnecessary br inside abstract */
$('div.panwrap.abstract div.panel-body > br').remove();

/* working with tabs */

$( function() {
    $( "#article_page_tabs" ).tabs();
});

/* transfering article metrics to tabs */
$('div.item.downloads_chart').appendTo('div#article_metrics');

/* Bootstrap intra-article navigation */

$(function () {
    $("div.main_entry h2").each(function(i) {
        var current = $(this);
        current.attr("id", "title" + i);
        $(".nav.bs-docs-sidenav").append("<li><a id='link" + i + "' href='#title" + i + "'class='" + current.prop("tagName") + "'>" +
            current.html() + "</a></li>");
    });

    $('#myAffix').affix({
      offset: {
        top: $('div.item.issue div.panel-body').offset().top + 200,
        bottom: $(window).height() - $('div#pkp_content_main').offset().top
      }
    });
});

$(function () {
    $("a[href='#article_metrics']").click(function(){
        $("nav#myAffix").hide();
    });
    $("a[href='#details_tab']").click(function() {
        $("nav#myAffix").hide();
    });
    $("a[href='#article_tab']").click(function() {
        $("nav#myAffix").show();
    })
});
/*
$(window).scroll(function() {
    if(($(this).scrollTop() > $('#title0').offset().top) && ($(this).scrollTop() < ($(document).height() - $('#title5').offset().top))) {
        $('nav.bs-docs-sidebar.affix').addClass('opaque');
    } else {
        $('nav.bs-docs-sidebar.affix').removeClass('opaque');
    }
});
*/


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


$('[class="ref-tip btn btn-info"]').attr({
    "data-placement": "top",
    "tabindex": "0",
    "role": "button",
    "data-trigger": "hover"
});

var refAuth = jQuery.makeArray(document.getElementsByClassName("ref-auth"));
var refTitle = jQuery.makeArray(document.getElementsByClassName("ref-title"));
var refSource = jQuery.makeArray(document.getElementsByClassName("ref-source"));
//var refInfo = jQuery.makeArray(document.getElementsByClassName("ref-info"));

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

(function () {
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

/* reload page on window resize */
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

$(function () {
    var collapseNumber = jQuery.makeArray(document.getElementsByClassName("panel panel-default"));
    for (var i = 0; i < collapseNumber.length; i++) {
      $('#collapse' + i).on('shown.bs.collapse', function () {
         $('#s' + $('.panel-collapse').index(this)).goTo();
       });
    }
});

/* show reference on in-text citation click */
$(function () {
    if ($(window).width() > 975) {
        var referencesNumberElement = $('a.ref-tip.btn.btn-info');
        var referencesNumbers = jQuery.makeArray(referencesNumberElement);
        for (var i = 0; i < referencesNumbers.length; i++) {
            var referencesNumber = referencesNumbers[i];
            $(referencesNumber).click(function () {
                window.location.hash = "#" + $(this).attr("rid");
            });
        }
    }
});

/* show citation first occurrence */
$(function () {
    var citationLinkElement = $('button.tocite');
    var citationLinkElementArray = jQuery.makeArray(citationLinkElement);
    for (var i = 0; i < citationLinkElementArray.length; i++) {
        var citationLink = citationLinkElementArray[i];
        $(citationLink).click(function () {
            var citationLocation = $(this).attr("id").toString().replace("to-", "");
            $("a[rid=" + citationLocation + "]").get(0).scrollIntoView();
        });
    }
});

/* articles by the same author */
$('#articlesBySameAuthorList').appendTo('#details_tab');