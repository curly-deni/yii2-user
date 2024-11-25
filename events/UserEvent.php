<?php

namespace aesis\user\events;
use yii\base\Event;

class UserEvent extends Event
{
    private $_user;

    public function getUser()
    {
        return $this->_user;
    }

    public function setUser($user)
    {
        $this->_user = $user;
    }
}
