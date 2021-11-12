<?php

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

use Tygh\Registry;
use Tygh\Addons\ImportB2B\ImportB2B;

 if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if ($mode == 'get') {
		$settings = Registry::get('addons.sm_forsaken_cart');

		$left = 86400;
		// получаем массив пользователей с брошенными корзинами
		$ids = db_get_fields("SELECT ?:user_session_products.user_id FROM ?:user_session_products WHERE UNIX_TIMESTAMP() - ?:user_session_products.timestamp >= ?i GROUP BY ?:user_session_products.user_id", $left);
		// $ids = db_get_fields("SELECT ?:user_session_products.user_id FROM ?:user_session_products WHERE ?:user_session_products.user_id = ?i GROUP BY ?:user_session_products.user_id", 965);
		// по каждому пользователю получаем информацию: почта контрагента, контрагент, список товаров
		foreach($ids as $id) {
			$user_data = db_get_row("SELECT ?:users.user_id, ?:sm_b2b_balance.name, ?:users.email, ?:user_session_products.timestamp FROM ?:users LEFT JOIN ?:sm_b2b_balance ON (?:sm_b2b_balance.email = ?:users.email) LEFT JOIN ?:user_session_products ON (?:user_session_products.user_id = ?:users.user_id) WHERE ?:users.user_id = ?i GROUP BY ?:users.user_id", $id);
			if(!empty($user_data)) {
				$user_data['cart'] = db_get_array("SELECT ?:products.product_code, ?:product_descriptions.product, ?:user_session_products.amount FROM ?:user_session_products LEFT JOIN ?:products ON (?:products.product_id = ?:user_session_products.product_id) LEFT JOIN ?:product_descriptions ON (?:product_descriptions.product_id = ?:products.product_id) WHERE ?:user_session_products.user_id = ?i AND ?:products.product_code IS NOT NULL", $id);
				if(count($user_data['cart']) > 0) {
					// проверяем, было ли отправлено сообщение об этой корзине (по user_id и timestamp) в таблице cscart_sm_forsaken_cart_messages
					$message = db_get_row("SELECT * FROM ?:sm_forsaken_cart_messages WHERE ?:sm_forsaken_cart_messages.user_id = ?i AND ?:sm_forsaken_cart_messages.timestamp = ?i", $user_data['user_id'], $user_data['timestamp']);
					if(empty($message)) {
						$date = date('Y-m-d H:i:s', $user_data['timestamp']);
						$e_text = '<p><u>Брошенная корзина</u></p>';
						$text = "[U]Брошенная козрина[/U]\n";
						$e_text .= "<p><b>Пользователь:</b> {$user_data['name']}</p>";
						$text .= "[B]Пользователь:[/B] {$user_data['name']}\n";
						$e_text .= "<p><b>Email пользователя:</b> {$user_data['email']}</p>";
						$text .= "[B]Email пользователя:[/B] {$user_data['email']}\n";
						$e_text .= "<p><b>Дата и время последнего использования корзины:</b> {$date}</p>";
						$text .= "[B]Дата и время последнего использования корзины:[/B] {$date}\n";
						
						$e_text .= "{manager}";
						$text .= "{manager}";
						
						$e_text .= "<p><b>Товары:</b></p>";
						$text .= "[B]Товары:[/B]\n";
						foreach($user_data['cart'] as $product) {
							$e_text .= "<p>[{$product['product_code']}] {$product['product']}  (количество: {$product['amount']})</p>";
							$text .= "[{$product['product_code']}] {$product['product']}  (количество: {$product['amount']})\n";
						}
						// если нет, то ищем менеджера, отправляем уведомление и записываем в таблицу cscart_sm_forsaken_cart_messages информацию об этом
						$manager = fn_sm_b2b_import_get_manager($user_data['user_id']);
						if($manager !== null && $manager['Email'] !== '') {
							// привязанный клиент, отправлять:
							// ответственный сейл менеджер
							
							$e_text = str_replace('{manager}', "<p><b>Ответственный сейл менеджер:</b> {$manager['Менеджер']}</p>", $e_text);
							$text = str_replace('{manager}', "[B]Ответственный сейл менеджер:[/B] {$manager['Менеджер']}\n", $text);
							
							$message_data = array (
								'user_id' => $user_data['user_id'],
								'timestamp' => $user_data['timestamp'],
							);
							$message_id = db_query("INSERT INTO ?:sm_forsaken_cart_messages ?e", $message_data);
						
							$emails = explode(',' , $settings['emails']);
							$bitrix_ids = explode(',' , $settings['bitrix_ids']);
							if($settings['sm_send_manager']== 'Y'){
								$emails[] = $manager['Email']; // ответственный сейл
								$bitrix_data = fn_sm_bitrix_search($bitrix_url, $manager['Менеджер']);
								if($bitrix_data !== false) {
									$bitrix_ids[] = $bitrix_data['ID']; // ответственный сейл
								}
							}
							
							fn_sm_forsaken_cart_send_email('Брошенная корзина', $e_text, $emails);
							
							foreach($bitrix_ids as $bitrix_id) {
								fn_sm_bitrix_send($settings['bitrix_url'], $bitrix_id, $text);
							}
						} else {
							// новый клиент, отправлять:

							
							$e_text = str_replace('{manager}', '', $e_text);
							$text = str_replace('{manager}', '', $text);
							
							$message_data = array (
								'user_id' => $user_data['user_id'],
								'timestamp' => $user_data['timestamp'],
							);
							$message_id = db_query("INSERT INTO ?:sm_forsaken_cart_messages ?e", $message_data);
							
							$emails = explode(',' , $settings['emails']);
							$bitrix_ids = explode(',' , $settings['bitrix_ids']);
								
							fn_sm_forsaken_cart_send_email('Брошенная козрина', $e_text, $emails);
							foreach($bitrix_ids as $bitrix_id) {
								fn_sm_bitrix_send($settings['bitrix_url'], $bitrix_id, $text);
							}
						}
					}
				}
			}
		}
	} elseif($mode == 'test') {
		// echo 'atata';
		// $manager = fn_sm_b2b_import_get_manager(84);
		// print_r($manager);
		$config = Registry::get('config.sm_spoilage.ftp');
		print_r($config);
		echo PHP_EOL;
	}
 }