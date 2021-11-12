<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if($mode == 'complete') {
	$bitrix_url = Registry::get('config.bitrix.API_URL');
	$portals = Registry::get('config.bitrix.checkout_contacts');
	$host = Registry::get('config.current_host');
	$contacts = [
		'emails' => [],
		'bitrix_ids' => [],
	];
	$b2b = fn_sm_prices_is_confirmed_customer();
	if(isset($portals[$host]) && !$b2b) {
		$contacts = $portals[$host];
		$message = db_get_row("SELECT * FROM ?:sm_forsaken_cart_orders WHERE ?:sm_forsaken_cart_orders.order_id = ?i AND ?:sm_forsaken_cart_orders.user_id = ?i", $_REQUEST['order_id'], $auth['user_id']);
		$order = db_get_row("SELECT * FROM ?:orders WHERE ?:orders.order_id = ?i AND ?:orders.user_id = ?i", $_REQUEST['order_id'], $auth['user_id']);
		if(empty($message) && !empty($order)) {
			$message_data = array (
				'order_id' => $_REQUEST['order_id'],
				'user_id' => $auth['user_id'],
			);
			$message_id = db_query("INSERT INTO ?:sm_forsaken_cart_orders ?e", $message_data);
			$user_data = db_get_row("SELECT ?:users.user_id, ?:users.email, ?:users.phone FROM ?:users WHERE ?:users.user_id = ?i", $auth['user_id']);
			$user_data['order'] = db_get_array("SELECT * FROM ?:order_details LEFT JOIN ?:product_descriptions ON (?:product_descriptions.product_id = ?:order_details.product_id) WHERE ?:order_details.order_id = ?i", $_REQUEST['order_id']);
			$e_text = '<p><u>Внимание, новый клиент!</u></p>';
			$text = "[U]Внимание, новый клиент![/U]\n";
			$e_text .= "<p><b>Пользователь:</b> {$user_data['name']}</p>";
			$text .= "[B]Пользователь:[/B] {$user_data['name']}\n";
			$e_text .= "<p><b>Email пользователя:</b> {$user_data['email']}</p>";
			$text .= "[B]Email пользователя:[/B] {$user_data['email']}\n";
			$e_text .= "<p><b>Телефон:</b> {$user_data['phone']}</p>";
			$text .= "[B]Телефон:[/B] {$user_data['phone']}\n";
			$e_text .= "<p><b>Товары:</b></p>";
			$text .= "[B]Товары:[/B]\n";
			foreach($user_data['order'] as $product) {
				$e_text .= "<p>[{$product['product_code']}] {$product['product']}  (количество: {$product['amount']})</p>";
				$text .= "[{$product['product_code']}] {$product['product']}  (количество: {$product['amount']})\n";
			}
			fn_sm_forsaken_cart_send_email('Внимание, новый клиент!', $e_text, $contacts['emails']);
			foreach($contacts['bitrix_ids'] as $bitrix_id) {
				fn_sm_bitrix_send($bitrix_url, $bitrix_id, $text);
			}
		}
	}
}