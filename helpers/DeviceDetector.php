<?php

namespace aesis\user\helpers;

use Yii;
use yii\base\InvalidConfigException;

class DeviceDetector
{

    /**
     * @throws InvalidConfigException
     */
    public static function getDeviceInfo()
    {

        # for mobile app detect
        # appname/aesis

        # android tv
        # (Android 6.0; SDK 23; x86; Smart TV Pro; ru)

        # android phone
        # (Android 6.0; SDK 23; x86; Google Nexus 5X; ru)

        # iphone
        # (iOS 10.0; CPU iPhone OS 10_0 like Mac OS X; ru)

        # ipad
        # (iOS 10.0; CPU iPad OS 10_0 like Mac OS X; ru)

        $dd = Yii::$app->get('dd');

        if ($dd->isBot()) {
            $botInfo = $dd->getBot();
            $botName = $botInfo['name'] ?: 'unknown';

            return 'bot/' . $botName . '/' . $botInfo . '/' . 'unknown';
        } else {
            $clientInfo = $dd->getClient();

            $clientType = $clientInfo['type'] ?: 'unknown';
            $clientName = $clientInfo['name'] ?: 'unknown';

            $osName = $dd->getOs('name') ?: 'unknown';
            $device = $dd->getDeviceName() ?: 'unknown';

            return str_replace(' ', '', $clientType . '/' . $clientName . '/' . $osName . '/' . $device);
        }
    }

}