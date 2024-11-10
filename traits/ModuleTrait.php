<?php

namespace aesis\user\traits;

use Yii;

trait ModuleTrait
{
    public function getModule()
    {
        return Yii::$app->getModule('user');
    }

    public static function getDb()
    {
        return Yii::$app->getModule('user')->getDb();
    }
}
