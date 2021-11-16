<?php

namespace app\models\suppliers;

use app\models\Prices;
use app\models\Products;
use GuzzleHttp\Client;
use Yii;
use yii\helpers\ArrayHelper;

class Products_name extends \app\models\Products
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

    public function __construct($product, $supplierId, $extra)
    {
        // supplierId
        $this->supplierId = $supplierId;
        // code
        $this->code = (string) $product->cod;
        // barcode
        $this->barcode = (string) $product->barcode;
        // categories
        $categories = [];
        $tmp = [];
        self::makeCategories($extra['categories'], (string) $product->categoryId, $categories);
        $tmp = array_reverse($categories);
        $tmp1 = array_shift($tmp);
        $this->categories = $tmp;
        // remains
        $this->remains = intval((string) $product->quantum ?? 0);
        // name
        $this->name = (string) $product->name . ' ' . (string) $product->articul;
        // properties
        $properties = [];
        $properties['article'] = (string) $product->articul;
        $properties['vendor'] = (string) $product->vendor;
        $properties['material'] = (string) $product->material;
        $properties['colour'] = (string) $product->colour;
        $properties['size'] = (string) $product->size;
        $properties['gender'] = (string) $product->gender;
        $properties['age'] = (string) $product->age;
        $this->properties = $properties;
        // description
        $this->description = (string) $product->description ?? 0;
        // images
        $images = [];
        $images[] = '';
        $this->images = array_values(array_unique(array_diff($images, ['', null])));
        // prices
        $this->price1 = floatval((string) $product->Price1 ?? 0);
        $this->currency1 = 'RUB';
        $this->price2 = floatval(0);
        $this->currency2 = '';
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
    // объявление переменных
    public static function run($supplier)
    {
        //Имена файлов
        $local_file = '';
        $server_file = '';
        //Логин
        $ftp_user_name = '';
        //Пароль
        $ftp_user_pass = '';

        // установка соединения с ftp(имя хоста, порт)
        $conn_id = ftp_connect('', '');

        // вход с именем пользователя и паролем
        $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
        ftp_pasv($conn_id, true);
        $contents = ftp_nlist($conn_id, '');
        // попытка скачать $server_file и сохранить в $local_file
        ftp_get($conn_id, $local_file, $server_file, FTP_BINARY); //{
        $file = '';
        $handle = fopen(__DIR__ . '/' . $file, 'w');

        if (ftp_fget($conn_id, $handle, $file, FTP_ASCII, 0)) {
            echo 'Файл успешно скачен';
        } else {
            echo 'Ошибка';
        }

        // Закрытие файла и соединения
        ftp_close($conn_id);
        //Путь к файлу
        $pr = ('/.../..../..../../.xml');
        $prices[] = [
            'path' => $pr,
            'modified' => 0,
        ];
        // Обработка файлов
        // Инициализация
        foreach ($prices as $price) {
            $xml = simplexml_load_file($price['path']);
            // Категории
            $rawCategories = [];
            foreach ($xml->categories->category as $item => $category) {
                $id = (string) mb_strtolower($category->attributes()->{'id'});
                $parentId = (string) $category->attributes()->{'parentId'};
                $name = (string) $category[0] ?? '';
                $rawCategories[$id] = [
                    'id' => mb_strtolower($id),
                    'parentId' => mb_strtolower($parentId),
                    'name' => $name,
                ];
            }
            $extra['categories'] = $rawCategories;
            // Товары
            foreach ($xml->offers->offer as $item => $product) {
                $supplier->data[] = ArrayHelper::toArray(new self($product, $supplier->id, $extra));
                $supplier->remainsSum += intval((string) $product->quantum ?? 0);
            }
            \app\models\Suppliers::clearRemains($supplier);
        }
    }
    public function makeCategories($inputCategories, $category_id, &$categories)
    {
        $categories[] = $inputCategories[$category_id]['name'] ?? '';

        if (!empty($inputCategories[$category_id]['parentId'])) {
            self::makeCategories($inputCategories, $inputCategories[$category_id]['parentId'], $categories);
        }
    }
}
