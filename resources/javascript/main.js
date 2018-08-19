/**
 * @file plugins/generic/jatsParser/resources/javascript/main.js
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * Plugin's scripts are here
 */

// Navigation inside an article
(function () {

	var hasSubtitles = 0;
	var intranav;
	$(".article-section-title").each(function (i) {
		if ($(this).prop("tagName") === "H2") {
			$(this).attr("id", "title-" + i);
			$("#article-navigation-menu-items").append("<a class='nav-link' href='#title-"+i+"'>" + $(this).text() + "</a>");
			hasSubtitles = 0;
		} else if ($(this).prop("tagName") === "H3") {
			hasSubtitles++;
			
			$(this).attr("id", "title-" + i);
			if (hasSubtitles === 1) {
				intranav = $("<nav>", {class: "nav nav-pills flex-column"});
				$("#article-navigation-menu-items").append(intranav);
			}
			var intralink = $("<a>", {
				class: "nav-link ml-3 my-1 nav-subtitle",
				href: '#title-'+i,
				text: $(this).text()
			});
			
			$(intranav).append(intralink);
		}
	});
})();

$('body').add({
	'data-spy': 'scroll',
	'data-target': '#navbar-article'
});

$(document).ready(function () {
	$('body').scrollspy({target: '#article-navbar'});
});

// handling tables
(function () {
	$('table').addClass("table").wrap("<div class='table-wrapper'></div>")
})();

// handling figures

(function () {
	var figure = document.getElementsByClassName('figure');
	
	for (var i = 0; i < figure.length; i++) {
		var imageEnlargeLink = document.createElement('a');
		imageEnlargeLink.classList.add('image-enlarge-link');
		figure.item(i).appendChild(imageEnlargeLink);
		
		var imageEnlarge = document.createElement('i');
		imageEnlarge.classList.add('fas', 'fa-expand-arrows-alt', 'fa-lg', 'figure-image-expand');
		imageEnlargeLink.appendChild(imageEnlarge);
	}
	
})();

(function () {
	var figure = document.getElementsByTagName('figure');
	
	for (i = 0; i < figure.length; i++) {
		if ((figure.item(i).offsetHeight - figure.item(i).style.marginBottom) < figure.item(i).scrollHeight) {
			var caption = figure.item(i).getElementsByClassName('caption')[0];
			var ellipsis = document.createElement('span');
			ellipsis.classList.add('ellipsis');
			ellipsis.innerText = '...';
			caption.appendChild(ellipsis);
		}
		
		var figImage = figure.item(i).getElementsByTagName('img')[0];
		var imageEnlarge =  figure.item(i).getElementsByClassName('image-enlarge-link')[0];
		var boxImage = figure.item(i).getElementsByClassName('figure')[0];
		
		// Download figure image link
		var downloadImageLink = document.createElement('a');
		downloadImageLink.classList.add('download-image');
		var downloadIcon = document.createElement('i');
		downloadIcon.classList.add('fas', 'fa-download', 'fa-lg');
		boxImage.appendChild(downloadImageLink);
		downloadImageLink.appendChild(downloadIcon);
		downloadImageLink.href = figImage.src;
		
		var imageEnlargeSvg = figure.item(i).getElementsByTagName('svg')[0];
		// Activate modal on click
		figImage.addEventListener('click', openModal);
		imageEnlarge.addEventListener('click', openModal);
		
		function openModal() {
			var modalWindow = document.createElement('div');
			setTimeout(modalWindow.classList.add('modal-window'), 5000);
			var modalContent = document.createElement('div');
			modalContent.classList.add('modal-content');
			
			var image = null;
			if (this.tagName === 'img') {
				image = this.cloneNode(true);
			} else {
				image = this.parentElement.getElementsByTagName('img')[0].cloneNode(true);
			}
			
			figureCaption = this.parentElement.parentElement.getElementsByClassName('caption')[0].cloneNode(true);
			
			document.body.appendChild(modalWindow);
			modalWindow.appendChild(modalContent);
			modalContent.appendChild(image);
			modalContent.appendChild(figureCaption);
			
			// hide body overflow
			document.body.style.overflow = 'hidden';
			
			// create close symbol
			var closeButton = document.createElement('i');
			closeButton.classList.add('fas', 'fa-times', 'close-modal', 'fa-2x');
			modalContent.appendChild(closeButton);
		}
		
	}
	
	// Dismiss modal on click
	var number = 0;
	
	window.addEventListener('click', function(e){
		var modalWindow = document.getElementsByClassName('modal-window')[0];
		
		if (typeof modalWindow === 'undefined' || modalWindow == null) return false;
		
		number++;
		
		var modalContent = modalWindow.getElementsByClassName('modal-content')[0];
		var closeModal = modalWindow.getElementsByClassName('close-modal')[0];
		
		if ((modalContent !== null && !modalContent.contains(e.target) && number > 1) || closeModal.contains(e.target)){
			modalWindow.parentNode.removeChild(modalWindow);
			document.body.style.overflow = 'auto';
			number = 0;
		} 
	});
})();


