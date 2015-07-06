<?php

/*
 *用户关系链
 */

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use app\models\Account;
use app\models\Material;
use app\models\Relation;

class RelationController extends Controller
{
	public function actionOperate()
	{
		$account = new Account;
		$account->setScenario('verify');
		$post = $_POST ? $_POST : json_decode(Yii::$app->request->getRawBody(), true);
		Yii::trace($post, 'relation\operation');
		$account->attributes = $post;
		$account->skey = $post["skey"];
		Yii::trace($account->attributes, 'relation\operation');
		
		$opcode = $post["opcode"];
		//验证skey
		if($account->validate())
		{
			switch($opcode)
			{
				case 40:
					//增加好友关系
					return $this->addRelation($post);
				case 41:
					//删除好友关系
					return $this->deleteRelation($post);
				case 42:
					//判断是否是好友关系
					return $this->queryRelation($post);
				case 43:
					//判断是否是好友关系
					return $this->queryRelationById($post);
				default:
					//不支持的操作，不回包
					break;
			}
		}else{
			switch($opcode)
			{
				case 40:
					//增加好友关系
					$errcode = 20301;
					break;
				case 41:
					//删除好友关系
					$errcode = 20301;
					break;
				default:
					//不支持的操作
					return;
			}
			Yii::trace($account->getErrors(), 'relation\operation');
			return json_encode(array("errcode"=>$errcode, "errmsg"=>"invalid skey"));
		}
 	}

	public function addRelation($post)
	{
		if(($post["phoneNumber"] != $post["phoneNumberA"]) && ($post["phoneNumber"] != $post["phoneNumberB"]))
		{
			Yii::trace("add a relation failed", 'relation\add');
			return json_encode(array("errcode"=>20601, "errmsg"=>"add a relation failed, user have no power"));
		}

		//TODO... 有效性检查，避免读 $post数据失败
		$relation = new Relation;
		$relation->setScenario('add');
		$relation->attributes = $post;

		$relation->time = "" . date("Y-m-d H:i:s");
		Yii::trace($relation->attributes, 'relation\add');

		if($relation->save())
		{
			Yii::trace("add a relation succeed", 'relation\add');
			return json_encode(array("errcode"=>0, "errmsg"=>"add a relation succeed", "relation"=>$relation->attributes));
		}else{
			Yii::trace($relation->getErrors(), 'relation\add');
			return json_encode(array("errcode"=>20602, "errmsg"=>"save file failed"));
		}
	}

	//是否绑定小宝
	public function deleteRelation($post)
	{
		if(($post["phoneNumber"] != $post["phoneNumberA"]) && ($post["phoneNumber"] != $post["phoneNumberB"]))
		{
			Yii::trace("delete a relation failed", 'relation\delete');
			return json_encode(array("errcode"=>20601, "errmsg"=>"delete a relation failed, user have no power"));
		}

		//TODO... 有效性检查，避免读 $post数据失败
		$relation = new Relation;
		$relation->setScenario('delete');
		$relation->attributes = $post;
		Yii::trace($relation->attributes, 'relation\delete');
		if($relation->validate())
		{
			$phoneNumberA = $relation->phoneNumberA;
			$phoneNumberB = $relation->phoneNumberB;
			$relation = Relation::find()->where(['phoneNumberA' =>$phoneNumberA, 'phoneNumberB'=>$phoneNumberB])->one();
			if($relation->delete())
			{
				Yii::trace("delete a relation succeed", 'relation\delete');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"delete a relation succeed", "relation"=>$relation->attributes));
			}else{
				Yii::trace($relation->getErrors(), 'relation\delete');
				return json_encode(array("errcode"=>20602, "errmsg"=>"call delete failed when delete a relation"));
			}
		}else{
			Yii::trace($relation->getErrors(), 'relation\delete');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call delete failed when delete a relation"));
		}
	}

	public function queryRelation($post)
	{
		$relation = new Relation;
		$relation->setScenario('verify');
		$relation->phoneNumberA = $post["phoneNumberA"];
		$relation->phoneNumberB = $post["phoneNumberB"];
		Yii::trace($relation->attributes, 'circle\fetch');

		if(!$relation->validate())
		{
			//非好友关系
			return json_encode(array("errcode"=>0, "relation"=>0));
		}

		return json_encode(array("errcode"=>0, "relation"=>1));
	}

	public function queryRelationById($post)
	{
		$phoneNumberB = Account::findOne(['id' => $post["userB"]])->phoneNumber;
		$relation = new Relation;
		$relation->setScenario('verify');
		$relation->phoneNumberA = $post["phoneNumberA"];
		$relation->phoneNumberB = $phoneNumberB;
		Yii::trace($relation->attributes, 'circle\fetch');

		if(!$relation->validate())
		{
			//非好友关系
			return json_encode(array("errcode"=>0, "relation"=>0));
		}

		return json_encode(array("errcode"=>0, "relation"=>1));
	}
}

