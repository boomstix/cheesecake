<?

require_once('assets/config.php');
require_once('assets/s3.php');

// validate functions

function isValidPhone($phone) {
	return preg_match('/[0-9 ()+]{8,16}/', $phone);
}

function isValidEmail($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

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

$your_branch = null;
$your_branch_err = '';

$agree_terms = null;
$agree_terms_err = '';

$img_guid = null;
$img_ext = null;
$img_landscape = true;

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
	
		if ($_FILES['upload_image']['size'] > (1024 * $max_file_size_kb)) {
				$img_err = 'Please keep file size under '  . $max_file_size_kb . 'KB.';
		}
		else {
		
			// calculate aspect ratio
			list($width, $height, $type, $attr) = getimagesize($_FILES['upload_image']['tmp_name']);
		
			$img_guid = generateGuid();
			$img_ext = pathinfo($_FILES['upload_image']['name'], PATHINFO_EXTENSION);
			$img_landscape = (($width / $height) >= 1) ? 1 : 0;
			$new_file_name = $img_guid . '.' . $img_ext;
		
			//move the file  
			if ($s3->putObjectFile($_FILES['upload_image']['tmp_name'], $awsUserUploadBucket, $new_file_name, S3::ACL_PUBLIC_READ)) {
				$img_url = 'http://' . $awsUserUploadBucket . '.s3.amazonaws.com/' . $new_file_name;
				$img_msg = 'Click to change<br /> your image';
			}
			else {
				// deal with error
				$img_err = 'There is a problem<br>saving your image.';
			}
		}
	
	}

}

