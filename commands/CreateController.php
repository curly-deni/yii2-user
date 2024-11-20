<?php

namespace aesis\user\commands;

use aesis\user\helpers\Password;
use aesis\user\models\User;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\helpers\BaseConsole;

class CreateController extends Controller
{
    use ModuleTrait;

    /**
     * @throws InvalidConfigException
     */
    public function actionIndex($email, $username=null, $role=null, $password = null)
    {

        $user = Yii::createObject([
            'class' => $this->module->modelMap['User'],
            'scenario' => 'create',
            'email' => $email,
            'role' => $role,
            'username' => $username,
            'password' => $password ?? Password::generate(12),
            'passwordGenerated' => true
        ]);

        if ($user->create()) {
            $this->stdout(Yii::t('user', 'User has been created') . "!\n", BaseConsole::FG_GREEN);
        } else {
            $this->stdout(Yii::t('user', 'Please fix following errors:') . "\n", BaseConsole::FG_RED);
            foreach ($user->errors as $errors) {
                foreach ($errors as $error) {
                    $this->stdout(' - ' . $error . "\n", BaseConsole::FG_RED);
                }
            }
        }
    }
}
