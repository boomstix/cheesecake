<?

require_once('assets/config.php');

//
$loggedin = false;
$submitted = false;
$search_term = false;

$is_approved_only = isset($_GET['approved']);
$is_rejected_only = isset($_GET['rejected']);

// whether the form was submitted
$submitted = isset($_POST['submit_button']);
$search_term = $_POST['search_term'];

// whether the form was submitted
$submitted = isset($_POST['submit_button']);

$entry_data = array();

if ($submitted) {

	//if (SHA1($_POST['username'] . $_POST['password']) == $leaderboard_login_hash) {
		$_SESSION['logged_in'] = session_id;
	//}
	
}

if (isset($_SESSION['logged_in'])) {
	
	$loggedin = true;
	
	try {

		$conn = new PDO("mysql:host=$db_addr;dbname=$db_schema",$db_user,$db_pass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
		if (!$conn) {
			$db_ex = "Could not open connection to the database";
			$db_err = true;
		
		}
		else {
	
			if (isset($_POST['approve'])) {
				// register the approve
				$stmt = $conn->prepare("UPDATE `register` SET is_approved = 1, approved_at = now() where id = :id;");
				if (!$stmt->execute(array(':id' => $_POST['approve'])))
				{
					$db_err = true;
					$db_ex = 'update register approve statement failed';
				}
				// register the approve
				$stmt = $conn->prepare("INSERT INTO approved (`register_id`) VALUES (:id)");
				if (!$stmt->execute(array(':id' => $_POST['approve'])))
				{
					$db_err = true;
					$db_ex = 'insert approve statement failed';
				}
			}

			if (isset($_POST['reject'])) {
				// register the reject
				$stmt = $conn->prepare("CALL reject_entry (:id);");
				if (!$stmt->execute(array(':id' => $_POST['reject'])))
				{
					$db_err = true;
					$db_ex = 'update vote approve statement failed';
				}
			}

			// grab the unmoderated registrations
			$sql_str = "SELECT r.`id`, `dads_name`, `your_email`, `contact_number`, `battle_count`, `vote_count`, `img_guid`, `img_ext` FROM `register` r WHERE is_approved " . ($is_approved_only ? ' = 1' : ($is_rejected_only ? ' = 0' : ' is null')) . " ORDER BY created_at DESC LIMIT 100;";
			$stmt = $conn->prepare($sql_str);
			$stmt->setFetchMode(PDO::FETCH_BOTH);
		
			if (!$stmt->execute())
			{
				$db_err = true;
				$db_ex = 'Select leaderboard statement failed';
			}
			else {
				$entry_data = $stmt->fetchall();
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

require_once('assets/head.php');


?>
<body class="admin">

<div id="wrap">
<div id="main">

<div class="container">
	<div class="stage">
	
<h2><?= $is_approved_only ? 'Approved' : ($is_rejected_only ? 'Rejected' : 'Moderation') ?></h2>

<div class="content">

<h3>
<?
if ($is_approved_only) : ?>
Approved <small> | <a href="?rejected">Rejected</a> | <a href="?">Moderate</a></small>
<?
elseif ($is_rejected_only) : ?>
Rejected <small> | <a href="?approved">Approved</a> | <a href="?">Moderate</a></small>
<?
else : ?>
Moderation  <small> | <a href="?approved">Approved</a> | <a href="?rejected">Rejected</a></small>
<?
endif;
?>
</h3>

	<?

if ($db_err) :

?>

	<p>There was problem communicating with the database:</p>
	<p><?= $db_ex->getMessage() ?></p>

<?

else : 

	if (!$loggedin) : ?>
	
	<form class="form" method="post">
	<table>
	<tr>
		<td><label for="username">Username</label></td>
		<td><input type="text" name="username" maxlength="32" /></td> 
	</tr>
	<tr>
		<td><label for="username">Password</label></td>
		<td><input type="password" name="password" maxlength="32" /></td> 
	</tr>
	<tr>
		<td></td>
		<td><button type="submit" name="submit_button" class="btn">Login</button></td> 
	</tr>
	</table>
	</form>
	<?
	
	else :

	if (count($entry_data) == 0) : ?>

<p>No items here, Chopper.</p>
<p>Items - here - none.</p> <?

	else : ?>
<form method="post">
<input type="hidden" name="submit_button" value="" />
<table class="table">
<tbody><?
			$itemsPerRow = 6;
			foreach ($entry_data as $ix => $data) : 
				if ($ix % $itemsPerRow == 0) : ?> 
	<tr><?
				endif; ?> 
		<td>
			<img src="<?= 'http://' . $awsUserUploadBucket . '.s3.amazonaws.com/' . $data['img_guid'] .'.'. (is_null($data['img_ext']) ? 'jpg' : $data['img_ext'] ) ?>" style="max-width: 120px; max-height: 120px; min-width: 120px; min-height: 120px;" />
			<div><?= $data['dads_name'] ?></div>
			<? if (!$is_approved_only) : ?><button type="submit" name="approve" value="<?= $data['id'] ?>">Approve</button><? endif; ?>
			<? if (!$is_rejected_only) : ?><button type="submit" name="reject" value="<?= $data['id'] ?>">Reject</button><? endif; ?>
		</td> <?
				if (($ix % $itemsPerRow == $itemsPerRow - 1) || ($ix % $itemsPerRow == count($entry_data) -1)) : ?> 
	</tr><?
				endif;
			endforeach;
	?> 
</tbody>
</table>
</form><?
		endif; // count entry_data

	endif; // loggedin

endif; // db_err


?>
</div>

	</div>
</div>

	</div>
</div>

<?
require_once('assets/foot.php');
?>