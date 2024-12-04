<?php

namespace aesis\user\controllers\resource;

use aesis\rest\controllers\ControllerCRUDAbstract;
use aesis\user\traits\ModuleTrait;
use yii\filters\VerbFilter;

class ProfileController extends ControllerCRUDAbstract
{
    use ModuleTrait;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['access']['only'] = ['index', 'me'];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'me' => ['get']
            ],
        ];

        return $behaviors;
    }

    public function checkAccess($method, $id): bool
    {
        if ($this->user->isAdmin)
            return true;

        if (in_array($method, ['create', 'delete', 'update']))
            return false;

        return true;
    }


    public function actionMe()
    {
        $profile = $this->user->profile;
        return $this->makeResponse($profile);
    }


    public function actionFind()
    {
        $username = \Yii::$app->request->get('search');

        if (empty($username)) {
            return $this->makeResponse(
                '',
                \Yii::t('user', 'Empty search query parameter'),
                400
            );
        }

        if (str_starts_with($username, 'id')) {
            return $this->RESTview(intval(substr($username, 2)));
        }
        $user = $this->getModule()->modelMap['User']::findOne(['username' => $username]);
        return $this->RESTview($user->id ?? null);
    }


    function _getModelClass(): string
    {
        return $this->getModule()->modelMap['ProfileResource'];
    }
}