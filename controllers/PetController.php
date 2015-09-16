<?php

/*
 *宠物及主人
 */

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use app\models\Account;
use app\models\Material;
use app\models\Pet;
use app\models\PetKind;
use app\models\Sponsor;

class PetController extends Controller
{
    function img2thumb($src_img, $dst_img, $dst_ext, $width = 75, $height = 75, $cut = 0, $proportion = 0)
    {
            if(!is_file($src_img))
            {
                return false;
            }
            $ot = $dst_ext;
            $otfunc = 'image' . ($ot == 'jpg' ? 'jpeg' : $ot);
            $srcinfo = getimagesize($src_img);
            $src_w = $srcinfo[0];
            $src_h = $srcinfo[1];
            $type  = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
            $createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);
     
            $dst_h = $height;
            $dst_w = $width;
            $x = $y = 0;
     
            /**
             * 缩略图不超过源图尺寸（前提是宽或高只有一个）
             */
            if(($width> $src_w && $height> $src_h) || ($height> $src_h && $width == 0) || ($width> $src_w && $height == 0))
            {
                $proportion = 1;
            }
            if($width> $src_w)
            {
                $dst_w = $width = $src_w;
            }
            if($height> $src_h)
            {
                $dst_h = $height = $src_h;
            }
     
            if(!$width && !$height && !$proportion)
            {
                return false;
            }
            if(!$proportion)
            {
                if($cut == 0)
                {
                    if($dst_w && $dst_h)
                    {
                        if($dst_w/$src_w> $dst_h/$src_h)
                        {
                            $dst_w = $src_w * ($dst_h / $src_h);
                            $x = 0 - ($dst_w - $width) / 2;
                        }
                        else
                        {
                            $dst_h = $src_h * ($dst_w / $src_w);
                            $y = 0 - ($dst_h - $height) / 2;
                        }
                    }
                    else if($dst_w xor $dst_h)
                    {
                        if($dst_w && !$dst_h)  //有宽无高
                        {
                            $propor = $dst_w / $src_w;
                            $height = $dst_h  = $src_h * $propor;
                        }
                        else if(!$dst_w && $dst_h)  //有高无宽
                        {
                            $propor = $dst_h / $src_h;
                            $width  = $dst_w = $src_w * $propor;
                        }
                    }
                }
                else
                {
                    if(!$dst_h)  //裁剪时无高
                    {
                        $height = $dst_h = $dst_w;
                    }
                    if(!$dst_w)  //裁剪时无宽
                    {
                        $width = $dst_w = $dst_h;
                    }
                    $propor = min(max($dst_w / $src_w, $dst_h / $src_h), 1);
                    $dst_w = (int)round($src_w * $propor);
                    $dst_h = (int)round($src_h * $propor);
                    $x = ($width - $dst_w) / 2;
                    $y = ($height - $dst_h) / 2;
                }
            }
            else
            {
                $proportion = min($proportion, 1);
                $height = $dst_h = $src_h * $proportion;
                $width  = $dst_w = $src_w * $proportion;
            }
     
            $src = $createfun($src_img);
            $dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
     
            if(function_exists('imagecopyresampled'))
            {
                imagecopyresampled($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
            }
            else
            {
                imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
            }
            $otfunc($dst, $dst_img);
            imagedestroy($dst);
            imagedestroy($src);
            return true;
    }

