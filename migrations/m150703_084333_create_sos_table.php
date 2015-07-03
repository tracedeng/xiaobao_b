<?php

use yii\db\Schema;
use yii\db\Migration;

class m150703_084333_create_sos_table extends Migration
{
    public function up()
    {
	    $this->createTable("sos", [
	    	"petId" => Schema::TYPE_BIGINT . ' NOT NULL',
			"times" => Schema::TYPE_SMALLINT. ' NOT NULL',
			"lastTime" => Schema::TYPE_DATETIME . ' NOT NULL',
			'PRIMARY KEY (petId)',
	    ], "DEFAULT CHARSET=utf8;");

    }

    public function down()
    {
        echo "m150703_084333_create_sos_table cannot be reverted.\n";
		$this->dropTable("sos");

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
