<?php

namespace aesis\user\events;
use yii\base\Event;

class KeyEvent extends Event
{
    private $_user;
    private $_key;

    public function getKey()
    {
        return $this->_key;
    }

    public function setKey($key)
    {
        $this->_key = $key;
    }

    public function getUser()
    {
        return $this->_user;
    }

    public function setUser($user)
    {
        $this->_user = $user;
    }
}
