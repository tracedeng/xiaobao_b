<?php

use yii\db\Schema;
use yii\db\Migration;

//上次读取好友朋友圈的last序号，冒泡提示

class m150423_214654_create_circle_unread_sequence_table extends Migration
{
    public function up()
    {
	    $this->createTable("sequence", [
			"id" => Schema::TYPE_BIGINT . ' NOT NULL',
			"lastSequence" => Schema::TYPE_BIGINT . ' DEFAULT 0',
	    ], "DEFAULT CHARSET=utf8;");
    }

    public function down()
    {
        echo "m150423_214654_create_circle_unread_sequence_table cannot be reverted.\n";
		$this->dropTable("sequence");

        //return false;
    }
}
