<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Energy extends ActiveRecord
{
	public static function tableName()
	{
		return 'energy';
	}
}