	public function actionOperate()
	{
		$account = new Account;
		$account->setScenario('verify');
		$post = $_POST ? $_POST : json_decode(Yii::$app->request->getRawBody(), true);
		Yii::trace($post, 'pet\operation');
		$account->attributes = $post;
		$account->skey = $post["skey"];
		Yii::trace($account->attributes, 'pet\operation');
		
		$opcode = $post["opcode"];
		//验证skey
		if($account->validate())
		{
			switch($opcode)
			{
				case 0:
					//查询
					return $this->query($post);
				case 1:
					//修改狗狗种类
					return $this->kind($post);
				case 2:
					//修改头像
					return $this->headImage($post);
				case 3:
					//修改昵称
					return $this->nickname($post);
				case 4:
					//修改性别
					return $this->sex($post);
				case 5:
					//修改年龄
					return $this->birthday($post);
				case 6:
					//修改体重
					return $this->weight($post);
				case 7:
					//修改狗狗卖萌介绍
					return $this->introduce($post);
				case 8:
					//修改口粮类型
					return $this->food($post);
				case 20:
					//修改是否绑定小宝
					return $this->isBindGprs($post);
				case 21:
					//修改小宝序列号
					return $this->gprsId($post);
				case 30:
					//修改助养开关
					return $this->sponsorOpen($post);
				case 40:
					//增加宠物
					return $this->add($post);
				case 41:
					//删除
					return $this->deleteAPet($post);
				case 42:
					//助养
					return $this->sponsorAPet($post);
				case 43:
					//取消助养
					return $this->cancelSponsorAPet($post);
				case 50:
					//宠物种类
					return $this->queryKinds($post);
				default:
					//不支持的操作，不回包
					break;
			}
		}else{
			switch($opcode)
			{
				case 0:
					//查询
					$errcode = 20301;
					break;
				case 1:
					//新增宠物
					$errcode = 20301;
					break;
				case 2:
					//头像
					$errcode = 20301;
					break;
				case 3:
					//修改昵称
					$errcode = 20301;
					break;
				case 4:
					//修改性别
					$errcode = 20301;
					break;
				case 5:
					//修改年龄
					$errcode = 20301;
					break;
				case 6:
					//修改体重
					$errcode = 20301;
					break;
				case 7:
					//
					$errcode = 20301;
					break;
				case 8:
					//修改小宝序列号
					$errcode = 20301;
					break;
				default:
					//不支持的操作
					return;
			}
			Yii::trace($account->getErrors(), 'pet\operation');
			return json_encode(array("errcode"=>$errcode, "errmsg"=>"invalid skey"));
		}
 	}

	public function query($post)
	{
		//$id = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		//Yii::trace('id=' . $id, 'pet\query');
		$id = $post["who"];
		if (0 == $id)
		{
			$id = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
			Yii::trace('id=' . $id, 'pet\query');
		}
		$pets = $this->queryPets($id);
		Yii::trace($pets, 'pet\query');
		$sponsorPets = $this->querySponsorPets($id);
		Yii::trace($sponsorPets, 'pet\query');

		return json_encode(array("errcode"=>0, "errmsg"=>"query pet success", "pets"=>$pets, "sponsor"=>$sponsorPets));
	}

	//获取用户拥有的狗狗
	public function queryPets($id)
	{
		return Pet::find()->where(['ownerId' => $id, 'isDeleted' => 0])->with('petKind', 'petFood', 'snapshot')->asArray()->all();
	}

	//获取用户助养的狗狗
	public function querySponsorPets($id)
	{
		return Sponsor::find()->where(['sponsorId' => $id])->with('pet')->asArray()->all();
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
	}

	public function canCancelSponsor($sponsorId, $petId)
	{
		$sponsor = new Sponsor;
		$sponsor ->setScenario("cancel");
		$sponsor->petId = $petId;
		$sponsor->sponsorId = $sponsorId;
		Yii::trace($sponsor->attributes, 'pet\cancelSponsor');
		if(!$sponsor->validate())
		{
			Yii::trace($sponsor->getErrors(), 'pet\cancelSponsor');
			return false;
		}
		return true;
	}

