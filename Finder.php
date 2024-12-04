<?php

namespace aesis\user;

use yii\base\BaseObject;

class Finder extends BaseObject
{
    protected $userQuery;

    protected $tokenQuery;

    protected $profileQuery;

    public function getUserQuery()
    {
        return $this->userQuery;
    }

    public function getTokenQuery()
    {
        return $this->tokenQuery;
    }

    public function getProfileQuery()
    {
        return $this->profileQuery;
    }

    public function setUserQuery($userQuery)
    {
        $this->userQuery = $userQuery;
    }

    public function setTokenQuery($tokenQuery)
    {
        $this->tokenQuery = $tokenQuery;
    }

    public function setProfileQuery($profileQuery)
    {
        $this->profileQuery = $profileQuery;
    }

    public function findUserById($id)
    {
        return $this->findUser(['id' => $id])->one();
    }

    public function findUserByUsername($username)
    {
        return $this->findUser(['username' => $username])->one();
    }

    public function findUserByEmail($email)
    {
        return $this->findUser(['email' => $email])->one();
    }

    public function findUserByUsernameOrEmail($usernameOrEmail)
    {
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->findUserByEmail($usernameOrEmail);
        }

        return $this->findUserByUsername($usernameOrEmail);
    }

    public function findUser($condition)
    {
        return $this->userQuery->where($condition);
    }

    public function findToken($condition)
    {
        return $this->tokenQuery->where($condition);
    }

    public function findTokenByParams(int $userId, string $code, int $type)
    {
        return $this->findToken([
            'user_id' => $userId,
            'code' => $code,
            'type' => $type,
        ])->one();
    }

    public function findProfileById($id)
    {
        return $this->findProfile(['id' => $id])->one();
    }

    public function findProfile($condition)
    {
        return $this->profileQuery->where($condition);
    }
}
