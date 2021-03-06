<?php

function hashCreate($name) {
	return $_SESSION[$name] = bin2hex(random_bytes(24));
} // hashCreate

function hashExists($name, $hash) {
	return isset($_SESSION[$name]) && ($_SESSION[$name] == $hash);
} // hashExists

function isValidEmail($address) {

	if (filter_var($address, FILTER_VALIDATE_EMAIL) == FALSE) return false;
	
	// explode out local and domain
	list($local,$domain)=explode('@',$address);
	
	$localLength = strlen($local);
	$domainLength = strlen($domain);
	
	return
		// check for proper lengths
		($localLength > 0 && $localLength < 65) &&
		($domainLength > 3 && $domainLength < 256) &&
		// and if it's a valid domain
		( checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A') );
		
} // isValidEmail

function postNotMailHeaderSafe($indexes) {
	foreach ($indexes as $index)
		if (array_key_exists($index, $_POST) && (
			strpos($_POST[$index], "\n") || strpos($_POST[$index], "\r")
		)) return true;
	return false;
} // postNotMailHeaderSafe

function mailCleanPost($index) {
	return str_replace(["\r", "\n", ';'], ' ', $_POST[$index]);
} // mailCleanPost

function formMail() {

	$subject = mailCleanPost('subject');
	$email = mailCleanPost('email');
	$from = mailCleanPost('name');
		
	$header =
		'From: ' . $from . ' - ' .
		mailCleanPost('name') . ' <' . $email . ">\r\n" .
		'Reply-To: ' . $email . "\r\n" . 
		'X-Mailer: PHP/' . phpversion() . "\r\n" .
		'Content-Type: text/plain';
		
	$message = htmlspecialchars($_POST['message']) . '
		Logged IP: ' . $_SERVER['REMOTE_ADDR'] . '
		UA String: ' . $_SERVER['HTTP_USER_AGENT'];

	return mail(
		'ccx@conceal.network',
		$subject, 
		$message,
		$header
	);

} // formMail

if (
	!empty($_POST['contactHash']) &&
    hashExists('contactHash',$_POST['contactHash'])
) {
    if (postNotMailHeaderSafe([
		'name',
		'email',
		'subject'
    ]) || !isValidEmail($_POST['email'])) {
        header('HTTP/1.0 403 Forbidden');
		echo json_encode([
			'title' => 'Error',
			'content' => ''
		]);
    } else if (formMail()) {
        header('HTTP/1.0 200 Successful');
		echo json_encode([
			'title' => 'Message Sent Successfully',
			'content' => ''
		]);
    } else {
        ob_clean();
		header('HTTP/1.0 500 Internal Server Error');
		echo json_encode([
			'title' => 'Error - Unable to Send Message',
			'content' => ''
		]);
    }
}

?>