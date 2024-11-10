<?php

namespace aesis\user\migrations;

use yii\db\Migration;

/**
 * Class m241101_141824_init
 */
class m241101_141824_init extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Таблица User
        $this->createTable('{{%user}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'username' => $this->string()->notNull(),
            'email' => $this->string()->notNull(),
            'unconfirmed_email' => $this->string(),
            'password_hash' => $this->string()->notNull(),
            'confirmed_at' => $this->bigInteger(),
            'blocked_at' => $this->bigInteger(),
            'created_at' => $this->bigInteger()->notNull(),
            'updated_at' => $this->bigInteger()->notNull(),
            'flags' => $this->integer()->defaultValue(0),
        ]);

        // Индексы для таблицы User
        $this->createIndex('idx_user_username_unique', '{{%user}}', 'username', true);
        $this->createIndex('idx_user_email_unique', '{{%user}}', 'email', true);

        // Таблица AuthKey
        $this->createTable('{{%auth_key}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'user_id' => $this->bigInteger()->unsigned()->notNull(),
            'key' => $this->string()->notNull(),
            'device_info' => $this->string(),
            'location' => $this->string(),
            'last_login_at' => $this->bigInteger()->notNull(),
        ]);
        $this->addForeignKey(
            'fk_auth_key_user',
            '{{%auth_key}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // Таблица ApiKey
        $this->createTable('{{%api_key}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'user_id' => $this->bigInteger()->unsigned()->notNull(),
            'key' => $this->string()->notNull(),
            'by_creds' => $this->boolean()->notNull()->defaultValue(false),
            'name' => $this->string(),
        ]);
        $this->addForeignKey(
            'fk_api_key_user',
            '{{%api_key}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // Индекс для таблицы ApiKey
        $this->createIndex('idx_api_key_unique', '{{%api_key}}', 'key', true);

        // Таблица Profile
        $this->createTable('{{%profile}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'user_id' => $this->bigInteger()->unsigned()->notNull(),
            'name' => $this->string(),
            'surname' => $this->string(),
            'nickname' => $this->string(),
            'bio' => $this->text(),
            'birthday' => $this->date(),
        ]);
        $this->addForeignKey(
            'fk_profile_user',
            '{{%profile}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // Unique index to enforce one-to-one relationship
        $this->createIndex('idx_profile_user_id_unique', '{{%profile}}', 'user_id', true);

        // Индекс для таблицы Profile
        $this->createIndex('idx_profile_nickname_unique', '{{%profile}}', 'nickname', true);

        // Таблица Token
        $this->createTable('{{%token}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'user_id' => $this->bigInteger()->unsigned()->notNull(),
            'code' => $this->string()->notNull(),
            'created_at' => $this->bigInteger()->notNull(),
            'type' => $this->smallInteger()->notNull(),
        ]);
        $this->addForeignKey(
            'fk_token_user',
            '{{%token}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241101_141824_init cannot be reverted.\n";

        return false;
    }
}