<?php

namespace aesis\user\models;

use aesis\user\helpers\DeviceDetector;
use aesis\user\traits\ModuleTrait;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\StaleObjectException;

/**
 * This is the model class for table "api_key".
 *
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property boolean $by_creds
 * @property string|null $name
 *
 * @property User $user
 */
class ApiKey extends AbstractKeyProvider
{
    use ModuleTrait;

    public static function tableName()
    {
        return 'api_key';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'key', 'by_creds'], 'required'],
            [['user_id'], 'default', 'value' => null],
            [['by_creds'], 'default', 'value' => false],
            [['user_id'], 'integer'],
            [['key', 'name'], 'string', 'max' => 255],
            [['key'], 'unique'],
            [['by_creds'], 'boolean'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'key' => 'Key',
            'by_creds' => 'By Credentials',
            'name' => 'Name',
        ];
    }

    public function getUser()
    {
        return $this->hasOne($this->module->modelMap['User'], ['id' => 'user_id']);
    }

    public static function getCurrentKey()
    {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');

        if ($authHeader !== null && preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            return static::findOne(['key' => $token]);
        } else {
            return null;
        }

    }

    public static function findIdentity($key)
    {
        $user = static::findOne(['key' => $key]);

        return is_null($user) ? $user : $user->user;
    }

    /**
     * @throws Exception
     * @throws \yii\db\Exception
     * @throws InvalidConfigException
     */
    public static function getNewKey($user_id, $by_creds = false, $name = null)
    {
        $key = Yii::$app->security->generateRandomString();
        $model = new static();
        $model->user_id = $user_id;
        $model->key = $key;
        $model->by_creds = $by_creds;
        if (empty($name))
            $model->name = DeviceDetector::getDeviceInfo();
        $model->save();
        return $key;
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function processDelete()
    {
        if ($this->by_creds) {
            $this->delete();
        }
    }
}