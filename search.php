<?php
//http://parsing.mooo.com/cpi/parser/merlion/search.php?text=SA400S37/120G
header('Content-Type: application/json');
if (!isset($_GET['text'])) {
	echo json_encode(false);
	exit;
}

require_once realpath('../../vendor/autoload.php');
require_once realpath('../proxy.php');
use DiDom\Document;
use GuzzleHttp\Client;

$client = new Client([
	// Base URI is used with relative requests
	'base_uri' => 'https://krym.holodilnik.ru/',
	// You can set any number of default request options.
	'timeout' => 5.0,
]);

$response = $client->request('GET', 'search/', [
	'query' => [
		'search' => $_GET['text'],
	],
	'headers' => [
		'User-Agent' => getRandomUserAgent(),
		// 'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.116 Safari/537.36',
		'Accept' => 'application/json',
	],
	'proxy' => getRandomProxyString(),
]);
$body = iconv('UTF-8', 'UTF-8//IGNORE', (string) $response->getBody());
//var_dump($body);
$document = new Document($body, $isFile = false, $encoding = "UTF-8", $type = Document::TYPE_HTML);
//
$parser_data = [];
foreach ($body->xpath('//div[@id="view-row"]/div/div/div[@class="row"]/div[2]/div/div[1]/div[@class="product-code"]/span') as $arr) {
	$parser_data['id'][] = str_replace('id ', '', trim($arr->text()));
	var_dump($parser_data);
}
//var_dump($parser_data);
foreach ($document->xpath('////div[@id="view-row"]/div/div/div[@class="row"]/div[2]/div/div[1]/div[@class="product-name"]/a/span') as $arr) {
	$parser_data['name'][] = trim($arr->text());
}
foreach ($document->xpath('////div[@id="view-row"]/div/div/div[@class="row"]/div[@class="product-image"]/a/img[@class="image-fluid"]') as $arr) {
	$parser_data['thumbnail'][] = trim($arr->getAttribute('src'));
}
foreach ($document->xpath('////div[@id="view-row"]/div/div/div[@class="row"]/div[2]/div/div[1]/div[@class="product-name"]/a') as $arr) {
	$parser_data['link'][] = "https:" . trim($arr->getAttribute('href'));
}

if (!empty($parser_data['id'])) {
	for ($i = 0; $i < count($parser_data['id']); $i++) {
		$array[$i]['id'] = $parser_data['id'][$i] ?? null;
		$array[$i]['name'] = $parser_data['name'][$i] ?? null;
		$array[$i]['thumbnail'] = $parser_data['thumbnail'][$i] ?? null;
		$array[$i]['link'] = $parser_data['link'][$i] ?? null;
	}
	echo json_encode($array);
} else {
	echo json_encode(false);
}