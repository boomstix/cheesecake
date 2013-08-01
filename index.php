<?

require_once('assets/config.php');

// did we submit
$submitted = false;
// was there an error
$db_err = false;
$db_ex = null;
// for registering the vote
$the_id = -1;
// for updating the battle
$dad1 = -1;
$dad2 = -1;
// for rendering the battle
$dad1_datarow = null;
$dad2_datarow = null;

// if the form was submitted
if (isset($_POST['submit'])) {
	
	if (isset($_POST['vote1'])) {
		$the_id = $_POST['vote1'];
	}
	else if (isset($_POST['vote2'])) {
		$the_id = $_POST['vote2'];
	}
	if (isset($_POST['dad1'])) {
		$dad1 = $_POST['dad1'];
	}
	if (isset($_POST['dad2'])) {
		$dad2 = $_POST['dad2'];
	}
	
	if ($the_id > 0) {
		
		try {
		
			$conn = new PDO("mysql:host=$db_addr;dbname=$db_schema",$db_user,$db_pass);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			if (!$conn) {
				$db_ex = "Could not open connection to the database";
				$db_err = true;
				
			}
			else {
			
				// register the battle
				$stmt = $conn->prepare("UPDATE `register` SET battle_count = battle_count + 1 where id in (:id1, :id2);");
				if (!$stmt->execute(array(':id1' => $dad1, ':id2' => $dad2)))
				{
					$db_err = true;
					$db_ex = 'update vote count statement failed';
				}
			
				// register the vote
				// is there an entry with this id and this ipaddress already?
				$vote_log_id = -1;
				
				$stmt = $conn->prepare("SELECT id from `vote_log` WHERE ip_address = :ip_address AND registered_id = :id;");
				$stmt->setFetchMode(PDO::FETCH_BOTH);

				if (!$stmt->execute(array(':ip_address' =>  $_SERVER['REMOTE_ADDR'],':id' => $the_id)))
				{
					$db_err = true;
					$db_ex = 'Select vote id statement failed';
				}
				else {
					// grab the vote log id
					$vote_log_id = $stmt->fetchColumn();
				}
				
				if ($vote_log_id > 0) {
					$stmt = $conn->prepare("UPDATE `vote_log` SET vote_count = vote_count + 1 where id = :id;");
					if (!$stmt->execute(array(':id' => $vote_log_id)))
					{
						$db_err = true;
						$db_ex = 'update vote count statement failed';
					}
				}
				else {
					$stmt = $conn->prepare("INSERT INTO vote_log (ip_address, registered_id, vote_count) VALUES (:ip_address, :id, 1);");
					if (!$stmt->execute(array(':ip_address' => $_SERVER['REMOTE_ADDR'],':id' => $the_id)))
					{
						$db_err = true;
						$db_ex = 'insert vote count statement failed';
					}
				}
				
				// update the vote count
				$stmt = $conn->prepare("UPDATE `register` SET vote_count = vote_count + 1 WHERE id = :id");

				if (!$stmt->execute(array(':id' => $the_id)))
				{
					$db_err = true;
					$db_ex = 'Update statement failed';
				}
				
			}
			$conn = null;
		}
		catch (PDOException $e) {
			$db_err = true;
			$db_ex = $e;
			$conn = null;
		}
		
	}
	
}


try {

	$conn = new PDO("mysql:host=$db_addr;dbname=$db_schema",$db_user,$db_pass);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	if (!$conn) {
		$db_ex = "Could not open connection to the database";
		$db_err = true;
		
	}
	else {
	
		// grab two random entries from register table
		
		// dad 1
		$stmt = $conn->prepare("SELECT v.`id`, `dads_name`, `vote_count`, `img_guid`, `img_ext`, `img_landscape` FROM `register` v JOIN ( SELECT `register_id` FROM `approved` a JOIN (SELECT (RAND() * (SELECT MAX(id) FROM approved)) AS id) AS r WHERE a.id >= r.id LIMIT 1 ) as x WHERE v.id = x.register_id;");
		$stmt->setFetchMode(PDO::FETCH_BOTH);
		
		if (!$stmt->execute())
		{
			$db_err = true;
			$db_ex = 'Select dad1 statement failed';
		}
		else {
			$dad1_datarow = $stmt->fetch();
			if (!empty($dad1_datarow)) {
				$dad1 = $dad1_datarow['id'];
			}
		}

		$dad2 = -1;
		
		while ($dad2 == -1 || $dad2 == $dad1) {
			// dad 2
			
			$sql = "SELECT v.`id`, `dads_name`, `vote_count`, `img_guid`, `img_ext`, `img_landscape` FROM `register` v JOIN ( SELECT `register_id` FROM `approved` a JOIN (SELECT (RAND() * (SELECT MAX(id) FROM approved  WHERE register_id != " . $dad1 . ")) AS id) AS r WHERE a.id >= r.id LIMIT 1 ) as x WHERE v.id = x.register_id;";
			$stmt = $conn->prepare($sql);
			$stmt->setFetchMode(PDO::FETCH_BOTH);

			if (!$stmt->execute())
			{
				$db_err = true;
				$db_ex = 'Select dad2 statement failed';
				$dad2 = 1;
			}
			else {
				$dad2_datarow = $stmt->fetch();
				$dad2 = $dad2_datarow['id'];
			}
		}
		
		
	}
}
catch (PDOException $e) {
	$db_err = true;
	$db_ex = $e;
}

