<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Account;
use app\models\Circle;
use app\models\Comment;
use app\models\Material;
use app\models\Relation;
use yii\web\UploadedFile;

class CircleController extends Controller
{
	/*
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }*/

    public function generateRandomString($len)
    {
	    $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	    return substr(str_shuffle($str), 0, $len); 
    }

    //生成每条朋友圈图片存储的路径名
    //100000000000 + 朋友圈最大ID ＋ 1，然后每2位之间插入一个随机字母(_00_00_00_00_00_00_)
    public function generateImageDictionary()
    {
	    $max = Circle::find()->max('id') + 1;
	    Yii::trace("max=" . $max, 'circle\add');

	    $dic = "";
	    $left = 20;
	    do {
		    $dic = $this->generateRandomString(1) . ($max % 10) . $dic;
		    $left -= 2;
		    $max = floor($max / 10);
	    } while($max > 0);
	    $dic = $this->generateRandomString($left) . $dic;

	    Yii::trace("dic=" . $dic . '/', 'circle\add');
	    return $dic;
    }


    /*
    * @param string     源图绝对完整地址{带文件名及后缀名}
    * @param string     目标图绝对完整地址{带文件名及后缀名}
    * @param int        缩略图宽{0:此时目标高度不能为0，目标宽度为源图宽*(目标高度/源图高)}
    * @param int        缩略图高{0:此时目标宽度不能为0，目标高度为源图高*(目标宽度/源图宽)}
    * @param int        是否裁切{宽,高必须非0}
    * @param int/float  缩放{0:不缩放, 0<this<1:缩放到相应比例(此时宽高限制和裁切均失效)}
    * @return boolean
    */
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

    public function materialOfId($id, array $materials)
    {
	    if(0 == $id)
		    return array("id"=>0, "nickname"=>"dummy", "headImage"=>"dummy", "sex"=>0);
	    foreach($materials as $material)
	    {
		    if($material["id"] == $id)
			    return array("id"=>$id, "nickname"=>$material["nickname"], "headImage"=>$material["headImage"], "sex"=>$material["sex"]);
	    }
    }

    //[[k=>v1],[k=>v2],[k=>v3]...]=>[v1, v2, v3...]
    public function filterSelectValue($rows, $key)
    {
	    $filterResult = [];
	    foreach($rows as $row)
	    {
		    array_push($filterResult, $row[$key]);
	    }
	    return $filterResult;
    }

