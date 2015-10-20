<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 15.10.2015
 * Time: 15:00
 */

namespace mantesko\priceparser;


use yii\base\Model;

class UploadLink extends Model
{
    public $link;
    public $dir;

    public static function Initial($link, $dir)
    {
        return new UploadLink($link, $dir);
    }

    public function __construct($link, $dir)
    {
        $this->link = $link;
        $this->dir = $dir;
    }

    public function verifyFile()
    {
        if(($fp = @fopen($this->link, 'r')) && (is_writable($this->dir))){
            return true;
        }
        else return false;
    }

    public function saveFile()
    {
        if($this->verifyFile()){
            $filePath = $this->dir . '/' . uniqid() . '.' . pathinfo($this->link)['extension'];
            $fp = file_get_contents($this->link);
            file_put_contents($filePath, $fp);
            return $filePath;
        }
        return 'Error file save';
    }
}