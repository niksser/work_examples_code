<?php

namespace app\models\suppliers;

use app\models\Products;
use app\models\ProxyList;
use app\models\UserAgentList;
use DiDom\Document;
use GuzzleHttp\Client;
use yii\helpers\ArrayHelper;

class Products_название extends \app\models\Products
{

    public function rules()
    {
        $rules = \app\models\Products::rules();
        // code
        // barcode
        // categories
        // prices
        // remains
        // name
        // description
        // properties
        // images
        return $rules;
    }

    public function __construct($product, $supplierId)
    {
        // supplierId
        $this->supplierId = $supplierId;
        // code
        $this->code = $product['code'];
        //var_dump($this->code);
        // barcode
        $this->barcode = '';
        // categories
        $this->categories = $product['categories'];
        // remains
        $this->remains = $product['remains'];
        // name
        $this->name = $product['name'];
        //var_dump($this->name);
        // properties
        $this->properties = $product['properties'];
        // description
        $this->description = '';
        // images
        $images = [];
        $this->images = array_values(array_unique(array_diff($images, ['', null])));
        // prices
        $this->price1 = floatval((string) $product['price1']) ?? 0;
        $this->currency1 = $product['currency1'];
        if (isset($product['price2'])) {
            $this->price2 = floatval((string) $product['price2']);
            //var_dump($this->price2);
            $this->currency2 = $product['currency2'];
        } else {
            $this->price2 = floatval(0);
            $this->currency2 = '';
        }
        $this->price3 = floatval(0);
        $this->currency3 = '';
        $this->price4 = floatval(0);
        $this->currency4 = '';
        $this->price5 = floatval(0);
        // at
        $this->updated_at = time();
        $this->created_at = time();
        $this->validate();
    }

    public static function run($supplier)
    {
        // Инициализация
        $client = new Client([
            'base_uri' => $supplier->url,
            'http_errors' => false,
            'cookies' => true,
            'delay' => 1000,
            'headers' => [
                'User-Agent' => UserAgentList::getRandomUserAgent(),
            ],
            'timeout' => 60.0,
            'proxy' => ProxyList::getRandomProxyString(),
        ]);
        $response = $client->request('GET', '/login');
        $document = new Document($response->getBody()->getContents(), $isFile = false, $encoding = 'UTF-8', $type = Document::TYPE_HTML);
        foreach ($document->xpath('//form[@name="jlogin"]/input[@type="hidden" and contains (@value, "1")]') as $arr) {
            $token = trim($arr->getAttribute('name'));
        }
        // Авторизация
        $response = $client->request('POST', '/products/user/loginsave', [
            'form_params' => [
                'username' => $supplier->username,
                'passwd' => $supplier->password,
                'remember' => 'yes',
                'return' => '',
                $token => '1',
            ],
        ]);
        // Получаем категории
        $document = new Document($response->getBody()->getContents(), $isFile = false, $encoding = 'UTF-8', $type = Document::TYPE_HTML);

        foreach ($document->xpath('/html/body/section[2]/div/div[3]/div/div/div/div/div[1]/div/ul/li/a') as $arr) {

            $categories[] = [
                'name' => $arr->text(),
                'url' => $arr->getAttribute('href'),
            ];
        }

        foreach ($categories as $category) {
            $categoryUrl = $category['url'];

            do {

                $response = $client->get($categoryUrl);
                $document = new Document($response->getBody()->getContents());

                // Получаем список товаров

                foreach ($document->xpath('//div[@id="catalog-list"]/div/div[1]/div[3]/div[2]/a[2]') as $key => $value) {
                    // Разбор товаров
                    $productUrl = $value->getAttribute('href');

                    $response = $client->get($productUrl);
                    $document = new Document($response->getBody()->getContents());

                    $product = [];
                    foreach ($document->find('#product_id') as $value) {
                        $product['code'] = $value->getAttribute('value');
                    }

                    foreach ($document->find('span.align-middle.d-inline-block.lead.mt-2.catalog-price') as $value) {
                        $product['price1'] = preg_replace("/[^\d]/", '', $value->text());
                        $product['currency1'] = 'RUB';
                    }
                    foreach ($document->find('meta[itemprop=price]') as $value) {
                        $product['price2'] = preg_replace("/[^\d]/", '', $value->attr('content'));
                        $product['currency2'] = 'RUB';
                    }
                    $product['remains'] = 1;
                    foreach ($document->find('div.product_label > span') as $value) {
                        if (trim($value->text()) === 'В наличии') {
                            $product['remains'] = 10;
                        }
                    }
                    foreach ($document->find('title') as $value) {
                        $product['name'] = $value->text();
                    }
                    $product['properties'] = [];
                    foreach ($document->xpath('//div[@id="specs"]/dl') as $key => $value) {
                        $dt = '';
                        $dd = '';
                        foreach ($value->find('dt') as $v) {
                            $dt = $v->text();
                        }
                        foreach ($value->find('dd') as $v) {
                            $dd = $v->text();
                        }
                        if (!empty($dt) && !empty($dd)) {
                            $product['properties'][$dt] = $dd;
                        }
                    }
                    $product['categories'] = [$category['name']];
                    $supplier->data[] = ArrayHelper::toArray(new self($product, $supplier->id));
                    $supplier->remainsSum += $product['remains'];
                }
                // получаем следующую страницу
                $categoryUrl = null;
                foreach ($document->find('a.pagenav.page-link[title^=Вперёд]') as $a) {
                    $categoryUrl = $a->getAttribute('href');
                }
            } while (!is_null($categoryUrl));
        }
        \app\models\Suppliers::clearRemains($supplier);
    }
}