    //获取朋友圈列表
    public function fetch($post)
    {
	    //获取单个好友做好友检查
	    /*
	    if(3 == $post["mode"])
	    {
		    $userPhoneNumber = Account::findOne(['id' => $post["userId"]])->phoneNumber;
		    $relation = new Relation;
		    $relation->setScenario('verify');
		    $relation->phoneNumberA = $post["phoneNumber"];
		    $relation->phoneNumberB = $userPhoneNumber;
		    Yii::trace($relation->attributes, 'circle\fetch');

		    if(!$relation->validate())
		    {
			    //非好友关系
	    	    	    Yii::trace($relation->getErrors(), 'circle\fetch');
		    	    return json_encode(array("errcode"=>10023, "errmsg"=>"not friends"));
		    }
	    }*/

	    //$type = 'type=' . $this->type;
	    $userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
	    Yii::trace('id=' . $userId, 'circle\fetch');
	    $sequence = $post["sequence"];
	    $conditionex = ($sequence == 0) ? [] : ['<', 'id', $post["sequence"]];
	    switch($post["mode"])
	    {
		    case 0:
		    	//所有人
		    	//$condition = ($sequence == 0) ? ['deleted'=>0, 'type'=>$post["type"]] : ['and', 'deleted=0', ['and', 'type=' . $post["type"], ['<', 'id', $post["sequence"]]]];
		    	$condition = ['deleted'=>0, 'type'=>$post["type"]];
			$with = ['comments', 'material'];
			break;
		    case 1:
		    	//所有好友
				//找出所有好友
				$allFriends = array_merge(Relation::find()->select('phoneNumberB as phoneNumber')->where(["phoneNumberA" => $post["phoneNumber"]])->asArray()->all(), Relation::find()->select('phoneNumberA as phoneNumber')->where(["phoneNumberB" => $post["phoneNumber"]])->asArray()->all());
				$allFriends = $this->filterSelectValue($allFriends, "phoneNumber");
				Yii::trace($allFriends, 'circle\fetch');

				$allFriendsId = Account::find()->select('id')->where(["phoneNumber" => $allFriends])->asArray()->all();
				$allFriendsId = $this->filterSelectValue($allFriendsId, "id");
				Yii::trace($allFriendsId, 'circle\fetch');

		    		$condition = ['deleted'=>0, 'type'=>$post["type"], 'ownerId'=>$allFriendsId];
		    		//$condition = ($sequence == 0) ? ['deleted'=>0, 'type'=>$post["type"], 'ownerId'=>$allFriendsId] : ['and', 'deleted=0', ['and', 'type=' . $post["type"], ['and', 'id' => $allFriendsId, ['<', 'id', $post["sequence"]]]]];
				$with = ['comments', 'material'];
				break;
		    case 2:
		    	//单个用户
		    	//$condition = ($sequence == 0) ? ['deleted'=>0, 'ownerId'=>$post["userId"], 'type'=>$post["type"]] : ['and', 'deleted=0', ['and',  'ownerId=' . $post["userId"], ['and', 'type=' . $post["type"], ['<', 'id', $post["sequence"]]]]];
		    	$condition = ['deleted'=>0, 'ownerId'=>$post["userId"], 'type'=>$post["type"]];
			$with = ['comments', 'material'];
			break;
		    case 3:
		    	//单个好友
		        $userPhoneNumber = Account::findOne(['id' => $post["userId"]])->phoneNumber;
		        $relation = new Relation;
		        $relation->setScenario('verify');
		        $relation->phoneNumberA = $post["phoneNumber"];
		        $relation->phoneNumberB = $userPhoneNumber;
		        Yii::trace($relation->attributes, 'circle\fetch');

		        if(!$relation->validate())
		        {
		        	//非好友关系
	    	        Yii::trace($relation->getErrors(), 'circle\fetch');
		        	return json_encode(array("errcode"=>10023, "errmsg"=>"not friends"));
		        }
		    	$condition = ['deleted'=>0, 'ownerId'=>$post["userId"], 'type'=>$post["type"]];
		    	//$condition = ($sequence == 0) ? ['deleted'=>0, 'ownerId'=>$post["userId"], 'type'=>$post["type"]] : ['and', 'deleted=0', ['and',  'ownerId=' . $post["userId"], ['and', 'type=' . $post["type"], ['<', 'id', $post["sequence"]]]]];
			$with = ['comments', 'material'];
			/*$with = ['comments', 'material', 'relation'=>function($query) {
				$query->andWhere(['and', 'phoneNumberA=' . $userId, 'phoneNumberB=' . $post["userId"]]);
			}];*/
			break;
		    case 4:
		    	//自己
		    	$condition = ['deleted'=>0, 'ownerId'=>$userId, 'type'=>$post["type"]];
		    	//$condition = ($sequence == 0) ? ['deleted'=>0, 'ownerId'=>$userId, 'type'=>$post["type"]] : ['and', 'deleted=0', ['and',  'ownerId=' . $userId, ['and', 'type=' . $post["type"], ['<', 'id', $post["sequence"]]]]];
		    	//$condition = ($sequence == 0) ? ['and', 'deleted=0', ['and', 'ownerId=' . $userId, ['and', 'type=' . $post["type"]]]] : ['and', 'deleted=0', ['and',  'ownerId=' . $userId, ['and', 'type=' . $post["type"], ['<', 'id', $post["sequence"]]]]];
			$with = ['comments', 'material'];
			break;
	    }
	    Yii::trace($condition, 'circle\fetch');
	    $circles = Circle::find()->where($condition)->andWhere($conditionex)->orderby(['id' => SORT_DESC])->limit($post["limit"])->with('material')->asArray()->all();
	    Yii::trace($circles, 'circle\fetch');
	    //数组修改，使用引用
	    foreach($circles as &$circle)
	    {
		    //Yii::trace($circle, 'circle\get');
		    //Yii::trace($circle->material, 'circle\get');
		    //修改点赞字段
		    $thumbIds = json_decode($circle["thumbOwnerIds"], true);
		    //Yii::trace($thumbIds, 'circle\get');
		    //SELECT * FROM table WHERE id IN (118,17,113,23,72) ORDER BY FIELD(id,118,17,113,23,72)
		    //$order = 'field (id, ' . implode(',', $thumbIds) . ')';
		    if(empty($thumbIds))
		    {
			    $thumbOwnerIds= Material::find()->select('id, nickname')->where(['id'=>$thumbIds])->asArray()->all();
		    }else{
		    	    $order = implode(', ', $thumbIds);
		    	    //Yii::trace($order, 'circle\get');
		    	    $order = 'field (id, ' . $order . ')';
		    	    //Yii::trace($order, 'circle\get');
			    $thumbOwnerIds= Material::find()->select('id, nickname')->where(['id'=>$thumbIds])->orderby(array($order=>''))->asArray()->all();
		    }
		    //Yii::trace($thumbOwnerIds, 'circle\get');
		    $circle["thumbOwnerIds"] = $thumbOwnerIds;

		    //增加评论人资料信息－－昵称
		    //凑齐所有评论者ID
		    //Yii::trace($circle["comments"], 'circle\get');
		    /*if($circle["commentCount"] > 0)
		    {
		    	    $ids = array();
		    	    foreach($circle["comments"] as &$comment)
		    	    {
		    	            $ids = array_merge($ids, array($comment["reviewerId"], $comment["revieweredId"]));
		    	  //  Yii::trace($ids, 'circle\get');
		    	    //$order = implode(', ', $ids);
		    	    //$order = 'field (id, ' . $order . ')';
		    	    	    $materials = Material::find()->select('id, nickname, headImage')->where(['id'=>$ids])->asArray()->all();
			    	    $comment["reviewerId"] = $this->materialOfId($comment["reviewerId"], $materials);
			    	    $comment["revieweredId"] = $this->materialOfId($comment["revieweredId"], $materials);
		    	    }
		    }*/
		    //Yii::trace($circle["comments"], 'circle\get');
	    }
	    unset($circle);

	    //客户端更新beginId、lastId
	    //$circles["beginId"] = $this->beginId;
	    //$circles["lastId"] = $this->lastId;

	    //$circles = array(array("nickname"=>"lufei", "contentText"=>"what about to be a men of sea thief, is this be your question?", "addTime"=>"2014-11-15 12:25:30"), array("nickname"=>"solong", "contentText"=>"I lost my way again.", "releaseTime"=>"2014-11-15 11:30:30"), array("nickname"=>"wusuopu", "contentText"=>"I am afraid.", "releaseTime"=>"2014-11-12 14:30:30"));
	    return json_encode(array("errcode"=>0, "circles"=>$circles));
    }

