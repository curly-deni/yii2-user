<?php

namespace aesis\user\traits;

use aesis\user\helpers\InternalChecker;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

trait ApiTrait
{
//    var $allRoutesNeedAuth = true;
//    var $useAccessControl = true;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        if (!InternalChecker::isInternalApi()) {
            $behaviors['authenticator'] = [
                'class' => HttpBearerAuth::class,
                'optional' => ['*'],
            ];
        }

        $behaviors['contentNegotiator'] = [
            'class' => 'yii\filters\ContentNegotiator',
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ]
        ];

        if ($this->useAccessControl ?? true) {
            $behaviors['access'] = [
                'class' => AccessControl::class,
                'rules' => [],
                'denyCallback' => function ($rule, $action) {
                    throw new ForbiddenHttpException($action->uniqueId);
                },
            ];
        }


        if (($this->allRoutesNeedAuth ?? true) && ($this->useAccessControl ?? true)) {
            $behaviors['access']['rules'][] = [
                'allow' => true,
                'actions' => [],
                'roles' => ['@'],
            ];
        }

        return $behaviors;
    }

    public $user;

    /**
     * @throws Throwable
     */
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->getIdentity();
    }

    /**
     * @throws Throwable
     */
    public function getIdentity(): void
    {
        $this->user = Yii::$app->user->getIdentity();
    }
}