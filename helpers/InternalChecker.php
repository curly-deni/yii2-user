<?php

namespace aesis\user\helpers;

use Yii;
use function Aws\describe_type;

class InternalChecker
{

    public static function isInternalApi()
    {
        return Yii::$app->request->headers->get('X-Internal-Api', 'false') == 'true';
    }

    public static function isIgnoreUserAgent()
    {
        return Yii::$app->request->headers->get('X-WS', 'false') == 'true';
    }

}