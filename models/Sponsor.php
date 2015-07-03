<?php

/*
 * 狗狗助养
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Pet;

class Sponsor extends ActiveRecord
{
	//每只宠物有一种类型
	public function getPet()
	{
		return $this->hasOne(Pet::className(), ['id' => 'petId'])->innerJoinWith('petKind');
	}

	public function rules()
	{
		return [

			[['petId', 'sponsorId'], 'exist', 'targetAttribute' => ['petId', 'sponsorId'], 'on' => 'cancel'],		//取消助养
		];
	}

	/*
	public function scenarios()
	{
		$scenarios = parent::scenarios();
		$scenarios['verifyCode'] = ['phoneNumber'];
		$scenarios['login'] = ['phoneNumber', 'password'];
		$scenarios['register'] = ['phoneNumber', 'password', 'verifyCode', 'nickName', 'passwordMd5'];
		return $scenarios;
	}*/

	public static function tableName()
	{
		return 'sponsor';
	}
}

?>
