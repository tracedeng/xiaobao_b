<?php

/*
 * 狗狗类型
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Pet;

class PetKind extends ActiveRecord
{
	public function rules()
	{
		return [
			[['name', 'time'], 'required', 'on' => 'add'],	//新增一种狗狗类型

			//[['pid'], 'exist', 'on' => 'add'],		//新增一条评论
			//[['id', 'circleId'], 'exist', 'on' => 'delete'],		//删除一条评论
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
		return 'petkind';
	}
}

?>
