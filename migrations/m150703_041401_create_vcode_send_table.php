<?php

use yii\db\Schema;
use yii\db\Migration;

class m150703_041401_create_vcode_send_table extends Migration
{
    public function up()
    {
	    $this->createTable("vcode", [
	    	"id" => Schema::TYPE_BIGPK,
			"phoneNumber" => Schema::TYPE_STRING . ' NOT NULL',
			"vcode" => Schema::TYPE_STRING . ' DEFAULT ""',
			"retcode" => Schema::TYPE_SMALLINT,
			"sendid" => Schema::TYPE_STRING . ' DEFAULT "0"',
			"msg" => Schema::TYPE_STRING . ' DEFAULT ""',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
	    ], "DEFAULT CHARSET=utf8;");

    }

    public function down()
    {
        echo "m150703_041401_create_vcode_send_table cannot be reverted.\n";
		$this->dropTable("vcode");

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
