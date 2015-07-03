<?php

use yii\db\Schema;
use yii\db\Migration;

class m141217_130711_create_pet_table extends Migration
{
    public function up()
    {
	    $this->createTable("pet", [
	    	"id" => Schema::TYPE_BIGPK,
			"nickname" => Schema::TYPE_STRING . ' DEFAULT ""',
			"kind" => Schema::TYPE_BIGINT . ' DEFAULT 0',
			"sex" => Schema::TYPE_SMALLINT . ' DEFAULT 2',
			"birthday" => Schema::TYPE_STRING . ' DEFAULT ""',
			"weight" => Schema::TYPE_FLOAT . ' DEFAULT 0',
			"headImage" => Schema::TYPE_STRING . ' DEFAULT "default"',
			"introduce" => Schema::TYPE_TEXT . ' DEFAULT ""',
			"ownerId" => Schema::TYPE_BIGINT . ' NOT NULL',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
			"isDeleted" => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
			"isBindGprs" => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
			"gprsId" => Schema::TYPE_STRING . ' DEFAULT ""',
			"bindGprsTime" => Schema::TYPE_DATETIME . ' DEFAULT "1970-01-01 00:00:00"',
			"sponsorOpen" => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
			"sponsorCount" => Schema::TYPE_BIGINT . ' DEFAULT 0',
	    	"food" => Schema::TYPE_BIGINT . ' default 0',
	    ], "DEFAULT CHARSET=utf8;");

	    //宠物助养关系
	    $this->createTable("sponsor", [
	    	"petId" => Schema::TYPE_BIGINT . ' NOT NULL',
			"sponsorId" => Schema::TYPE_BIGINT . ' NOT NULL',
			"sponsorTime" => Schema::TYPE_DATETIME . ' NOT NULL',
			'PRIMARY KEY (petId, sponsorId)',
	    ], "DEFAULT CHARSET=utf8;");

	    //宠物类型列表
	    $this->createTable("petkind", [
	    	"id" => Schema::TYPE_BIGPK,
			"name" => Schema::TYPE_STRING . ' NOT NULL',
			"info" => Schema::TYPE_TEXT . ' DEFAULT ""',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
	    	"section" => Schema::TYPE_STRING . ' DEFAULT ""',
	    ], "DEFAULT CHARSET=utf8;");

	    //插入宠物类型列表
	    $this->insert("petkind", ['name' => '吉娃娃', 'info' => '吉娃娃(西班牙语：Chihuahueño，英语：Chihuahua)也译作奇瓦瓦、芝娃娃、奇娃娃、齐花花，属小型犬种里次小型，优雅、警惕、动作迅速，以匀称的体格和娇小的体型广受人们的喜爱。', 'time' => 'now()']);

    }

    public function down()
    {
        echo "m141217_130711_create_pet_table cannot be reverted.\n";
		$this->dropTable("pet");
		$this->dropTable("petkind");
		$this->dropTable("sponsor");

        //return false;
    }
}
