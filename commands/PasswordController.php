<?php

namespace aesis\user\commands;

use aesis\user\Finder;
use Yii;
use yii\console\Controller;
use yii\helpers\BaseConsole;

class PasswordController extends Controller
{
    protected $finder;

    public function __construct($id, $module, Finder $finder, $config = [])
    {
        $this->finder = $finder;
        parent::__construct($id, $module, $config);
    }

    public function actionIndex($search, $password)
    {
        $user = $this->finder->findUserByUsernameOrEmail($search);
        if ($user === null) {
            $this->stdout(Yii::t('user', 'User is not found') . "\n", BaseConsole::FG_RED);
        } else {
            if ($user->resetPassword($password)) {
                $this->stdout(Yii::t('user', 'Password has been changed') . "\n", BaseConsole::FG_GREEN);
            } else {
                $this->stdout(Yii::t('user', 'Error occurred while changing password') . "\n", BaseConsole::FG_RED);
            }
        }
    }
}
