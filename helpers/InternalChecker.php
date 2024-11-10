<?php

namespace aesis\user\helpers;

use Yii;

class InternalChecker
{

    public static function isInternalApi()
    {
        return Yii::$app->request->headers->get('X-Internal-Api', 'false') == 'true';
    }

}