(function ($) {
	
	// Show author affiliation under authors list (for large screen only)
	var authorString = $('.jatsparser-author-string-href');
	$(authorString).click(function(event) {
		event.preventDefault();
		var elementId = $(this).attr('href').replace('#', '');
		$('.article-details-author').each(function () {
			
			// Show only targeted author's affiliation on click
			if ($(this).attr('id') === elementId && $(this).hasClass('hideAuthor')) {
				$(this).removeClass('hideAuthor');
			} else {
				$(this).addClass('hideAuthor');
			}
		});
		
		// Add specifiers to the clicked author's link
		$(authorString).each(function () {
			if ($(this).attr('href') === ('#' + elementId) && !$(this).hasClass('active')){
				$(this).addClass('active');
				$(this).children('.author-plus').addClass('hide');
				$(this).children('.author-minus').removeClass('hide');
			} else if ($(this).attr('href') !== ('#' + elementId) || $(this).hasClass('active')) {
				$(this).removeClass('active');
				$(this).children('.author-plus').removeClass('hide');
				$(this).children('.author-minus').addClass('hide');
			}
		});
	})
})(jQuery);

// Separeting links in references
(function () {
	$('.references li a:not(:last-child)').each(function (i) {
		var delimeter = document.createElement('span');
		$(delimeter).addClass('references-link-delimeter').text('|');
		$(this).after(delimeter);
	})
})();

// Accordion for small screens

(function () {
	
	var sectionTitles = $('h2.article-section-title');
	var mobileArticleTag = 'jatsparser-article-mobile-view';
	
	function accordionSwitch() {
		var articleWrapper = $('.article-fulltext');
		if (window.innerWidth < 992 && !articleWrapper.hasClass(mobileArticleTag)) {
			articleWrapper.addClass(mobileArticleTag);
			
			sectionTitles.each(function () {
				$(this).nextUntil('h2.article-section-title').wrapAll('<div class="jatsparser-section-content"></div>');
			});
			
		} else if (window.innerWidth >= 992 && articleWrapper.hasClass(mobileArticleTag)) {
			articleWrapper.removeClass(mobileArticleTag);
			$('.jatsparser-section-content').children().unwrap();
		}
	}
	
	accordionSwitch();
	
	
	window.addEventListener("resize", function (event) {
		accordionSwitch();
	}, false);
	
	sectionTitles.click(function (event) {
		if (sectionTitles.not(this).hasClass('active')) {
			sectionTitles.not(this).removeClass('active');
			
		}
		
		if ($(this).hasClass('active')) {
			$(this).removeClass('active');
		} else {
			$(this).addClass('active');
			$(window).scrollTop($(this).offset().top);
		}
	})
	
})();