<?php

namespace aesis\user\controllers;

use aesis\user\controllers\BaseController as Controller;
use aesis\user\models\Token;
use aesis\user\traits\EventTrait;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;

class DeleteController extends Controller
{
    use ModuleTrait;
    use EventTrait;

    const EVENT_AFTER_USER_DELETE = 'afterUserDelete';
    const EVENT_AFTER_USER_DELETE_REQUEST = 'afterUserDeleteRequest';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['access']['only'][] = 'index';
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['index'], 'roles' => ['@']];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['post'],
                'confirm' => ['post'],
            ]
        ];

        return $behaviors;
    }


    /**
     * @throws InvalidConfigException
     */
    public function actionIndex()
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

        $result = $model->sendDeleteMessage();

        if ($result) {
            $event = $this->getTokenEvent($this->user, $result);

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
    public function actionConfirm($id, $code)
    {
        if (!($this->module->enableAccountDelete ?? false)) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'Account deletion is disabled.'),
                503
            );
        }

        $token = $this->finder->findToken(['user_id' => $id, 'code' => $code, 'type' => $this->module->modelMap['Token']::TYPE_ACCOUNT_DELETE])->one();

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
        $result = $model->deleteAccount($token);

        if ($result) {
            $event = $this->getUserEvent($result);
            $this->trigger(self::EVENT_AFTER_USER_DELETE, $event);
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
