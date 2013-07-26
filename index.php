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
		$stmt = $conn->prepare("SELECT r.`id`, `dads_name`, `img_guid` FROM `register` r JOIN (SELECT round(MAX(`ID`)*RAND()) AS `ID` FROM `register` where `is_approved` = 1) AS x ON r.ID >= x.ID LIMIT 1;");
		$stmt->setFetchMode(PDO::FETCH_BOTH);
		
		if (!$stmt->execute())
		{
			$db_err = true;
			$db_ex = 'Select dad1 statement failed';
		}
		else {
			$dad1_datarow = $stmt->fetch();
		}
		
		// dad 2
		$stmt = $conn->prepare("SELECT r.`id`, `dads_name`, `img_guid` FROM `register` r JOIN (SELECT round(MAX(`ID`)*RAND()) AS `ID` FROM `register` where `is_approved` = 1 and id != " . $dad1_datarow['id'] . ") AS x ON r.ID >= x.ID  where r.`is_approved` = 1 and r.id != " . $dad1_datarow['id'] . " LIMIT 1;");
		$stmt->setFetchMode(PDO::FETCH_BOTH);

		if (!$stmt->execute())
		{
			$db_err = true;
			$db_ex = 'Select dad2 statement failed';
		}
		else {
			$dad2_datarow = $stmt->fetch();
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

		<!--[if lt IE 7]>
		<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
		<![endif]-->

		<div class="container">
		
			<div class="stage">
			
			<? if ($db_err) : ?>
			<?= $db_ex->getMessage() ?>
			<? elseif ($dad1_datarow && $dad2_datarow) : ?>
			
				<form class="form" method="post">
					<input type="hidden" name="submit" value="1" />
					<input type="hidden" name="dad1" value="<?= $dad1_datarow['id'] ?>" />
					<input type="hidden" name="dad2" value="<?= $dad2_datarow['id'] ?>" />
					<div class="row">
						<div class="four offset-by-one column image">
							<input type="image" id="image-2" name="vote1" value="<?= $dad1_datarow['id'] ?>" src="img/upload.jpg" width="320" height="250" />
							<?= $dad1_datarow['dads_name'] ?>
						</div>
						<div class="two column vs">
							VS
						</div>
						<div class="four column image end">
							<input type="image" id="image-2" name="vote2" value="<?= $dad2_datarow['id'] ?>" src="img/upload.jpg" width="320" height="250" />
							<?= $dad2_datarow['dads_name'] ?>
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
		
		</div>
	
</body>
</html>