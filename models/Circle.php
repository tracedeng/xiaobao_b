<?php

/*
 *用户账号信息
 */
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Comment;
use app\models\Material;
use app\models\Relation;

class Circle extends ActiveRecord
{
	public $userId;		//获取所有好友朋友圈时使用，标志发起请求用户

	//一条朋友圈对应多个评论
	public function getComments()
	{
		return $this->hasMany(Comment::className(), ['circleId' => 'id']);
		//return $this->hasMany(Comment::className(), ['circleId' => 'id'])->innerJoinWith(['material', 'material2'])->select('comment.reviewerId, material.nickname');
		//return $this->hasMany(Comment::className(), ['circleId' => 'id'])->innerJoinWith('material')->select('comment.reviewerId, material.nickname');
		//return $this->hasMany(Comment::className(), ['circleId' => 'id'])->innerJoinWith('material')->onCondition(['comment.reviewerId' => 'material.id'])->select('comment.reviewerId, material.nickname');
	}

	//一条朋友圈owner对应一条昵称
	public function getMaterial()
	{
		return $this->hasOne(Material::className(), ['id' => 'ownerId'])->select('id, nickname, headImage, sex, location');
	}

	//生成4位随机验证码
	public function generateVerifyCode()
	{
		$str = '0123456789';
		return substr(str_shuffle($str), 0, 4);
	}

	//0 <= type <= 4，判断同时为空
	public function notBothEmpty()
	{
		if(($this->type < 0) || ($this->type > 4))
		{
			$this->addError('addCircle', 'invalid circle type');
		}
		if(empty($this->detailText) && empty($this->detailImagesPath))
		{
			$this->addError('addCircle', 'both text and image are empty');
		}
	}

	public function rules()
	{
		return [
			[['ownerId', 'type', 'releaseTime'], 'required', 'on' => 'release'],	//新增一条朋友圈
			['id', 'required', 'on' => ['delete', 'thumb', 'cancelThumb', 'comment']],

			[['type', 'detailText', 'detailImagesPath'], 'notBothEmpty', 'on' => 'release'],	//新增朋友圈，文本内容和图片路径不能都为空
			['id', 'exist', 'on' => ['delete', 'thumb', 'cancelThumb', 'comment']],
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
		return 'circle';
	}
}

?>
