<?

require_once('../assets/config.php');

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

$states = array('NIL','NSW','ACT','VIC','TASS','QLD','SA','NT','WA','NZ');

if ($submitted) {

	if (SHA1($_POST['username'] . $_POST['password']) == $franchise_login_hash) {
		setcookie("orfenticsecret", true, time()+3600);
		header('Location: /secret');
	}

}

if (isset($_COOKIE['orfenticsecret'])) {
	
	$your_state = isset($_POST['your_state']) ? $_POST['your_state'] : array();
	if ($submitted) {
		$your_state_err = ($your_state < 0) ? 'Please supply your state' : '';
	}

	$your_branch = isset($_POST['your_branch']) ? $_POST['your_branch'] : array();
	if ($submitted) {
		$your_branch_err = ($your_branch < 0) ? 'Please supply your bakery' : '';
	}

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
select ifnull(bc.count, 0) as entrants, ifnull(bc.votes, 0) as votes, b.store_name, b.state_id
from (select count(id) as count, sum(vote_count) as votes, your_branch from register group by your_branch) as bc
right join branch b on b.id = bc.your_branch
order by bc.count desc, bc.votes desc, b.store_name;";
			$stmt = $conn->prepare($sql_str);
			$stmt->setFetchMode(PDO::FETCH_BOTH);
		
			if (!$stmt->execute(array(':branch' => $your_branch)))
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

require_once('../assets/head.php');


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
		<p>No votes!</p><?		
		endif; 
?>
<table class="table" style="width: 100%;">
<thead>
	<tr>
		<th>Bakery</th>
		<th>State</th>
		<th>Entries</th>
		<th>Votes</th>
	</tr>
</thead>
<tbody><?
	foreach ($leader_data as $ix => $data) : ?>
	<tr>
		<td><?= $data['store_name'] ?></td>
		<td><?= $states[$data['state_id']] ?></td>
		<td class="text-center"><?= $data['entrants'] ?></td>
		<td class="text-center"><strong><?= $data['votes'] ?></strong></td>
	</tr><?
	endforeach;
	?>
</tbody>
</table><?

	endif; // loggedin

endif; // db_err

?>
</div>

	</div>	
</div>

	</div>
</div>

<?
require_once('../assets/scripts.php');
require_once('../assets/foot.php');
?>