    //发布一条朋友圈
	public function release($post)
	{
	    //$post = $_POST;	//post附加信息
	    $files = $_FILES;	//上传的图片信息，关联数组结构
	    Yii::trace($post, 'circle\release');
	    Yii::trace($files, 'circle\release');

		$circle = new Circle;
		$circle->setScenario('release');

		//Account表中根据phoneNumber获取ID
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		Yii::trace('id=' . $userId, 'circle\release');

		$directory = $this->generateImageDictionary();
		//不能直接@circle，why?
		$saveDirectory = Yii::getAlias('@circle') . '/' . $directory . '/';
		if(count($files) > 0)
		{
		    if(false == mkdir($saveDirectory, 0777, true))
		    {
			    Yii::warning("create directory " . $saveDirectory . "failed", 'circle\release');
			    break;
		    }
		    $circle->detailImagesPath = $directory;
		}
		$index = 1;	//文件名，注意这是从1开始
		//Yii::trace(array_keys($files), 'circle\release');
		//验证成功，遍历上传的文件
		//TOTO... 遍历可放入上面的if中
		foreach(array_keys($files) as $file)
		{
		    //暂时只支持9个文件
		    if($index > 9) break;
		    //也可以分割字符串explode('.', $file['name']);
		    //list($name, $extension) = split('\.', $file['name']);
			//Yii::trace($name . "." . $extension, 'circle\release');
			//$attach = UploadedFile::getInstanceByName($name);
			Yii::trace($file, 'circle\release');
			$attach = UploadedFile::getInstanceByName($file);
			if ($attach != null)
			{
				/*
					    Yii::trace($attach->baseName, 'circle\release');
					    Yii::trace($attach->extension, 'circle\release');
					    Yii::trace($attach->name, 'circle\release');
					    Yii::trace($attach->tempName, 'circle\release');
					    Yii::trace($attach->size, 'circle\release');
					    Yii::trace($attach->type, 'circle\release');
				*/
				//文件名称不够2位前面补0，最多可保存99个文件，为了方便管理不使用后缀名
				$savepath = sprintf("%s%02u", $saveDirectory, $index);
				$savethumbpath = sprintf("%sthumb%02u", $saveDirectory, $index);
				$save = $attach->saveAs($savepath, true);
				if($save)
				{
					Yii::trace("save file to " . $savepath . " success", 'circle\release');
					//生成缩略图
					if(true == $this->img2thumb($savepath, $savethumbpath, $attach->extension, 100, 100, 0, 0))
					{
							Yii::trace("save thumbnail file to " . $savethumbpath . " success", 'circle\release');
						$index++;
					}else{
							//即使失败，继续
							Yii::warning("save thumbnail file to " . $savethumbpath . " failed", 'circle\release');
					}
				}else{
					//即使失败，继续
					Yii::warning("save file to " . $savepath . " failed", 'circle\release');
				}
			}else{
				//即使失败，继续
				Yii::warning("UploadedFile::getInstanceByName() failed", 'circle\release');
		    }
		}

		//保存到circle表中
		$circle->ownerId = $userId;
		$circle->detailImagesCount = $index - 1;
		$circle->type = $post["type"];
		$circle->detailText = $post["detailText"];
		$circle->location = $post["position"];
		$circle->releaseTime = "" . date("Y-m-d H:i:s");
	    Yii::trace($circle->attributes, 'circle\release');

		//TODO... save()后调用validate，可省略
		if($circle->validate())
		{
		    Yii::trace("check success", "circle\release");
		    if($circle->save())
		    {
	    	    	return json_encode(array("errcode"=>0, "errmsg"=>"release circle succeed"));
		    }else{
	    	    	return json_encode(array("errcode"=>20103, "errmsg"=>"circle save failed"));
		    }
		}else{
	        Yii::trace($circle->getErrors(), 'circle\release');
	    	return json_encode(array("errcode"=>20102, "errmsg"=>"cirlce validate check failed"));
		}
	    /*}else{
	    	    Yii::trace($account->getErrors(), 'circle\release');
		    return json_encode(array("errcode"=>20101, "errmsg"=>"invalid skey"));
	    }*/
    }

