<?php

namespace aesis\user\controllers;

use aesis\user\controllers\BaseController as Controller;
use aesis\user\models\Token;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;

class RecoveryController extends Controller
{
    use ModuleTrait;

    const EVENT_BEFORE_RECOVERY_REQUEST = 'beforeRecoveryRequest';
    const EVENT_AFTER_RECOVERY_REQUEST = 'afterRecoveryRequest';
    const EVENT_BEFORE_RECOVERY_RESET = 'beforeRecoveryReset';
    const EVENT_AFTER_RECOVERY_RESET = 'afterRecoveryReset';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['access']['only'] = ['request', 'reset'];
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['request', 'reset'], 'roles' => ['?']];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'request' => ['post'],
                'reset' => ['post'],
            ]
        ];

        return $behaviors;
    }


    /**
     * @throws InvalidConfigException
     */
    public function actionRequest()
    {

        if (!($this->module->enablePasswordRecovery ?? false)) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'Password recovery is disabled.'),
                503
            );
        }

        $model = Yii::createObject([
            'class' => $this->module->modelMap['RecoveryForm'],
            'scenario' => $this->module->modelMap['RecoveryForm']::SCENARIO_REQUEST,
        ]);

        $data = Yii::$app->getRequest()->post();
        $model->email = $data['email'] ?? '';

        if (empty($model->email)) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'User email is required.'),
                400
            );
        }

        $this->trigger(self::EVENT_BEFORE_RECOVERY_REQUEST);

        if ($model->sendRecoveryMessage()) {
            $this->trigger(self::EVENT_AFTER_RECOVERY_REQUEST);
            return $this->makeResponse(
                '',
                Yii::t('user', 'Recovery message sent')
            );
        }

        return $this->makeResponse(
            '',
            Yii::t('user', 'Recovery message could not be sent.'),
            500
        );
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionReset($id, $code)
    {
        if (!($this->module->enablePasswordRecovery ?? false)) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'Password recovery is disabled.'),
                503
            );
        }

        $token = $this->finder->findToken(['user_id' => $id, 'code' => $code, 'type' => $this->module->modelMap['Token']::TYPE_RECOVERY])->one();

        if (empty($token) || !$token instanceof Token || $token->isExpired || $token->user === null) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'Recovery link is invalid or expired. Please try requesting a new one.'),
                400
            );
        }

        $model = Yii::createObject([
            'class' => $this->module->modelMap['RecoveryForm'],
            'scenario' => $this->module->modelMap['RecoveryForm']::SCENARIO_RESET,
        ]);

        $data = Yii::$app->getRequest()->post();
        $model->load($data, '');

        if (empty($model->password)) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'New password is required.'),
                400
            );
        }

        $this->trigger(self::EVENT_BEFORE_RECOVERY_RESET);

        if ($model->resetPassword($token)) {
            $this->trigger(self::EVENT_AFTER_RECOVERY_RESET);
            return $this->makeResponse(
                '',
                Yii::t('user', 'Password has been changed')
            );
        }

        return $this->makeResponse(
            '',
            Yii::t('user', 'Password could not be changed.'),
            500
        );
    }
}
