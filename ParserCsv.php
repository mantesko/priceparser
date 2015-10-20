<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.10.2015
 * Time: 12:16
 */

namespace mantesko\priceparser;


use yii\base\Model;
use yii\base\DynamicModel;
use yii\base\UnknownPropertyException;

class ParserCsv extends Model implements ParseFileInterface
{
    private $response = [];
    private $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }
    public static function Initial($filePath)
    {
        return new ParserCsv($filePath);
    }

    public function Run()
    {
        $file = @fopen($this->filePath, 'r');
        $resArray = [];
        if ((isset($file) && $line = fgetcsv($file, 0,';')) !== FALSE){
            $headerArray = $line;
            while (($line = fgetcsv($file, 0,';')) !== FALSE){
                $itemArray = @array_combine($headerArray, $line);
                if($itemArray){
                    try{
                        $model = DynamicModel::validateData($itemArray, [
                            [['id', 'type', 'available', 'bid', 'price', 'currencyId', 'categoryId', 'name', 'ISBN'], 'required'],
                        ]);
                    } catch(UnknownPropertyException $e){
                        continue;
                    }
                    if (isset($model) && !$model->hasErrors()) {
                        $resArray[] = $itemArray;
                    }
                }
            }
        }
        $this->response = $resArray;
        unlink($this->filePath);
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

}