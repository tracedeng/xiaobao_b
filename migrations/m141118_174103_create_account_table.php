<?php

use yii\db\Schema;
use yii\db\Migration;

class m141118_174103_create_account_table extends Migration
{
    public function up()
    {
	    $this->createTable("account", [
	    	"id" => Schema::TYPE_BIGPK,
			"phoneNumber" => Schema::TYPE_STRING . ' NOT NULL',
			"password" => Schema::TYPE_STRING,
			"passwordMd5" => Schema::TYPE_STRING . ' NOT NULL',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
	    ], "DEFAULT CHARSET=utf8;");

    }

    public function down()
    {
        echo "m141118_174103_create_account_table cannot be reverted.\n";
		$this->dropTable("account");

        //return false;
    }
}
