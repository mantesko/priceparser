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

class ParserJson extends Model implements ParseFileInterface
{
    private $response = [];
    private $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }
    public static function Initial($filePath)
    {
        return new ParserJson($filePath);
    }

    public function Run()
    {
        $resArray = [];
        $json = @file_get_contents($this->filePath);
        if(isset($json) && $priceArray = json_decode($json, true)) {
            foreach($priceArray as $k=>$itemArray){
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
        $this->response = $resArray;
        unlink($this->filePath);
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

}