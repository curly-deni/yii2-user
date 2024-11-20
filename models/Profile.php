<?php

namespace aesis\user\models;

use aesis\user\traits\ModuleTrait;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "profile".
 *
 * @property int $id
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

    public static function tableName()
    {
        return 'profile';
    }

    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id'], 'integer'],
            [['id'], 'unique'], // Уникальность id
            [['bio'], 'string'],
            [['birthday'], 'safe'],
            [['name', 'surname'], 'string', 'max' => 255],
            [['id'], 'exist', 'skipOnError' => true, 'targetClass' => $this->module->modelMap['User'], 'targetAttribute' => ['id' => 'id']],
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['username'] = function () {
            return $this->user->username ?? null;
        };
        return $fields;
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'surname' => 'Surname',
            'bio' => 'Bio',
            'birthday' => 'Birthday',
        ];
    }

    public function getUser()
    {
        return $this->hasOne($this->module->modelMap['User'], ['id' => 'id']);
    }

}