<?php

namespace aesis\user\events;

use yii\base\Event;

class TokenEvent extends Event
{
    private $_user;
    private $_token;

    public function getToken()
    {
        return $this->_token;
    }

    public function setToken($token)
    {
        $this->_token = $token;
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
