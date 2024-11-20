<?php

namespace aesis\user\traits;

use Yii;

trait ModuleTrait
{
    public static function getModuleStatic()
    {
        return Yii::$app->getModule('user');
    }

    public function getModule()
    {
        return self::getModuleStatic();
    }

    public static function getDb()
    {
        return Yii::$app->getModule('user')->getDb();
    }
}
