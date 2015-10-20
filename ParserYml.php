<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.10.2015
 * Time: 12:15
 */

namespace app\models;


use yii\base\DynamicModel;
use yii\base\Model;
use yii\base\UnknownPropertyException;

class ParserYml extends Model implements ParseFileInterface
{
    private $response = [];
    private $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public static function Initial($filePath)
    {
        return new ParserYml($filePath);
    }

    public function Run()
    {
        $resArray = [];
        libxml_use_internal_errors(true);
        $xmlstr = @file_get_contents($this->filePath);
        try{
            $xml = new \SimpleXMLElement($xmlstr);
        } catch(\Exception $e){
            echo $e->getMessage();
        }
        if(isset($xml)){
            if(strtolower($xml->getName()) === 'yml_catalog'){
                $priceArray = &$xml->shop->offers->offer;
                foreach($priceArray as $key => $subArray){
                    $itemArray = [];
                    foreach($subArray->attributes() as $k => $v){
                        $itemArray[$k] = $v->__toString();
                    }
                    foreach($subArray as $k => $v){
                        $itemArray[$k] = $v->__toString();
                    }
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