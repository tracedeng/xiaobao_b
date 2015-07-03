<?php

/*
 * 每天运动指数统计
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Motion extends ActiveRecord
{
	public static function tableName()
	{
		return 'motion';
	}
}

?>
