; (function ($, window, undefined) {
	'use strict';
	var $doc = $(document),
	Modernizr = window.Modernizr;
	$(document).ready(function () {
		$.fn.foundationAlerts ? $doc.foundationAlerts() : null;
		$.fn.foundationButtons ? $doc.foundationButtons() : null;
		$.fn.foundationAccordion ? $doc.foundationAccordion() : null;
		$.fn.foundationNavigation ? $doc.foundationNavigation() : null;
		$.fn.foundationTopBar ? $doc.foundationTopBar() : null;
		$.fn.foundationCustomForms ? $doc.foundationCustomForms() : null;
		$.fn.foundationMediaQueryViewer ? $doc.foundationMediaQueryViewer() : null;
		$.fn.foundationTabs ? $doc.foundationTabs({ callback: $.foundation.customForms.appendCustomMarkup }) : null;
		$.fn.foundationTooltips ? $doc.foundationTooltips() : null;
		$.fn.foundationMagellan ? $doc.foundationMagellan() : null;
		$.fn.foundationClearing ? $doc.foundationClearing() : null;
		$.fn.placeholder ? $('input, textarea').placeholder() : null;
	});
	// Hide address bar on mobile devices (except if #hash present, so we don't mess up deep linking).
	if (Modernizr.touch && !window.location.hash) {
		$(window).load(function () {
			setTimeout(function () {
				window.scrollTo(0, 1);
			}, 0);
		});
	}
	$(function () {

		$('.frame-holder').hide().css('visibility', 'visible').fadeIn();

		var can_upload = false;

		// clear error css class when entering text
		$('.error input').on('keypress', function (e) {
			var el = $($(this).parents('.columns')[0]);
			if (el) {
				el.removeClass('error');
			}
		});
		
		// setup bootstrap input file
		$('input[type=file]').bootstrapFileInput();
		
		function disableButtons() {
			$('button').attr('disabled', 'disabled');
		}
		
		$('input[type="file"]').on('change', function() {
			if ($('#upload_image').val() != '') {
				can_upload = true;
			}
		});
		
		$('#upload_button').on('click', function(e) {
			if (can_upload) {
				setTimeout(disableButtons, 50)
			}
			else {
				e.preventDefault();
			}
		});

	});
})(jQuery, this);