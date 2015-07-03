<?php

use yii\db\Schema;
use yii\db\Migration;

class m150703_082226_create_hardware_seqno_table extends Migration
{
    public function up()
    {

	    $this->createTable("seqno", [
	    	"id" => Schema::TYPE_BIGINT . ' NOT NULL',
			"gprsId" => Schema::TYPE_STRING . ' DEFAULT ""',
			"enable" => Schema::TYPE_BOOLEAN . ' DEFAULT TRUE',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
	    ], "DEFAULT CHARSET=utf8;");
    }

    public function down()
    {
        echo "m150703_082226_create_hardware_seqno_table cannot be reverted.\n";
		$this->dropTable("seqno");

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
