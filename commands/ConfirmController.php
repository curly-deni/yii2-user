<?php

namespace aesis\user\commands;

use aesis\user\Finder;
use Yii;
use yii\console\Controller;
use yii\helpers\BaseConsole;

class ConfirmController extends Controller
{
    protected Finder $finder;

    public function __construct($id, $module, Finder $finder, $config = [])
    {
        $this->finder = $finder;
        parent::__construct($id, $module, $config);
    }


    public function actionIndex($search)
    {
        $user = $this->finder->findUserByUsernameOrEmail($search);
        if ($user === null) {
            $this->stdout(Yii::t('user', 'User is not found') . "\n", BaseConsole::FG_RED);
        } else {
            if ($user->confirm()) {
                $this->stdout(Yii::t('user', 'User has been confirmed') . "\n", BaseConsole::FG_GREEN);
            } else {
                $this->stdout(Yii::t('user', 'Error occurred while confirming user') . "\n", BaseConsole::FG_RED);
            }
        }
    }
}
