		<?
		if ($_SERVER['REMOTE_ADDR'] == $analytics_url) : ?>
		<script>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
			ga('create', '<?= $analytics_acct ?>', '<?= $analytics_url ?>');
			ga('send', 'pageview');
		</script><?
		endif;
		?>
<footer>
	<div class="stage">
<a href="http://www.cheesecake.com.au" class="badge">The Cheesecake Shop</a>
<a href="#terms-modal" data-reveal-id="terms-modal" class="terms">Terms and Conditions</a>
<span class="sep">|</span>
<a href="http://www.cheesecake.com.au" class="url">www.cheesecake.com.au</a>
<a href="http://www.cheesecake.com.au" class="logo">The Cheesecake Shop</a>
	</div>
</footer>
	</body>
</html>
