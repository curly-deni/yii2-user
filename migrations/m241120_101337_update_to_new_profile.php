<?php

use yii\db\Migration;

/**
 * Class m241120_101337_update_to_new_profile
 */
class m241120_101337_update_to_new_profile extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Добавляем временную колонку temp_id как nullable
        $this->addColumn('{{%profile}}', 'temp_id', $this->integer()->null()->after('id'));

        // Переносим данные из user_id в temp_id
        $this->execute('UPDATE {{%profile}} SET temp_id = user_id');

        // Удаляем колонку user_id
        $this->dropColumn('{{%profile}}', 'user_id');

        // Если id является первичным ключом, удаляем его
        if ($this->db->getTableSchema('{{%profile}}')->primaryKey) {
            $this->dropColumn('{{%profile}}', 'id');
        }

        // Переименовываем временную колонку temp_id в id
        $this->renameColumn('{{%profile}}', 'temp_id', 'id');

        // Добавляем новый PRIMARY KEY на колонку id
        $this->addPrimaryKey('PRIMARY', '{{%profile}}', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241120_101337_update_to_new_profile cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241120_101337_update_to_new_profile cannot be reverted.\n";

        return false;
    }
    */
}