	public function kind($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
                //file_put_contents("teste.log","pet测试，是否到这里");
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
		}
	}

	public function food($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify food failed, user not own this pet", 'pet\food');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify food failed"));
		}

		$pet = Pet::findOne($id);
		$pet->food = $post["food"];
		$pet->foodName = $post["foodName"];
		Yii::trace($pet->attributes, 'pet\food');
		if($pet->save())
		{
			Yii::trace("modify food succeed", 'pet\food');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify food succeed", "food"=>$pet->food));
		}else{
			Yii::trace($account->getErrors(), 'pet\food');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify food"));
		}
	}

	public function nickname($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify nickname failed, user not own this pet", 'pet\nickname');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify nickname failed"));
		}

		$pet = Pet::findOne($id);
		$pet->nickname = $post["nickname"];
		Yii::trace($pet->attributes, 'pet\nickname');
		if($pet->save())
		{
			Yii::trace("modify nickname succeed", 'pet\nickname');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify nickname succeed", "nickname"=>$pet->nickname));
		}else{
			Yii::trace($account->getErrors(), 'pet\nickname');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify nickname"));
		}
	}

	public function sex($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify sex failed, user not own this pet", 'pet\sex');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify sex failed"));
		}

		$pet = Pet::findOne($id);
		$pet->sex = $post["sex"];
		Yii::trace($pet->attributes, 'pet\sex');
		if($pet->save())
		{
			Yii::trace("modify sex succeed", 'pet\sex');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify sex succeed", "sex"=>$pet->sex));
		}else{
			Yii::trace($account->getErrors(), 'pet\sex');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify sex"));
		}
		
	}

	public function birthday($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify birthday failed, user not own this pet", 'pet\birthday');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify birthday failed"));
		}

		$pet = Pet::findOne($id);
		$pet->birthday = $post["birthday"];
		Yii::trace($pet->attributes, 'pet\birthday');
		if($pet->save())
		{
			Yii::trace("modify birthday succeed", 'pet\birthday');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify birthday succeed", "birthday"=>$pet->birthday));
		}else{
			Yii::trace($account->getErrors(), 'pet\birthday');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify birthday"));
		}
		
	}

	public function weight($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify weight failed, user not own this pet", 'pet\weight');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify weight failed"));
		}

		$pet = Pet::findOne($id);
		$pet->weight = $post["weight"];
		Yii::trace($pet->attributes, 'pet\weight');
		if($pet->save())
		{
			Yii::trace("modify weight succeed", 'pet\weight');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify weight succeed", "weight"=>$pet->weight));
		}else{
			Yii::trace($account->getErrors(), 'pet\weight');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify weight"));
		}
		
	}

	public function introduce($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify introduce failed, user not own this pet", 'pet\introduce');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify introduce failed"));
		}

		$pet = Pet::findOne($id);
		$pet->introduce = $post["introduce"];
		Yii::trace($pet->attributes, 'pet\introduce');
		if($pet->save())
		{
			Yii::trace("modify introduce succeed", 'pet\introduce');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify introduce succeed", "introduce"=>$pet->introduce));
		}else{
			Yii::trace($account->getErrors(), 'pet\introduce');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify introduce"));
		}
	}

	public function headImage($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify headImage failed, user not own this pet", 'pet\headimage');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify headImage failed"));
		}

		$pet = Pet::findOne($id);
		//if(empty($pet->headImage))
		if(true)
		{
			//还没有头像时，需新建头像文件夹
			$directory = $this->generateImageDictionary();
			//不能直接@pet，why?
			$saveDirectory = Yii::getAlias('@pet') . '/' . $directory . '/';
			if(false == mkdir($saveDirectory, 0777, true))
			//if(false == mkdir($saveDirectory))
			{
				Yii::warning("create directory " . $saveDirectory . "failed", 'pet\headimage');
				return json_encode(array("errcode"=>20602, "errmsg"=>"create directory failed"));
			}
			$pet->headImage = $directory;
		}else{
			$saveDirectory = Yii::getAlias('@pet') . '/' . $pet->headImage. '/';
		}

		$files = $_FILES;	//上传的图片信息，关联数组结构
		$file = array_keys($files)[0];	//不管有多少个文件，只使用第一张图片
		$attach = UploadedFile::getInstanceByName($file);
		if ($attach != null)
		{
			$savepath = sprintf("%sheadimage", $saveDirectory);
			$savethumbpath = sprintf("%sthumbheadimage", $saveDirectory);
			$save = $attach->saveAs($savepath, true);
			if($save)
			{
				Yii::trace("save file to " . $savepath . " success", 'pet\headimage');
				//生成缩略图
				if(true == $this->head2thumb($savepath, $savethumbpath, $attach->extension, 400, 300, 0, 0))
				{
					Yii::trace("save thumbnail file to " . $savethumbpath . " success", 'pet\headimage');
				}else{
				    	//失败
					Yii::warning("save thumbnail file to " . $savethumbpath . " failed", 'pet\headimage');
					return json_encode(array("errcode"=>20602, "errmsg"=>"save thumbnail file failed"));
				}
			}else{
				//失败
				Yii::warning("save file to " . $savepath . " failed", 'pet\headimage');
				return json_encode(array("errcode"=>20602, "errmsg"=>"save file failed"));
			}
			if($pet->save())
			{
				return json_encode(array("errcode"=>0, "errmsg"=>"modify headimage succeed", "headImage"=>$pet->headImage));
			}else{
				return json_encode(array("errcode"=>20602, "errmsg"=>"save file failed"));
			}
		}else{
			return json_encode(array("errcode"=>20602, "errmsg"=>"create directory failed"));
		}
	}

	public function generateRandomString($len)
	{
		$str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		return substr(str_shuffle($str), 0, $len); 
	}

	public function generateImageDictionary()
	{
		$max = Pet::find()->max('id') + 1;
		Yii::trace("max=" . $max, 'pet\headimage');
		
		$dic = "";
		$left = 20;
		do {
			$dic = $this->generateRandomString(1) . ($max % 10) . $dic;
			$left -= 2;
			$max = floor($max / 10);
		} while($max > 0);
		$dic = $this->generateRandomString($left) . $dic;
		
		Yii::trace("dic=" . $dic . '/', 'pet\headimage');
		return $dic;
	}

	public function add($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$pet = new Pet;
		$pet->setScenario('add');
		//$pet->birthday = $post["nickname"];
		//$pet->nickname = $post["nickname"];
		$pet->attributes = $post;
		Yii::trace($pet->attributes, 'pet\add');
                //file_put_contents("teste.log","pet测试，是否到这里");
		$id = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		$material = Material::findOne($id);
		Yii::trace($material->attributes, 'material\headimage');
		$pet->ownerId = $material->id;

		$directory = $this->generateImageDictionary();
		//不能直接@pet，why?
		$saveDirectory = Yii::getAlias('@pet') . '/' . $directory . '/';
		if(false == mkdir($saveDirectory, 0777, true))
		{
			Yii::warning("create directory " . $saveDirectory . "failed", 'pet\add');
			return json_encode(array("errcode"=>20602, "errmsg"=>"create directory failed"));
		}
		$pet->headImage = $directory;

		$files = $_FILES;	//上传的图片信息，关联数组结构
		$file = array_keys($files)[0];	//不管有多少个文件，只使用第一张图片
		$attach = UploadedFile::getInstanceByName($file);
		if ($attach != null)
		{
			$savepath = sprintf("%sheadimage", $saveDirectory);
			$savethumbpath = sprintf("%sthumbheadimage", $saveDirectory);
			$save = $attach->saveAs($savepath, true);
			if($save)
			{
				Yii::trace("save file to " . $savepath . " success", 'pet\add');
				//生成缩略图
				if(true == $this->head2thumb($savepath, $savethumbpath, $attach->extension, 100, 100))
				{
					Yii::trace("save thumbnail file to " . $savethumbpath . " success", 'pet\add');
				}else{
					Yii::warning("save thumbnail file to " . $savethumbpath . " failed", 'pet\add');
					$pet->headImage = "default";	//保存头像失败，使用缺省头像
				}
			}else{
				Yii::warning("save headimage failed, use default", 'pet\add');
				$pet->headImage = "default";	//保存头像失败，使用缺省头像
			}
		}else{
			Yii::warning("save headimage failed, use default", 'pet\add');
			$pet->headImage = "default";	//保存头像失败，使用缺省头像
		}

		$pet->introduce = @"";
		$pet->time = "" . date("Y-m-d H:i:s");
		$pet->isBindGprs = 0;
		Yii::trace($pet->attributes, 'pet\add');

		if($pet->save())
		{
			Yii::trace("add a pet succeed", 'pet\add');
			return json_encode(array("errcode"=>0, "errmsg"=>"add a pet succeed", "pet"=>$pet->attributes));
		}else{
			Yii::trace($account->getErrors(), 'pet\add');
			return json_encode(array("errcode"=>20602, "errmsg"=>"save file failed"));
		}
	}

	//TODO... controller间公用的函数怎么共享
	//只有用户有一只宠物打开助养该用户就拥有助养标志
	public function updateUserSponsor($userId)
	{
		$material = Material::find()->where(['id' => $userId])->one();
		$pets = Pet::find()->where(['ownerId' => $userId, 'isDeleted' => 0, 'sponsorOpen' => 1])->select('sponsorOpen')->asArray()->all();

		foreach($pets as $pet)
		{
			if($pet['sponsorOpen'] == 1)
			{
				//该用户还有宠物打开助养
				$material->sponsor = 1;
				$material->save();
				return;
			}
		}
		$material->sponsor = 0;
		$material->save();

		return;
	}

	//绑定小宝则自动解绑，并更新助养标志
	public function deleteAPet($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("delete a pet failed, user not own this pet", 'pet\delete');
			return json_encode(array("errcode"=>20602, "errmsg"=>"delete a pet failed"));
		}

		$pet = Pet::findOne($id);
		$pet->isDeleted = true;
		$pet->isBindGprs = true;
		Yii::trace($pet, 'pet\delete');
		if($pet->save())
		{
			self::updateUserSponsor($ownerId);
			Yii::trace("delete a pet succeed", 'pet\delete');
	 		return json_encode(array("errcode"=>0, "errmsg"=>"delete a pet succeed", "isDeleted"=>$pet->isDeleted));
		}else{
			Yii::trace($account->getErrors(), 'pet\delete');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when delete a pet"));
		}
	}

	//助养
	public function sponsorAPet($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$sponsorOpen = Pet::findOne(['id' => $petId])->sponsorOpen;

		if(!$sponsorOpen)
		{
			Yii::trace("sponsor pet failed, sponsor this pet deny", 'pet\sponsor');
			return json_encode(array("errcode"=>20602, "errmsg"=>"sponsor a pet failed"));
		}

		$sponsorId = Account::findOne(['phoneNumber' => $post["sponsor"]])->id;
		$sponsor = new Sponsor;
		$sponsor->petId = $petId;
		$sponsor->sponsorId = $sponsorId;
		$sponsor->sponsorTime = "" . date("Y-m-d H:i:s");
		Yii::trace($sponsor, 'pet\sponsor');
		if($sponsor->save())
		{
			Yii::trace("sponsor a pet succeed", 'pet\sponsor');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"sponsor a pet succeed", "result"=>true));
		}else{
			Yii::trace($sponsor->getErrors(), 'pet\sponsor');
	        	return json_encode(array("errcode"=>20602, "errmsg"=>"sponsor a pet failed", "result"=>false));
		}
	}

	//解除助养
	public function cancelSponsorAPet($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canCancelSponsor($ownerId, $id))
		{
			Yii::trace("cancel sponsor pet failed, user not sponsor this pet", 'pet\cancelSponsor');
			return json_encode(array("errcode"=>20602, "errmsg"=>"cancel sponsor a pet failed"));
		}

		$sponsor = Sponsor::find()->where(['petId'=>$id, 'sponsorId'=>$ownerId])->one();
		Yii::trace($sponsor, 'pet\cancelSponsor');
		if($sponsor->delete())
		{
			Yii::trace("cancel sponsor a pet succeed", 'pet\cancelSponsor');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"delete a pet succeed", "result"=>true));
		}else{
			Yii::trace($sponsor->getErrors(), 'pet\cancelSponsor');
	        	return json_encode(array("errcode"=>20602, "errmsg"=>"cancel sponsor a pet failed", "result"=>false));
		}
	}

	public function queryKinds($post)
	{
		$rawKinds = PetKind::find()->orderby(['section' => SORT_ASC, 'name' => SORT_ASC])->asArray()->all();
		Yii::trace($rawKinds, 'pet\queryKinds');
		$cooKinds = array();
		foreach($rawKinds as $kind)
		{
			$section = $kind["section"];
			if(array_key_exists($section, $cooKinds))
			{
				array_push($cooKinds[$section], $kind);
			}else{
				$cooKinds[$section]= array($kind);
			}
		}
		Yii::trace($cooKinds, 'pet\queryKinds');
                
		$kinds = array();
		foreach($cooKinds as $key=>$value)
		{
			array_push($kinds, array("section"=>$key, "kinds"=>$value));
		}
		Yii::trace($kinds, 'pet\queryKinds');
	        return json_encode(array("errcode"=>0, "errmsg"=>"query pet kinds succeed", "kinds"=>$kinds));
	}

	//是否绑定小宝
	public function isBindGprs($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify isBindGprs failed, user not own this pet", 'pet\isBindGprs');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify isBindGprs failed"));
		}

		$pet = Pet::findOne($id);
		$pet->isBindGprs = $post["isBindGprs"];
		if(0 == $post["isBindGprs"])
		{
			$pet->gprsId = "";
			$pet->sponsorOpen = 0;
		}
		Yii::trace($pet, 'pet\isBindGprs');
		if($pet->save())
		{
			Yii::trace("modify isBindGprs succeed", 'pet\isBindGprs');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify isBindGprs succeed", "isBindGprs"=>$pet->isBindGprs));
		}else{
			Yii::trace($account->getErrors(), 'pet\isBindGprs');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify isBindGprs"));
		}
	}

	//小宝序列号
	public function gprsId($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify gprsId failed, user not own this pet", 'pet\gprsId');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify gprsId failed"));
		}

		$pet = Pet::findOne($id);
		$pet->gprsId = $post["gprsId"];
		Yii::trace($pet, 'pet\gprsId');
		if($pet->save())
		{
			Yii::trace("modify gprsId succeed", 'pet\gprsId');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify gprsId succeed", "gprsId"=>$pet->gprsId));
		}else{
			Yii::trace($account->getErrors(), 'pet\gprsId');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify gprsId"));
		}
	}

	public function sponsorOpen($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = $post["petId"];
		$ownerId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canModifyMaterial($id, $ownerId))
		{
			Yii::trace("modify sponsorOpen failed, user not own this pet", 'pet\sponsorOpen');
			return json_encode(array("errcode"=>20602, "errmsg"=>"modify sponsorOpen failed"));
		}

		$pet = Pet::findOne($id);
		$pet->sponsorOpen = $post["sponsorOpen"];
		Yii::trace($pet->attributes, 'pet\sponsorOpen');
		if($pet->save())
		{
			self::updateUserSponsor($ownerId);
			Yii::trace("modify sponsorOpen succeed", 'pet\sponsorOpen');
	        return json_encode(array("errcode"=>0, "errmsg"=>"modify sponsorOpen succeed", "sponsorOpen"=>$pet->sponsorOpen));
		}else{
			Yii::trace($account->getErrors(), 'pet\sponsorOpen');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify sponsorOpen"));
		}
	}
   
    /*
    * @param string     源图绝对完整地址{带文件名及后缀名}
    * @param string     目标图绝对完整地址{带文件名及后缀名}
    * @param string     扩展名
    * @param int        缩略图宽{0:此时目标高度不能为0，目标宽度为源图宽*(目标高度/源图高)}
    * @param int        缩略图高{0:此时目标宽度不能为0，目标高度为源图高*(目标宽度/源图宽)}
    */
    function head2thumb($src_img, $dst_img, $dst_ext, $width = 300, $height = 320)
    {
        if(!is_file($src_img))
        {
            return false;
        }
        $ot = $dst_ext;
        $otfunc = 'image' . ($ot == 'jpg' ? 'jpeg' : $ot);
        $srcinfo = getimagesize($src_img);
        //print_r($srcinfo);exit();
        $src_w = $srcinfo[0];
        $src_h = $srcinfo[1];
        //计算目标图片长宽比率
        $bilv = $height/$width;
        $_3 = 9/3;
        //源图最小的边
        $min_border = min($src_w,$src_h);

        if($src_w == $min_border){
            //根据目标图比率计算源高应该的取值
            $src_h2 = $bilv*$src_w;
            $src_w2 = $min_border;
            //因为宽小所以高大，需计算高的截取时候坐标
            $scr_y2 = ($src_h - $src_h2)/2;//实际高减去要截取的高，除以2得出y的坐标

            $scr_x2 = 0;
        }else{
            //根据目标比率给源图宽取值
            $src_w2 = $src_h/$bilv;
            $src_h2 = $min_border;
            //因为高小所以宽大，需计算宽的截取时候坐标
            $scr_x2 =($src_w - $src_w2)/2;
            $scr_y2 = 0;

        }
        //计算源图的x或y坐标


        $type  = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
        $createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);

        $dst_h = $height;
        $dst_w = $width;
        $x = $y = 0;

        //如果目标图像的或高大于源图，则将目标图像的快高都为源图
        if($width> $src_w)
        {
            $dst_w = $width = $src_w;
        }
        if($height> $src_h)
        {
            $dst_h = $height = $src_h;
        }

        if(!$width && !$height && !$proportion)
        {
            return false;
        }

        $src = $createfun($src_img);
        $dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        if(function_exists('imagecopyresampled'))
        {
            imagecopyresampled($dst, $src, 0, 0, $scr_x2, $scr_y2, $width, $height, $src_w2, $src_h2);
        }
        else
        {
            imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w2, $src_h2);
        }
        $otfunc($dst, $dst_img);
        imagedestroy($dst);
        imagedestroy($src);
        return true;
    }
}

?>
