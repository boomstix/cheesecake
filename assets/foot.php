		<script type="text/javascript" src="//use.typekit.net/rgu4xlw.js"></script>
		<script type="text/javascript">try{Typekit.load();}catch(e){}</script>
		<script type="text/javascript" src="js/foundation.min.js"></script>
		<script type="text/javascript" src="js/bootstrap.file-input.js"></script>
		<script type="text/javascript" src="js/jquery.customSelect.js"></script>
		<script type="text/javascript" src="js/jquery-checkbox-2.0.js"></script>
		<script type="text/javascript" src="js/main.js"></script>
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
	</body>
</html>
