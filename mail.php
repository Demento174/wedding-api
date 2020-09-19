<?php
require 'wp-load.php';

if (isset($_POST['phone'])) {
//	$admin_email = get_option('admin_email');
	$emails = get_field('emails', 16);
	$to = '';
	foreach ($emails as $n => $email) {
		$to .= $email['email'];
		if ($n !== (count($emails)-1)) $to .= ', ';
	}
	
	$msg = "
		тел. {$_POST['phone']} \n
		дата: {$_POST['date']} \n
		блок: {$_POST['block']} \n
	";
	wp_mail($to, 'письмо с сайта, тема' . $_POST['block'], $msg);
wp_send_json_success($_POST);
}