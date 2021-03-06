<?php

use rokorolov\parus\user\models;
use rokorolov\parus\user\contracts\DefaultInstallInterface;
use rokorolov\parus\language\models\Language;
use yii\db\Migration;

class m160719_080253_init_module_user extends Migration
{
    public $settings;
    
    public function init()
    {
        $this->settings = Yii::createObject('rokorolov\parus\user\helpers\DefaultInstall');
        
        if (!$this->settings instanceof DefaultInstallInterface) {
            throw new Exception("Migration failed. Class rokorolov\parus\user\helpers\DefaultInstall must be an instance of rokorolov\parus\user\contracts\DefaultInstallInterface");
        }

        parent::init();
    }
    
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        
        $this->createTable(models\User::tableName(), [
            'id' => $this->primaryKey(10),
            'username' => $this->string()->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string()->notNull(),
            'password_reset_token' => $this->string()->unique(),
            'email' => $this->string()->notNull(),
            'role' => $this->string(255)->null()->defaultValue(null),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->dateTime()->notNull()->defaultValue('0000-00-00 00:00:00'),
            'updated_at' => $this->dateTime()->notNull()->defaultValue('0000-00-00 00:00:00'),
            'deleted_at' => $this->timestamp()->null()->defaultValue(null)
        ], $tableOptions);
        
        $this->createIndex('username_idx', models\User::tableName(), 'username', true);
        $this->createIndex('email_idx', models\User::tableName(), 'email', true);
        $this->createIndex('status_idx', models\User::tableName(), 'status');
        $this->createIndex('created_at_idx', models\User::tableName(), 'created_at');
        
        $this->createTable(models\Profile::tableName(), [
            'user_id' => $this->integer(10)->notNull(),
            'name' => $this->string(50)->notNull(),
            'surname' => $this->string(50)->notNull(),
            'language' => $this->string(7)->null()->defaultValue(null),
            'avatar_url' => $this->string(64)->notNull(),
            'last_login_on' => $this->dateTime()->notNull()->defaultValue('0000-00-00 00:00:00'),
            'last_login_ip' => $this->string(50)->notNull()
            ],
            $tableOptions
        );
        
        $this->addPrimaryKey('', models\Profile::tableName(), 'user_id');
        $this->addForeignKey('fk__profile_user_id__user_id', models\Profile::tableName(), 'user_id', models\User::tableName(), 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk__profile_language__language_lang_code', models\Profile::tableName(), 'language', Language::tableName(), 'lang_code', 'SET NULL', 'CASCADE');
        
        if ($this->settings->shouldInstallDefaults() === true) {
            $this->executeUserSql();
            $this->executeProfileSql();
        }
    }
    
    private function executeUserSql()
    {
        $this->insert(models\User::tableName(), $this->settings->getUserParams());
    }
    
    private function executeProfileSql()
    {
        $this->insert(models\Profile::tableName(), $this->settings->getUserProfileParams());
    }
    
    public function down()
    {
        $this->dropForeignKey('fk__profile_user_id__user_id', models\Profile::tableName());
        $this->dropForeignKey('fk__profile_language__language_lang_code', models\Profile::tableName());
        
        $this->dropIndex('username_idx', models\User::tableName());
        $this->dropIndex('email_idx', models\User::tableName());
        $this->dropIndex('status_idx', models\User::tableName());
        $this->dropIndex('created_at_idx', models\User::tableName());
        
        $this->dropTable(models\Profile::tableName());
        $this->dropTable(models\User::tableName());
    }
}
