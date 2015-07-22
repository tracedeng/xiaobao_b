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
use Easemob\Easemob;

class UserController extends Controller
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
		Yii::trace($post, 'material\operation');
		$account->attributes = $post;
		$account->skey = $post["skey"];
		Yii::trace($account->attributes, 'material\operation');
		
		$opcode = $post["opcode"];
		//验证skey
		if($account->validate())
		{
			switch($opcode)
			{
				case 0:
					//根据用户ID查询
					return $this->fetch($post);
				case 1:
					//根据用户手机号查询
					return $this->fetchWithPhoneNumber($post);
				case 2:
					//根据用户手机号查询
					return $this->batchFetchWithPhoneNumber($post);
				case 10:
					//头像
					return $this->headPic($post);
				case 11:
					//修改昵称
					return $this->nickname($post);
				case 12:
					//修改性别
					return $this->sex($post);
				case 13:
					//修改个性签名
					return $this->introduce($post);
				case 14:
					//修改地理位置
					return $this->location($post);
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
					//头像
					$errcode = 20301;
					break;
				case 2:
					//修改昵称
					$errcode = 20301;
					break;
				case 3:
					//修改性别
					$errcode = 20301;
					break;
				case 4:
					//修改性别
					$errcode = 20301;
					break;
				default:
					//不支持的操作
					return;
			}
			Yii::trace($account->getErrors(), 'material\operation');
			return json_encode(array("errcode"=>$errcode, "errmsg"=>"invalid skey"));
		}
 	}

	public function fetch($post)
	{
		//who=0表示查自己的资料
		$id = $post["who"];
		$id = ($id == 0) ? Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id : $id;
		Yii::trace('id=' . $id, 'material\fetch');

		$material = Material::find()->where(['id' => $id])->asArray()->one();
		Yii::trace($material, 'material\fetch');

		return json_encode(array("errcode"=>0, "errmsg"=>"fetch material success", "material"=>$material));
	}

	//根据用户手机号查用户资料
	public function fetchWithPhoneNumber($post)
	{
		//$post["who"] 是要查询的用户手机号
		$id = Account::findOne(['phoneNumber' => $post["who"]])->id;
		//$id = ($id == 0) ? Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id : $id;
		Yii::trace('id=' . $id, 'material\fetchWithPhoneNumber');

		$material = Material::find()->where(['id' => $id])->asArray()->one();
		Yii::trace($material, 'material\fetchWithPhoneNumber');

		return json_encode(array("errcode"=>0, "errmsg"=>"fetch material success", "material"=>$material));
	}

	public function filterSelectValue($rows, $key)
	{
	        $filterResult = [];
	        foreach($rows as $row)
	        {
	    	    array_push($filterResult, $row[$key]);
	        }
	        return $filterResult;
	}

	//根据用户手机号批量查用户资料
	public function batchFetchWithPhoneNumber($post)
	{
		//$post["who"] 是要查询的用户手机号
		//$id = Account::findOne(['phoneNumber' => $post["who"]])->id;
		$phoneNumbers = json_decode($post["phoneNumberList"], true);
		Yii::trace($phoneNumbers, 'material\batchFetchWithPhoneNumber');
		$ids = Account::find()->where(['phoneNumber' => $phoneNumbers])->select("id")->asArray()->all();
		$ids = $this->filterSelectValue($ids, "id");
		//$id = ($id == 0) ? Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id : $id;
		Yii::trace($ids, 'material\batchFetchWithPhoneNumber');

		$material = Material::find()->where(['id' => $ids])->asArray()->all();
		Yii::trace($material, 'material\batchFetchWithPhoneNumber');

		return json_encode(array("errcode"=>0, "errmsg"=>"batch fetch material success", "material"=>$material));
	}

	//获取用户资料
	/*
	public function queryUserMaterial($id)
	{
		return Material::find()->where(['id' => $id])->asArray()->one();
	}*/

	public function generateRandomString($len)
	{
		$str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		return substr(str_shuffle($str), 0, $len); 
	}

	public function generateImageDictionary()
	{
		$max = Material::find()->max('id') + 1;
		Yii::trace("max=" . $max, 'user\headimage');
		
		$dic = "";
		$left = 20;
		do {
			$dic = $this->generateRandomString(1) . ($max % 10) . $dic;
			$left -= 2;
			$max = floor($max / 10);
		} while($max > 0);
		$dic = $this->generateRandomString($left) . $dic;
		
		Yii::trace("dic=" . $dic . '/', 'user\headimage');
		return $dic;
	}

	public function headPic($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		$material = Material::findOne($id);
		Yii::trace($material->attributes, 'material\headimage');

		$directory = $this->generateImageDictionary();
		//不能直接@user，why?
		$saveDirectory = Yii::getAlias('@user') . '/' . $directory . '/';
		if(false == mkdir($saveDirectory, 0777, true))
		{
			Yii::warning("create directory " . $saveDirectory . "failed", 'user\headimage');
			return json_encode(array("errcode"=>20602, "errmsg"=>"create directory failed"));
		}
		$material->headImage = $directory;

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
				Yii::trace("save file to " . $savepath . " success", 'user\headimage');
				//生成缩略图
				if(true == $this->img2thumb($savepath, $savethumbpath, $attach->extension, 400, 300, 0, 0))
				{
					Yii::trace("save thumbnail file to " . $savethumbpath . " success", 'user\headimage');
				}else{
				    	//失败
					Yii::warning("save thumbnail file to " . $savethumbpath . " failed", 'user\headimage');
					return json_encode(array("errcode"=>20602, "errmsg"=>"save thumbnail file failed"));
				}
			}else{
				//失败
				Yii::warning($attach->error, 'user\headimage');
				Yii::warning("save file to " . $savepath . " failed", 'user\headimage');
				return json_encode(array("errcode"=>20602, "errmsg"=>"save file failed"));
			}
			if($material->save())
			{
				return json_encode(array("errcode"=>0, "errmsg"=>"modify headimage succeed", "headImage"=>$material->headImage));
			}else{
				return json_encode(array("errcode"=>20602, "errmsg"=>"save file failed"));
			}
		}else{
			return json_encode(array("errcode"=>20602, "errmsg"=>"create directory failed"));
		}
	}

	public function nickname($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		$material = Material::findOne($id);
		Yii::trace($material->attributes, 'material\nickname');
		$material->nickname = $post["nickname"];

		Yii::trace($material->attributes, 'material\nickname');
		if($material->save())
		{
			//环信修改用户昵称
			$easemob = new Easemob;
			$result = $easemob->editNickname(array('username'=>$post["phoneNumber"], 'nickname'=>$material->nickname));

			Yii::trace("modify nickname succeed", 'material\nickname');
	        return json_encode(array("errcode"=>0, "errmsg"=>"modify nickname succeed", "nickname"=>$material->nickname));
		}else{
			Yii::trace($account->getErrors(), 'material\nickname');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify nickname"));
		}
	}

	public function sex($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		$material = Material::findOne($id);
		Yii::trace($material->attributes, 'material\sex');
		$material->sex = $post["sex"];

		Yii::trace($material->attributes, 'material\sex');
		if($material->save())
		{
			Yii::trace("modify sex succeed", 'material\sex');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify sex succeed", "sex"=>$material->sex));
		}else{
			Yii::trace($account->getErrors(), 'material\sex');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify sex"));
		}
	}

	public function location($post)
	{
		$id = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		$material = Material::findOne($id);
		$material->location = $post["location"];

		Yii::trace($material, 'material\location');
		if($material->save())
		{
			Yii::trace("modify location succeed", 'material\location');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify location succeed", "location"=>$material->location));
		}else{
			Yii::trace($account->getErrors(), 'material\location');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify location"));
		}
	}

	public function introduce($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$id = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		$material = Material::findOne($id);
		$material->introduce = $post["introduce"];

		Yii::trace($material, 'material\introduce');
		if($material->save())
		{
			Yii::trace("modify introduce succeed", 'material\introduce');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"modify introduce succeed", "introduce"=>$material->introduce));
		}else{
			Yii::trace($account->getErrors(), 'material\introduce');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when modify introduce"));
		}
	}

	/*
	public function add($post)
	{
		$material = new Pet;
		$material->attributes = $post;
		$material->time = "" . date("Y-m-d H:i:s");
		Yii::trace($account->attributes, 'material\add');
		if($material->save())
		{
			Yii::trace("add a material succeed", 'material\add');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"add a material succeed"));
		}else{
			Yii::trace($account->getErrors(), 'material\add');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when add a material"));
		}
	}
	*/
}

?>
