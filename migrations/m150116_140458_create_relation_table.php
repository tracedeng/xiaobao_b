<?php

use yii\db\Schema;
use yii\db\Migration;

class m150116_140458_create_relation_table extends Migration
{
    public function up()
    {
	    $this->createTable("relation", [
	    	//"id" => Schema::TYPE_BIGPK,
			"phoneNumberA" => Schema::TYPE_STRING . ' NOT NULL',
			"phoneNumberB" => Schema::TYPE_STRING . ' NOT NULL',
			"type" => Schema::TYPE_STRING . ' NOT NULL',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
			"userAMarkB" => Schema::TYPE_STRING . ' DEFAULT ""',
			"userBMarkA" => Schema::TYPE_STRING . ' DEFAULT ""',
			'PRIMARY KEY (phoneNumberA, phoneNumberB)',
	    ], "DEFAULT CHARSET=utf8;");

    }

    public function down()
    {
        echo "m150116_140458_create_relation_table cannot be reverted.\n";
		$this->dropTable("relation");

        //return false;
    }
}
