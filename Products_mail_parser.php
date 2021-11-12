<?php

namespace app\models\suppliers;

use app\models\Products;
use Yii;
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

    function __construct($product, $supplierId, $categories)
    {
        // supplierId
        $this->supplierId = $supplierId;
        // code
        $this->code = intval($product[3]);
        // barcode
        $this->barcode = '';
        // categories
        $this->categories = [$categories];
        // remains
        $this->remains = $product[5] === 'Много' ? 10 : intval($product[5]);
        // name
        $this->name = $product[2] ?? 0;
        // properties
        $properties = [];
        $properties['vendor'] = $product[10];
        $this->properties = $properties;
        // description
        $this->description = $product[9];
        // images
        $images = [];
        $this->images = array_values(array_unique(array_diff($images, ['', null])));
        // prices
        $this->price1 = floatval($product[8]) ?? 0;
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

    function getData($supplier, $letter)
    {
        $attachments = [];
        $data = [];
        // Обработка письма
        foreach ($letter->getAttachments() as $attachment) {
            $pathinfo = pathinfo($attachment->name);
            if (!empty($pathinfo['extension']) && in_array(mb_strtolower($pathinfo['extension']), ['zip', 'zipx'])) {
                $newPath = implode(DIRECTORY_SEPARATOR, [
                    Yii::getAlias('@webroot'),
                    Yii::$app->params['prices']['dir'],
                    'original',
                    $attachment->name,
                ]);
                rename($attachment->filePath, $newPath);
                $attachments[] = $newPath;
            }
        }
        $zip = new \ZipArchive();
        $zip->open($newPath);
        $zip->extractTo('/.../..../.../..../../');
        $zip->close();
        $path = dirname('/.../..../..../.../../');
        $files = array_diff(scandir($path), ['..', '.']);
        $dir = opendir($path);
        $list = array();
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && $file[strlen($file) - 1] != '~') {
                $ctime = filectime($path . "/" . $file) . ',' . $file;
                $list[$ctime] = $file;
            }
        }
        closedir($dir);
        krsort($list);
        $newestFile = array_shift($list);
        $pr = ('/.../..../..../.../...' . $newestFile);
        $prices[] = [
            'path' => $pr,
            'modified' => 0,
        ];
        // Обработка файлов
        foreach ($prices as $price) {
            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($price['path']);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(false);
            $spreadsheet = $reader->load($price['path']);
            $worksheets = $spreadsheet->getSheetNames();
            foreach ($worksheets as $worksheet) {
                $highestRow = $spreadsheet->getSheetByName($worksheet)->getHighestRow();
                $highestColumn = $spreadsheet->getSheetByName($worksheet)->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                $remains = [];
                $categories = [];
                for ($row = 1; $row <= $highestRow; $row++) {
                    $rows = [];
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $value = trim($spreadsheet->getSheetByName($worksheet)->getCellByColumnAndRow($col, $row)->getValue());
                        $rows[] = !empty($value) ? $value : '';
                    }

                    if (!empty($rows[2])  && !empty($rows[3])  && !empty($rows[8])) {
                        $data[] = ArrayHelper::toArray(new self($rows, $supplier->id, $categories));
                    } elseif (!empty($rows[0]) && empty($rows[1]) && empty($rows[2]) && empty($rows[3])) {
                        $categories = trim($rows[0]);
                    }
                }
            }
        }
        return $data;
    }
}
