<?php

/*
 * 实时消耗
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Consumption extends ActiveRecord
{
	public static function tableName()
	{
		return 'consumption';
	}
}

?>
