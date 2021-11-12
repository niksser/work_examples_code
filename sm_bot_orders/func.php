<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_sm_bot_orders_send($API_URL, $DIALOG_ID, $MESSAGE) 
{
	$data = [
		'DIALOG_ID' => $DIALOG_ID,
		'MESSAGE' => $MESSAGE,
	];
	$ch = curl_init($API_URL . 'im.message.add/');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	return json_decode(curl_exec($ch), true);
}
