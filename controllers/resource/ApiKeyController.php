<?php

namespace aesis\user\controllers\resource;

use aesis\rest\controllers\ControllerCRUDAbstract;
use aesis\user\traits\EventTrait;
use aesis\user\traits\ModuleTrait;
use yii\filters\VerbFilter;

class ApiKeyController extends ControllerCRUDAbstract
{
    use ModuleTrait;
    use EventTrait;

    const EVENT_AFTER_API_KEY_CREATE = 'afterApiKeyCreate';
    const EVENT_AFTER_API_KEY_DELETE = 'afterApiKeyDelete';
    const EVENT_AFTER_API_KEY_UPDATE = 'afterApiKeyUpdate';
    const EVENT_AFTER_API_KEY_DELETE_ALL = 'afterApiKeyDeleteAll';

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete-all' => ['delete', 'post'],
            ],
        ];

        return $behaviors;
    }

    public function actionDeleteAll()
    {

        $result = $this->modelClass::deleteAll();

        if ($result) {
            $event = $this->getKeyEvent($this->user, 'all');
            $this->trigger(self::EVENT_AFTER_API_KEY_DELETE_ALL, $event);
        }

        return $this->makeResponse('', '', $result ? 200 : 500);
    }

    function RESTcreate($id)
    {
        $result = parent::RESTcreate($id);

        if ($result) {
            $event = $this->getKeyEvent($this->user, $result['data']);
            $this->trigger(self::EVENT_AFTER_API_KEY_CREATE, $event);
        }

        return $result;
    }

    function RESTupdate($id)
    {
        $result = parent::RESTupdate($id);

        if ($result) {
            $event = $this->getKeyEvent($this->user, $result['data']);
            $this->trigger(self::EVENT_AFTER_API_KEY_UPDATE, $event);
        }

        return $result;
    }

    function RESTdelete($id)
    {
        $key = $this->modelClass::findOne($id);
        $result = parent::RESTdelete($id);

        if ($result) {
            $event = $this->getKeyEvent($this->user, $key->toArray());
            $this->trigger(self::EVENT_AFTER_API_KEY_DELETE, $event);
        }

        return $result;
    }


    function _getModelClass(): string
    {
        return $this->getModule()->modelMap['ApiKeyResource'];
    }

}