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

