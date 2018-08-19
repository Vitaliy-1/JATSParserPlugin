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
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm1haW4uanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBIiwiZmlsZSI6ImFwcC5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8vIE5hdmlnYXRpb24gaW5zaWRlIGFuIGFydGljbGVcbihmdW5jdGlvbiAoKSB7XG5cblx0dmFyIGhhc1N1YnRpdGxlcyA9IDA7XG5cdHZhciBpbnRyYW5hdjtcblx0JChcIi5hcnRpY2xlLXNlY3Rpb24tdGl0bGVcIikuZWFjaChmdW5jdGlvbiAoaSkge1xuXHRcdGlmICgkKHRoaXMpLnByb3AoXCJ0YWdOYW1lXCIpID09PSBcIkgyXCIpIHtcblx0XHRcdCQodGhpcykuYXR0cihcImlkXCIsIFwidGl0bGUtXCIgKyBpKTtcblx0XHRcdCQoXCIjYXJ0aWNsZS1uYXZpZ2F0aW9uLW1lbnUtaXRlbXNcIikuYXBwZW5kKFwiPGEgY2xhc3M9J25hdi1saW5rJyBocmVmPScjdGl0bGUtXCIraStcIic+XCIgKyAkKHRoaXMpLnRleHQoKSArIFwiPC9hPlwiKTtcblx0XHRcdGhhc1N1YnRpdGxlcyA9IDA7XG5cdFx0fSBlbHNlIGlmICgkKHRoaXMpLnByb3AoXCJ0YWdOYW1lXCIpID09PSBcIkgzXCIpIHtcblx0XHRcdGhhc1N1YnRpdGxlcysrO1xuXHRcdFx0XG5cdFx0XHQkKHRoaXMpLmF0dHIoXCJpZFwiLCBcInRpdGxlLVwiICsgaSk7XG5cdFx0XHRpZiAoaGFzU3VidGl0bGVzID09PSAxKSB7XG5cdFx0XHRcdGludHJhbmF2ID0gJChcIjxuYXY+XCIsIHtjbGFzczogXCJuYXYgbmF2LXBpbGxzIGZsZXgtY29sdW1uXCJ9KTtcblx0XHRcdFx0JChcIiNhcnRpY2xlLW5hdmlnYXRpb24tbWVudS1pdGVtc1wiKS5hcHBlbmQoaW50cmFuYXYpO1xuXHRcdFx0fVxuXHRcdFx0dmFyIGludHJhbGluayA9ICQoXCI8YT5cIiwge1xuXHRcdFx0XHRjbGFzczogXCJuYXYtbGluayBtbC0zIG15LTEgbmF2LXN1YnRpdGxlXCIsXG5cdFx0XHRcdGhyZWY6ICcjdGl0bGUtJytpLFxuXHRcdFx0XHR0ZXh0OiAkKHRoaXMpLnRleHQoKVxuXHRcdFx0fSk7XG5cdFx0XHRcblx0XHRcdCQoaW50cmFuYXYpLmFwcGVuZChpbnRyYWxpbmspO1xuXHRcdH1cblx0fSk7XG59KSgpO1xuXG4kKCdib2R5JykuYWRkKHtcblx0J2RhdGEtc3B5JzogJ3Njcm9sbCcsXG5cdCdkYXRhLXRhcmdldCc6ICcjbmF2YmFyLWFydGljbGUnXG59KTtcblxuJChkb2N1bWVudCkucmVhZHkoZnVuY3Rpb24gKCkge1xuXHQkKCdib2R5Jykuc2Nyb2xsc3B5KHt0YXJnZXQ6ICcjYXJ0aWNsZS1uYXZiYXInfSk7XG59KTtcblxuLy8gaGFuZGxpbmcgdGFibGVzXG4oZnVuY3Rpb24gKCkge1xuXHQkKCd0YWJsZScpLmFkZENsYXNzKFwidGFibGVcIikud3JhcChcIjxkaXYgY2xhc3M9J3RhYmxlLXdyYXBwZXInPjwvZGl2PlwiKVxufSkoKTtcblxuLy8gaGFuZGxpbmcgZmlndXJlc1xuXG4oZnVuY3Rpb24gKCkge1xuXHR2YXIgZmlndXJlID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnZmlndXJlJyk7XG5cdFxuXHRmb3IgKHZhciBpID0gMDsgaSA8IGZpZ3VyZS5sZW5ndGg7IGkrKykge1xuXHRcdHZhciBpbWFnZUVubGFyZ2VMaW5rID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYScpO1xuXHRcdGltYWdlRW5sYXJnZUxpbmsuY2xhc3NMaXN0LmFkZCgnaW1hZ2UtZW5sYXJnZS1saW5rJyk7XG5cdFx0ZmlndXJlLml0ZW0oaSkuYXBwZW5kQ2hpbGQoaW1hZ2VFbmxhcmdlTGluayk7XG5cdFx0XG5cdFx0dmFyIGltYWdlRW5sYXJnZSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2knKTtcblx0XHRpbWFnZUVubGFyZ2UuY2xhc3NMaXN0LmFkZCgnZmFzJywgJ2ZhLWV4cGFuZC1hcnJvd3MtYWx0JywgJ2ZhLWxnJywgJ2ZpZ3VyZS1pbWFnZS1leHBhbmQnKTtcblx0XHRpbWFnZUVubGFyZ2VMaW5rLmFwcGVuZENoaWxkKGltYWdlRW5sYXJnZSk7XG5cdH1cblx0XG59KSgpO1xuXG4oZnVuY3Rpb24gKCkge1xuXHR2YXIgZmlndXJlID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeVRhZ05hbWUoJ2ZpZ3VyZScpO1xuXHRcblx0Zm9yIChpID0gMDsgaSA8IGZpZ3VyZS5sZW5ndGg7IGkrKykge1xuXHRcdGlmICgoZmlndXJlLml0ZW0oaSkub2Zmc2V0SGVpZ2h0IC0gZmlndXJlLml0ZW0oaSkuc3R5bGUubWFyZ2luQm90dG9tKSA8IGZpZ3VyZS5pdGVtKGkpLnNjcm9sbEhlaWdodCkge1xuXHRcdFx0dmFyIGNhcHRpb24gPSBmaWd1cmUuaXRlbShpKS5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCdjYXB0aW9uJylbMF07XG5cdFx0XHR2YXIgZWxsaXBzaXMgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdzcGFuJyk7XG5cdFx0XHRlbGxpcHNpcy5jbGFzc0xpc3QuYWRkKCdlbGxpcHNpcycpO1xuXHRcdFx0ZWxsaXBzaXMuaW5uZXJUZXh0ID0gJy4uLic7XG5cdFx0XHRjYXB0aW9uLmFwcGVuZENoaWxkKGVsbGlwc2lzKTtcblx0XHR9XG5cdFx0XG5cdFx0dmFyIGZpZ0ltYWdlID0gZmlndXJlLml0ZW0oaSkuZ2V0RWxlbWVudHNCeVRhZ05hbWUoJ2ltZycpWzBdO1xuXHRcdHZhciBpbWFnZUVubGFyZ2UgPSAgZmlndXJlLml0ZW0oaSkuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnaW1hZ2UtZW5sYXJnZS1saW5rJylbMF07XG5cdFx0dmFyIGJveEltYWdlID0gZmlndXJlLml0ZW0oaSkuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnZmlndXJlJylbMF07XG5cdFx0XG5cdFx0Ly8gRG93bmxvYWQgZmlndXJlIGltYWdlIGxpbmtcblx0XHR2YXIgZG93bmxvYWRJbWFnZUxpbmsgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdhJyk7XG5cdFx0ZG93bmxvYWRJbWFnZUxpbmsuY2xhc3NMaXN0LmFkZCgnZG93bmxvYWQtaW1hZ2UnKTtcblx0XHR2YXIgZG93bmxvYWRJY29uID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaScpO1xuXHRcdGRvd25sb2FkSWNvbi5jbGFzc0xpc3QuYWRkKCdmYXMnLCAnZmEtZG93bmxvYWQnLCAnZmEtbGcnKTtcblx0XHRib3hJbWFnZS5hcHBlbmRDaGlsZChkb3dubG9hZEltYWdlTGluayk7XG5cdFx0ZG93bmxvYWRJbWFnZUxpbmsuYXBwZW5kQ2hpbGQoZG93bmxvYWRJY29uKTtcblx0XHRkb3dubG9hZEltYWdlTGluay5ocmVmID0gZmlnSW1hZ2Uuc3JjO1xuXHRcdFxuXHRcdHZhciBpbWFnZUVubGFyZ2VTdmcgPSBmaWd1cmUuaXRlbShpKS5nZXRFbGVtZW50c0J5VGFnTmFtZSgnc3ZnJylbMF07XG5cdFx0Ly8gQWN0aXZhdGUgbW9kYWwgb24gY2xpY2tcblx0XHRmaWdJbWFnZS5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIG9wZW5Nb2RhbCk7XG5cdFx0aW1hZ2VFbmxhcmdlLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgb3Blbk1vZGFsKTtcblx0XHRcblx0XHRmdW5jdGlvbiBvcGVuTW9kYWwoKSB7XG5cdFx0XHR2YXIgbW9kYWxXaW5kb3cgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcblx0XHRcdHNldFRpbWVvdXQobW9kYWxXaW5kb3cuY2xhc3NMaXN0LmFkZCgnbW9kYWwtd2luZG93JyksIDUwMDApO1xuXHRcdFx0dmFyIG1vZGFsQ29udGVudCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuXHRcdFx0bW9kYWxDb250ZW50LmNsYXNzTGlzdC5hZGQoJ21vZGFsLWNvbnRlbnQnKTtcblx0XHRcdFxuXHRcdFx0dmFyIGltYWdlID0gbnVsbDtcblx0XHRcdGlmICh0aGlzLnRhZ05hbWUgPT09ICdpbWcnKSB7XG5cdFx0XHRcdGltYWdlID0gdGhpcy5jbG9uZU5vZGUodHJ1ZSk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRpbWFnZSA9IHRoaXMucGFyZW50RWxlbWVudC5nZXRFbGVtZW50c0J5VGFnTmFtZSgnaW1nJylbMF0uY2xvbmVOb2RlKHRydWUpO1xuXHRcdFx0fVxuXHRcdFx0XG5cdFx0XHRmaWd1cmVDYXB0aW9uID0gdGhpcy5wYXJlbnRFbGVtZW50LnBhcmVudEVsZW1lbnQuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnY2FwdGlvbicpWzBdLmNsb25lTm9kZSh0cnVlKTtcblx0XHRcdFxuXHRcdFx0ZG9jdW1lbnQuYm9keS5hcHBlbmRDaGlsZChtb2RhbFdpbmRvdyk7XG5cdFx0XHRtb2RhbFdpbmRvdy5hcHBlbmRDaGlsZChtb2RhbENvbnRlbnQpO1xuXHRcdFx0bW9kYWxDb250ZW50LmFwcGVuZENoaWxkKGltYWdlKTtcblx0XHRcdG1vZGFsQ29udGVudC5hcHBlbmRDaGlsZChmaWd1cmVDYXB0aW9uKTtcblx0XHRcdFxuXHRcdFx0Ly8gaGlkZSBib2R5IG92ZXJmbG93XG5cdFx0XHRkb2N1bWVudC5ib2R5LnN0eWxlLm92ZXJmbG93ID0gJ2hpZGRlbic7XG5cdFx0XHRcblx0XHRcdC8vIGNyZWF0ZSBjbG9zZSBzeW1ib2xcblx0XHRcdHZhciBjbG9zZUJ1dHRvbiA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2knKTtcblx0XHRcdGNsb3NlQnV0dG9uLmNsYXNzTGlzdC5hZGQoJ2ZhcycsICdmYS10aW1lcycsICdjbG9zZS1tb2RhbCcsICdmYS0yeCcpO1xuXHRcdFx0bW9kYWxDb250ZW50LmFwcGVuZENoaWxkKGNsb3NlQnV0dG9uKTtcblx0XHR9XG5cdFx0XG5cdH1cblx0XG5cdC8vIERpc21pc3MgbW9kYWwgb24gY2xpY2tcblx0dmFyIG51bWJlciA9IDA7XG5cdFxuXHR3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBmdW5jdGlvbihlKXtcblx0XHR2YXIgbW9kYWxXaW5kb3cgPSBkb2N1bWVudC5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCdtb2RhbC13aW5kb3cnKVswXTtcblx0XHRcblx0XHRpZiAodHlwZW9mIG1vZGFsV2luZG93ID09PSAndW5kZWZpbmVkJyB8fCBtb2RhbFdpbmRvdyA9PSBudWxsKSByZXR1cm4gZmFsc2U7XG5cdFx0XG5cdFx0bnVtYmVyKys7XG5cdFx0XG5cdFx0dmFyIG1vZGFsQ29udGVudCA9IG1vZGFsV2luZG93LmdldEVsZW1lbnRzQnlDbGFzc05hbWUoJ21vZGFsLWNvbnRlbnQnKVswXTtcblx0XHR2YXIgY2xvc2VNb2RhbCA9IG1vZGFsV2luZG93LmdldEVsZW1lbnRzQnlDbGFzc05hbWUoJ2Nsb3NlLW1vZGFsJylbMF07XG5cdFx0XG5cdFx0aWYgKChtb2RhbENvbnRlbnQgIT09IG51bGwgJiYgIW1vZGFsQ29udGVudC5jb250YWlucyhlLnRhcmdldCkgJiYgbnVtYmVyID4gMSkgfHwgY2xvc2VNb2RhbC5jb250YWlucyhlLnRhcmdldCkpe1xuXHRcdFx0bW9kYWxXaW5kb3cucGFyZW50Tm9kZS5yZW1vdmVDaGlsZChtb2RhbFdpbmRvdyk7XG5cdFx0XHRkb2N1bWVudC5ib2R5LnN0eWxlLm92ZXJmbG93ID0gJ2F1dG8nO1xuXHRcdFx0bnVtYmVyID0gMDtcblx0XHR9IFxuXHR9KTtcbn0pKCk7XG5cblxuKGZ1bmN0aW9uICgkKSB7XG5cdFxuXHQvLyBTaG93IGF1dGhvciBhZmZpbGlhdGlvbiB1bmRlciBhdXRob3JzIGxpc3QgKGZvciBsYXJnZSBzY3JlZW4gb25seSlcblx0dmFyIGF1dGhvclN0cmluZyA9ICQoJy5qYXRzcGFyc2VyLWF1dGhvci1zdHJpbmctaHJlZicpO1xuXHQkKGF1dGhvclN0cmluZykuY2xpY2soZnVuY3Rpb24oZXZlbnQpIHtcblx0XHRldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdHZhciBlbGVtZW50SWQgPSAkKHRoaXMpLmF0dHIoJ2hyZWYnKS5yZXBsYWNlKCcjJywgJycpO1xuXHRcdCQoJy5hcnRpY2xlLWRldGFpbHMtYXV0aG9yJykuZWFjaChmdW5jdGlvbiAoKSB7XG5cdFx0XHRcblx0XHRcdC8vIFNob3cgb25seSB0YXJnZXRlZCBhdXRob3IncyBhZmZpbGlhdGlvbiBvbiBjbGlja1xuXHRcdFx0aWYgKCQodGhpcykuYXR0cignaWQnKSA9PT0gZWxlbWVudElkICYmICQodGhpcykuaGFzQ2xhc3MoJ2hpZGVBdXRob3InKSkge1xuXHRcdFx0XHQkKHRoaXMpLnJlbW92ZUNsYXNzKCdoaWRlQXV0aG9yJyk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHQkKHRoaXMpLmFkZENsYXNzKCdoaWRlQXV0aG9yJyk7XG5cdFx0XHR9XG5cdFx0fSk7XG5cdFx0XG5cdFx0Ly8gQWRkIHNwZWNpZmllcnMgdG8gdGhlIGNsaWNrZWQgYXV0aG9yJ3MgbGlua1xuXHRcdCQoYXV0aG9yU3RyaW5nKS5lYWNoKGZ1bmN0aW9uICgpIHtcblx0XHRcdGlmICgkKHRoaXMpLmF0dHIoJ2hyZWYnKSA9PT0gKCcjJyArIGVsZW1lbnRJZCkgJiYgISQodGhpcykuaGFzQ2xhc3MoJ2FjdGl2ZScpKXtcblx0XHRcdFx0JCh0aGlzKS5hZGRDbGFzcygnYWN0aXZlJyk7XG5cdFx0XHRcdCQodGhpcykuY2hpbGRyZW4oJy5hdXRob3ItcGx1cycpLmFkZENsYXNzKCdoaWRlJyk7XG5cdFx0XHRcdCQodGhpcykuY2hpbGRyZW4oJy5hdXRob3ItbWludXMnKS5yZW1vdmVDbGFzcygnaGlkZScpO1xuXHRcdFx0fSBlbHNlIGlmICgkKHRoaXMpLmF0dHIoJ2hyZWYnKSAhPT0gKCcjJyArIGVsZW1lbnRJZCkgfHwgJCh0aGlzKS5oYXNDbGFzcygnYWN0aXZlJykpIHtcblx0XHRcdFx0JCh0aGlzKS5yZW1vdmVDbGFzcygnYWN0aXZlJyk7XG5cdFx0XHRcdCQodGhpcykuY2hpbGRyZW4oJy5hdXRob3ItcGx1cycpLnJlbW92ZUNsYXNzKCdoaWRlJyk7XG5cdFx0XHRcdCQodGhpcykuY2hpbGRyZW4oJy5hdXRob3ItbWludXMnKS5hZGRDbGFzcygnaGlkZScpO1xuXHRcdFx0fVxuXHRcdH0pO1xuXHR9KVxufSkoalF1ZXJ5KTtcblxuLy8gU2VwYXJldGluZyBsaW5rcyBpbiByZWZlcmVuY2VzXG4oZnVuY3Rpb24gKCkge1xuXHQkKCcucmVmZXJlbmNlcyBsaSBhOm5vdCg6bGFzdC1jaGlsZCknKS5lYWNoKGZ1bmN0aW9uIChpKSB7XG5cdFx0dmFyIGRlbGltZXRlciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ3NwYW4nKTtcblx0XHQkKGRlbGltZXRlcikuYWRkQ2xhc3MoJ3JlZmVyZW5jZXMtbGluay1kZWxpbWV0ZXInKS50ZXh0KCd8Jyk7XG5cdFx0JCh0aGlzKS5hZnRlcihkZWxpbWV0ZXIpO1xuXHR9KVxufSkoKTtcblxuLy8gQWNjb3JkaW9uIGZvciBzbWFsbCBzY3JlZW5zXG5cbihmdW5jdGlvbiAoKSB7XG5cdFxuXHR2YXIgc2VjdGlvblRpdGxlcyA9ICQoJ2gyLmFydGljbGUtc2VjdGlvbi10aXRsZScpO1xuXHR2YXIgbW9iaWxlQXJ0aWNsZVRhZyA9ICdqYXRzcGFyc2VyLWFydGljbGUtbW9iaWxlLXZpZXcnO1xuXHRcblx0ZnVuY3Rpb24gYWNjb3JkaW9uU3dpdGNoKCkge1xuXHRcdHZhciBhcnRpY2xlV3JhcHBlciA9ICQoJy5hcnRpY2xlLWZ1bGx0ZXh0Jyk7XG5cdFx0aWYgKHdpbmRvdy5pbm5lcldpZHRoIDwgOTkyICYmICFhcnRpY2xlV3JhcHBlci5oYXNDbGFzcyhtb2JpbGVBcnRpY2xlVGFnKSkge1xuXHRcdFx0YXJ0aWNsZVdyYXBwZXIuYWRkQ2xhc3MobW9iaWxlQXJ0aWNsZVRhZyk7XG5cdFx0XHRcblx0XHRcdHNlY3Rpb25UaXRsZXMuZWFjaChmdW5jdGlvbiAoKSB7XG5cdFx0XHRcdCQodGhpcykubmV4dFVudGlsKCdoMi5hcnRpY2xlLXNlY3Rpb24tdGl0bGUnKS53cmFwQWxsKCc8ZGl2IGNsYXNzPVwiamF0c3BhcnNlci1zZWN0aW9uLWNvbnRlbnRcIj48L2Rpdj4nKTtcblx0XHRcdH0pO1xuXHRcdFx0XG5cdFx0fSBlbHNlIGlmICh3aW5kb3cuaW5uZXJXaWR0aCA+PSA5OTIgJiYgYXJ0aWNsZVdyYXBwZXIuaGFzQ2xhc3MobW9iaWxlQXJ0aWNsZVRhZykpIHtcblx0XHRcdGFydGljbGVXcmFwcGVyLnJlbW92ZUNsYXNzKG1vYmlsZUFydGljbGVUYWcpO1xuXHRcdFx0JCgnLmphdHNwYXJzZXItc2VjdGlvbi1jb250ZW50JykuY2hpbGRyZW4oKS51bndyYXAoKTtcblx0XHR9XG5cdH1cblx0XG5cdGFjY29yZGlvblN3aXRjaCgpO1xuXHRcblx0XG5cdHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKFwicmVzaXplXCIsIGZ1bmN0aW9uIChldmVudCkge1xuXHRcdGFjY29yZGlvblN3aXRjaCgpO1xuXHR9LCBmYWxzZSk7XG5cdFxuXHRzZWN0aW9uVGl0bGVzLmNsaWNrKGZ1bmN0aW9uIChldmVudCkge1xuXHRcdGlmIChzZWN0aW9uVGl0bGVzLm5vdCh0aGlzKS5oYXNDbGFzcygnYWN0aXZlJykpIHtcblx0XHRcdHNlY3Rpb25UaXRsZXMubm90KHRoaXMpLnJlbW92ZUNsYXNzKCdhY3RpdmUnKTtcblx0XHRcdFxuXHRcdH1cblx0XHRcblx0XHRpZiAoJCh0aGlzKS5oYXNDbGFzcygnYWN0aXZlJykpIHtcblx0XHRcdCQodGhpcykucmVtb3ZlQ2xhc3MoJ2FjdGl2ZScpO1xuXHRcdH0gZWxzZSB7XG5cdFx0XHQkKHRoaXMpLmFkZENsYXNzKCdhY3RpdmUnKTtcblx0XHRcdCQod2luZG93KS5zY3JvbGxUb3AoJCh0aGlzKS5vZmZzZXQoKS50b3ApO1xuXHRcdH1cblx0fSlcblx0XG59KSgpOyJdfQ==
