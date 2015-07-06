<?php

use yii\db\Schema;
use yii\db\Migration;

class m141120_134910_create_account_material_table extends Migration
{
    public function up()
    {
	    $this->createTable("material", [
	    	"id" => Schema::TYPE_BIGPK,
			"phoneNumber" => Schema::TYPE_STRING . ' NOT NULL',
			"nickname" => Schema::TYPE_STRING . ' DEFAULT ""',
			"sex" => Schema::TYPE_SMALLINT . ' default 2',
			"age" => Schema::TYPE_SMALLINT . ' default 0',
			"sponsor" => Schema::TYPE_SMALLINT . ' default 0',
			"location" => Schema::TYPE_STRING . ' default ""',
			"introduce" => Schema::TYPE_STRING . ' DEFAULT ""',
			"headImage" => Schema::TYPE_STRING . ' default "default"',
			"level" => Schema::TYPE_SMALLINT . ' default 0',
			"vip" => Schema::TYPE_BOOLEAN . ' default 0',
			"vipLevel" => Schema::TYPE_SMALLINT . ' default 0',
			"easemob" => Schema::TYPE_BOOLEAN . ' default 0',
			"easemobPassword" => Schema::TYPE_BOOLEAN . ' default 0', //修改环信密码结果
	    ], "DEFAULT CHARSET=utf8;");

	    //一级评论id=0
	    //$this->insert("comment", ['id' => 0, 'nickname' => 'dummy']);

    }

    public function down()
    {
        echo "m141120_134910_create_account_material_table cannot be reverted.\n";
		$this->dropTable("material");

        //return false;
    }
}
