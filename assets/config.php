<?

$db_addr = '127.0.0.1';
$db_user = 'cheese';
$db_pass = 'cakeshop';
$db_schema = 'cheesecake';

$support_email = 'cheese@cakeshop.com';

$leaderboard_login_hash = '2589ae3b10daecb373857aec5fb76e06d7e278a2';
$franchise_login_hash = '';

$awsAccessKey = 'AKIAJNCR3PWSSU3RUHZA';
$awsSecretKey = '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';
$awsUserUploadBucket = 'dads-au';
$max_file_size_kb = 200;

$analytics_acct = 'UA-1933701-10';
$analytics_url = 'pimpupdad.com.au';

// $analytics_acct = 'UA-1933701-11';
// $analytics_url = 'pimpupdad.co.nz';


/**
 * Generate Globally Unique Identifier (GUID)
 * E.g. 2EF40F5A-ADE8-5AE3-2491-85CA5CBD6EA7
 *
 * @param boolean $include_braces Set to true if the final guid needs to be wrapped in 
 * curly braces.
 * @return string
 */
function generateGuid($include_braces = false) {
	if (function_exists('com_create_guid')) {
		if ($include_braces === true) {
			return com_create_guid();
		} else {
			return substr(com_create_guid(), 1, 36);
		}
	}
	else {
		mt_srand((double) microtime() * 10000);
		$charid = strtoupper(md5(uniqid(rand(), true)));

		$guid = substr($charid,  0, 8) . '-' .
		substr($charid,  8, 4) . '-' .
		substr($charid, 12, 4) . '-' .
		substr($charid, 16, 4) . '-' .
		substr($charid, 20, 12);

		if ($include_braces) {
			$guid = '{' . $guid . '}';
		}

		return $guid;
		}
	}

?>