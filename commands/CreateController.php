<?php

namespace aesis\user\commands;

use aesis\user\models\User;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\helpers\BaseConsole;

class CreateController extends Controller
{

    /**
     * @throws InvalidConfigException
     */
    public function actionIndex($email, $username, $password = null)
    {

        $user = Yii::createObject([
            'class' => User::class,
            'scenario' => 'create',
            'email' => $email,
            'username' => $username,
            'password' => $password,
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
