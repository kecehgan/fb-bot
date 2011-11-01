<?php

/**
 * PHP Curl status update script
 * @since Sep 2010
 * @version 1.0
 * @link http://360percents.com/posts/php-curl-status-update-working-example/
 * @author Luka Pušić <pusic93@gmail.com>
 */
/**
 * Required parameters
 */
$status = 'It Works :)';
$email = 'your@email.com';
$pass = 'your password';

/**
 * Call the update function with your email, password and status parameters.
 */
update(urlencode($email), urlencode($pass), $status);

function update($email, $pass, $status) {
    /**
     * Optional parameters
     */
    $uagent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)';
    $cookies = 'cookie.txt';

    /**
     * prepare status (Facebook allows only 420 chars)
     */
    if (strlen($status) > 419) {
	$status = urlencode(substr($status, 0, 419));
	echo "[*] Status was stripped to 420 characters!\n\n";
    } else {
	$status = urlencode($status);
    }

    function postify($arr) {
	$fields_string = '';
	foreach ($arr as $key => $value) {
	    $fields_string .= $key . '=' . $value . '&';
	}
	return rtrim($fields_string, '&');
    }

    /**
     * GET: http://m.facebook.com/
     * Parse the webpage and collect login parameters
     * (chartest, lsd, formaction).
     */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
    curl_setopt($ch, CURLOPT_URL, "http://m.facebook.com/");
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $buf = curl_exec($ch);

    echo "\n[+] Sending GET request to: http://m.facebook.com/\n\n";

    preg_match("/<form method=\"post\" class=\"mobile-login-form\" id=\"login_form\" action=\"(.*)\"/U", $buf, $formaction);
    urlencode(preg_match("/<input type=\"hidden\" name=\"charset_test\" value=\"(.*)\" \/>/U", $buf, $chartest));
    //preg_match("/<input type=\"hidden\" name=\"lsd\" value=\"(.*)\" autocomplete=\"off\" \/>/U", $buf, $lsd);
    preg_match("/<input type=\"hidden\" name=\"post_form_id\" value=\"(.*)\" \/>/U", $buf, $formid);

    curl_close($ch);
    unset($buf);
    unset($ch);

    /**
     * LOGIN: POST to facebook login form (formaction) with previously
     * collected parameters lsd, chartest, email, pass and login.
     */
    $postdata = array(
	'lsd' => '',
	'post_form_id' => $formid[1],
	'charset_test' => $chartest[1],
	'email' => $email,
	'pass' => $pass,
	'ajax' => '0',
	'width' => '0',
	'pxr' => '0',
	'gps' => '0',
	'version' => '1',
	'login' => 'Log+in'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
    curl_setopt($ch, CURLOPT_URL, $formaction[1]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, postify($postdata));

    $buf = curl_exec($ch);
    echo $buf;

    echo "[+] Sending POST data to: " . html_entity_decode(urldecode($formaction[1])) . "\n";
    print_r($postdata);
    echo "\n\n";

    curl_close($ch);
    unset($buf);
    unset($ch);
    unset($postdata);
    /**
     * Fetch facebook.com/home.php to Collect parameters from the form which
     * updates status (formaction, chartest, dtsg, formid). This step is
     * important because we want to avoid using CURLOPT_FOLLOWLOCATION.
     */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
    curl_setopt($ch, CURLOPT_URL, 'http://m.facebook.com/home.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $buf = curl_exec($ch); //execute the curl command

    echo "[+] Fetching http://m.facebook.com/home.php\n\n";

    preg_match("/<form method=\"post\" id=\"composer_form\" action=\"\/a\/home.php(.*)\">/U", $buf, $formaction);
    preg_match("/<input type=\"hidden\" name=\"charset_test\" value=\"(.*)\" \/>/U", $buf, $chartest);
    preg_match("/<input type=\"hidden\" name=\"fb_dtsg\" value=\"(.*)\" autocomplete=\"off\" \/>/U", $buf, $dtsg);
    preg_match("/<input type=\"hidden\" name=\"post_form_id\" value=\"(.*)\" \/>/U", $buf, $formid);

    curl_close($ch);
    unset($buf);
    unset($ch);

    /**
     * 3. UPDATE STATUS: Use previously collected form data to send a post query
     * which will update our status.
     */
    $postdata = array(
	'charset_test' => $chartest[1],
	'fb_dtsg' => $dtsg[1],
	'post_form_id' => $formid[1],
	'status' => $status,
	'update' => 'Share'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
    curl_setopt($ch, CURLOPT_URL, 'http://m.facebook.com/a/home.php' . $formaction[1]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, postify($postdata));

    $buf = curl_exec($ch);

    echo "[+] Sending POST data to: http://m.facebook.com/a/home.php" . html_entity_decode($formaction[1]) . "\n";
    print_r($postdata);
    echo "\n\n";

    /**
     * Something will probably appear in $buf if it fails :)
     */
    curl_close($ch);
    if (strlen($buf) > 0) {
	echo "[ERR] Status update response:$buf\n\n";
    }

    unset($buf);
    unset($ch);
    unset($postdata);

    /**
     * Logout, end funtion update and return.
     */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
    curl_setopt($ch, CURLOPT_URL, 'http://m.facebook.com/logout.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);

    curl_exec($ch);

    curl_close($ch);
    echo "[+] Logging out: http://m.facebook.com/logout.php\n\n";
    unset($ch);

    return 0;
}

?>
