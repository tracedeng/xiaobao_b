<?php

/*
 * 朋友圈评论
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\PetKind;
use app\models\PetFood;
use app\models\Hardware;
use app\models\Snapshot;

class Pet extends ActiveRecord
{
	//public $gprsId;

	//每只宠物有一种类型
	public function getPetKind()
	{
		return $this->hasOne(PetKind::className(), ['id' => 'kind']);
	}

	public function getPetFood()
	{
		return $this->hasOne(PetFood::className(), ['id' => 'food']);
	}

	public function addPetAuth($attribute)
	{
	}

	public function getHardware()
	{
		return $this->hasOne(Hardware::className(), ['gprsId' => 'gprsId'])->select('gprsId, battery');
	}

	public function getSnapshot()
	{
		return $this->hasOne(Snapshot::className(), ['gprsId' => 'gprsId']);
	}

	public function rules()
	{
		return [
			[['ownerId', 'time'], 'required', 'on' => 'add'],	//新增一只宠物
			[['ownerId', 'id'], 'required', 'on' => 'modify'],	//编辑宠物资料

			[['ownerId', 'id'], 'exist', 'targetAttribute' => ['ownerId', 'id'], 'on' => 'modify'],		//编辑宠物资料
			[['birthday', 'kind', 'nickname', 'sex', 'weight'], 'addPetAuth', 'on' => 'add'],

			//[['id', 'gprsId'], 'required', 'on' => 'bindGprs'],
			//['id', 'exist', 'on' => 'bindGprs'],
		];
	}

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
		return 'pet';
	}
}

?>
