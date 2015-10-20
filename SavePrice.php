<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.10.2015
 * Time: 10:43
 */

namespace mantesko\priceparser;


use yii\base\Model;
use Yii;
use yii\web\UploadedFile;

class SavePrice extends Model
{
    private $filePath;
    private $parserModel;

    public static function Initial()
    {
        return new SavePrice();
    }

    private function parserFactory()
    {
        $model = false;
        if($filePath = $this->filePath){
            $ext = pathinfo($filePath)['extension'];
            switch ($ext){
                case 'xls':
                    $model = ParserExcel::Initial($filePath);
                    break;
                case 'xlsx':
                    $model = ParserExcel::Initial($filePath);
                    break;
                case 'csv':
                    $model = ParserCsv::Initial($filePath);
                    break;
                case 'xml':
                    $model = ParserYml::Initial($filePath);
                    break;
                case 'json':
                    $model = ParserJson::Initial($filePath);
                    break;
                case 'js':
                    $model = ParserJson::Initial($filePath);
                    break;
                default:
                    unlink($filePath);
                    break;
            }
        }

        $this->parserModel = $model;
        return $this;
    }

    public function Run()
    {
        $result = false;
        $model = $this->saveFile()->parserFactory();
        if($model->parserModel){
            $priceArray = $model->parserModel->Run()->getResponse();
            if(!empty($priceArray)){
                $userId = Yii::$app->user->getId();
                Yii::$app->db->createCommand('DELETE FROM last_import WHERE user_id = :userId')
                ->bindValue(':userId', $userId)
                ->execute();
                foreach($priceArray as $k => $priceItem){
                    if(isset($priceItem['picture'])){
                        $picture = $priceItem['picture'];
                    } else {
                        $picture = 'null';
                    }
                    $priceItem['picture'] = $this->uploadImage($picture);
                    $result = $this->savePriceItem($priceItem);
                }
            }
        }

        return $result;
    }

    private function saveFile()
    {
        $dir = Yii::getAlias('@app/uploads');
        $this->filePath = false;

        if($file = UploadedFile::getInstanceByName('file')){
            $model = UploadFile::Initial();
            $model->file = $file;

            if($model->validate()) {
                $filePath = $dir . '/' . uniqid() . '.' .$model->file->extension;
                if($model->file->saveAs($filePath)){
                    $this->filePath = $filePath;
                }
            }
        } elseif($file = $_POST['link']){
            $model = UploadLink::Initial($file, $dir);
            if($filePath = $model->saveFile()){
                $this->filePath = $filePath;
            }
        }
        return $this;
    }

    private function uploadImage($path)
    {
        $dir = Yii::getAlias('@app/images');
        if($fp = @fopen($path, 'r')){
            if($imageInfo = @getimagesize($path)){
                switch ($imageInfo['mime']){
                    case 'image/jpeg':
                        $ext = 'JPG';
                        break;
                    case 'image/gif':
                        $ext = 'GIF';
                        break;
                    case 'image/png':
                        $ext = 'PNG';
                        break;
                    default:
                        $ext = 'invalid';
                }
                if($ext == 'invalid'){
                    $image = null;
                } else {
                    //Создаем уникальное имя и сохраняем изображение на сервер
                    $filename = uniqid().".".$ext;
                    file_put_contents($dir . '/' . $filename, file_get_contents($path));
                    $image = $dir . '/' . $filename;
                }
            }
        } else $image = null;
        return $image;
    }

