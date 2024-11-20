<?php

namespace aesis\user\controllers;

use aesis\user\controllers\BaseController as Controller;
use aesis\user\models\DeleteForm;
use aesis\user\models\Token;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;

class DeleteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['access']['only'][] = 'request';
        $behaviors['access']['rules'][] = ['allow' => true, 'actions' => ['request'], 'roles' => ['@']];

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
            'class' => DeleteForm::class,
        ]);



        if ($model->sendDeleteMessage()) {
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

        $token = $this->finder->findToken(['user_id' => $id, 'code' => $code, 'type' => Token::TYPE_ACCOUNT_DELETE])->one();

        if (empty($token) || !$token instanceof Token || $token->isExpired || $token->user === null) {
            return $this->makeResponse(
                '',
                Yii::t('user', 'Recovery link is invalid or expired. Please try requesting a new one.'),
                400
            );
        }

        $model = Yii::createObject([
            'class' => DeleteForm::class,
        ]);

        $data = Yii::$app->getRequest()->post();
        $model->load($data, '');

        if ($model->deleteAccount($token)) {
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
