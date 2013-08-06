<?

require_once('./assets/config.php');

//
$loggedin = false;
$register_id = isset($_GET['id']) ? $_GET['id'] : -1;

$register_data = array();
$vote_data = array();

if (isset($_COOKIE['orfentic']) && $register_id > -1) {
	
	$loggedin = true;
	
	try {

		$conn = new PDO("mysql:host=$db_addr;dbname=$db_schema",$db_user,$db_pass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
		if (!$conn) {
			$db_ex = "Could not open connection to the database";
			$db_err = true;
		
		}
		else {
	
			// grab the register row
			$stmt = $conn->prepare("SELECT r.*, v.ratio FROM register
 r
LEFT JOIN (
	SELECT registered_id, AVG(calc.ratio) AS ratio
	FROM (
		SELECT registered_id, sum(vote_count) AS ratio
		FROM vote_log
		GROUP BY registered_id, ip_address
	) AS calc
	GROUP BY registered_id
) AS v ON v.registered_id = r.id
			where r.id = :register_id");
			if (!$stmt->execute(array(':register_id' => $register_id)))
			{
				$db_err = true;
				$db_ex = 'select registration statement failed';
			}
			else {
				$register_data = $stmt->fetchall();
			}
			// grab the vote data
			$stmt = $conn->prepare("SELECT * FROM vote_log where registered_id = :register_id");
			if (!$stmt->execute(array(':register_id' => $register_id)))
			{
				$db_err = true;
				$db_ex = 'select vote statement failed';
			}
			else {
				$vote_data = $stmt->fetchall();
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
else {
	header("Location: /");
}

require_once('./assets/head.php');


?>
<body class="admin">

<div id="wrap">
<div id="main">

<div class="container">
	<div class="stage">
	
<h2>Entrant Details</h2>

<div class="content">

<h3>Entrant Details</h3>

<?

if ($db_err) :

?>

	<p>There was problem communicating with the database:</p>
	<p><?= $db_ex->getMessage() ?></p>

<?

else : 

	if (count($register_data) > 0) :

?>
	
	<table>
	<tr>
		<th>Email</th>
		<th>IP Address</th>
		<th>Contact</th>
		<th>Name</th>
		<th>Dad's Name</th>
		<th>Branch</th>
		<th>Date</th>
		<th>Votes</th>
		<th>Battles</th>
		<th>Confidence</th>
	</tr>
	<tr>
		<td><?= $register_data[0]['your_email'] ?></td> 
		<td><?= $register_data[0]['ip_address'] ?></td> 
		<td><?= $register_data[0]['contact_number'] ?></td> 
		<td><?= $register_data[0]['first_name'] ?> <?= $register_data[0]['last_name'] ?></td> 
		<td><?= $register_data[0]['dads_name'] ?></td> 
		<td><?= $register_data[0]['your_branch'] ?></td> 
		<td><?= $register_data[0]['created_at'] ?></td> 
		<td class="text-center"><?= $register_data[0]['battle_count'] ?></td>
		<td class="text-center"><strong><?= $register_data[0]['vote_count'] ?></strong></td>
		<td class="text-center"><?= $register_data[0]['ratio'] == 0 ? 0 : round(1 / $register_data[0]['ratio'] * 100, 2) ?>%</td>
	</tr>
	</table>

	<table class="left">
	<tr>
		<th>IP Address</th>
		<th>Vote Count</th>
	</tr><?
	foreach ($vote_data as $vote) :?>
	<tr><td><?= $vote['ip_address'] ?></td><td><?= $vote['vote_count'] ?></td></tr><?
	endforeach;
	?>
	</table>
	
	<form action="entries.php?rejected" method="post" class="left">
		<input type="hidden" name="submit_button" value="">
		<button id="reject" type="submit" name="reject" value="<?= $register_data[0]['id'] ?>" style="margin-left: 20px;">Reject</button>
	</form>
	<form action="leaderboard.php" method="post">
		<button type="submit" style="margin-left: 20px;">Return to the Leaderboard</button>
	</form>
	
	<br style="clear:left" />
<script>
$('#reject').on('click', function(){
	return confirm('Click OK to confirm that you would like to reject this entrant.');
})
</script>
	<?
	
	else :

	?>

<p>No entrants with id <?= $register_id ?> here, Chopper.</p>
<p>Items - here - none.</p> <?

	endif; // count entry_data

endif; // db_err


?>
</div>

	</div>
</div>

	</div>
</div>

<?
require_once('./assets/foot.php');
?>