require_once('assets/head.php');

?>
<body class="vote">

<div id="wrap">
<div id="main">

	<!--[if lt IE 7]>
	<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
	<![endif]-->

<div class="container">
	<div class="stage">
		
		<h2>Pimp Up Your Dad</h2>
		
		<h3>Enter your dad for a chance to win</h3>
<?

if ($competition_running) :

?>		
		<? if ($db_err) : ?>
		<?= $db_ex->getMessage() ?>
		<? elseif ($dad1_datarow && $dad2_datarow) : ?>
			
		<form class="form" method="post" action="?">
			<input type="hidden" name="submit" value="1" />
			<input type="hidden" name="dad1" value="<?= $dad1_datarow['id'] ?>" />
			<input type="hidden" name="dad2" value="<?= $dad2_datarow['id'] ?>" />
			<div class="row">
				<div class="four offset-by-one column image">

					<div class="vert-outer vert-full">
						<div class="vert-inner">

							<div class="frame-holder<?= $dad1_datarow['img_landscape'] ? ' horz' : ' vert' ?>">
								<input class="frame frame-layer" type="image" id="vote1" name="vote1" value="<?= $dad1_datarow['id'] ?>" src="img/shim.png" />
								<div class="frame pimp-layer"></div>
								<div class="frame-inner transp-layer"></div>
								<div class="img-layer-outer vert-outer">
									<div class="img-layer-inner vert-inner">
									<img id="image-1" src="img/shim.png" />
									</div>
								</div>
							</div>
							<div class="vote-count cooper"><?= $dad1_datarow['vote_count'] . ' vote' . ($dad1_datarow['vote_count'] == 1 ? '' : 's') ?></div>

						</div>
					</div>

				</div>
				<div class="two column vs">
					<span>VS</span>
				</div>
				<div class="four column image end">

					<div class="vert-outer vert-full">
						<div class="vert-inner">
					
							<div class="frame-holder<?= $dad2_datarow['img_landscape'] ? ' horz' : ' vert' ?>">
								<input class="frame frame-layer" type="image" id="vote2" name="vote2" value="<?= $dad2_datarow['id'] ?>" src="img/shim.png" />
								<div class="frame pimp-layer"></div>
								<div class="frame-inner transp-layer"></div>
								<div class="img-layer-outer vert-outer">
									<div class="img-layer-inner vert-inner">
									<img id="image-2" src="img/shim.png" />
									</div>
								</div>
							</div>
							<div class="vote-count cooper"><?= $dad2_datarow['vote_count'] . ' vote' . ($dad2_datarow['vote_count'] == 1 ? '' : 's') ?></div>

						</div>
					</div>

				</div>
			</div>

			<div class="row">
				<div class="four offset-by-one column ribbon">
				
					<div class="dads-name cooper"><?= $dad1_datarow['dads_name'] ?></div>
					
				</div>
				<div class="two column">
				</div>
				<div class="four column ribbon end">
						
					<div class="dads-name cooper"><?= $dad2_datarow['dads_name'] ?></div>
					
				</div>
			</div>
			
			<div class="row">
				<div class="four offset-by-four column enter">
					<a href="form.php">Click Here To Enter</a>
				</div>
			</div>
			
		</form>
		<? endif; ?>
		
	</div>

			<div id="success-modal" class="reveal-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-body">
					<h4>Yay! Thanks for entering!</h4>
					<p>We love your Dad's guts cos you know you do.</p>
					<p>Once your entry is approved, your old man's head will appear for people to rate.</p>
					<p>We really hope he wasn't hit with the ugly stick,<br/>
					unless he's so dropped pie that its hilarious ..</p>
					<p>Now get out and vote!</p>
				</div>
				<div class="modal-footer">
					<a class="close-reveal-modal" data-dismiss="modal" aria-hidden="true">&times;</a>
				</div>
			</div>
			<?
			
else: // $competition_running

?>

<div class="content">
	<h4 class="cooper">Oh Noes!</h4>
	<p>We are sooo not accepting entries right now! Soooo sorry!!</p>
</div>

<?

endif; // $competition_running
?>

	</div><!-- .stage -->
</div><!-- .container -->
</div><!-- #main -->
</div><!-- #wrap -->
<?
require_once('assets/scripts.php');
?><script>
$(function(){

	if (location.search == '?entry_success') {
		$('#success-modal').reveal();
	}

	var	img1 = new ImgLoadFadeIn('#image-1', '<?= $img_domain . $dad1_datarow["img_guid"] . "." . $dad1_datarow["img_ext"] ?>')
	,	img2 = new ImgLoadFadeIn('#image-2', '<?= $img_domain . $dad2_datarow["img_guid"] . "." . $dad2_datarow["img_ext"] ?>')
	;
	

});
</script>	
<?
require_once('assets/foot.php');
?>