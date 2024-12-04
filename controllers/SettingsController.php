<?php

namespace aesis\user\controllers;

use aesis\user\controllers\BaseController as Controller;
use aesis\user\models\Profile;
use aesis\user\models\SettingsForm;
use aesis\user\traits\EventTrait;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\filters\VerbFilter;

class SettingsController extends Controller
{
    use ModuleTrait;
    use EventTrait;

    const EVENT_AFTER_ACCOUNT_UPDATE = 'afterAccountUpdate';
    const EVENT_AFTER_PROFILE_UPDATE = 'afterProfileUpdate';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['access']['rules'][] = [
            'allow' => true,
            'actions' => [],
            'roles' => ['@']
        ];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'profile' => ['post'],
                'account' => ['post'],
            ],
        ];

        return $behaviors;
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionProfile()
    {
        $model = $this->finder->findProfileById(Yii::$app->user->identity->getId());

        if ($model == null) {
            $model = Yii::createObject($this->module->modelMap['Profile']);
            $model->link('user', Yii::$app->user->identity);
        }

        if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
            $event = $this->getUserEvent($this->user);
            $this->trigger(self::EVENT_AFTER_PROFILE_UPDATE, $event);
            return $this->makeResponse(
                '',
                Yii::t('user', 'Your profile has been updated.')
            );
        }

        $errors = implode(". ", array_map(function ($error) {
            return implode(", ", $error);
        }, $model->getErrors()));

        return $this->makeResponse(
            '',
            Yii::t('user', 'Your profile has not been updated.')
            . (empty($errors) ? "" : " ") . $errors,
            400
        );
    }

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function actionAccount()
    {
        $model = Yii::createObject($this->module->modelMap['SettingsForm']);

        if ($model->load(Yii::$app->getRequest()->post(), '') && $model->save()) {
            $event = $this->getUserEvent($this->user);
            $this->trigger(self::EVENT_AFTER_ACCOUNT_UPDATE, $event);
            return $this->makeResponse(
                '',
                Yii::t('user', 'Your account details have been updated.')
                . (empty($model->message) ? "" : " ") . $model->message
            );
        }

        $errors = implode(". ", array_map(function ($error) {
            return implode(", ", $error);
        }, $model->getErrors()));

        return $this->makeResponse(
            '',
            Yii::t('user', 'Your account details have not been updated.')
            . (empty($errors) ? "" : " ") . $errors,
            400
        );
    }


}
