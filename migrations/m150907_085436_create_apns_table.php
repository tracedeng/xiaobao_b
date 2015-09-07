<?php

use yii\db\Schema;
use yii\db\Migration;

class m150907_085436_create_apns_table extends Migration
{
    public function up()
    {
	    $this->createTable("apns", [
	    	"id" => Schema::TYPE_BIGPK,
			"phoneNumber" => Schema::TYPE_STRING . ' NOT NULL',
			"ownerId" => Schema::TYPE_BIGINT . ' NOT NULL',
			"token" => Schema::TYPE_STRING . ' DEFAULT ""',
			"badge" => Schema::TYPE_BIGINT . ' DEFAULT 0',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
	    ], "DEFAULT CHARSET=utf8;");

    }

    public function down()
    {
        echo "m150907_085436_create_apns_table cannot be reverted.\n";
		$this->dropTable("apns");

        //return false;
    }
    
    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }
    
    public function safeDown()
    {
    }
    */
}
