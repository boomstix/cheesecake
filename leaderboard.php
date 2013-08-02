<?

require_once('./assets/config.php');

//
$loggedin = false;
$submitted = false;
$search_term = false;

// whether the form was submitted
$submitted = isset($_POST['submit_button']);
$search_term = $_POST['search_term'];

// whether the form was submitted
$submitted = isset($_POST['submit_button']);

$leader_data = array();

if ($submitted) {

	if (SHA1($_POST['username'] . $_POST['password']) == $leaderboard_login_hash) {
		$_SESSION['logged_in'] = session_id;
	}

}

if (isset($_SESSION['logged_in'])) {
	
	$loggedin = true;
	
	// retrieve the leaderboard
	try {

		$conn = new PDO("mysql:host=$db_addr;dbname=$db_schema",$db_user,$db_pass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
		if (!$conn) {
			$db_ex = "Could not open connection to the database";
			$db_err = true;
		
		}
		else {
	
			// grab the highest voted entries
		
			$sql_str = "
SELECT r.`id`, `dads_name`, `your_email`, `created_at`, `contact_number`, `battle_count`, `is_approved`, `vote_count`, `img_guid`, `ratio`
FROM `register` r
LEFT JOIN (
	SELECT registered_id, AVG(calc.ratio) AS ratio
	FROM (
		SELECT registered_id, sum(vote_count) AS ratio
		FROM vote_log
		GROUP BY registered_id, ip_address
	) AS calc
	GROUP BY registered_id
) AS v ON v.registered_id = r.id
WHERE !(is_approved IS NULL)
" . ((strlen($search_term) == 0) ? "
AND (first_name like concat('%', '$search_term', '%') OR last_name like concat('%', '$search_term', '%') OR dads_name like concat('%', '$search_term', '%') OR your_email like concat('%', '$search_term', '%'))"
: "") . "
ORDER BY is_approved DESC, vote_count DESC, ratio ASC, battle_count DESC, created_at ASC
LIMIT 20;
;";
			$stmt = $conn->prepare($sql_str);
			$stmt->setFetchMode(PDO::FETCH_BOTH);
		
			if (!$stmt->execute())
			{
				$db_err = true;
				$db_ex = 'Select leaderboard statement failed';
			}
			else {
				$leader_data = $stmt->fetchall();
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

require_once('./assets/head.php');


?>
<body class="admin">

<div id="wrap">
<div id="main">

<div class="container">
	<div class="stage">
	
<h2>Leaderboard</h2>

<div class="content">

<h3>Leaderboard</h3>

	
	<?


if ($db_err) :

?>
db error
<?= $db_ex->getMessage() ?><?

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
		if (count($leader_data) == 0) : ?>
		<p>No votes!</p>
<?
		
		else : ?>

<table class="table" style="width: 100%;">
<thead>
	<tr>
		<th>Entry</th>
		<th>Created</th>
		<th>Contact No</th>
		<th>Email</th>
		<th>Battles</th>
		<th>Score</th>
		<th>Confidence</th>
	</tr>
</thead>
<tbody><?
	foreach ($leader_data as $ix => $data) : ?>
	<tr<?= $data['is_approved'] == '1' ? '' : ' class="rejected" title="Entry has been rejected"' ; ?>>
		<td><?= $data['dads_name'] ?></td>
		<td><?= $data['created_at'] ?></td>
		<td><?= $data['contact_number'] ?></td>
		<td><?= $data['your_email'] ?></td>
		<td class="text-center"><?= $data['battle_count'] ?></td>
		<td class="text-center"><strong><?= $data['vote_count'] ?></strong></td>
		<td class="text-center"><?= $data['ratio'] == 0 ? 0 : round(1 / $data['ratio'] * 100, 2) ?>%</td>
	</tr><?
	endforeach;
	?>
</tbody>
</table><?
			endif;

	endif; // loggedin

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