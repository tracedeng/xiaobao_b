<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Seqno extends ActiveRecord
{
	public static function tableName()
	{
		return 'seqno';
	}
}
