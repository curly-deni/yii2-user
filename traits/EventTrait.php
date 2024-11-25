<?php

namespace aesis\user\traits;

use aesis\user\events\KeyEvent;
use aesis\user\events\UserEvent;

trait EventTrait
{

    protected function getUserEvent($user)
    {
        return \Yii::createObject(['class' => UserEvent::class, 'user' => $user]);
    }

    public function getKeyEvent($user, $key)
    {
        return \Yii::createObject(['class' => KeyEvent::class, 'user' => $user, 'key' => $key]);
    }

    public function getTokenEvent($user, $token)
    {
        return \Yii::createObject(['class' => KeyEvent::class, 'user' => $user, 'token' => $token]);
    }
}