// check the form submission
if ($submitted) {

	$first_name = isset($_POST['first_name']) ? $_POST['first_name'] : array();
	$first_name_err = (mb_strlen($first_name) < 2) ? 'Please supply your first name' : '';

	$last_name = isset($_POST['last_name']) ? $_POST['last_name'] : array();
	$last_name_err = (mb_strlen($last_name) < 2) ? 'Please supply your last name' : '';

	$dads_name = isset($_POST['dads_name']) ? $_POST['dads_name'] : array();
	$dads_name_err = (mb_strlen($dads_name) < 2) ? 'Please supply your Dad\'s name' : '';

	$your_email = isset($_POST['your_email']) ? $_POST['your_email'] : array();
	$your_email_err = !isValidEmail($your_email) ? 'Please supply your email address' : '';

	$your_branch = isset($_POST['your_branch']) ? $_POST['your_branch'] : array();
	$your_branch_err = (mb_strlen($your_branch) < 2) ? 'Please supply your branch' : '';

	$contact_number = isset($_POST['contact_number']) ? $_POST['contact_number'] : array();
	$contact_number_err = !isValidPhone($contact_number) ? 'Please supply your contact number' : '';
	
	$agree_terms = isset($_POST['agree_terms']) ? strtolower(mb_strimwidth($_POST['agree_terms'], 0, 2)) == 'on' : false;
	$agree_terms_err = !$agree_terms;
	
	$img_guid = $img_guid ? $img_guid : (isset($_POST['img_guid']) ? $_POST['img_guid'] : array());
	$img_ext = $img_ext ? $img_ext : (isset($_POST['img_ext']) ? $_POST['img_ext'] : array());
	$img_landscape = $img_landscape ? $img_landscape : (isset($_POST['img_landscape']) ? $_POST['img_landscape'] : array());
	$img_url = 'http://' . $awsUserUploadBucket . '.s3.amazonaws.com/' . $img_guid . '.' . $img_ext;

	$form_err = $first_name_err || $last_name_err || $dads_name_err || $your_email_err || $your_branch_err || $contact_number_err || $agree_terms_err || $img_err;

	if (!$form_err) {

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
						$stmt = $conn->prepare("INSERT INTO `register` (`first_name`,`last_name`,`dads_name`,`your_email`,`contact_number`,`your_branch`,`img_guid`,`ip_address`,`created_at`) VALUES (:first_name,:last_name,:dads_name,:your_email,:contact_number,:your_branch,:ip_address,now())");

						if (!$stmt->execute(array(
							':first_name' => $first_name,
							':last_name' => $last_name,
							':dads_name' => $dads_name,
							':your_email' => $your_email,
							':contact_number' => $contact_number,
							':your_branch' => $your_branch,
							':ip_address' => $_SERVER['REMOTE_ADDR'],
							':img_guid' => $img_guid
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

	}
}

require_once('assets/head.php');

?>
<body>
		<!--[if lt IE 7]>
		<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
		<![endif]-->

		<div class="container">
		
			<div class="stage">
<?

if ($submitted && !($form_err || $db_err != '')) : ?>
<div class="row">

	<div class="eight offset-by-two columns">
	
		<h3>Thanks for entering!</h3>
		
		<h3>Remember to keep voting!</h3>
		
	</div>

</div>
<?
elseif ($db_err) :
?>
<div class="row">

	<div class="eight offset-by-two columns">
	
		<h3>We have a problem</h3>
		
		<p>We have been unable to save your Dad's entry.</p>
		
		<? if (gettype($db_ex) == 'string') : ?>
		<?= $db_ex ?><?
		elseif (strpos($db_ex->getMessage(), 'Duplicate entry') > -1) : ?>
		
		<p>The email address <?= $your_email ?> is already registered!</p>
		
		<? else : ?>

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
										<div class="frame-holder<?= $img_landscape ? ' horz' : ' vert' ?><?= (strlen($img_err) > 0) ? ' error' : '' ?>">
											<input type="file" id="upload_image" name="upload_image" title="" class="frame frame-layer" />
											<div class="frame text-layer"><?= strlen($img_err) > 0 ? $img_err : $img_msg ?></div>
											<div class="frame opacity-layer"></div>
											<div class="img-layer-outer">
												<div class="img-layer-inner">
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
										<div class="six columns <?= $first_name_err ? 'error' : ''; ?>">
											<label for="first_name">Your first name</label>
											<div class="controls">
												<input type="text"id="first_name" name="first_name" maxlength="64" value="<?= mb_strlen($first_name_err) > 0 ? '' : $first_name ?>" placeholder="<?= $first_name_err ?>" />
											</div>
										</div>
										<div class="six columns <?= $last_name_err ? 'error' : ''; ?>">
											<div class="controls">
											<label for="last_name">Your last name</label>
												<input type="text" id="last_name" name="last_name" maxlength="64" value="<?= mb_strlen($last_name_err) > 0 ? '' : $last_name ?>" placeholder="<?= $last_name_err ?>" />
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="six columns <?= $dads_name_err ? 'error' : ''; ?>">
											<label for="dads_name">Dad's name</label>
											<div class="controls">
												<input type="text" id="dads_name" name="dads_name" maxlength="64" value="<?= mb_strlen($dads_name_err) > 0 ? '' : $dads_name ?>" placeholder="<?= $dads_name_err ?>" />
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="twelve columns <?= $contact_number_err ? 'error' : ''; ?>">
											<label for="your_email">Your email address</label>
											<div class="controls">
												<input type="text" id="your_email" name="your_email" maxlength="128" value="<?= mb_strlen($your_email_err) > 0 ? '' : $your_email ?>" class="input-large" placeholder="<?= $your_email_err ?>" />
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="six columns <?= $contact_number_err ? 'error' : ''; ?>">
											<label for="contact_number">Contact Number</label>
											<div class="controls">
												<input type="text" id="contact_number" name="contact_number" maxlength="64" value="<?= mb_strlen($contact_number_err) > 0 ? '' : $contact_number ?>" placeholder="<?= $contact_number_err ?>" />
											</div>
										</div>
										<div class="six columns <?= $your_branch_err ? 'error' : ''; ?>">
											<label for="your_branch">Your branch</label>
											<div class="controls">
												<input type="text"id="your_branch" name="your_branch" maxlength="64" value="<?= mb_strlen($your_branch_err) > 0 ? '' : $your_branch ?>" data-provide="typeahead" placeholder="<?= $your_branch_err ?>" />
											</div>
										</div>
									</div>
									<div class="row form-elem">
										<div class="twelve columns <?= $agree_terms_err ? 'error' : ''; ?>">
											<div class="controls">
												<label class="checkbox">
													<input type="checkbox" id="agree_terms" name="agree_terms" <?= $agree_terms ? ' checked="checked"' : '' ?> />
													I have read and agree to the <a href="#"  data-reveal-id="terms-modal" >terms and conditions</a>.
												</label>
											</div>
										</div>
									</div>

									<div class="row">
										<div class="five offset-by-three enter">
											<button id="submit_button" name="submit_button">Save</button>
										</div>
									</div>
									
								</div><!-- .span6 -->
							</div><!-- .row-fluid -->
							
						</form>
<?
endif;
?>
			</div><!-- .stage -->

			<!-- Modal -->
			<div id="terms-modal" class="reveal-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-body">
					<p>Terms and Conditions</p>
				</div>
				<div class="modal-footer">
					<a class="close-reveal-modal" data-dismiss="modal" aria-hidden="true">&times;</a>
				</div>
			</div>
			
			<div id="win-modal" class="reveal-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-body">
					<p>What you can win!</p>
				</div>
				<div class="modal-footer">
					<a class="close-reveal-modal" data-dismiss="modal" aria-hidden="true">&times;</a>
				</div>
			</div>
			
		<footer>
		<p>&copy; Company 2012</p>
		</footer>

		</div> <!-- /container -->

<?
if ($img_guid != '') : ?>
<script>
$(function(){

	var img = $('#new-img'), img_url = '<?= $img_url ?>';
	img.hide();
	img.attr('src', img_url);
	// setup the img layer to fade in when the img is loaded
	img.on('load', function(){
		if (img_url != '') {
			img.fadeIn();
			$(img.parents('.frame-holder')[0]).addClass('hover');
		}
	});

});
</script><?
endif;

require_once('assets/foot.php');
?>