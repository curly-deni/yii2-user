<?php

use yii\db\Migration;

/**
 * Class m241120_105110_add_role_to_user
 */
class m241120_105110_add_role_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'role', $this->string(255)->defaultValue('user')->notNull()->after('email'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241120_105110_add_role_to_user cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241120_105110_add_role_to_user cannot be reverted.\n";

        return false;
    }
    */
}
