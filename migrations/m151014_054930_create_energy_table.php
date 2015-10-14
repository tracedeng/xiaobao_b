<?php

use yii\db\Schema;
use yii\db\Migration;

class m151014_054930_create_energy_table extends Migration
{
    public function up()
    {
	    $this->createTable("energy", [
	    	"id" => Schema::TYPE_BIGPK,
			"weight" => Schema::TYPE_FLOAT,
			"k" => Schema::TYPE_SMALLINT,
	    ], "DEFAULT CHARSET=utf8;");

    }

    public function down()
    {
        echo "m151014_054930_create_energy_table cannot be reverted.\n";
		$this->dropTable("energe");

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
