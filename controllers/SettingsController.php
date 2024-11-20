<?php

namespace aesis\user\controllers;

use aesis\user\controllers\BaseController as Controller;
use aesis\user\models\Profile;
use aesis\user\models\SettingsForm;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\filters\VerbFilter;

class SettingsController extends Controller
{
    use ModuleTrait;

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
                'user' => ['post'],
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
            $model = Yii::createObject($this->module->modelMap['Profile']::class);
            $model->link('user', Yii::$app->user->identity);
        }

        if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
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
    public function actionUser()
    {
        $model = Yii::createObject($this->module->modelMap['SettingsForm']::class);

        if ($model->load(Yii::$app->getRequest()->post(), '') && $model->save()) {
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
