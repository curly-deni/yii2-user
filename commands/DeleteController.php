<?php

namespace aesis\user\commands;

use aesis\user\Finder;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\db\StaleObjectException;
use yii\helpers\BaseConsole;

class DeleteController extends Controller
{
    protected Finder $finder;

    public function __construct($id, $module, Finder $finder, $config = [])
    {
        $this->finder = $finder;
        parent::__construct($id, $module, $config);
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function actionIndex($search)
    {
        if ($this->confirm(Yii::t('user', 'Are you sure? Deleted user can not be restored'))) {
            $user = $this->finder->findUserByUsernameOrEmail($search);
            if ($user === null) {
                $this->stdout(Yii::t('user', 'User is not found') . "\n", BaseConsole::FG_RED);
            } else {
                if ($user->delete()) {
                    $this->stdout(Yii::t('user', 'User has been deleted') . "\n", BaseConsole::FG_GREEN);
                } else {
                    $this->stdout(Yii::t('user', 'Error occurred while deleting user') . "\n", BaseConsole::FG_RED);
                }
            }
        }
    }
}
