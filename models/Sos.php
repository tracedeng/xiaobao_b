<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Sos extends ActiveRecord
{
	public static function tableName()
	{
		return 'sos';
	}
}
