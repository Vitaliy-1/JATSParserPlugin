$(document).ready(function () {
	$(window).on("load", function() {
		// Create a link to the production ready files
		var workflowButton = $("#workflow-button");
		var linkToProduction = $("#jatsParser__linkToProduction");
		var productionButton = $("#ui-id-7");
		
		linkToProduction.click(function () {
			workflowButton.trigger("click");
			productionButton.trigger("click");
		});
	});
});

$(document).ready(function () {
	// Create a link to preview based on checked option
	var linkToPreview = $("#jatsParser__linkPreview");
	var baseUrl = linkToPreview.attr("href");
	var inputs = linkToPreview.closest("fieldset").find("input");
	inputs.change(function () {
		if (!$(this).is(":checked")) {
			return false;
		}
		var value = $(this).prop("value");
		if (!$.isNumeric(value)) {
			linkToPreview.attr("href", baseUrl);
			return false;
		}
		
		linkToPreview.attr("href", baseUrl + "?_full-text-preview=" + value);
	});
	
});
