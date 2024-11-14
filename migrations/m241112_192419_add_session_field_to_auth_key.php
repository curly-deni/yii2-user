<?php

use yii\db\Migration;

/**
 * Class m241112_192419_add_session_field_to_auth_key
 */
class m241112_192419_add_session_field_to_auth_key extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('auth_key', 'is_session', $this->boolean()->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241112_192419_add_session_field_to_auth_key cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241112_192419_add_session_field_to_auth_key cannot be reverted.\n";

        return false;
    }
    */
}
