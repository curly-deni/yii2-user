<?php

namespace aesis\user\traits;

trait hasOwnerFind
{
    public static function find()
    {
        $user = \Yii::$app->user->getIdentity();
        return parent::find()->where(['user_id' => $user->id]);
    }
}