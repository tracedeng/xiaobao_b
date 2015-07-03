<?php

/*
 * 好友关系链
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\PetKind;

class Relation extends ActiveRecord
{
	//每只宠物有一种类型
	/*public function getPetKind()
	{
		return $this->hasOne(PetKind::className(), ['id' => 'kind']);
	}*/

	public function rules()
	{
		return [
			[['phoneNumberA', 'phoneNumberB', 'time', 'type'], 'required', 'on' => 'add'],	//新增好友关系
			[['phoneNumberA', 'phoneNumberB'], 'required', 'on' => ['delete', 'verify']],	//删除验证好友关系，考虑和exist判读的顺序关系
			[['phoneNumberA', 'phoneNumberB'], 'sortPhoneNumber', 'on' => ['add', 'delete', 'verify']],	//新增好友关系
			[['phoneNumberA', 'phoneNumberB'], 'exist', 'targetAttribute' => ['phoneNumberA', 'phoneNumberB'], 'on' => ['delete', 'verify']],	//删除验证好友关系
		];
	}

	public function sortPhoneNumber()
	{
		$users = array($this->phoneNumberA, $this->phoneNumberB);
		sort($users);
		list($this->phoneNumberA, $this->phoneNumberB) = $users;
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
		return 'relation';
	}
}

?>
