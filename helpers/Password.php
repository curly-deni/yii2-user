<?php

namespace aesis\user\helpers;

use Yii;
use yii\base\Exception;

class Password
{
    /**
     * @throws Exception
     */
    public static function hash($password)
    {
        return Yii::$app->security->generatePasswordHash($password, (Yii::$app->getModule('user')->cost ?? 10));
    }


    public static function validate($password, $hash)
    {
        return Yii::$app->security->validatePassword($password, $hash);
    }


    public static function generate($length)
    {
        $sets = [
            'abcdefghjkmnpqrstuvwxyz',
            'ABCDEFGHJKMNPQRSTUVWXYZ',
            '23456789',
        ];
        $all = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++) {
            $password .= $all[array_rand($all)];
        }

        return str_shuffle($password);
    }
}
