<?php

/*
 * 硬件上报数据
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Pet;

class Snapshot extends ActiveRecord
{
	//每只宠物有一种类型

	/*
	public function scenarios()
	{
		return [
			'add' => ['birthday', 'kind', 'nickname', 'sex', 'weight'],
			'modify' => [],
			];
	}*/
	public function getPet()
	{
		return $this->hasOne(Pet::className(), ['gprsId' => 'gprsId'])->select('id, nickname, kind, sex, headImage, ownerId, gprsId');
	}

	public static function tableName()
	{
		return 'snapshot';
	}
}

?>
