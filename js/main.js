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
			url: "/assets/stores.json",
			success: function(data, textStatus, jqXHR) {
				var states = [{region:'au',id:1,name:'NSW'},{region:'au',id:2,name:'ACT'},
				{region:'au',id:3,name:'VIC'},{region:'au',id:4,name:'TAS'},
				{region:'au',id:5,name:'QLD'},{region:'au',id:6,name:'SA'},
				{region:'au',id:7,name:'NT'},{region:'au',id:8,name:'WA'},
				{region:'nz',id:9,name:"NZ"}],
				regionIx = 0, lastRegionID = 0;
				// populate the states and attach the state change handler, and trigger it
				your_state.empty();
				your_state.append($('<option value="-1">Please select your state</option>'));
				$.each(states, function(ix, el){
					if (el.region == region && el.region ){
						your_state.append($('<option value="'+el.id+'"'+(your_state_val == el.id ? ' selected="selected"' : '')+'>'+el.name+'</option>'));
						regionIx++;
						lastRegionID = el.id;
					}
				});
				your_state.on('change', function(e){
					your_branch.empty();
					your_branch.append($('<option value="-1">Please select'+(your_state.val() === '-1' ? ' your state' : ' your bakery')+'</option>'));
					$.each(data, function(ix, el){
						if (el.state_id == your_state.val()) {
							your_branch.append($('<option value="'+el.store_id+'"'+(your_branch_val == el.store_id ? ' selected="selected"' : '')+'>'+el.store_name+'</option>'));
						}
					});
					// setup the branch
					your_branch.trigger('update');
				});
				if (regionIx == 1) {
					your_state.val(lastRegionID);
					your_state.hide();
				}
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
		$('button').hide();
	}
	
	var origMsg = $('.text-layer').html();

	function testUploadImage() {
		if ($('#upload_image').val() != '') {
			$('#upload_button').removeAttr('disabled');
			$('#upload_button').fadeIn();
			$('.text-layer').html($('.file-input-name').html());
			$('.file-input-name').html('Click to chose<br />a different image');
		}
		else {
			$('#upload_button').attr('disabled', 'disabled');
			$('#upload_button').fadeOut();
			$('.text-layer').html(origMsg);
		}
	}
	
	$('#upload_button').on('click', function(e) {
		if ($('#upload_image').val() != '') { // c:
			setTimeout(disableButtons, 50)	// a:
			$('.text-layer').html('Uploading ...');
		}
		else {	// b:
			e.preventDefault();
		}
	});
	
	$('#upload_image').on('change', testUploadImage);
	testUploadImage();
	
	
	$('.btn-facebook-share').on('click', function(){
	
		window.open(
		'https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(location.href), 
		'facebook-share-dialog', 
		'width=626,height=436'
		);
		
	});
	
	/* */
	var Slideshow = function() {
		var holder = $('.slideshow');
		var prizes = $('.slideshow div.prize');
		var leftNav = null, rightNav = null;
		var elems = [];
		var currentIx = 0, maxIx = 0;
		
		function setup() {
			$.each(prizes, function(ix,el){
				// console.log(el, $(el));
				elems.push($(el).detach());
			});
			maxIx = elems.length -1;
			// pack away the divs;
			leftNav = $('<div class="left"></div>').appendTo(holder).on('click', prev);
			rightNav = $('<div class="right"></div>').appendTo(holder).on('click', next);
			reveal();
		}
		
		function prev() {
			console.log('prev');
			currentIx = (currentIx == 0) ? maxIx : currentIx - 1;
			showCurrent();
		}
		
		function next() {
			console.log('next');
			currentIx = (currentIx == maxIx) ? 0 : currentIx + 1;
			showCurrent();
		}
		
		function reveal() {
			console.log('reveal');
			$('div.prize', holder).remove();
			holder.append(elems[currentIx].clone().hide());
			$('div.prize', holder).fadeIn(400);
		}
		
		function showCurrent() {
			console.log('showCurrent');
			$('div.prize', holder).fadeOut(400, reveal);
		}
		
		setup();
		
	}
	var v = new Slideshow();
	// $('.slideshow div').orbit();
	/* */
	
});
