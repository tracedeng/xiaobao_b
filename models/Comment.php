<?php

/*
 * 朋友圈评论
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Circle;
use app\models\Material;

class Comment extends ActiveRecord
{
	//每一条评论对应一条朋友圈
	public function getCircle()
	{
		return $this->hasOne(Circle::className(), ['id' => 'circleId']);
	}

	public function rules()
	{
		return [
			[['id', 'pid', 'circleId', 'time', 'reviewerId', 'revieweredId', 'content'], 'required', 'on' => 'add'],	//新增一条评论
			[['id', 'circleId'], 'required', 'on' => 'delete'],		//删除一条评论

			['pid', 'exist', 'on' => 'add'],		//新增一条评论
			[['id', 'circleId'], 'exist', 'targetAttribute' => ['id', 'circleId'], 'on' => 'delete'],		//删除一条评论
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
		return 'comment';
	}
}

?>
