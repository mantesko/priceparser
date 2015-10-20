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

class ParserExcel extends Model implements ParseFileInterface
{
    private $response = [];
    private $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }
    public static function Initial($filePath)
    {
        return new ParserExcel($filePath);
    }

    public function Run()
    {
        $resArray = [];
        $objPHPExcel = @\PHPExcel_IOFactory::load($this->filePath);
        if($objPHPExcel){
            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
            $headerArray = $sheetData[1];
            unset($sheetData[1]);
            foreach ($sheetData as $k=>$itemArray) {
                $itemArray = @array_combine($headerArray, $itemArray);
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