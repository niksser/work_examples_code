
<?php
header('Content-Type: application/json');
if (!isset($_GET['link'])) {
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

$response = $client->request('GET', $_GET['link'], [
	'headers' => [
		'User-Agent' => getRandomUserAgent(),
		// 'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.116 Safari/537.36',
		// 'Accept' => 'application/json',
	],
	'proxy' => getRandomProxyString(),
]);
$body = iconv('windows-1251', 'UTF-8//IGNORE', (string) $response->getBody());

file_put_contents('index.html', $body);
$document = new Document($body, $isFile = false, $encoding = "UTF-8", $type = Document::TYPE_HTML);

foreach ($document->xpath('//div[@class="container"]/h1') as $arr) {
	$parser_data['name'] = trim($arr->text());
}
$parser_data['referer'] = $_GET['link'] . trim($arr->getAttribute('href'));

foreach ($document->xpath('//div[@class="container"]/div[@class="row"]/div[@class="col"]/div[@class="det-content clearfix"]/div[@id="opisAndTTH"]/div[@id="full_description"]') as $arr) {
	$parser_data['short_description'] = trim($arr->text());
}

foreach ($document->xpath('//div[@class="swiper-wrapper"]/div/img') as $arr) {
	$parser_data['images'][] = "" . str_replace('&w=1', '&w=0', trim($arr->getAttribute('data-src')));
}
echo json_encode($parser_data);