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
	
	$('#article-navbar .nav-link').each(function () {
		$(this).prepend("<i class=\"far fa-circle\"></i>");
	})
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


//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm1haW4uanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBIiwiZmlsZSI6ImFwcC5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8vIE5hdmlnYXRpb24gaW5zaWRlIGFuIGFydGljbGVcbihmdW5jdGlvbiAoKSB7XG5cblx0dmFyIGhhc1N1YnRpdGxlcyA9IDA7XG5cdHZhciBpbnRyYW5hdjtcblx0JChcIi5hcnRpY2xlLXNlY3Rpb24tdGl0bGVcIikuZWFjaChmdW5jdGlvbiAoaSkge1xuXHRcdGlmICgkKHRoaXMpLnByb3AoXCJ0YWdOYW1lXCIpID09PSBcIkgyXCIpIHtcblx0XHRcdCQodGhpcykuYXR0cihcImlkXCIsIFwidGl0bGUtXCIgKyBpKTtcblx0XHRcdCQoXCIjYXJ0aWNsZS1uYXZpZ2F0aW9uLW1lbnUtaXRlbXNcIikuYXBwZW5kKFwiPGEgY2xhc3M9J25hdi1saW5rJyBocmVmPScjdGl0bGUtXCIraStcIic+XCIgKyAkKHRoaXMpLnRleHQoKSArIFwiPC9hPlwiKTtcblx0XHRcdGhhc1N1YnRpdGxlcyA9IDA7XG5cdFx0fSBlbHNlIGlmICgkKHRoaXMpLnByb3AoXCJ0YWdOYW1lXCIpID09PSBcIkgzXCIpIHtcblx0XHRcdGhhc1N1YnRpdGxlcysrO1xuXHRcdFx0XG5cdFx0XHQkKHRoaXMpLmF0dHIoXCJpZFwiLCBcInRpdGxlLVwiICsgaSk7XG5cdFx0XHRpZiAoaGFzU3VidGl0bGVzID09PSAxKSB7XG5cdFx0XHRcdGludHJhbmF2ID0gJChcIjxuYXY+XCIsIHtjbGFzczogXCJuYXYgbmF2LXBpbGxzIGZsZXgtY29sdW1uXCJ9KTtcblx0XHRcdFx0JChcIiNhcnRpY2xlLW5hdmlnYXRpb24tbWVudS1pdGVtc1wiKS5hcHBlbmQoaW50cmFuYXYpO1xuXHRcdFx0fVxuXHRcdFx0dmFyIGludHJhbGluayA9ICQoXCI8YT5cIiwge1xuXHRcdFx0XHRjbGFzczogXCJuYXYtbGluayBtbC0zIG15LTEgbmF2LXN1YnRpdGxlXCIsXG5cdFx0XHRcdGhyZWY6ICcjdGl0bGUtJytpLFxuXHRcdFx0XHR0ZXh0OiAkKHRoaXMpLnRleHQoKVxuXHRcdFx0fSk7XG5cdFx0XHRcblx0XHRcdCQoaW50cmFuYXYpLmFwcGVuZChpbnRyYWxpbmspO1xuXHRcdH1cblx0fSk7XG5cdFxuXHQkKCcjYXJ0aWNsZS1uYXZiYXIgLm5hdi1saW5rJykuZWFjaChmdW5jdGlvbiAoKSB7XG5cdFx0JCh0aGlzKS5wcmVwZW5kKFwiPGkgY2xhc3M9XFxcImZhciBmYS1jaXJjbGVcXFwiPjwvaT5cIik7XG5cdH0pXG59KSgpO1xuXG4kKCdib2R5JykuYWRkKHtcblx0J2RhdGEtc3B5JzogJ3Njcm9sbCcsXG5cdCdkYXRhLXRhcmdldCc6ICcjbmF2YmFyLWFydGljbGUnXG59KTtcblxuJChkb2N1bWVudCkucmVhZHkoZnVuY3Rpb24gKCkge1xuXHQkKCdib2R5Jykuc2Nyb2xsc3B5KHt0YXJnZXQ6ICcjYXJ0aWNsZS1uYXZiYXInfSk7XG59KTtcblxuLy8gaGFuZGxpbmcgdGFibGVzXG4oZnVuY3Rpb24gKCkge1xuXHQkKCd0YWJsZScpLmFkZENsYXNzKFwidGFibGVcIikud3JhcChcIjxkaXYgY2xhc3M9J3RhYmxlLXdyYXBwZXInPjwvZGl2PlwiKVxufSkoKTtcblxuIl19
