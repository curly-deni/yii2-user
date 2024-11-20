<?php

namespace aesis\user\helpers;

use Yii;
use IP2LocationYii\IP2Location_Yii;

class Location
{

    public static $record = null;

    public static function setup($databasePath) {

        if (!self::$record)
            return;

        define('IP2LOCATION_DATABASE', $databasePath);
        define('IP2LOCATION_LANGUAGE', \Yii::$app->language);

        $realIP = Yii::$app->request->headers->get('X-Real-IP');
        if (!$realIP)
            $realIP = Yii::$app->request->remoteIP;

        $IP2Location = new IP2Location_Yii();

        self::$record = $IP2Location->get($realIP);
    }

    public static function getLocationRequest()
    {
        self::setup();
        if (YII_ENV_DEV) {
            return "Local country";
        }
        return self::$record['country'];
    }

    public static function getCityRequest()
    {
        self::setup();

        if (YII_ENV_DEV) {
            return "Local city";
        }
        return self::$record['city'];
    }

    public static function getLocation()
    {
        return Yii::$app->params['location'] ?: self::getLocationRequest();
    }

}