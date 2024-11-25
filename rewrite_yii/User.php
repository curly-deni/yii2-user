<?php

namespace aesis\user\rewrite_yii;

use Yii;
use yii\base\InvalidConfigException;
use Yii\web\Cookie;
use yii\web\User as BaseUser;

class User extends BaseUser
{

    /**
     * @throws InvalidConfigException
     */
    protected function sendIdentityCookie($identity, $duration, $key = null)
    {
        if (empty($key))
            $key = $identity->getAuthKey();

        /** @var $cookie Cookie */
        $cookie = Yii::createObject(array_merge($this->identityCookie, [
            'class' => 'yii\web\Cookie',
            'value' => json_encode([
                $identity->getId(),
                $key,
                $duration,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'expire' => time() + $duration,
        ]));
        Yii::$app->getResponse()->getCookies()->add($cookie);
    }

    protected function removeIdentityCookie($removeKey = false)
    {
        if ($removeKey) {
            $this->identityClass::removeCurrentKey();
        }
        parent::removeIdentityCookie();
    }


    /**
     * @throws InvalidConfigException
     */
    public function switchIdentity($identity, $duration = 0)
    {
        $this->setIdentity($identity);

        if (!$this->enableSession) {
            return;
        }

        if ($this->enableAutoLogin && ($this->autoRenewCookie || $identity === null)) {
            $this->removeIdentityCookie(true);
        }

        $session = Yii::$app->getSession();
        $session->regenerateID(true);
        $session->remove($this->idParam);
        $session->remove($this->authTimeoutParam);
        $session->remove($this->authKeyParam);

        if ($identity) {
            // set user and profile into session
            if ($identity instanceof $this->identityClass) {
                $data = [
                    'user' => [
                        'id' => $identity->id,
                        'username' => $identity->username,
                        'email' => $identity->email,
                        'role' => $identity->role
                    ],
                    'profile' => $identity->profile->toArray()
                ];

                Yii::$app->getResponse()->getCookies()->add(new Cookie([
                    'name' => 'aesis_id',
                    'value' => json_encode($data),
                ]));
            }

            $key = $identity->getAuthKey($duration <= 0);

            $session->set($this->idParam, $identity->getId());
            $session->set($this->authKeyParam, $key);
            if ($this->authTimeout !== null) {
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
            if ($this->absoluteAuthTimeout !== null) {
                $session->set($this->absoluteAuthTimeoutParam, time() + $this->absoluteAuthTimeout);
            }
            if ($this->enableAutoLogin && $duration > 0) {
                $this->sendIdentityCookie($identity, $duration, $key);
            }
        }
    }


}