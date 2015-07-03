<?php

use yii\db\Schema;
use yii\db\Migration;

class m141203_133237_create_circle_table extends Migration
{
    public function up()
    {
	    //朋友圈
	    $this->createTable("circle", [
	    	"id" => Schema::TYPE_BIGPK,
			"ownerId" => Schema::TYPE_BIGINT . ' NOT NULL',
			"type" => Schema::TYPE_SMALLINT . ' NOT NULL',
			"releaseTime" => Schema::TYPE_DATETIME . ' NOT NULL',
			"detailText" => Schema::TYPE_TEXT . ' DEFAULT ""',
			"location" => Schema::TYPE_STRING . ' default ""',
			"detailImagesPath" => Schema::TYPE_STRING . ' DEFAULT ""',
			"detailImagesCount" => Schema::TYPE_SMALLINT . ' DEFAULT 0',
			"deleted" => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
			"thumb" => Schema::TYPE_INTEGER . ' DEFAULT 0',
			"thumbOwnerIds" => Schema::TYPE_TEXT . ' DEFAULT ""',
			"commentCount" => Schema::TYPE_INTEGER . ' DEFAULT 0',
			"commentDeleteCount" => Schema::TYPE_INTEGER . ' DEFAULT 0',
	    ], "DEFAULT CHARSET=utf8;");

	    //朋友圈评论
	    $this->createTable("comment", [
			"id" => Schema::TYPE_BIGINT . ' NOT NULL',
			"pid" => Schema::TYPE_BIGINT . ' NOT NULL',
			"circleId" => Schema::TYPE_BIGINT . ' NOT NULL',
			"reviewerId" => Schema::TYPE_BIGINT . ' NOT NULL',
			"revieweredId" => Schema::TYPE_BIGINT . ' NOT NULL DEFAULT 0',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
			"content" => Schema::TYPE_TEXT . ' NOT NULL',
			"isDeleted" => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
			'PRIMARY KEY (id, circleId)',
	    ], "DEFAULT CHARSET=utf8;"); 

	    //评论插入一条id＝0的评论，作为所有一级评论的父评论
	    //配合model中rule [['pid'], 'exist', 'on' => 'add']
	    //$rightnow = "" . date("Y-m-d H:i:s");
	    //$this->insert("comment", ['id' => 0, 'pid' => 0, 'circleId' => 0, 'reviewerId' => 0, 'revieweredId' => 0, 'time' => 'now()', 'content' => 'dummy']);
	    //$this->insert("comment", ['id' => 0, 'pid' => 0, 'circleId' => 0, 'revierweId' => 0, 'revieweredId' => 0, 'content' => 'dummy']);

    }

    public function down()
    {
        echo "m141203_133237_create_circle_table cannot be reverted.\n";
		$this->dropTable("circle");
		$this->dropTable("comment");

        //return false;
    }
}
