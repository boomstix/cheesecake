<?

require_once('./assets/config.php');
require_once('./assets/S3.php');
require_once('./assets/class.upload.php');


// initialise our validation vars

$first_name = null;
$first_name_err = '';

$last_name = null;
$last_name_err = '';

$dads_name = null;
$dads_name_err = '';

$your_email = null;
$your_email_err = '';

$contact_number = null;
$contact_number_err = '';

$your_state = null;
$your_state_err = '';

$your_branch = null;
$your_branch_err = '';

$why_for = null;
$your_receipt = null;

$agree_terms = null;
$agree_terms_err = '';

$img_guid = null;
$img_ext = null;
$img_landscape = null;

$img_msg = 'Click to select<br> your image';
$img_err = false;

// whether the form was submitted
$submitted = isset($_POST['submit_button']);
$image_submitted = isset($_POST['upload_button']);

$already_registered = false;

// whether submission of the form resulted in an error
$form_err = false;
// whether writing the data to the db resulted in a database error
$db_err = false;
$db_ex = null;

// did we get an image upload?
if ($image_submitted || $submitted) {
	
	// save to s3
	$s3 = new S3($awsAccessKey, $awsSecretKey);
	
	// we have to add img guid to the form for saving on submit_button click
	// if there is a failure, we need to reset the guid to null

	if ($_FILES['upload_image']['name']) {

		// calculate aspect ratio
		list($width, $height, $type, $attr) = getimagesize($_FILES['upload_image']['tmp_name']);
		
		$img_guid = generateGuid();
		$img_ext = pathinfo($_FILES['upload_image']['name'], PATHINFO_EXTENSION);
		$new_file_name = $img_guid . '.' . $img_ext;
		// read exif data to see if the camera rotated the image 
		$exif = exif_read_data($_FILES['upload_image']['tmp_name']);
		
		$handle = new upload($_FILES['upload_image']);
		if ($handle->uploaded) {
			$handle->file_new_name_body_add = '_resized';
			$handle->image_resize = true;
			$handle->image_ratio = true;
			$handle->image_x = 300;
			$handle->image_y = 300;
			if ($exif['Orientation'] == 6) {
				$handle->image_rotate = '90';
			}
			$handle->process('./upload/');
			if ($handle->processed) {

				$img_landscape = (($handle->image_dst_x  / $handle->image_dst_y) >= 1) ? 1 : 0;
		
				//move the file  
				// if ($s3->putObjectFile($_FILES['upload_image']['tmp_name'], $awsUserUploadBucket, $new_file_name, S3::ACL_PUBLIC_READ)) {
				if ($s3->putObjectFile('./upload/' . $handle->file_dst_name, $awsUserUploadBucket, $new_file_name, S3::ACL_PUBLIC_READ)) {
					$img_url = $img_domain . $new_file_name;
					$img_msg = 'Click to chose<br />a different image';
				}
				else {
					// deal with error
					$img_err = 'There is a problem<br>saving your image.';
				}
				// clean up
				$handle->clean();
				unlink('./upload/' . $handle->file_dst_name);
			} else {
				$img_err = $handle->error;
			}
		}
		
	}

	$first_name = isset($_POST['first_name']) ? $_POST['first_name'] : array();
	if ($submitted) {
		$first_name_err = (strlen($first_name) < 2) ? 'Please supply your first name' : '';
	}

	$last_name = isset($_POST['last_name']) ? $_POST['last_name'] : array();
	if ($submitted) {
		$last_name_err = (strlen($last_name) < 2) ? 'Please supply your last name' : '';
	}

	$dads_name = isset($_POST['dads_name']) ? $_POST['dads_name'] : array();
	if ($submitted) {
		$dads_name_err = (strlen($dads_name) < 2) ? 'Please supply your Dad\'s name' : '';
	}

	$your_email = isset($_POST['your_email']) ? $_POST['your_email'] : array();
	if ($submitted) {
		$your_email_err = !isValidEmail($your_email) ? 'Please supply your email address' : '';
	}

	$your_state = isset($_POST['your_state']) ? $_POST['your_state'] : array();
	if ($submitted) {
		$your_state_err = ($your_state < 0) ? 'Please supply your state' : '';
	}

	$your_branch = isset($_POST['your_branch']) ? $_POST['your_branch'] : array();
	if ($submitted) {
		$your_branch_err = ($your_branch < 0) ? 'Please supply your bakery' : '';
	}

	$contact_number = isset($_POST['contact_number']) ? $_POST['contact_number'] : array();
	if ($submitted) {
		$contact_number_err = !isValidPhone($contact_number) ? 'Please supply your contact number' : '';
	}

	$why_for = isset($_POST['why_for']) ? $_POST['why_for'] : array();
	$your_receipt = isset($_POST['your_receipt']) ? $_POST['your_receipt'] : null;
	
	$agree_terms = isset($_POST['agree_terms']) ? strtolower(substr($_POST['agree_terms'], 0, 2)) == 'on' : false;
	if ($submitted) {
		$agree_terms_err = !$agree_terms;
	}
	
	$img_guid = $img_guid ? $img_guid : (isset($_POST['img_guid']) ? $_POST['img_guid'] : array());
	$img_ext = $img_ext ? $img_ext : (isset($_POST['img_ext']) ? $_POST['img_ext'] : array());
	// value passed from form 1st
	// value from  
	$img_landscape = $img_landscape ? $img_landscape : (isset($_POST['img_landscape']) ? $_POST['img_landscape'] : array());
	
	$img_url = $img_guid == '' ? '' : $img_domain . $img_guid . '.' . $img_ext;

	if ($submitted) {
		$img_err = (strlen($img_guid) == 0) ? 'Please select and<br/>upload an image' : '';
	}

	$form_err = $first_name_err || $last_name_err || $dads_name_err || $your_email_err || $your_branch_err || $contact_number_err || $agree_terms_err || $img_err;

	if (!$form_err && $submitted) {

		// now submit to the database.

		try {
			$conn = new PDO("mysql:host=$db_addr;dbname=$db_schema",$db_user,$db_pass);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			if (!$conn) {
				$db_ex = "Could not open connection to the database";
				$db_err = true;
			}
			else {
			
				// does this email address already exist?
				$stmt = $conn->prepare("SELECT id from `register` WHERE your_email = :your_email;");
				$stmt->setFetchMode(PDO::FETCH_BOTH);

				if (!$stmt->execute(array(':your_email' =>  $your_email)))
				{
					$db_err = true;
					$db_ex = 'Select email statement failed';
				}
				else {
					$data = $stmt->fetchAll();
					
					if (count($data) > 0) {
						$your_email_err = 'That email address is already registered';
					}
					else {
						$stmt = $conn->prepare("
INSERT INTO `register` (`first_name`,`last_name`,`dads_name`,`your_email`,`contact_number`,`your_branch`,`img_guid`,`img_ext`,`img_landscape`,`ip_address`,`why_for`,`your_receipt`,`created_at`)
VALUES (:first_name,:last_name,:dads_name,:your_email,:contact_number,:your_branch,:img_guid,:img_ext,:img_landscape,:ip_address,:why_for,:your_receipt,now())");

						if (!$stmt->execute(array(
							':first_name' => $first_name,
							':last_name' => $last_name,
							':dads_name' => $dads_name,
							':your_email' => $your_email,
							':contact_number' => $contact_number,
							':your_branch' => $your_branch,
							':img_guid' => $img_guid,
							':img_ext' => $img_ext,
							':img_landscape' => $img_landscape,
							':ip_address' => $_SERVER['REMOTE_ADDR'],
							':why_for' => $why_for,
							':your_receipt' => $your_recipt
							))
						)
						{
							$db_err = true;
							$db_ex = 'Insert statement failed';
						}
					}
				}
			
			}
			$conn = null;
		}
		catch (PDOException $e) {
			$db_err = true;
			$db_ex = $e;
			$conn = null;
		}
		// double check there's no email error
		$form_err = $first_name_err || $last_name_err || $dads_name_err || $your_email_err || $your_branch_err || $contact_number_err || $agree_terms_err || $img_err;
		
		if (!$form_err && !$db_err) {
			header("Location: index.php?entry_success");
			echo $img_landscape . ':' . $img_ext;
			exit;
		}
	}
}

require_once('assets/head.php');

?>
<body class="enter">

<div id="wrap">
<div id="main">

		<!--[if lt IE 7]>
		<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
		<![endif]-->

		<div class="container">
		
			<div class="stage">
			
			<a href="#" data-reveal-id="win-modal" class="what-can-i-win top">What Can I Win?</a>
			
			<a href="/" class="btn-back top">Back to voting</a>

			<h2><a href="/">Pimp Up Your Dad</a></h2>
			
			<h3>Enter your dad for a chance to win</h3>

<?

if ($competition_running) :

/*
echo $submitted ? 'submitted' : 'not submitted';
echo $form_err ? 'form_err' : 'not form_err';
echo $db_err ? 'db_err' : 'not db_err';
*/

if ($submitted && !($form_err || $db_err != '')) :

	// success - pass through - we should have been redirected by now

elseif ($db_err) :
?>
<div class="content row">

	<div class="eight offset-by-two columns">
	
		<h4>We have a problem</h4>
		
		<p>We have been unable to save your dad's entry.</p>
		
		<? if (gettype($db_ex) == 'string') : ?>
		<?= $db_ex ?><?
		elseif (strpos($db_ex->getMessage(), 'Duplicate entry') > -1) : ?>
		
		<p>The email address <?= $your_email ?> is already registered!</p>
		
		<? else : 
		echo $db_ex;
		?>

		<p>If this issue persists, please contact <a href="mailto:<?= $support_email ?>"><?= $support_email ?></a> to resolve the issue.</p>
		
		<? endif ?>
	
	</div>

</div>
<?
else :
?>

						<!--form-->
						<form class="form" method="post" enctype="multipart/form-data">
							<input type="hidden" name="img_guid" value="<?= $img_guid ?>" />
							<input type="hidden" name="img_ext" value="<?= $img_ext ?>" />
							<input type="hidden" name="img_landscape" value="<?= $img_landscape ? "1" : "0" ?>" />
							<div class="row">
							
								<div class="five offset-by-one columns">
									<div class="row form-elem">
										<!--upload img-->
										<div class="frame-holder<?= $img_landscape == 1 ? ' horz' : ' vert' ?><?= (strlen($img_err) > 0) ? ' error' : '' ?>" style="visibility:hidden;">
											<input type="file" id="upload_image" name="upload_image" title="" class="frame frame-layer" />
											<div class="frame text-layer"><?= strlen($img_err) > 0 ? $img_err : $img_msg ?></div>
											<div class="frame-inner opacity-layer"></div>
											<div class="img-layer-outer vert-outer vert-full">
												<div class="img-layer-inner vert-inner">
													<img id="new-img" />
												</div>
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="five offset-by-two enter">
											<button id="upload_button" name="upload_button">Upload Image</button>
										</div>
									</div>
								</div>
						
								<div class="six columns">
									<div class="row form-elem">
										<div class="six columns mandatory <?= $first_name_err ? 'error' : ''; ?>">
											<label class="cooper" for="first_name">Your first name</label>
											<div class="controls">
												<input type="text"id="first_name" name="first_name" maxlength="64" value="<?= strlen($first_name_err) > 0 ? '' : $first_name ?>" placeholder="<?= $first_name_err ?>" />
											</div>
										</div>
										<div class="six columns mandatory <?= $last_name_err ? 'error' : ''; ?>">
											<div class="controls">
											<label class="cooper" for="last_name">Your last name</label>
												<input type="text" id="last_name" name="last_name" maxlength="64" value="<?= strlen($last_name_err) > 0 ? '' : $last_name ?>" placeholder="<?= $last_name_err ?>" />
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="six columns mandatory <?= $dads_name_err ? 'error' : ''; ?>">
											<label class="cooper" for="dads_name">Your dad's name</label>
											<div class="controls">
												<input type="text" id="dads_name" name="dads_name" maxlength="64" value="<?= strlen($dads_name_err) > 0 ? '' : $dads_name ?>" placeholder="<?= $dads_name_err ?>" />
											</div>
										</div>
										<div class="six columns mandatory <?= $contact_number_err ? 'error' : ''; ?>">
											<label class="cooper" for="contact_number">Your contact number</label>
											<div class="controls">
												<input type="text" id="contact_number" name="contact_number" maxlength="64" value="<?= strlen($contact_number_err) > 0 ? '' : $contact_number ?>" placeholder="<?= $contact_number_err ?>" />
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="six columns mandatory <?= $contact_number_err ? 'error' : ''; ?>">
											<label class="cooper" for="your_email">Your email address</label>
											<div class="controls">
												<input type="text" id="your_email" name="your_email" maxlength="128" value="<?= strlen($your_email_err) > 0 ? '' : $your_email ?>" class="input-large" placeholder="<?= $your_email_err ?>" />
											</div>
										</div>
										<div class="six columns">
											<label class="cooper" for="your_receipt">Your proof of purchase</label>
											<div class="controls">
												<input type="text" id="your_receipt" name="your_receipt" maxlength="32" value="<?= $your_receipt ?>" placeholder="Enter receipt number" />
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="six columns mandatory <?= $your_state_err ? 'error' : ''; ?>">
											<label class="cooper" for="your_state">Your state</label>
											<div class="controls">
												<select id="your_state" name="your_state">
													<option>Please select</option>
												</select>
											</div>
										</div>
										<div class="six columns mandatory <?= $your_branch_err ? 'error' : ''; ?>">
											<label class="cooper" for="your_branch">Your bakery</label>
											<div class="controls">
												<select id="your_branch" name="your_branch">
													<option>Please select</option>
												</select>
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="twelve columns">
											<label class="cooper" for="why_for">Why we should pimp up your dad</label>
											<div class="controls">
													<textarea id="why_for" name="why_for"><?= stripslashes($why_for) ?></textarea>
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="twelve columns <?= $agree_terms_err ? 'error' : ''; ?>">
											<div class="controls">
												<label class="checkbox">
													<input type="checkbox" id="agree_terms" name="agree_terms" <?= $agree_terms ? ' checked="checked"' : '' ?> />
													<div class="copy">I have read and agree to the <a href="#"  data-reveal-id="terms-modal" >terms and conditions</a>.</div>
												</label>
											</div>
										</div>
									</div>

									<div class="row">
										<div class="five offset-by-three columns enter text-center"><?
										if ($img_guid != '') : ?>
											<button id="submit_button" name="submit_button">Enter</button><?
										endif; ?>
										</div>
									</div>
									
								</div><!-- .span6 -->
							</div><!-- .row-fluid -->
							
						</form>

		<a href="/?mobile" class="vote-for-a-dad bottom">Vote for a dad</a>

		<a href="#" data-reveal-id="win-modal" class="what-can-i-win bottom">What can I win?</a>


<?
endif; // $submitted

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
<?
?>

		</div> <!-- .container -->
</div><!-- #main -->
</div><!-- #wrap -->
<?
require_once('./assets/overlays.php');
require_once('./assets/scripts.php');
?>
<script>
var your_state_val = '<?= $your_state ?>', your_branch_val = '<?= $your_branch ?>';
$(function(){

	var img = new ImgLoadFadeIn('#new-img', '<?= $img_url ?>')

});
</script>
<?
require_once('./assets/foot.php');
?>