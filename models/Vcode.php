<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Vcode extends ActiveRecord
{
	public static function tableName()
	{
		return 'vcode';
	}
}
