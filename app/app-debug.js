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
	var imageEnlarge = document.createElement('i');
	$(imageEnlarge).addClass('fas fa-expand-arrows-alt fa-lg figure-image-expand');
	$('.figure').append(imageEnlarge);
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
		var imageEnlarge =  figure.item(i).getElementsByClassName('figure-image-expand')[0];
		var boxImage = figure.item(i).getElementsByClassName('figure')[0];
		
		// Download figure image link
		var downloadImageLink = document.createElement('a');
		downloadImageLink.classList.add('download-image');
		var downloadIcon = document.createElement('i');
		downloadIcon.classList.add('fas', 'fa-download', 'fa-lg');
		boxImage.appendChild(downloadImageLink);
		downloadImageLink.appendChild(downloadIcon);
		downloadImageLink.href = figImage.src;
		
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
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm1haW4uanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiJhcHAuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvLyBOYXZpZ2F0aW9uIGluc2lkZSBhbiBhcnRpY2xlXG4oZnVuY3Rpb24gKCkge1xuXG5cdHZhciBoYXNTdWJ0aXRsZXMgPSAwO1xuXHR2YXIgaW50cmFuYXY7XG5cdCQoXCIuYXJ0aWNsZS1zZWN0aW9uLXRpdGxlXCIpLmVhY2goZnVuY3Rpb24gKGkpIHtcblx0XHRpZiAoJCh0aGlzKS5wcm9wKFwidGFnTmFtZVwiKSA9PT0gXCJIMlwiKSB7XG5cdFx0XHQkKHRoaXMpLmF0dHIoXCJpZFwiLCBcInRpdGxlLVwiICsgaSk7XG5cdFx0XHQkKFwiI2FydGljbGUtbmF2aWdhdGlvbi1tZW51LWl0ZW1zXCIpLmFwcGVuZChcIjxhIGNsYXNzPSduYXYtbGluaycgaHJlZj0nI3RpdGxlLVwiK2krXCInPlwiICsgJCh0aGlzKS50ZXh0KCkgKyBcIjwvYT5cIik7XG5cdFx0XHRoYXNTdWJ0aXRsZXMgPSAwO1xuXHRcdH0gZWxzZSBpZiAoJCh0aGlzKS5wcm9wKFwidGFnTmFtZVwiKSA9PT0gXCJIM1wiKSB7XG5cdFx0XHRoYXNTdWJ0aXRsZXMrKztcblx0XHRcdFxuXHRcdFx0JCh0aGlzKS5hdHRyKFwiaWRcIiwgXCJ0aXRsZS1cIiArIGkpO1xuXHRcdFx0aWYgKGhhc1N1YnRpdGxlcyA9PT0gMSkge1xuXHRcdFx0XHRpbnRyYW5hdiA9ICQoXCI8bmF2PlwiLCB7Y2xhc3M6IFwibmF2IG5hdi1waWxscyBmbGV4LWNvbHVtblwifSk7XG5cdFx0XHRcdCQoXCIjYXJ0aWNsZS1uYXZpZ2F0aW9uLW1lbnUtaXRlbXNcIikuYXBwZW5kKGludHJhbmF2KTtcblx0XHRcdH1cblx0XHRcdHZhciBpbnRyYWxpbmsgPSAkKFwiPGE+XCIsIHtcblx0XHRcdFx0Y2xhc3M6IFwibmF2LWxpbmsgbWwtMyBteS0xIG5hdi1zdWJ0aXRsZVwiLFxuXHRcdFx0XHRocmVmOiAnI3RpdGxlLScraSxcblx0XHRcdFx0dGV4dDogJCh0aGlzKS50ZXh0KClcblx0XHRcdH0pO1xuXHRcdFx0XG5cdFx0XHQkKGludHJhbmF2KS5hcHBlbmQoaW50cmFsaW5rKTtcblx0XHR9XG5cdH0pO1xufSkoKTtcblxuJCgnYm9keScpLmFkZCh7XG5cdCdkYXRhLXNweSc6ICdzY3JvbGwnLFxuXHQnZGF0YS10YXJnZXQnOiAnI25hdmJhci1hcnRpY2xlJ1xufSk7XG5cbiQoZG9jdW1lbnQpLnJlYWR5KGZ1bmN0aW9uICgpIHtcblx0JCgnYm9keScpLnNjcm9sbHNweSh7dGFyZ2V0OiAnI2FydGljbGUtbmF2YmFyJ30pO1xufSk7XG5cbi8vIGhhbmRsaW5nIHRhYmxlc1xuKGZ1bmN0aW9uICgpIHtcblx0JCgndGFibGUnKS5hZGRDbGFzcyhcInRhYmxlXCIpLndyYXAoXCI8ZGl2IGNsYXNzPSd0YWJsZS13cmFwcGVyJz48L2Rpdj5cIilcbn0pKCk7XG5cbi8vIGhhbmRsaW5nIGZpZ3VyZXNcblxuKGZ1bmN0aW9uICgpIHtcblx0dmFyIGltYWdlRW5sYXJnZSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2knKTtcblx0JChpbWFnZUVubGFyZ2UpLmFkZENsYXNzKCdmYXMgZmEtZXhwYW5kLWFycm93cy1hbHQgZmEtbGcgZmlndXJlLWltYWdlLWV4cGFuZCcpO1xuXHQkKCcuZmlndXJlJykuYXBwZW5kKGltYWdlRW5sYXJnZSk7XG59KSgpO1xuXG4oZnVuY3Rpb24gKCkge1xuXHR2YXIgZmlndXJlID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeVRhZ05hbWUoJ2ZpZ3VyZScpO1xuXHRcblx0Zm9yIChpID0gMDsgaSA8IGZpZ3VyZS5sZW5ndGg7IGkrKykge1xuXHRcdGlmICgoZmlndXJlLml0ZW0oaSkub2Zmc2V0SGVpZ2h0IC0gZmlndXJlLml0ZW0oaSkuc3R5bGUubWFyZ2luQm90dG9tKSA8IGZpZ3VyZS5pdGVtKGkpLnNjcm9sbEhlaWdodCkge1xuXHRcdFx0dmFyIGNhcHRpb24gPSBmaWd1cmUuaXRlbShpKS5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCdjYXB0aW9uJylbMF07XG5cdFx0XHR2YXIgZWxsaXBzaXMgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdzcGFuJyk7XG5cdFx0XHRlbGxpcHNpcy5jbGFzc0xpc3QuYWRkKCdlbGxpcHNpcycpO1xuXHRcdFx0ZWxsaXBzaXMuaW5uZXJUZXh0ID0gJy4uLic7XG5cdFx0XHRjYXB0aW9uLmFwcGVuZENoaWxkKGVsbGlwc2lzKTtcblx0XHR9XG5cdFx0XG5cdFx0dmFyIGZpZ0ltYWdlID0gZmlndXJlLml0ZW0oaSkuZ2V0RWxlbWVudHNCeVRhZ05hbWUoJ2ltZycpWzBdO1xuXHRcdHZhciBpbWFnZUVubGFyZ2UgPSAgZmlndXJlLml0ZW0oaSkuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnZmlndXJlLWltYWdlLWV4cGFuZCcpWzBdO1xuXHRcdHZhciBib3hJbWFnZSA9IGZpZ3VyZS5pdGVtKGkpLmdldEVsZW1lbnRzQnlDbGFzc05hbWUoJ2ZpZ3VyZScpWzBdO1xuXHRcdFxuXHRcdC8vIERvd25sb2FkIGZpZ3VyZSBpbWFnZSBsaW5rXG5cdFx0dmFyIGRvd25sb2FkSW1hZ2VMaW5rID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYScpO1xuXHRcdGRvd25sb2FkSW1hZ2VMaW5rLmNsYXNzTGlzdC5hZGQoJ2Rvd25sb2FkLWltYWdlJyk7XG5cdFx0dmFyIGRvd25sb2FkSWNvbiA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2knKTtcblx0XHRkb3dubG9hZEljb24uY2xhc3NMaXN0LmFkZCgnZmFzJywgJ2ZhLWRvd25sb2FkJywgJ2ZhLWxnJyk7XG5cdFx0Ym94SW1hZ2UuYXBwZW5kQ2hpbGQoZG93bmxvYWRJbWFnZUxpbmspO1xuXHRcdGRvd25sb2FkSW1hZ2VMaW5rLmFwcGVuZENoaWxkKGRvd25sb2FkSWNvbik7XG5cdFx0ZG93bmxvYWRJbWFnZUxpbmsuaHJlZiA9IGZpZ0ltYWdlLnNyYztcblx0XHRcblx0XHQvLyBBY3RpdmF0ZSBtb2RhbCBvbiBjbGlja1xuXHRcdGZpZ0ltYWdlLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgb3Blbk1vZGFsKTtcblx0XHRpbWFnZUVubGFyZ2UuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBvcGVuTW9kYWwpO1xuXHRcdFxuXHRcdGZ1bmN0aW9uIG9wZW5Nb2RhbCgpIHtcblx0XHRcdHZhciBtb2RhbFdpbmRvdyA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuXHRcdFx0c2V0VGltZW91dChtb2RhbFdpbmRvdy5jbGFzc0xpc3QuYWRkKCdtb2RhbC13aW5kb3cnKSwgNTAwMCk7XG5cdFx0XHR2YXIgbW9kYWxDb250ZW50ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG5cdFx0XHRtb2RhbENvbnRlbnQuY2xhc3NMaXN0LmFkZCgnbW9kYWwtY29udGVudCcpO1xuXHRcdFx0XG5cdFx0XHR2YXIgaW1hZ2UgPSBudWxsO1xuXHRcdFx0aWYgKHRoaXMudGFnTmFtZSA9PT0gJ2ltZycpIHtcblx0XHRcdFx0aW1hZ2UgPSB0aGlzLmNsb25lTm9kZSh0cnVlKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdGltYWdlID0gdGhpcy5wYXJlbnRFbGVtZW50LmdldEVsZW1lbnRzQnlUYWdOYW1lKCdpbWcnKVswXS5jbG9uZU5vZGUodHJ1ZSk7XG5cdFx0XHR9XG5cdFx0XHRcblx0XHRcdGZpZ3VyZUNhcHRpb24gPSB0aGlzLnBhcmVudEVsZW1lbnQucGFyZW50RWxlbWVudC5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCdjYXB0aW9uJylbMF0uY2xvbmVOb2RlKHRydWUpO1xuXHRcdFx0XG5cdFx0XHRkb2N1bWVudC5ib2R5LmFwcGVuZENoaWxkKG1vZGFsV2luZG93KTtcblx0XHRcdG1vZGFsV2luZG93LmFwcGVuZENoaWxkKG1vZGFsQ29udGVudCk7XG5cdFx0XHRtb2RhbENvbnRlbnQuYXBwZW5kQ2hpbGQoaW1hZ2UpO1xuXHRcdFx0bW9kYWxDb250ZW50LmFwcGVuZENoaWxkKGZpZ3VyZUNhcHRpb24pO1xuXHRcdFx0XG5cdFx0XHQvLyBoaWRlIGJvZHkgb3ZlcmZsb3dcblx0XHRcdGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnaGlkZGVuJztcblx0XHRcdFxuXHRcdFx0Ly8gY3JlYXRlIGNsb3NlIHN5bWJvbFxuXHRcdFx0dmFyIGNsb3NlQnV0dG9uID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaScpO1xuXHRcdFx0Y2xvc2VCdXR0b24uY2xhc3NMaXN0LmFkZCgnZmFzJywgJ2ZhLXRpbWVzJywgJ2Nsb3NlLW1vZGFsJywgJ2ZhLTJ4Jyk7XG5cdFx0XHRtb2RhbENvbnRlbnQuYXBwZW5kQ2hpbGQoY2xvc2VCdXR0b24pO1xuXHRcdH1cblx0XHRcblx0fVxuXHRcblx0Ly8gRGlzbWlzcyBtb2RhbCBvbiBjbGlja1xuXHR2YXIgbnVtYmVyID0gMDtcblx0XG5cdHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uKGUpe1xuXHRcdHZhciBtb2RhbFdpbmRvdyA9IGRvY3VtZW50LmdldEVsZW1lbnRzQnlDbGFzc05hbWUoJ21vZGFsLXdpbmRvdycpWzBdO1xuXHRcdFxuXHRcdGlmICh0eXBlb2YgbW9kYWxXaW5kb3cgPT09ICd1bmRlZmluZWQnIHx8IG1vZGFsV2luZG93ID09IG51bGwpIHJldHVybiBmYWxzZTtcblx0XHRcblx0XHRudW1iZXIrKztcblx0XHRcblx0XHR2YXIgbW9kYWxDb250ZW50ID0gbW9kYWxXaW5kb3cuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnbW9kYWwtY29udGVudCcpWzBdO1xuXHRcdHZhciBjbG9zZU1vZGFsID0gbW9kYWxXaW5kb3cuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnY2xvc2UtbW9kYWwnKVswXTtcblx0XHRcblx0XHRpZiAoKG1vZGFsQ29udGVudCAhPT0gbnVsbCAmJiAhbW9kYWxDb250ZW50LmNvbnRhaW5zKGUudGFyZ2V0KSAmJiBudW1iZXIgPiAxKSB8fCBjbG9zZU1vZGFsLmNvbnRhaW5zKGUudGFyZ2V0KSl7XG5cdFx0XHRtb2RhbFdpbmRvdy5wYXJlbnROb2RlLnJlbW92ZUNoaWxkKG1vZGFsV2luZG93KTtcblx0XHRcdGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnYXV0byc7XG5cdFx0XHRudW1iZXIgPSAwO1xuXHRcdH0gXG5cdH0pO1xufSkoKTtcblxuXG4oZnVuY3Rpb24gKCQpIHtcblx0XG5cdC8vIFNob3cgYXV0aG9yIGFmZmlsaWF0aW9uIHVuZGVyIGF1dGhvcnMgbGlzdCAoZm9yIGxhcmdlIHNjcmVlbiBvbmx5KVxuXHR2YXIgYXV0aG9yU3RyaW5nID0gJCgnLmphdHNwYXJzZXItYXV0aG9yLXN0cmluZy1ocmVmJyk7XG5cdCQoYXV0aG9yU3RyaW5nKS5jbGljayhmdW5jdGlvbihldmVudCkge1xuXHRcdGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG5cdFx0dmFyIGVsZW1lbnRJZCA9ICQodGhpcykuYXR0cignaHJlZicpLnJlcGxhY2UoJyMnLCAnJyk7XG5cdFx0JCgnLmFydGljbGUtZGV0YWlscy1hdXRob3InKS5lYWNoKGZ1bmN0aW9uICgpIHtcblx0XHRcdFxuXHRcdFx0Ly8gU2hvdyBvbmx5IHRhcmdldGVkIGF1dGhvcidzIGFmZmlsaWF0aW9uIG9uIGNsaWNrXG5cdFx0XHRpZiAoJCh0aGlzKS5hdHRyKCdpZCcpID09PSBlbGVtZW50SWQgJiYgJCh0aGlzKS5oYXNDbGFzcygnaGlkZUF1dGhvcicpKSB7XG5cdFx0XHRcdCQodGhpcykucmVtb3ZlQ2xhc3MoJ2hpZGVBdXRob3InKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdCQodGhpcykuYWRkQ2xhc3MoJ2hpZGVBdXRob3InKTtcblx0XHRcdH1cblx0XHR9KTtcblx0XHRcblx0XHQvLyBBZGQgc3BlY2lmaWVycyB0byB0aGUgY2xpY2tlZCBhdXRob3IncyBsaW5rXG5cdFx0JChhdXRob3JTdHJpbmcpLmVhY2goZnVuY3Rpb24gKCkge1xuXHRcdFx0aWYgKCQodGhpcykuYXR0cignaHJlZicpID09PSAoJyMnICsgZWxlbWVudElkKSAmJiAhJCh0aGlzKS5oYXNDbGFzcygnYWN0aXZlJykpe1xuXHRcdFx0XHQkKHRoaXMpLmFkZENsYXNzKCdhY3RpdmUnKTtcblx0XHRcdFx0JCh0aGlzKS5jaGlsZHJlbignLmF1dGhvci1wbHVzJykuYWRkQ2xhc3MoJ2hpZGUnKTtcblx0XHRcdFx0JCh0aGlzKS5jaGlsZHJlbignLmF1dGhvci1taW51cycpLnJlbW92ZUNsYXNzKCdoaWRlJyk7XG5cdFx0XHR9IGVsc2UgaWYgKCQodGhpcykuYXR0cignaHJlZicpICE9PSAoJyMnICsgZWxlbWVudElkKSB8fCAkKHRoaXMpLmhhc0NsYXNzKCdhY3RpdmUnKSkge1xuXHRcdFx0XHQkKHRoaXMpLnJlbW92ZUNsYXNzKCdhY3RpdmUnKTtcblx0XHRcdFx0JCh0aGlzKS5jaGlsZHJlbignLmF1dGhvci1wbHVzJykucmVtb3ZlQ2xhc3MoJ2hpZGUnKTtcblx0XHRcdFx0JCh0aGlzKS5jaGlsZHJlbignLmF1dGhvci1taW51cycpLmFkZENsYXNzKCdoaWRlJyk7XG5cdFx0XHR9XG5cdFx0fSk7XG5cdH0pXG59KShqUXVlcnkpO1xuXG4vLyBTZXBhcmV0aW5nIGxpbmtzIGluIHJlZmVyZW5jZXNcbihmdW5jdGlvbiAoKSB7XG5cdCQoJy5yZWZlcmVuY2VzIGxpIGE6bm90KDpsYXN0LWNoaWxkKScpLmVhY2goZnVuY3Rpb24gKGkpIHtcblx0XHR2YXIgZGVsaW1ldGVyID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnc3BhbicpO1xuXHRcdCQoZGVsaW1ldGVyKS5hZGRDbGFzcygncmVmZXJlbmNlcy1saW5rLWRlbGltZXRlcicpLnRleHQoJ3wnKTtcblx0XHQkKHRoaXMpLmFmdGVyKGRlbGltZXRlcik7XG5cdH0pXG59KSgpO1xuXG4vLyBBY2NvcmRpb24gZm9yIHNtYWxsIHNjcmVlbnNcblxuKGZ1bmN0aW9uICgpIHtcblx0XG5cdHZhciBzZWN0aW9uVGl0bGVzID0gJCgnaDIuYXJ0aWNsZS1zZWN0aW9uLXRpdGxlJyk7XG5cdHZhciBtb2JpbGVBcnRpY2xlVGFnID0gJ2phdHNwYXJzZXItYXJ0aWNsZS1tb2JpbGUtdmlldyc7XG5cdFxuXHRmdW5jdGlvbiBhY2NvcmRpb25Td2l0Y2goKSB7XG5cdFx0dmFyIGFydGljbGVXcmFwcGVyID0gJCgnLmFydGljbGUtZnVsbHRleHQnKTtcblx0XHRpZiAod2luZG93LmlubmVyV2lkdGggPCA5OTIgJiYgIWFydGljbGVXcmFwcGVyLmhhc0NsYXNzKG1vYmlsZUFydGljbGVUYWcpKSB7XG5cdFx0XHRhcnRpY2xlV3JhcHBlci5hZGRDbGFzcyhtb2JpbGVBcnRpY2xlVGFnKTtcblx0XHRcdFxuXHRcdFx0c2VjdGlvblRpdGxlcy5lYWNoKGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0JCh0aGlzKS5uZXh0VW50aWwoJ2gyLmFydGljbGUtc2VjdGlvbi10aXRsZScpLndyYXBBbGwoJzxkaXYgY2xhc3M9XCJqYXRzcGFyc2VyLXNlY3Rpb24tY29udGVudFwiPjwvZGl2PicpO1xuXHRcdFx0fSk7XG5cdFx0XHRcblx0XHR9IGVsc2UgaWYgKHdpbmRvdy5pbm5lcldpZHRoID49IDk5MiAmJiBhcnRpY2xlV3JhcHBlci5oYXNDbGFzcyhtb2JpbGVBcnRpY2xlVGFnKSkge1xuXHRcdFx0YXJ0aWNsZVdyYXBwZXIucmVtb3ZlQ2xhc3MobW9iaWxlQXJ0aWNsZVRhZyk7XG5cdFx0XHQkKCcuamF0c3BhcnNlci1zZWN0aW9uLWNvbnRlbnQnKS5jaGlsZHJlbigpLnVud3JhcCgpO1xuXHRcdH1cblx0fVxuXHRcblx0YWNjb3JkaW9uU3dpdGNoKCk7XG5cdFxuXHRcblx0d2luZG93LmFkZEV2ZW50TGlzdGVuZXIoXCJyZXNpemVcIiwgZnVuY3Rpb24gKGV2ZW50KSB7XG5cdFx0YWNjb3JkaW9uU3dpdGNoKCk7XG5cdH0sIGZhbHNlKTtcblx0XG5cdHNlY3Rpb25UaXRsZXMuY2xpY2soZnVuY3Rpb24gKGV2ZW50KSB7XG5cdFx0aWYgKHNlY3Rpb25UaXRsZXMubm90KHRoaXMpLmhhc0NsYXNzKCdhY3RpdmUnKSkge1xuXHRcdFx0c2VjdGlvblRpdGxlcy5ub3QodGhpcykucmVtb3ZlQ2xhc3MoJ2FjdGl2ZScpO1xuXHRcdFx0XG5cdFx0fVxuXHRcdFxuXHRcdGlmICgkKHRoaXMpLmhhc0NsYXNzKCdhY3RpdmUnKSkge1xuXHRcdFx0JCh0aGlzKS5yZW1vdmVDbGFzcygnYWN0aXZlJyk7XG5cdFx0fSBlbHNlIHtcblx0XHRcdCQodGhpcykuYWRkQ2xhc3MoJ2FjdGl2ZScpO1xuXHRcdFx0JCh3aW5kb3cpLnNjcm9sbFRvcCgkKHRoaXMpLm9mZnNldCgpLnRvcCk7XG5cdFx0fVxuXHR9KVxuXHRcbn0pKCk7Il19
