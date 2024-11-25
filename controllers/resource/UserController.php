<?php

namespace aesis\user\controllers\resource;

use aesis\rest\controllers\ControllerCRUDAbstract;
use aesis\user\traits\ModuleTrait;
use Yii;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;

class UserController extends ControllerCRUDAbstract
{
    use ModuleTrait;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['access']['only'] = ['index', 'vefify-password'];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'me' => ['get'],
                'verify-password' => ['post'],
                'get-last-activity-time' => ['get']
            ],
        ];

        return $behaviors;
    }

    public function checkAccess($method, $id): bool
    {
        return $this->user->isAdmin;
    }

    public function actionGetLastActivityTime()
    {
        $user_id = Yii::$app->getRequest()->get('id');

        if (empty($user_id)) {
            throw new ForbiddenHttpException(\Yii::t('error', 'Missing user id'));
        }

        $user = User::findOne(intval($user_id));
        if (empty($user)) {
            throw new ForbiddenHttpException(\Yii::t('error', 'User not found'));
        }

        $keys = $user->authKeys;
        $lastLoginAt = 0;
        foreach ($keys as $key) {
            if ($key->last_login_at > $lastLoginAt) {
                $lastLoginAt = $key->last_login_at;
            }
        }
        return $this->makeResponse($lastLoginAt);
    }

    public function actionMe()
    {
        return $this->makeResponse(
            $this->user,
            '',
            empty($this->user) ? 403 : 200
        );
    }

    public function actionVerifyPassword()
    {

        $password = \Yii::$app->getRequest()->post()['password'] ?? '';
        $isValid = Yii::$app->security->validatePassword($password, $this->user->password_hash);

        return $this->makeResponse(
            $isValid,
            '',
            $isValid ? 200 : 403
        );
    }

    function _getModelClass(): string
    {
        return $this->getModule()->modelMap['UserResource'];
    }
}