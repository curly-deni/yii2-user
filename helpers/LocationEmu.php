<?php

namespace aesis\user\helpers;

use Yii;

class LocationEmu
{

    public static function getLocationRequest()
    {
        return "unused";
    }

    public static function getCityRequest()
    {
        return "unused";
    }

    public static function getLocation()
    {
        return Yii::$app->params['location'] ?: self::getLocationRequest();
    }

}