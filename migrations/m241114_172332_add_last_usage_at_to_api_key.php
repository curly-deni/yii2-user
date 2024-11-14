<?php

use yii\db\Migration;

/**
 * Class m241114_172332_add_last_usage_at_to_api_key
 */
class m241114_172332_add_last_usage_at_to_api_key extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('api_key', 'last_usage_at', $this->bigInteger()->notNull()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241114_172332_add_last_usage_at_to_api_key cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241114_172332_add_last_usage_at_to_api_key cannot be reverted.\n";

        return false;
    }
    */
}
