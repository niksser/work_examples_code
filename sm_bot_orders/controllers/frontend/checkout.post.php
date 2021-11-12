<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if($mode == 'complete') {
    if(!empty($_REQUEST)) {
        $user_data = db_get_row('SELECT ?:users.user_id, ?:users.email, ?:users.phone FROM ?:users WHERE ?:users.user_id = ?i', $auth['user_id']);
        $user_data['order'] = db_get_array('SELECT * FROM ?:order_details LEFT JOIN ?:product_descriptions ON (?:product_descriptions.product_id = ?:order_details.product_id) WHERE ?:order_details.order_id = ?i', $_REQUEST['order_id']);
        $text = "[U]Внимание, новый заказ [№{$_REQUEST['order_id']}]![/U] {$_REQUEST['order_id']}\n";           
        $text .= "[B]Пользователь:[/B] {$user_data['name']}\n";            
        $text .= "[B]Email пользователя:[/B] {$user_data['email']}\n";           
        $text .= "[B]Телефон:[/B] {$user_data['phone']}\n";            
        $text .= "[B]Товары:[/B]\n";
        foreach($user_data['order'] as $product) {
            $text .= "[{$product['product_code']}] {$product['product']}  (количество: {$product['amount']})\n";
        }
        // Отправка в Битрикс
        $setting = Registry::get('addons.sm_bot_orders');
        $url = $setting['bitrix_url'];
        $dialog_ids = explode(',' , $setting['dialog_ids']);
        if (!empty($url) && !empty($dialog_ids) && (!isset($_SESSION['sm_bot_orders']['order_id']) || $_SESSION['sm_bot_orders']['order_id'] != $_REQUEST['order_id'])) {                            
            foreach($dialog_ids as $bitrix_id) {
                fn_sm_bot_orders_send($url, $bitrix_id, $text);
            }
            $_SESSION['sm_bot_orders']['order_id'] = $_REQUEST['order_id'];
        }
    }
}