    //删除朋友圈列表
    public function delete($post)
    {
	    //检查id是否存在
	    $circle = new Circle;
	    $circle->setScenario('delete');
	    $circle->attributes = $post;
	    if(!$circle->validate())
	    {
    	            Yii::trace($circle->getErrors(), 'circle\delete');
    	            return json_encode(array("errcode"=>20301, "errmsg"=>"delete a non-exist circle"));
	    }

	    $id = $post["id"];
    	    $circle = Circle::findOne($id);
    	    $circle->deleted = 1;
    	    if($circle->save())
    	    {
    	            Yii::trace("delete a cricle succeed", 'circle\delete');
    	            return json_encode(array("errcode"=>0, "errmsg"=>"delete a circle succeed"));
    	    }else{
    	            Yii::trace($account->getErrors(), 'circle\delete');
    	            return json_encode(array("errcode"=>20302, "errmsg"=>"call save failed when delete a circle"));
    	    }
    }

    //点赞朋友圈
    public function thumb($post)
    {
	    //检查id是否存在
	    $circle = new Circle;
	    $circle->setScenario('thumb');
	    $circle->attributes = $post;
	    if(!$circle->validate())
	    {
    	            Yii::trace($account->getErrors(), 'circle\thumb');
    	            return json_encode(array("errcode"=>20401, "errmsg"=>"thumb a non-exist circle"));
	    }

	    $id = $post["id"];
	    $circle = Circle::findOne($id);
	    Yii::trace($circle->thumbOwnerIds, 'circle\thumb');
	    Yii::trace($circle->thumb, 'circle\thumb');
	    //更新点赞人列表
	    $userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;	//点赞人ID
	    $nickname = Material::findOne(['id' => $userId])->nickname;
	    if(empty($circle->thumbOwnerIds))
	    {
		    $thumbIds = array($userId);
	       	    $circle->thumb++;	//点赞人数加1
	    }else{
	       	    $thumbIds = json_decode($circle->thumbOwnerIds, true);
	       	    if(in_array($userId, $thumbIds))
	       	    {
	            	    Yii::trace("user has thumbed already", 'circle\thumb');
		    }else{
			    array_push($thumbIds, $userId);
	       	    	    $circle->thumb++;	//点赞人数加1
		    }
	    }
	    $circle->thumbOwnerIds = json_encode($thumbIds);
	    Yii::trace($circle->thumbOwnerIds, 'circle\thumb');
	    Yii::trace($circle->thumb, 'circle\thumb');
	    if($circle->save())
	    {
	            Yii::trace("thumb a cricle succeed", 'circle\thumb');
	            return json_encode(array("errcode"=>0, "errmsg"=>"thumb a circle succeed"));
	            //return json_encode(array("errcode"=>0, "errmsg"=>"thumb a circle succeed", "id"=>$userId, "nickname"=>$nickname));
	    }else{
	            Yii::trace($account->getErrors(), 'circle\thumb');
	            return json_encode(array("errcode"=>20402, "errmsg"=>"call save failed when thumb a circle"));
	    }
    }

