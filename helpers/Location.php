<?php

namespace aesis\user\helpers;

use Yii;

class Location
{

    public static function getLocationRequest()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $details = json_decode(file_get_contents("http://ipinfo.io/$ip/json"), true);

        if (isset($details['bogon']))
            return "localhost";
        return $details['country'];
    }

    public static function getLocation()
    {
        return Yii::$app->params['location'] ?: self::getLocationRequest();
    }

}