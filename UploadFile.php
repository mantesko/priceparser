<?php

namespace mantesko\priceparser;

use Yii;
use yii\base\Model;

class UploadFile extends Model
{
    public $file;

    public function rules()
    {
        return [
            [['file'], 'required'],
            ['file', 'file', 'maxSize' => 120000000],

        ];
    }

    public static function Initial()
    {
        return new UploadFile();
    }
}