    //取消点赞朋友圈
    public function cancelThumb($post)
    {
	    //检查id是否存在
	    $circle = new Circle;
	    $circle->setScenario('cancelThumb');
	    $circle->attributes = $post;
	    if(!$circle->validate())
	    {
    	            Yii::trace($account->getErrors(), 'circle\cancelThumb');
    	            return json_encode(array("errcode"=>20501, "errmsg"=>"cancel thumb a non-exist circle"));
	    }

	    $id = $post["id"];
	    $circle = Circle::findOne($id);
	    Yii::trace($circle->thumbOwnerIds, 'circle\cancelThumb');
	    Yii::trace($circle->thumb, 'circle\cancelThumb');
	    //更新点赞人列表
	    $thumbIds = json_decode($circle->thumbOwnerIds, true);
	    $userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;	//点赞人ID
	    $nickname = Material::findOne(['id' => $userId])->nickname;
	    if(in_array($userId, $thumbIds))
	    {
	            $thumbIds = array_values(array_diff($thumbIds, [$userId]));
	    	    Yii::trace($thumbIds, 'circle\cancelThumb');
	            $circle->thumbOwnerIds = json_encode($thumbIds);
	            $circle->thumb--;	//点赞人数-1
	    }else{
	            Yii::trace("user has not thumbed before", 'circle\cancelThumb');
	            return json_encode(array("errcode"=>20503, "errmsg"=>"user has not thumbed before"));
	    }
	    Yii::trace($circle->thumbOwnerIds, 'circle\cancelThumb');
	    Yii::trace($circle->thumb, 'circle\cancelThumb');
	    if($circle->save())
	    {
	            Yii::trace("cancel thumb a cricle succeed", 'circle\cancelThumb');
	            return json_encode(array("errcode"=>0, "errmsg"=>"cancel thumb a circle succeed"));
	            //return json_encode(array("errcode"=>0, "errmsg"=>"cancel thumb a circle succeed", "id"=>$userId, "nickname"=>$nickname));
	    }else{
	            Yii::trace($account->getErrors(), 'circle\cancelThumb');
	            return json_encode(array("errcode"=>20502, "errmsg"=>"call save failed when cancel thumb a circle"));
	    }
    }

    public function fetchComment($post)
    {
	    $sequence = $post["sequence"];
	    $condition = ($sequence == 0) ? ['isDeleted'=>0, 'circleId'=>$post["circleId"]] : ['and', 'isDeleted=0', ['and', 'circleId=' . $post["circleId"], ['<', 'id', $post["sequence"]]]];
	    Yii::trace($condition, 'comment\fetch');
	    $comments = Comment::find()->where($condition)->orderby(['id' => SORT_DESC])->limit($post["limit"])->asArray()->all();

   	    $ids = array();
   	    foreach($comments as &$comment)
   	    {
   	            $ids = array_merge($ids, array($comment["reviewerId"], $comment["revieweredId"]));
		    	Yii::trace($ids, 'circle\get');
   	    	    $materials = Material::find()->select('id, nickname, headImage, sex')->where(['id'=>$ids])->asArray()->all();
   	    	    $comment["reviewerId"] = $this->materialOfId($comment["reviewerId"], $materials);
   	    	    $comment["revieweredId"] = $this->materialOfId($comment["revieweredId"], $materials);
   	    }
	    return json_encode(array("errcode"=>0, "comments"=>$comments));
    }

