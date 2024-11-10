<?php

namespace aesis\user\models;

use yii\db\ActiveRecord;

abstract class AbstractKeyProvider extends ActiveRecord
{

    abstract public static function getCurrentKey();

    abstract public static function getNewKey($user_id);

    abstract public function processDelete();

}