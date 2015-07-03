<?php

/*
 * 硬件上报数据
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Hardware extends ActiveRecord
{
	/*
	public function scenarios()
	{
		return [
			'add' => ['birthday', 'kind', 'nickname', 'sex', 'weight'],
			'modify' => [],
			];
	}*/

	public static function tableName()
	{
		return 'hardware';
	}
}

?>
