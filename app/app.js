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
				class: "nav-link ml-3 nav-subtitle",
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


//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm1haW4uanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EiLCJmaWxlIjoiYXBwLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLy8gTmF2aWdhdGlvbiBpbnNpZGUgYW4gYXJ0aWNsZVxuKGZ1bmN0aW9uICgpIHtcblxuXHR2YXIgaGFzU3VidGl0bGVzID0gMDtcblx0dmFyIGludHJhbmF2O1xuXHQkKFwiLmFydGljbGUtc2VjdGlvbi10aXRsZVwiKS5lYWNoKGZ1bmN0aW9uIChpKSB7XG5cdFx0aWYgKCQodGhpcykucHJvcChcInRhZ05hbWVcIikgPT09IFwiSDJcIikge1xuXHRcdFx0JCh0aGlzKS5hdHRyKFwiaWRcIiwgXCJ0aXRsZS1cIiArIGkpO1xuXHRcdFx0JChcIiNhcnRpY2xlLW5hdmlnYXRpb24tbWVudS1pdGVtc1wiKS5hcHBlbmQoXCI8YSBjbGFzcz0nbmF2LWxpbmsnIGhyZWY9JyN0aXRsZS1cIitpK1wiJz5cIiArICQodGhpcykudGV4dCgpICsgXCI8L2E+XCIpO1xuXHRcdFx0aGFzU3VidGl0bGVzID0gMDtcblx0XHR9IGVsc2UgaWYgKCQodGhpcykucHJvcChcInRhZ05hbWVcIikgPT09IFwiSDNcIikge1xuXHRcdFx0aGFzU3VidGl0bGVzKys7XG5cdFx0XHRcblx0XHRcdCQodGhpcykuYXR0cihcImlkXCIsIFwidGl0bGUtXCIgKyBpKTtcblx0XHRcdGlmIChoYXNTdWJ0aXRsZXMgPT09IDEpIHtcblx0XHRcdFx0aW50cmFuYXYgPSAkKFwiPG5hdj5cIiwge2NsYXNzOiBcIm5hdiBuYXYtcGlsbHMgZmxleC1jb2x1bW5cIn0pO1xuXHRcdFx0XHQkKFwiI2FydGljbGUtbmF2aWdhdGlvbi1tZW51LWl0ZW1zXCIpLmFwcGVuZChpbnRyYW5hdik7XG5cdFx0XHR9XG5cdFx0XHR2YXIgaW50cmFsaW5rID0gJChcIjxhPlwiLCB7XG5cdFx0XHRcdGNsYXNzOiBcIm5hdi1saW5rIG1sLTMgbXktMSBuYXYtc3VidGl0bGVcIixcblx0XHRcdFx0aHJlZjogJyN0aXRsZS0nK2ksXG5cdFx0XHRcdHRleHQ6ICQodGhpcykudGV4dCgpXG5cdFx0XHR9KTtcblx0XHRcdFxuXHRcdFx0JChpbnRyYW5hdikuYXBwZW5kKGludHJhbGluayk7XG5cdFx0fVxuXHR9KTtcblx0XG5cdCQoJyNhcnRpY2xlLW5hdmJhciAubmF2LWxpbmsnKS5lYWNoKGZ1bmN0aW9uICgpIHtcblx0XHQkKHRoaXMpLnByZXBlbmQoXCI8aSBjbGFzcz1cXFwiZmFyIGZhLWNpcmNsZVxcXCI+PC9pPlwiKTtcblx0fSlcbn0pKCk7XG5cbiQoJ2JvZHknKS5hZGQoe1xuXHQnZGF0YS1zcHknOiAnc2Nyb2xsJyxcblx0J2RhdGEtdGFyZ2V0JzogJyNuYXZiYXItYXJ0aWNsZSdcbn0pO1xuXG4kKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbiAoKSB7XG5cdCQoJ2JvZHknKS5zY3JvbGxzcHkoe3RhcmdldDogJyNhcnRpY2xlLW5hdmJhcid9KTtcbn0pO1xuXG4iXX0=
