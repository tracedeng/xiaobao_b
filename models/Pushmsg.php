<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Pushmsg extends ActiveRecord
{
	public static function tableName()
	{
		return 'pushmsg';
	}
}
