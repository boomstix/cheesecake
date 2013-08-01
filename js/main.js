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
	
})(jQuery, this);


	var ImgLoadFadeIn = function (_imgSelector, _imgUrl) {

		var img = $(_imgSelector), img_url = _imgUrl;
		if (img.length) {
			img.hide();
			if (img_url != '') {
				img.attr('src', img_url);
			}
			// setup the img layer to fade in when the img is loaded
			img.on('load', function(){
				if (img_url != '') {
					img.fadeIn();
					$(img.parents('.frame-holder')[0]).addClass('hover');
				}
			});
		}
		else {
			console.warn('img selector failed');
		}
	
	}


$(function () {

	// hide and fade in any overlay frame fadein thingos
	$('.frame-holder').hide().css('visibility', 'visible').fadeIn();

	// clear error states
	function removeError(e) {
		var el = $($(this).parents('.columns')[0]);
		if (el) {
			el.removeClass('error');
		}
	}
	function removeErrorSelect(e) {
		var me = $(this), el = $(me.parents('.columns')[0]);
		if (el && me.val() !== '-1') {
			el.removeClass('error');
		}
	}
	// clear error css class when entering text
	$('.error input').on('keypress', removeError);
	$('.error input[type="checkbox"]').on('change', removeError);
	$('.error select').on('change', removeErrorSelect);
	
	// setup bootstrap input file
	$('input[type=file]').bootstrapFileInput();

	// setup the custom checkbox
	$('#agree_terms').checkbox();

	// setup custom select boxes
	var your_state = $('#your_state'), your_branch = $('#your_branch');
	// this will be in the page if the selects are
	// your_state_val = '<?= $your_state ?>', your_branch_val = '<?= $your_branch ?>';

	if (your_state.length && your_branch.length) {
	
		// setup the state render upon successful retrieval 
		$.ajax({
			dataType: "json",
			url: "assets/stores.json",
			success: function(data, textStatus, jqXHR) {
				var states = [{id:1,name:'NSW'},{id:2,name:'ACT'},{id:3,name:'VIC'},{id:4,name:'TAS'},{id:5,name:'QLD'},{id:6,name:'SA'},{id:7,name:'NT'},{id:8,name:'WA'}];
				// populate the states and attach the state change handler, and trigger it
				your_state.empty();
				your_state.append($('<option value="-1">Please select your state</option>'));
				$.each(states, function(ix, el){
					your_state.append($('<option value="'+el.id+'"'+(your_state_val == el.id ? ' selected="selected"' : '')+'>'+el.name+'</option>'));
				});
				your_state.on('change', function(e){
					your_branch.empty();
					your_branch.append($('<option value="-1">Please select'+(your_state.val() === '-1' ? ' your state' : ' your branch')+'</option>'));
					$.each(data, function(ix, el){
						if (el.state_id == your_state.val()) {
							your_branch.append($('<option value="'+el.store_id+'"'+(your_branch_val == el.store_id ? ' selected="selected"' : '')+'>'+el.store_name+'</option>'));
						}
					});
					// setup the branch
					your_branch.trigger('update');
				});
				your_state.change();
			}
		});

		// custom select
		your_state.customSelect();
		your_state.css('height', '25px');
		your_branch.customSelect();
		your_branch.css('height', '25px');

	}

	// a: setup disabling of repeat submits and
	// b: prevent submitting when no file has been selected,
	// c: check conditions to set the flag whenever file changes.
	var can_upload = false;

	function disableButtons() {
		$('button').attr('disabled', 'disabled');
		$('button').fadeTo(200, 50);
	}
	
	$('#upload_button').on('click', function(e) {
		if ($('#upload_image').val() != '') { // c:
			setTimeout(disableButtons, 50)	// a:
		}
		else {	// b:
			e.preventDefault();
		}
	});
	
});
