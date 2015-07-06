<?php

use yii\db\Schema;
use yii\db\Migration;

class m150422_165338_create_pet_food_table extends Migration
{
    public function up()
    {
	    //宠物类型列表
	    $this->createTable("petfood", [
	    	"id" => Schema::TYPE_BIGPK,
	    	"pid" => Schema::TYPE_BIGINT . ' DEFAULT 0',
			"section" => Schema::TYPE_STRING . ' DEFAULT "#"',
			"name" => Schema::TYPE_STRING . ' DEFAULT ""',
			"info" => Schema::TYPE_STRING . ' DEFAULT ""',
			"scale" => Schema::TYPE_FLOAT . ' DEFAULT 1',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
	    ], "DEFAULT CHARSET=utf8;");

    }

    public function down()
    {
        echo "m150422_165338_create_pet_food_table cannot be reverted.\n";

		$this->dropTable("petfood");
        //return false;
    }
}
