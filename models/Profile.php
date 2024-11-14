<?php

namespace aesis\user\models;

use aesis\user\traits\ModuleTrait;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "profile".
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property string|null $surname
 * @property string|null $bio
 * @property string|null $birthday
 *
 * @property User $user
 */
class Profile extends ActiveRecord
{
    use ModuleTrait;

    protected $module;

    public function init(): void
    {
        $this->module = Yii::$app->getModule('user');
    }

    public static function tableName()
    {
        return 'profile';
    }

    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'default', 'value' => null],
            [['user_id'], 'integer'],
            [['bio'], 'string'],
            [['birthday'], 'safe'],
            [['name', 'surname'], 'string', 'max' => 255],
            [['user_id'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'name' => 'Name',
            'surname' => 'Surname',
            'bio' => 'Bio',
            'birthday' => 'Birthday',
        ];
    }

    public function getUser()
    {
        return $this->hasOne($this->module->modelMap['User'], ['id' => 'user_id']);
    }

}