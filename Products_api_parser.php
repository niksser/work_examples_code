<?php

namespace app\models\suppliers;

use app\models\Products;
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

    public function __construct($product, $supplierId, $categories)
    {
        // supplierId
        $this->supplierId = $supplierId;
        // code
        $this->code = (string) $product->id;
        //var_dump($this->code);
        // barcode
        $this->barcode = '';
        // categories
        $this->categories = array_values(array_unique(array_diff($categories, ['', null])));
        //var_dump($this->categories);
        // remains
        $this->remains =
            //intval($product->properties->{'количество на Курской'} ?? 0) +
            intval($product->properties->{'количество на Калужской'} ?? 0) +
            intval($product->properties->{'количество на Лобненской'} ?? 0);
        //var_dump($this->remains);
        // name
        $this->name = (string) $product->properties->{'название'};
        //var_dump($this->name);
        // properties
        $properties = [];
        $properties['warranty'] = $product->properties->{'гарантия'};
        $properties['article'] = $product->properties->{'PN'};
        $properties['vendor'] = $product->properties->{'производитель'};
        $properties['weight'] = $product->properties->{'вес, кг'};
        $properties['volume'] = $product->properties->{'объём, м^3'};
        $this->properties = $properties;
        // description
        $this->description = '';
        // images
        $images = [];
        $this->images = array_values(array_unique(array_diff($images, ['', null])));
        // prices
        $this->price1 = floatval((string) $product->properties->{'цена по категории F'} ?? 0);
        $this->currency1 = 'USD';
        $this->price2 = floatval((string) $product->properties->{'РРЦ'} ?? 0);
        $this->currency2 = 'RUB';
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
            'timeout' => 120.0,
        ]);
        // Аутентификация и получение токена
        $response = $client->request('GET', 'authentication/token.json', [
            'query' => [
                'username' => $supplier->username,
                'password' => $supplier->password,
            ],
        ]);
        $rawToken = json_decode(str_replace('{} && ', '', $response->getBody()->getContents()));
        $token = $rawToken->tokenResponse->data->token;
        // Категории
        $encodeCategory = urlencode('Прайс-лист');
        // var_dump($encodeCategory);

        $response = $client->request('GET', "catalogsZip/{$encodeCategory}.json", [
            'query' => [
                'oauth_token' => $token,
            ],
        ]);
        $categories = [];
        $rawCategories = json_decode(str_replace('{} && ', '', $response->getBody()->getContents()));
        foreach ($rawCategories->catalogResponse->data->category as $category) {
            $categories[$category->id] = [
                'id' => $category->id,
                'name' => $category->name,
                'parentId' => $category->parentId,
                'path' => [],
            ];
        }
        foreach ($categories as $key => &$category) {
            $category['path'] = self::makeCategoryPath($category, $categories);
        }
        // Товары
        foreach ($categories as $category) {
            $response = $client->request('GET', "catalogsZip/{$encodeCategory}/{$category['id']}.json", [
                'query' => [
                    'oauth_token' => $token,
                ],
            ]);
            $rawProducts = json_decode(str_replace('{} && ', '', $response->getBody()->getContents()));

            if (!empty($rawProducts->categoryResponse->data->goods)) {
                foreach ($rawProducts->categoryResponse->data->goods as $product) {
                    $supplier->data[] = ArrayHelper::toArray(new self($product, $supplier->id, $categories[$category['id']]['path'] ?? []));
                    $supplier->remainsSum +=
                        intval($product->properties->{'количество на Калужской'} ?? 0) +
                        intval($product->properties->{'количество на Лобненской'} ?? 0);
                }
            }
        }
        \app\models\Suppliers::clearRemains($supplier);
    }

    public function makeCategoryPath($category, $categories, &$path = [])
    {
        if ($category['parentId'] > 0 && isset($categories[$category['parentId']])) {
            $path[] = $categories[$category['parentId']]['name'];
            $path = self::makeCategoryPath($categories[$category['parentId']], $categories, $path);
        }
        return array_values(array_unique(array_merge(array_reverse($path), [$category['name']])));
    }
}
