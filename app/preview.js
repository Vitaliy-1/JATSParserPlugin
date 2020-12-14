$(document).ready(function () {
	// Create a link to preview based on checked option
	var linkToPreview = $("#jatsParser__linkPreview");
	var baseUrl = linkToPreview.attr("href");
	var inputs = linkToPreview.closest("fieldset").find("input");
	
	inputs.each(function () {
		if ($(this).is(":checked")) {
			changeValue($(this));
		}
	});
	
	inputs.change(function () {
		if (!$(this).is(":checked")) {
			return false;
		}
		changeValue($(this));
	});
	
	function changeValue(els) {
		var value = els.prop("value");
		if (!$.isNumeric(value)) {
			linkToPreview.attr("href", baseUrl);
			return false;
		}
		
		linkToPreview.attr("href", baseUrl + "&_full-text-preview=" + value);
	}
});
