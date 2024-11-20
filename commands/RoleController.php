<?php

namespace aesis\user\commands;

use aesis\user\Finder;
use Yii;
use yii\console\Controller;
use yii\helpers\BaseConsole;

class RoleController extends Controller
{
    protected $finder;

    public function __construct($id, $module, Finder $finder, $config = [])
    {
        $this->finder = $finder;
        parent::__construct($id, $module, $config);
    }

    public function actionIndex($search, $role)
    {
        $user = $this->finder->findUserByUsernameOrEmail($search);
        if ($user === null) {
            $this->stdout(Yii::t('user', 'User is not found') . "\n", BaseConsole::FG_RED);
        } else {
            $user->role = $role;
            if ($user->save()) {
                $this->stdout(Yii::t('user', 'Role has been changed') . "\n", BaseConsole::FG_GREEN);
            } else {
                $this->stdout(Yii::t('user', 'Error occurred while changing role') . "\n", BaseConsole::FG_RED);
            }
        }
    }
}
