<?php

namespace aesis\user\models;

use aesis\user\helpers\DeviceDetector;
use aesis\user\helpers\Location;
use aesis\user\traits\ModuleTrait;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * This is the model class for table "auth_key".
 *
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property boolean $is_session
 * @property string|null $device_info
 * @property string|null $location
 * @property int $last_login_at
 *
 * @property User $user
 */
class AuthKey extends ActiveRecord
{
    use ModuleTrait;

    public static function tableName()
    {
        return 'auth_key';
    }

    public function rules()
    {
        return [
            [['user_id', 'key', 'last_login_at'], 'required'],
            [['user_id', 'last_login_at'], 'default', 'value' => null],
            [['is_session'], 'default', 'value' => false],
            [['user_id', 'last_login_at'], 'integer'],
            [['key', 'device_info', 'location'], 'string', 'max' => 255],
            [['key'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['User'], 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'key' => 'Key',
            'device_info' => 'Device Info',
            'location' => 'Location',
            'last_login_at' => 'Last Login At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne($this->module->modelMap['User'], ['id' => 'user_id']);
    }

    protected static function getKeyFromCookie()
    {
        $value = Yii::$app->getRequest()->getCookies()->getValue('_identity');
        if ($value === null) {
            return null;
        }
        $data = json_decode($value, true);
        if (is_array($data) && count($data) == 3) {
            list(, $authKey,) = $data;

            /** @var ?AbstractKeyProvider $key */
            $key = static::findOne(['key' => $authKey]);
            return $key;
        }
        return null;
    }

    protected static function getKeyFromSession()
    {
        $authKey = Yii::$app->getSession()->get('__authKey');
        $key = static::findOne(['key' => $authKey]);
        return $key;
    }

    public static function getCurrentKey()
    {
        $cookie = self::getKeyFromCookie();
        if ($cookie)
            return $cookie;
        return self::getKeyFromSession();
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function processDelete()
    {
        $this->delete();
    }

    /**
     * @throws Exception
     * @throws \yii\db\Exception
     * @throws InvalidConfigException
     */
    public static function getNewKey($user_id, $session)
    {
        $key = Yii::$app->security->generateRandomString();
        $model = new static();
        $model->user_id = $user_id;
        $model->key = $key;
        $model->last_login_at = time();
        $model->location = Location::getLocation();
        $model->device_info = DeviceDetector::getDeviceInfo();

        $model->is_session = $session;
        // если сессионная, то через сутки удалить
        // также надо удалять просроченные

        if ($model->save()) {
            \Yii::debug('save key ' . $key . " ". $user_id . $session);
        }
        else {
            \Yii::debug($model->getErrors());
        }
        return $key;
    }

    /**
     * @throws \yii\db\Exception
     * @throws InvalidConfigException
     */
    public static function validateKey($user_id, $key, $ignoreDeviceInfo=false)
    {
        $query = [
            'user_id' => intval($user_id),
            'key' => $key,
            'location' => Location::getLocation()
        ];

        if (!$ignoreDeviceInfo) {
            $query['device_info'] = DeviceDetector::getDeviceInfo();
        }

        $model = static::findOne($query);

        if ($model === null) {
            return false;
        }

        $model->last_login_at = time();
        $model->save();
        return true;
    }
}
