<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.10.2015
 * Time: 16:12
 */

namespace app\models;


interface ParseFileInterface
{
    public static function Initial($filePath);

    public function Run();

    public function getResponse();
}