<?php

namespace aesis\user\models\resource;

use aesis\rest\traits\hasScenarios;
use aesis\user\traits\ProfileFind;
use yii\helpers\ArrayHelper;

class Profile extends \aesis\user\models\Profile
{
    use hasScenarios;
    use ProfileFind;

    public function scenarios()
    {
        return ArrayHelper::merge(parent::scenarios(), [
            self::SCENARIO_CREATE => ['user_id', 'name', 'surname', 'bio', 'birthday'],
            self::SCENARIO_UPDATE => ['name', 'surname', 'bio', 'birthday']
        ]);
    }
}