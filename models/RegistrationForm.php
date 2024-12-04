<?php

namespace aesis\user\models;

use aesis\user\traits\ModuleTrait;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;

class RegistrationForm extends Model
{
    use ModuleTrait;

    public $email;

    public $username;

    public $password;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $user = $this->module->modelMap['User'];

        return [
            // username rules
            'usernameTrim' => ['username', 'trim'],
            'usernameLength' => ['username', 'string', 'min' => 3, 'max' => 255],
            'usernamePattern' => ['username', 'match', 'pattern' => $user::$usernameRegexp],
            'usernameRequired' => ['username', 'required'],
            'usernameUnique' => [
                'username',
                'unique',
                'targetClass' => $user,
                'message' => Yii::t('user', 'This username has already been taken')
            ],
            // email rules
            'emailTrim' => ['email', 'trim'],
            'emailRequired' => ['email', 'required'],
            'emailPattern' => ['email', 'email'],
            'emailUnique' => [
                'email',
                'unique',
                'targetClass' => $user,
                'message' => Yii::t('user', 'This email address has already been taken')
            ],
            // password rules
            'passwordRequired' => ['password', 'required'],
            'passwordLength' => ['password', 'string', 'min' => 6, 'max' => 72],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('user', 'Email'),
            'username' => Yii::t('user', 'Username'),
            'password' => Yii::t('user', 'Password'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function formName(): string
    {
        return 'register-form';
    }

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function register()
    {
        if (!$this->validate()) {
            return false;
        }

        /** @var User $user */
        $user = Yii::createObject($this->module->modelMap['User']);
        $user->setScenario('register');
        $this->loadAttributes($user);

        if (!$user->register()) {
            return false;
        }

        return $user;
    }

    protected function loadAttributes(User $user)
    {
        $user->setAttributes($this->attributes);
    }
}
