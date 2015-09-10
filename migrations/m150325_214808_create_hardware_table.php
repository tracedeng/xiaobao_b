<?php

use yii\db\Schema;
use yii\db\Migration;

class m150325_214808_create_hardware_table extends Migration
{
    public function up()
    {
	    //所有上报数据
	    $this->createTable("hardware", [
	    	"id" => Schema::TYPE_BIGINT . ' NOT NULL',
			"gprsId" => Schema::TYPE_STRING . ' DEFAULT ""',
			"position" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo5" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo6" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo7" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo8" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo9" => Schema::TYPE_STRING . ' DEFAULT ""',
			"motionIndex" => Schema::TYPE_FLOAT . ' DEFAULT 0',
			"battery" => Schema::TYPE_SMALLINT . ' DEFAULT 2',
			"seq" => Schema::TYPE_SMALLINT,
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
			"deviceTime" => Schema::TYPE_DATETIME . ' NOT NULL',
			"seq" => Schema::TYPE_SMALLINT,
			"baiduMap" => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
			'PRIMARY KEY (id, gprsId)',
	    ], "DEFAULT CHARSET=utf8;");

	    //当前最新数据，petid是宠物ID，省去查宠物表
	    //geo精度 5-2.4km, 6-0.61km, 7-76m, 8-19m, 9-2m
	    $this->createTable("snapshot", [
			"gprsId" => Schema::TYPE_STRING . ' DEFAULT ""',
	    	//"petid" => Schema::TYPE_BIGINT . ' NOT NULL',
			"position" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo5" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo6" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo7" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo8" => Schema::TYPE_STRING . ' DEFAULT ""',
			"positionGeo9" => Schema::TYPE_STRING . ' DEFAULT ""',
			"motionIndex" => Schema::TYPE_FLOAT,
			"battery" => Schema::TYPE_SMALLINT,
			"seq" => Schema::TYPE_SMALLINT,
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
			"closed" => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
	    	"cliaddr" => Schema::TYPE_STRING . ' DEFAULT ""',
			"seq" => Schema::TYPE_SMALLINT,
			"baiduMap" => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
			'PRIMARY KEY (gprsId)',
	    ], "DEFAULT CHARSET=utf8;");

	    //实时消耗量和口粮
	    $this->createTable("consumption", [
			"gprsId" => Schema::TYPE_STRING . ' DEFAULT ""',
			"consumption" => Schema::TYPE_FLOAT,
			"ration" => Schema::TYPE_FLOAT,
			'PRIMARY KEY (gprsId)',
	    ], "DEFAULT CHARSET=utf8;");

	    //每天运动指数累计量
	    $this->createTable("motion", [
			"gprsId" => Schema::TYPE_STRING . ' DEFAULT ""',
			"day" => Schema::TYPE_DATE,
			"motionIndex" => Schema::TYPE_FLOAT,
			'PRIMARY KEY (gprsId, day)',
	    ], "DEFAULT CHARSET=utf8;");

	    //电子围栏，petid是宠物ID，省去查宠物表
	    $this->createTable("fence", [
			"gprsId" => Schema::TYPE_STRING . ' DEFAULT ""',
			//"petid" => Schema::TYPE_BIGINT . ' NOT NULL',
			"open" => Schema::TYPE_BOOLEAN . ' DEFAULT 0',
			"fenceCenter" => Schema::TYPE_STRING . ' DEFAULT ""',
			"fenceRadius" => Schema::TYPE_FLOAT . ' DEFAULT 0',
			"time" => Schema::TYPE_DATETIME . ' NOT NULL',
			'PRIMARY KEY (gprsId)',
	    ], "DEFAULT CHARSET=utf8;");

	    //一些固定参数
	    $this->createTable("fixpara", [
			"minScale" => Schema::TYPE_FLOAT . ' DEFAULT 3',
			"maxScale" => Schema::TYPE_FLOAT . ' DEFAULT 19',
			"bootScale" => Schema::TYPE_FLOAT . ' DEFAULT 13',
	    	"maxNail" => Schema::TYPE_BIGINT . ' DEFAULT 20',
	    ], "DEFAULT CHARSET=utf8;");

    }

    public function down()
    {
		echo "m150325_214808_create_hardware_table cannot be reverted.\n";
		$this->dropTable("hardware");
		$this->dropTable("snapshot");
		$this->dropTable("consumption");
		$this->dropTable("motion");
		$this->dropTable("fence");
		$this->dropTable("fixpara");

        //return false;
    }
}
