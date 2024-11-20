<?php

namespace aesis\user\controllers;

use aesis\user\controllers\BaseController as Controller;
use aesis\user\models\Token;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;

class DeleteController extends Controller
{
    use ModuleTrait;

    const EVENT_BEFORE_USER_DELETE = 'beforeUserDelete';
    const EVENT_AFTER_USER_DELETE = 'afterUserDelete';
    const EVENT_BEFORE_USER_DELETE_REQUEST = 'beforeUserDeleteRequest';
    const EVENT_AFTER_USER_DELETE_REQUEST = 'afterUserDeleteRequest';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['access']['only'][] = 'request';
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['request'], 'roles' => ['admin']];

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
        if (!($this->module->enableAccountDelete ?? false)) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'Account deletion is disabled.'),
                503
            );
        }

        $model = Yii::createObject([
            'class' => $this->module->modelMap['DeleteForm'],
        ]);

        $this->trigger(self::EVENT_BEFORE_USER_DELETE_REQUEST);

        if ($model->sendDeleteMessage()) {
            $this->trigger(self::EVENT_AFTER_USER_DELETE_REQUEST);
            return $this->makeResponse(
                '',
                Yii::t('user', 'Delete message sent')
            );
        }

        return $this->makeResponse(
            '',
            Yii::t('user', 'Delete message could not be sent.'),
            500
        );
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionDelete($id, $code)
    {
        if (!($this->module->enableAccountDelete ?? false)) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'Account deletion is disabled.'),
                503
            );
        }

        $token = $this->finder->findToken(['user_id' => $id, 'code' => $code, 'type' => $this->module->modelMap['Token']::TYPE_ACCOUNT_DELETE])->one();

        $this->trigger(self::EVENT_BEFORE_USER_DELETE);

        if (empty($token) || !$token instanceof Token || $token->isExpired || $token->user === null) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'Recovery link is invalid or expired. Please try requesting a new one.'),
                400
            );
        }

        $model = Yii::createObject([
            'class' => $this->module->modelMap['DeleteForm'],
        ]);

        $data = Yii::$app->getRequest()->post();
        $model->load($data, '');

        if ($model->deleteAccount($token)) {
            $this->trigger(self::EVENT_AFTER_USER_DELETE);
            return $this->makeResponse(
                '',
                Yii::t('user', 'Account has been deleted')
            );
        }

        return $this->makeResponse(
            '',
            Yii::t('user', 'Account could not be deleted.'),
            500
        );
    }
}
