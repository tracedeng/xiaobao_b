<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Apns extends ActiveRecord
{
	public static function tableName()
	{
		return 'apns';
	}
}