    private function savePriceItem($item)
    {
        $userId = Yii::$app->user->getId();
        $itemId = $item['id'];
        $optinalValues = [
            'url', 'oldprice', 'picture', 'store', 'pickup', 'delivery', 'local_delivery_cost', 'author', 'publisher',
            'series', 'year', 'volume', 'part', 'language', 'binding', 'page_extent', 'description', 'downloadable', 'age'
        ];
        foreach($optinalValues as $k=>$value){
            if(!array_key_exists($value, $item)){
                $item[$value] = 'null';
            }
        }
        foreach($item as $key => $v){
            if(strtolower($item[$key]) == 'true'){
                $item[$key] = 1;
            } elseif (strtolower($item[$key]) === 'false'){
                $item[$key] = 0;
            }
        }
        $params = [':userId'=>$userId, ':offerId'=>$itemId, ':type'=>$item['type'], ':available'=>$item['available'],
                    ':bid'=>$item['bid'], ':url'=>$item['url'], ':price'=>$item['price'], ':oldPrice'=>$item['oldprice'],
                    ':currencyId'=>$item['currencyId'], ':categoryId'=>$item['categoryId'], ':picture'=>$item['picture'],
                    ':store'=>$item['store'], ':pickup'=>$item['pickup'], ':delivery'=>$item['delivery'],
                    ':localDeliveryCost'=>$item['local_delivery_cost'], ':author'=>$item['author'], ':name'=>$item['name'],
                    ':publisher'=>$item['publisher'], ':series'=>$item['series'], ':year'=>$item['year'],
                    ':isbn'=>$item['ISBN'], ':volume'=>$item['volume'], ':part'=>$item['part'], ':language'=>$item['language'],
                    ':binding'=>$item['binding'], ':pageExtent'=>$item['page_extent'], ':description'=>$item['description'],
                    ':downloadable'=>$item['downloadable'], ':age'=>$item['age']];
        $isPriceItem = Yii::$app->db->createCommand('SELECT * FROM price WHERE user_id = :userId AND offer_id = :offerId')
            ->bindValue(':userId', $userId)
            ->bindValue(':offerId', $itemId)
            ->queryOne();
        if($isPriceItem){
            if($isPriceItem['picture']){
                unlink($isPriceItem['picture']);
            }
            Yii::$app->db->createCommand('UPDATE price SET type = :type, available = :available,
                bid = :bid, url = :url, price = :price, oldprice = :oldPrice, currency_id = :currencyId, category_id = :categoryId,
                picture = :picture, store = :store, pickup = :pickup, delivery = :delivery, local_delivery_cost = :localDeliveryCost,
                author = :author, name = :name, publisher = :publisher, series = :series, year = :year, isbn = :isbn, volume = :volume,
                part = :part, language = :language, binding = :binding, page_extent = :pageExtent, description = :description,
                downloadable = :downloadable, age = :age WHERE user_id = :userId AND offer_id = :offerId')
                ->bindValues($params)
                ->execute();
        } else {
            Yii::$app->db->createCommand('INSERT INTO price (user_id, offer_id, type, available, bid, url,
                price, oldprice, currency_id, category_id, picture, store, pickup, delivery, local_delivery_cost,
                author, name, publisher, series, year, isbn, volume, part, language, binding, page_extent, description,
                downloadable, age) VALUES (:userId, :offerId, :type, :available, :bid, :url, :price, :oldPrice, :currencyId,
                :categoryId, :picture, :store, :pickup, :delivery, :localDeliveryCost,:author, :name, :publisher, :series,
                :year, :isbn, :volume, :part, :language, :binding, :pageExtent, :description, :downloadable, :age)')
                ->bindValues($params)
                ->execute();
        }
        $result = Yii::$app->db->createCommand('INSERT INTO last_import (user_id, offer_id, type, available, bid, url,
                price, oldprice, currency_id, category_id, picture, store, pickup, delivery, local_delivery_cost,
                author, name, publisher, series, year, isbn, volume, part, language, binding, page_extent, description,
                downloadable, age) VALUES (:userId, :offerId, :type, :available, :bid, :url, :price, :oldPrice, :currencyId,
                :categoryId, :picture, :store, :pickup, :delivery, :localDeliveryCost,:author, :name, :publisher, :series,
                :year, :isbn, :volume, :part, :language, :binding, :pageExtent, :description, :downloadable, :age)')
            ->bindValues($params)
            ->execute();

        return $result;
    }
}