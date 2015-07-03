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

	/*public function query($post)
	{
		//$id = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		//Yii::trace('id=' . $id, 'pet\query');
		$id = $post["who"];
		$pets = $this->queryPets($id);
		Yii::trace($pets, 'pet\query');
		$sponsorPets = $this->querySponsorPets($id);
		Yii::trace($sponsorPets, 'pet\query');

		return json_encode(array("errcode"=>0, "errmsg"=>"query pet success", "pets"=>$pets, "sponsor"=>$sponsorPets));
	}*/

	//获取用户拥有的狗狗
	/*public function queryPets($id)
	{
		return Pet::find()->where(['ownerId' => $id, 'isDeleted' => 0])->with('petKind')->asArray()->all();
	}

	public function canModifyMaterial($id, $ownerId)
	{
		$checkpet = new Pet;
		$checkpet->setScenario("modify");
		$checkpet->id = $id;
		$checkpet->ownerId = $ownerId;
		Yii::trace($checkpet->attributes, 'pet\checkModify');
		if(!$checkpet->validate())
		{
			Yii::trace($checkpet->getErrors(), 'pet\nickname');
			return false;
		}
		return true;
	}*/

	public function kind($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		/*$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify kind failed, user not own this pet", 'pet\kind');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify kind failed"));
		}

		$pet = Pet::findOne($id);
		$pet->kind = $post["kind"];
		Yii::trace($pet->attributes, 'pet\kind');
		if($pet->save())
		{
			Yii::trace("modify kind succeed", 'pet\kind');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify kind succeed", "kind"=>$pet->kind));
		}else{
			Yii::trace($account->getErrors(), 'pet\kind');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify kind"));
		}*/
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
			Yii::trace($account->getErrors(), 'relation\add');
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
				Yii::trace($account->getErrors(), 'relation\delete');
				return json_encode(array("errcode"=>20602, "errmsg"=>"call delete failed when delete a relation"));
			}
		}else{
			Yii::trace($account->getErrors(), 'relation\delete');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call delete failed when delete a relation"));
		}
	}
}

