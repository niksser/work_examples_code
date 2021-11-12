<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_sm_forsaken_cart_send_email($SUBJECT,$MESSAGE,$EMAILS) {
	$mail = new PHPMailer(true);
	$mail->CharSet = 'UTF-8';
	// $mail->SMTPDebug = SMTP::DEBUG_SERVER;
	$mail->isSMTP();
	$mail->Host = Registry::get('settings')['Emails']['mailer_smtp_host'];
	$mail->SMTPAuth = true;
	$mail->Username = Registry::get('settings')['Emails']['mailer_smtp_username'];
	$mail->Password   = Registry::get('settings')['Emails']['mailer_smtp_password'];
	$mail->SMTPSecure = Registry::get('settings')['Emails']['mailer_smtp_ecrypted_connection'];
	$mail->setFrom(Registry::get('settings')['Emails']['mailer_smtp_username']);
	foreach($EMAILS as $e) {
		$mail->addAddress($e);
	}
	$mail->Subject = $SUBJECT;
	$mail->msgHTML($MESSAGE);
	$r = $mail->send();
}
/*
function fn_sm_forsaken_cart_send_to_bitrix($API_URL, $DIALOG_ID, $MESSAGE) {
	$data = [
		'DIALOG_ID' => $DIALOG_ID,
		'MESSAGE' => $MESSAGE,
	];
	$ch = curl_init($API_URL . 'im.message.add/');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	$out = json_decode(curl_exec($ch), true);
}

function fn_sm_forsaken_cart_search_from_bitrix($API_URL, $MANAGER) {
	$NAME = explode(' ', $MANAGER);
	$data = [
		'FILTER' => [
			'NAME' => $NAME[1],
			'LAST_NAME' => $NAME[0],
		],
	];
	$ch = curl_init($API_URL . 'user.search/');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	$out = json_decode(curl_exec($ch), true);
	return $out['result'][0] ?? false;
}
*/