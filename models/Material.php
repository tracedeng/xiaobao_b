<?php

/*
 * 用户详细资料
 */
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Account;
use app\models\Circles;
use app\models\Comment;

class Material extends ActiveRecord
{
	//关联朋友圈，账号ID
	public function getCircle()
	{
		return $this->hasOne(Circle::className(), ['ownerId' => 'id']);
	}

	//环信批量注册
	public function getAccount()
	{
		return $this->hasOne(Account::className(), ['phoneNumber' => 'phoneNumber'])->select('phoneNumber, passwordMd5');
	}

	public function rules()
	{
		return [
			['nickname', 'required', 'on' => ['register', 'nickname']],
			['who', 'required', 'on' => 'query'],
			['sex', 'required', 'on' => 'sex'],
			['introduce', 'required', 'on' => 'introduce'],

			//TODO... 昵称 个性签名 长度检查  性别输入检查
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
		return 'material';
	}
}

?>