    //当前不考虑评论针对哪条评论，评论都是针对帖子
    public function addComment($post)
    {
	    $comment = new Comment;
	    $comment->setScenario('add');
	    $comment->attributes = $post;
	    Yii::trace($comment->attributes, 'circle\comment');

	    //id，找出最大的id ＋ 1
	    $max = Comment::find()->where(['circleId'=>$comment->circleId])->max('id');
	    Yii::trace('max(id)=' . $max, 'circle\comment');
	    $comment->id = $max + 1;

	    //time
 	    $comment->time = "" . date("Y-m-d H:i:s");

	    //reviewId，Account表中根据phoneNumber获取ID，在verify场景中已经检查过手机号的有效性，必然有id
	    $userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
	    Yii::trace('id=' . $userId, 'circle\comment');
	    $comment->reviewerId = $userId;

	    //revieweredId，pid对应的父评论的评论者ID
	    //$revieweredId = Comment::find()->where(['id' => $comment->pid])->one()->reviewerId;
	    //Yii::trace('id=' . $revieweredId, 'circle\comment');
	    //$comment->revieweredId = $revieweredId;
	    $comment->revieweredId = 0;
	    $comment->isDeleted = 0;

	    Yii::trace($comment->attributes, 'circle\comment');
	    if($comment->save())
	    {
			Yii::trace("comment a cricle succeed", 'circle\comment');
		    //累加评论次数
		    $circle = Circle::find()->where(['id' => $comment->circleId])->one();
		    $circle->setScenario('comment');
		    $circle->commentCount++;
	    	Yii::trace($circle->attributes, 'circle\comment');
		    if(!$circle->save())
		    {
			    //即使失败也继续，评论次数以comment表为准
			    Yii::trace("save comment counts failed", 'circle\comment');
		    }
		    $comment->reviewerId = Material::find()->select('id, nickname, headImage, sex')->where(['id' => $comment->reviewerId])->asArray()->one();
		    $comment->revieweredId = (0 == $comment->revieweredId) ? array('id' => 0, 'nickname' => 'dummy') : Material::find()->select('id, nickname')->where(['id' => $comment->revieweredId])->asArray()->one();
		    
			return json_encode(array("errcode"=>0, "errmsg"=>"comment a circle succeed", "comment"=>$comment->attributes));
	    }else{
			Yii::trace($comment->getErrors(), 'circle\comment');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when comment a circle"));
	    }
    }

    public function actionOperate()
    {
	    $account = new Account;
	    $account->setScenario('verify');
	    //$post = json_decode(Yii::$app->request->getRawBody(), true);
	    $post = $_POST ? $_POST : json_decode(Yii::$app->request->getRawBody(), true);
	    Yii::trace($post, 'circle\operation');
	    $account->attributes = $post;
	    $account->skey = $post["skey"];
	    Yii::trace($account->attributes, 'circle\operation');

	    $opcode = $post["opcode"];
	    //验证skey
	    if($account->validate())
	    {
		    switch($opcode)
		    {
			    case 0:
			    	//发帖
					return $this->release($post);
			    case 1:
			    	//按条件读取发布的帖子
					return $this->fetch($post);
			    case 10:
			    	//删除
					return $this->delete($post);
			    case 11:
			    	//点赞
					return $this->thumb($post);
			    case 12:
			    	//取消点赞
					return $this->cancelThumb($post);
			    case 20:
			    	//拉取评论
					return $this->fetchComment($post);
			    case 21:
			    	//评论
					return $this->addComment($post);
			    case 22:
			    	//取消评论
					return $this->cancelComment($post);
			    default:
			    	//不支持的操作，不回包
			    	break;
		    }
	    }else{
		    switch($opcode)
		    {
			    case 0:
			    	//发帖
				$errcode = 20301;
			    	break;
			    case 1:
			    	//按条件读取发布的帖子
				$errcode = 20301;
			    	break;
			    case 10:
			    	//删除
				$errcode = 20301;
			    	break;
			    case 11:
			    	//点赞
				$errcode = 20301;
			    	break;
			    case 12:
			    	//取消点赞
				$errcode = 20301;
			    	break;
			    case 20:
			    	//拉取评论
				$errcode = 20301;
			    	break;
			    case 21:
			    	//评论
				$errcode = 20301;
			    	break;
			    case 22:
			    	//取消评论
				$errcode = 20301;
			    	break;
			    default:
			    	//不支持的操作
				return;
		    }
	    	    Yii::trace($account->getErrors(), 'circle\operate');
		    return json_encode(array("errcode"=>$errcode, "errmsg"=>"invalid skey"));
	    }
    }
}

