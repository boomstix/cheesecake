			<!-- Modal overlays -->
			<div id="terms-modal" class="reveal-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-body">
<?
require_once('./assets/tandc_' .$region. '.php');
?>
				</div>
				<div class="modal-footer">
					<a class="close-reveal-modal times" data-dismiss="modal" aria-hidden="true">&times;</a>
				</div>
			</div>
			
			<div id="win-modal" class="reveal-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-body">
					<h4 class="cooper">What Can I Win?</h4>
					<div class="slideshow">
<?
require_once('./assets/prizes_' .$region. '.php');
?>
					</div>
				</div>
				<div class="modal-footer">
					<a class="close-reveal-modal times" data-dismiss="modal" aria-hidden="true">&times;</a>
				</div>
			</div>
			
			<div id="success-modal" class="reveal-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-body">
					<h4 class="cooper">Thanks for entering<br>your dad</h4>
					<p>We just have to check that you haven't uploaded a naked photo of your dad or something even worse.</p>
					<p>Once your dad's photo is approved (takes about 12 hours for us to review and approve entries), he'll be entered into the competition and then it's over to the people to vote for who's dad needs pimping up more. </p>
					<p>Remember to spread the word and get people voting for your dad, oh and don't forget to treat him to one of our cakes this Father's Day.</p>
					<div class="row">
						<div class="twelve columns text-center">
							<div class="overlay-buttons">
								
								<a href="#" class="btn-facebook-share"><span>Share</span></a>
								<a data-dismiss="modal" aria-hidden="true" class="close-reveal-modal btn-close">Keep Voting</a>
								
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<a class="close-reveal-modal times" data-dismiss="modal" aria-hidden="true">&times;</a>
				</div>
			</div>
