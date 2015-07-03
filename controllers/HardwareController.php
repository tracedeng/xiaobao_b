<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\Account;
use app\models\Pet;
use app\models\Material;
use app\models\Hardware;
use app\models\Snapshot;
use app\models\Motion;
use app\models\Consumption;
use app\models\Fence;
use app\models\Fixpara;
use app\models\PetFood;

class GeoHash
{
    private static $table = "0123456789bcdefghjkmnpqrstuvwxyz";
    private static $bits = array(
        0b10000, 0b01000, 0b00100, 0b00010, 0b00001
    );
    public static function encode($lng, $lat, $prec = 0.0001)
    {
        $minlng = -180;
        $maxlng = 180;
        $minlat = -90;
        $maxlat = 90;
        $hash = array();
        $error = 180;
        $isEven = true;
        $chr = 0b00000;
        $b = 0;
        while ($error >= $prec) {
            if ($isEven) {
                $next = ($minlng + $maxlng) / 2;
                if ($lng > $next) {
                    $chr |= self::$bits[$b];
                    $minlng = $next;
                } else {
                    $maxlng = $next;
                }
            } else {
                $next = ($minlat + $maxlat) / 2;
                if ($lat > $next) {
                    $chr |= self::$bits[$b];
                    $minlat = $next;
                } else {
                    $maxlat = $next;
                }
            }
            $isEven = !$isEven;
            if ($b < 4) {
                $b++;
            } else {
                $hash[] = self::$table[$chr];
                $error = max($maxlng - $minlng, $maxlat - $minlat);
                $b = 0;
                $chr = 0b00000;
            }
        }
        return join('', $hash);
    }

    public static function expand($hash)
    {
	$precs = Array(5=>0.1, 6=>0.02, 7=>0.01, 8=>0.001, 9=>0.0001);
        $prec = $precs[strlen($hash)];
        list($minlng, $maxlng, $minlat, $maxlat) = self::decode($hash);
        $dlng = ($maxlng - $minlng) / 2;
        $dlat = ($maxlat - $minlat) / 2;
        return array(
            self::encode($minlng - $dlng, $maxlat + $dlat, $prec),
            self::encode($minlng + $dlng, $maxlat + $dlat, $prec),
            self::encode($maxlng + $dlng, $maxlat + $dlat, $prec),
            self::encode($minlng - $dlng, $maxlat - $dlat, $prec),
            self::encode($maxlng + $dlng, $maxlat - $dlat, $prec),
            self::encode($minlng - $dlng, $minlat - $dlat, $prec),
            self::encode($minlng + $dlng, $minlat - $dlat, $prec),
            self::encode($maxlng + $dlng, $minlat - $dlat, $prec),
        );
    }

    public static function getRect($hash)
    {
        list($minlng, $maxlng, $minlat, $maxlat) = self::decode($hash);
        return array(
            array($minlng, $minlat),
            array($minlng, $maxlat),
            array($maxlng, $maxlat),
            array($maxlng, $minlat),
        );
    }
    /**
     * decode a geohash string to a geographical area
     *
     * @var $hash string geohash
     * @return array array($minlng, $maxlng, $minlat, $maxlat);
     */
    public static function decode($hash)
    {
        $minlng = -180;
        $maxlng = 180;
        $minlat = -90;
        $maxlat = 90;
        for ($i=0,$c=strlen($hash); $i<$c; $i++) {
            $v = strpos(self::$table, $hash[$i]);
            if (1&$i) {
                if (16&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (8&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (4&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (2&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (1&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
            } else {
                if (16&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (8&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (4&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
                if (2&$v) {
                    $minlat = ($minlat + $maxlat) / 2;
                } else {
                    $maxlat = ($minlat + $maxlat) / 2;
                }
                if (1&$v) {
                    $minlng = ($minlng + $maxlng) / 2;
                } else {
                    $maxlng = ($minlng + $maxlng) / 2;
                }
            }
        }
        return array($minlng, $maxlng, $minlat, $maxlat);
    }
}

class HardwareController extends Controller
{
	//检查用户拥有该宠物
	public function canBindHardware($petId, $userId)
	{
		$pet = Pet::find()->where(["id"=>$petId, "ownerId"=>$userId]);
		if($pet)
			return true;
		return false;
	}

	//检查用户拥有该宠物
	public function canUnbindHardware($petId, $userId)
	{
		return $this->canBindHardware($petid, $userId);
	}

	public function canFetchPosition($petId, $userId)
	{
		if($this->canBindHardware($petId, $userId))
			return true;
		$sponsor = Sponsor::find()->where(["petId"=>$petId, "sponsorId"=>$userId]);
		if($sponsor)
			return true;
		return false;
	}

	/*
	public function canFetchPetsNearby($petId, $userId)
	{
		return $this->canFetchPosition($petId, $userId);
	}
	*/

	public function canFetchOrbit($petId, $userId)
	{
		return $this->canFetchPosition($petId, $userId);
	}

	public function canSetFence($petId, $userId)
	{
		return $this->canBindHardware($petId, $userId);
	}

	public function canGetFence($petId, $userId)
	{
		return $this->canBindHardware($petId, $userId);
	}

	public function canFetchMotion($petId, $userId)
	{
		return $this->canFetchPosition($petId, $userId);
	}

	public function bindGprs($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canBindHardware($petId, $userId))
		{
			Yii::trace("bind gprs failed, user not own this pet", 'hardware\bindGprs');
			return json_encode(array("errcode"=>20602, "errmsg"=>"bind gprs failed"));
		}

		//如果硬件已经绑定了宠物则取消之前的绑定
		$gprsId = $post["gprsId"];
		$pet = Pet::find()->where(['gprsId' => $gprsId])->one();
		if($pet)
		{
			$pet->isBindGprs = 0;
			$pet->gprsId = "";
			$pet->sponsorOpen = 0;	//取消硬件绑定关闭助养开关
			if(!$pet->save())
			{
				Yii::trace("cancel binded gprs failed", 'hardware\bindGprs');
				return json_encode(array("errcode"=>20602, "errmsg"=>"cancel binded gprs failed"));
			}
		}
		unset($pet);

		//检查通过则宠物一定存在
		$pet = Pet::findOne($petId);
		//$pet->setScenario('bindGprs');
		$pet->isBindGprs = 1;
		$pet->gprsId = $gprsId;
		$pet->bindGprsTime = "" . date("Y-m-d H:i:s");
		$pet->sponsorOpen = 1;	//绑定硬件默认打开助养
		$pet->sponsorCount = 90;
		Yii::trace($pet->attributes, 'hardware\bindGprs');

		if($pet->save())
		{
			Yii::trace("bind gprs succeed", 'hardware\bindGprs');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"bind gprs succeed"));
	        	//return json_encode(array("errcode"=>0, "errmsg"=>"bind gprs succeed", "gprsId"=>$pet->gprsId));
		}else{
			Yii::trace($account->getErrors(), 'hardware\bindGprs');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when bind gprs"));
		}
	}

	public function unbindGprs($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		//检查用户拥有该宠物
		if(false == $this->canUnbindHardware($petId, $userId))
		{
			Yii::trace("unbind gprs failed, user not own this pet", 'hardware\unbindGprs');
			return json_encode(array("errcode"=>20602, "errmsg"=>"unbind gprs failed"));
		}
		//检查通过则宠物一定存在，如果硬件已经绑定了宠物则取消之前的绑定
		$pet = Pet::findOne($id);
		//$pet->setScenario('ununbindGprs');
		$pet->isBindGprs = 0;
		$pet->gprsId = "";
		//$pet->unbindGprsTime = "" . date("Y-m-d H:i:s");
		$pet->sponsorOpen = 0;	//取消绑定硬件关闭助养
		Yii::trace($pet->attributes, 'hardware\unbindGprs');

		if($pet->save())
		{
			Yii::trace("unbind gprs succeed", 'hardware\unbindGprs');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"unbind gprs succeed", "gprsId"=>$hardware->gprsId));
		}else{
			Yii::trace($account->getErrors(), 'hardware\unbindGprs');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when unbind gprs"));
		}
	}

	public function petPosition($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		//检查用户拥有或助养该宠物
		if(false == $this->canFetchPosition($petId, $userId))
		{
			Yii::trace("unbind gprs failed, user not own this pet", 'snapshot\position');
			return json_encode(array("errcode"=>20602, "errmsg"=>"fetch position failed, user not own or sponsor this pet"));
		}
		//检查宠物是否绑定硬件
		$pet = Pet::find()->where(["id" => $petId])->select('gprsId, isBindGprs')->asArray()->one();
		if(!$pet["isBindGprs"])
		{
			Yii::trace("this pet not bind gprs", 'snapshot\position');
			return json_encode(array("errcode"=>20602, "errmsg"=>"pet not bind gprs"));
		}
		//找到硬件未关闭的宠物位置信息
		$position = Snapshot::find()->where(['gprsId'=>$pet["gprsId"], 'closed'=>0])->select('position, time')->asArray()->one();
		Yii::trace($position, 'snapshot\position');

		return json_encode(array("errcode"=>0, "position"=>$position));
	}

	//找到距离宠物附近几公里列表
	public function petsListNearby($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		/*
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		//检查用户拥有或助养该宠物
		if(false == $this->canFetchPetsNearby($petId, $userId))
		{
			Yii::trace("user not own or sponsor this pet", 'snapshot\nearby');
			return json_encode(array("errcode"=>20602, "errmsg"=>"user not own or sponsor this pet"));
		}
		//检查宠物是否绑定硬件
		$pet = Pet::find()->where(["id" => $petId])->select('gprsId, isBindGprs')->asArray()->one();
		if(!$pet["isBindGprs"])
		{
			Yii::trace("this pet not bind gprs", 'snapshot\nearby');
			return json_encode(array("errcode"=>20602, "errmsg"=>"pet not bind gprs"));
		}
		$position = Snapshot::find()->where(['gprsId'=>$pet["gprsId"], 'closed'=>0])->asArray()->one();
		Yii::trace($position, 'snapshot\nearby');
		//宠物关闭了硬件
		if(!$position)
		{
			Yii::trace("gprs closed", 'snapshot\nearby');
			return json_encode(array("errcode"=>20602, "errmsg"=>"gprs closed"));
		}*/

		//GEOHASH计算，找到硬件未关闭的附近宠物位置信息
		$center = $post["center"];
		Yii::trace($center, 'hardware\rawdata');
		$center = json_decode($center, true);
		Yii::trace($center, 'hardware\rawdata');

		$centerGeo9 = GeoHash::encode($center["lng"], $center["lat"]);
		Yii::trace($centerGeo9, 'hardware\rawdata');
		$type = $post["type"];
		switch($type)
		{
			case 0:
				//100m
				$positionGeo7 = substr($centerGeo9, 0, 7);
				$positionGeoExpand = GeoHash::expand($positionGeo7);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');
				foreach($positionGeoExpand as $hash)
				{
					$geoExpand = GeoHash::expand($hash);
					Yii::trace($hash, 'hardware\rawdata');
					Yii::trace($geoExpand, 'hardware\rawdata');
					$positionGeoExpand = array_merge($positionGeoExpand, $geoExpand);
				}
				$positionGeoExpand = array_unique($positionGeoExpand);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');
				$condition = ["positionGeo7" => $positionGeoExpand];
				break;
			case 1:
				//500m
				$positionGeo6 = substr($centerGeo9, 0, 6);
				$positionGeoExpand = GeoHash::expand($positionGeo6);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');
				$condition = ["positionGeo6" => $positionGeoExpand];
				break;
			case 2:
				//1km
				$positionGeo6 = substr($centerGeo9, 0, 6);
				$positionGeoExpand = GeoHash::expand($positionGeo6);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');
				foreach($positionGeoExpand as $hash)
				{
					$geoExpand = GeoHash::expand($hash);
					Yii::trace($hash, 'hardware\rawdata');
					Yii::trace($geoExpand, 'hardware\rawdata');
					$positionGeoExpand = array_merge($positionGeoExpand, $geoExpand);
				}
				$positionGeoExpand = array_unique($positionGeoExpand);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');
				$condition = ["positionGeo6" => $positionGeoExpand];
				break;
			case 3:
				//2km
				$positionGeo5 = substr($centerGeo9, 0, 5);
				$positionGeoExpand = GeoHash::expand($positionGeo5);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');
				$condition = ["positionGeo5" => $positionGeoExpand];
				break;
			case 4:
				//5km，误差200m，4%误差可允许范围
				$positionGeo5 = substr($centerGeo9, 0, 5);
				$positionGeoExpand = GeoHash::expand($positionGeo5);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');
				foreach($positionGeoExpand as $hash)
				{
					$geoExpand = GeoHash::expand($hash);
					Yii::trace($hash, 'hardware\rawdata');
					Yii::trace($geoExpand, 'hardware\rawdata');
					$positionGeoExpand = array_merge($positionGeoExpand, $geoExpand);
				}
				$positionGeoExpand = array_unique($positionGeoExpand);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');
				$condition = ["positionGeo5" => $positionGeoExpand];
				break;
			case 5:
				//10km，误差400m，4%误差可允许范围
				$positionGeo5 = substr($centerGeo9, 0, 5);
				//第一层
				$positionGeoExpand = GeoHash::expand($positionGeo5);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');

				//第二层
				$secondLevel = array();
				foreach($positionGeoExpand as $hash)
				{
					$geoExpand = GeoHash::expand($hash);
					Yii::trace($hash, 'hardware\rawdata');
					Yii::trace($geoExpand, 'hardware\rawdata');
					$secondLevel = array_merge($secondLevel, $geoExpand);
					$positionGeoExpand = array_merge($positionGeoExpand, $geoExpand);
				}
				$secondLevel = array_unique($secondLevel);

				//第三层
				$thirdLevel = array();
				foreach($secondLevel as $hash)
				{
					$geoExpand = GeoHash::expand($hash);
					Yii::trace($hash, 'hardware\rawdata');
					Yii::trace($geoExpand, 'hardware\rawdata');
					$thirdLevel = array_merge($thirdLevel, $geoExpand);
					$positionGeoExpand = array_merge($positionGeoExpand, $geoExpand);
				}
				$thirdLevel = array_unique($thirdLevel);

				//第四层
				foreach($thirdLevel as $hash)
				{
					$geoExpand = GeoHash::expand($hash);
					Yii::trace($hash, 'hardware\rawdata');
					Yii::trace($geoExpand, 'hardware\rawdata');
					$positionGeoExpand = array_merge($positionGeoExpand, $geoExpand);
				}
				$positionGeoExpand = array_unique($positionGeoExpand);
				Yii::trace($positionGeoExpand, 'hardware\rawdata');
				$condition = ["positionGeo5" => $positionGeoExpand];
				break;
			default:
				$condition = ["gprsId" => ""];
				break;
		}

		$nearby = Snapshot::find()->where($condition)->select('position, time, gprsId')->with('pet')->asArray()->all();
		Yii::trace($nearby, 'hardware\result');

		$maxNail= Fixpara::find()->select('maxNail')->one();
		$maxNail = $maxNail->maxNail;
		$count = count($nearby);
		if($count >= $maxNail)
		{
			//只返回一条数据
			$nearby[0]["count"] = $count;
			$nearby = array($nearby[0]);
		}

		return json_encode(array("errcode"=>0, "position"=>$center, "nearby"=>$nearby));
	}

	//找到距离宠物附近几公里列表
	public function petsListNearbyOfPet($post)
	{
		//获取宠物位置，赋值$post["center"]
		$petId = $post["petId"];
		$pet = Pet::find()->where(["id" => $petId])->select('gprsId, isBindGprs')->one();
		if(!$pet)
		{
			return json_encode(array("errcode"=>0, "position"=>"", "pets"=>[]));
		}
		$gprsId = $pet->gprsId;
		$center = Snapshot::find()->where(["gprsId"=>$gprsId])->one();
		$center = $center->position;
		$post["center"] = $center;

		return $this->petsListNearby($post);
	}

	//TODO... 获取时间段，limit条位置信息
	public function petOrbit($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		//检查用户拥有或助养该宠物
		if(false == $this->canFetchOrbit($petId, $userId))
		{
			Yii::trace("user not own or sponsor this pet", 'hardware\orbit');
			return json_encode(array("errcode"=>20602, "errmsg"=>"fetch orbit failed, user not own or sponsor this pet"));
		}
		//检查宠物是否绑定硬件
		$pet = Pet::find()->where(["id" => $petId])->select('gprsId, isBindGprs')->asArray()->one();
		if(!$pet["isBindGprs"])
		{
			Yii::trace("this pet not bind gprs", 'hardware\orbit');
			return json_encode(array("errcode"=>20602, "errmsg"=>"pet not bind gprs"));
		}
		//找到硬件未关闭的宠物位置信息
		$sql = 'select motionIndex, (timestampdiff(minute, time , now()) div 20) as seq from hardware where gprsId = ' . $gprsId .' and day < date_sub(curdate(), interval 1 day)';
		$orbit = Hardware::findBySql($sql)->asArray()->all();
		Yii::trace($orbit, 'hardware\orbit');

		return json_encode(array("errcode"=>0, "orbit"=>$orbit));
	}

	public function setFence($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canSetFence($petId, $userId))
		{
			Yii::trace("set fence failed, user not own this pet", 'fence\setFence');
			return json_encode(array("errcode"=>20602, "errmsg"=>"bind gprs failed"));
		}
		//检查宠物是否绑定硬件
		$pet = Pet::find()->where(["id" => $petId])->select('gprsId, isBindGprs')->asArray()->one();
		if(!$pet["isBindGprs"])
		{
			Yii::trace("this pet not bind gprs", 'fence\orbit');
			return json_encode(array("errcode"=>20602, "errmsg"=>"pet not bind gprs"));
		}

		$fence = new Fence;
		//$fence->setScenario('setFence');
		$fence->gprsId = $pet["gprsId"];
		$fence->fenceCenter = $post["center"];
		$fence->fenceRadius = $post["radius"];
		$fence->open = 1;	//绑定硬件默认打开助养
		$fence->time = "" . date("Y-m-d H:i:s");
		Yii::trace($fence->attributes, 'fence\setFence');

		if($fence->save())
		{
			Yii::trace("set fence succeed", 'fence\setFence');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"set fence succeed", "center"=>$fence->fenceCenter, "radius"=>$fence->fenceRadius));
		}else{
			Yii::trace($account->getErrors(), 'fence\setFence');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when set fence"));
		}
	}

	public function setFenceStatus($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canSetFence($petId, $userId))
		{
			Yii::trace("set fence status failed, user not own this pet", 'fence\setFenceStatus');
			return json_encode(array("errcode"=>20602, "errmsg"=>"set fence status failed"));
		}
		//检查宠物是否绑定硬件
		$pet = Pet::find()->where(["id" => $petId])->select('gprsId, isBindGprs')->asArray()->one();
		if(!$pet["isBindGprs"])
		{
			Yii::trace("this pet not bind gprs", 'fence\setFenceStatus');
			return json_encode(array("errcode"=>20602, "errmsg"=>"pet not bind gprs"));
		}

		$fence = Fence::findOne($pet["gprsId"]);
		if(!$fence)
		{
			Yii::trace("not set fence yet", 'fence\setFenceStatus');
			return json_encode(array("errcode"=>20602, "errmsg"=>"not set fence yet"));
		}
		$fence->open = $post["open"];
		$fence->time = "" . date("Y-m-d H:i:s");
		Yii::trace($fence->attributes, 'fence\setFenceStatus');

		if($fence->save())
		{
			Yii::trace("set fence status succeed", 'fence\setFenceStatus');
	        	return json_encode(array("errcode"=>0, "errmsg"=>"set fence status succeed", "open"=>$fence->open));
		}else{
			Yii::trace($account->getErrors(), 'fence\setFenceStatus');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when set fence status"));
		}
	}

	public function fence($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canGetFence($petId, $userId))
		{
			Yii::trace("get fence failed, user not own this pet", 'fence\getFence');
			return json_encode(array("errcode"=>20602, "errmsg"=>"bind gprs failed"));
		}
		//检查宠物是否绑定硬件
		$pet = Pet::find()->where(["id" => $petId])->select('gprsId, isBindGprs')->asArray()->one();
		if(!$pet["isBindGprs"])
		{
			Yii::trace("this pet not bind gprs", 'fence\orbit');
			return json_encode(array("errcode"=>20602, "errmsg"=>"pet not bind gprs"));
		}

		$fence = Fence::find()->where(["gprsId"=>$pet["gprsId"]])->asArray()->one();
		Yii::trace($fence, 'fence\getFence');

		return json_encode(array("errcode"=>0, "fence"=>$fence));
	}

	//每20分钟拉一次
	public function dayMotionIndex($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canFetchMotion($petId, $userId))
		{
			Yii::trace("get day motion index failed, user not own this pet", 'motion\dayMotion');
			return json_encode(array("errcode"=>20602, "errmsg"=>"bind gprs failed"));
		}

		$pet = Pet::find()->where(['id'=>$petId])->select('gprsId, isBindGprs')->one();
		if(!$pet->isBindGprs)
		{
			//不会到这
			return json_encode(array("errcode"=>0, "motion"=>array()));
		}
		$gprsId = $pet->gprsId;

		//24 * 60 + 20 = 1460min
		$sql = "select motionIndex, (timestampdiff(minute, curdate(), time) div 20) as seq from hardware where gprsId = " . $gprsId ." and time > curdate()";
		//$sql = "select motionIndex, (timestampdiff(minute, time, now()) div 20) as seq from hardware where gprsId = " . $gprsId ." and time > date_sub(curdate(), interval 1460 minute)";
		$motion = Hardware::findBySql($sql)->asArray()->all();
		Yii::trace($motion, 'motion\dayMotion');
		//motion格式array((motionIndex=>100, seq=9), (motionIndex=>200, seq=99),...)
		//转变后格式array(0=>100, 1=>200)
		$result = array();
		$dayTotal = 0;
		foreach($motion as $item)
		{
			$result[$item["seq"]] = $item["motionIndex"];
			$dayTotal += $item["motionIndex"];
		}
		$result["total"] = $dayTotal;

		return json_encode(array("errcode"=>0, "motion"=>$result));
	}

	public function weekMotionIndex($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canFetchMotion($petId, $userId))
		{
			Yii::trace("get real comsumption failed, user not own this pet", 'hardware\weekMotion');
			return json_encode(array("errcode"=>20602, "errmsg"=>"user not own this pet"));
		}

		$pet = Pet::find()->where(['id'=>$petId])->select('gprsId, isBindGprs')->one();
		if(!$pet->isBindGprs)
		{
			//不会到这
			return json_encode(array("errcode"=>0, "motion"=>array()));
		}
		$gprsId = $pet->gprsId;
		Yii::trace($gprsId, 'hardware\weekMotion');

		//找当前7天前数据，没有数据则为空
		/*for($i = 8; $i > 1; $i--)
		{
			$sql = 'select day, motionIndex from motion where gprsId = 123124 and day = date_sub(CURDATE(), interval ' . $i . ' day)';
			$dayMotion = Motion::findBySql($sql)->asArray()->one();
			$motion[8 - $i] = $dayMotion ? $dayMotion[0] : [];
		}*/
		$sql = "select motionIndex, datediff(day, date_sub(curdate(), interval 7 day)) as seq from motion where day > date_sub(curdate(), interval 8 day) and day < curdate() and gprsId=" . $gprsId;
		//$sql = "select motionIndex, datediff(now(), day) as seq from motion where day > date_sub(now(), interval 8 day) and gprsId=" . $gprsId;
		$motion = Motion::findBySql($sql)->asArray()->all();
		Yii::trace($motion, 'hardware\weekMotion');

		//motion格式array((motionIndex=>100, seq=0), (motionIndex=>200, seq=1),...)
		//转变后格式array(0=>100, 1=>200)
		$result = array();
		$weekTotal = 0;
		foreach($motion as $item)
		{
			$result[$item["seq"]] = $item["motionIndex"];
			$weekTotal += $item["motionIndex"];
		}
		$result["total"] = $weekTotal;

		return json_encode(array("errcode"=>0, "motion"=>$result));
	}

	public function realTimeConsumption($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		if(false == $this->canFetchMotion($petId, $userId))
		{
			Yii::trace("get real comsumption failed, user not own this pet", 'hardware\consumption');
			return json_encode(array("errcode"=>20602, "errmsg"=>"user not own this pet"));
		}

		//$pet = Pet::find($petId)->select('gprsId, isBindGprs')->one();
		$pet = Pet::find()->where(["id" => $petId])->select('gprsId, isBindGprs')->asArray()->one();
		if(!$pet->isBindGprs)
		{
			//不会到这
			return json_encode(array("errcode"=>0, "consumption"=>array()));
		}
		$gprsId = $pet->gprsId;

		$consumption = Consumption::find()->where(['gprsId'=>$gprsid])->asArray()->one();
		Yii::trace($consumption, 'hardware\consumption');

		return json_encode(array("errcode"=>0, "consumption"=>$consumption));
	}

	public function fixParameters($post)
	{
		$para = Fixpara::find()->asArray()->one();

		return json_encode(array("errcode"=>0, "parameters"=>$para));
	}

	public function queryFood($post)
	{
		$rawFoods = PetFood::find()->where(['pid' => $post['pid']])->orderby(['section' => SORT_ASC, 'name' => SORT_ASC])->asArray()->all();
		Yii::trace($rawFoods, 'pet\queryFoods');
		$cookFoods = array();
		foreach($rawFoods as $kind)
		{
			$section = $kind["section"];
			if(array_key_exists($section, $cookFoods))
			{
				array_push($cookFoods[$section], $kind);
			}else{
				$cookFoods[$section]= array($kind);
			}
		}
		Yii::trace($cookFoods, 'pet\queryFoods');

		$foods = array();
		foreach($cookFoods as $key=>$value)
		{
			array_push($foods, array("section"=>$key, "foods"=>$value));
		}
		Yii::trace($foods, 'pet\queryFoods');
	        return json_encode(array("errcode"=>0, "errmsg"=>"query pet foods succeed", "foods"=>$foods));
		$para = PetFood::find()->asArray()->one();

		return json_encode(array("errcode"=>0, "parameters"=>$para));
	}

	public function manageGprsRawData($post)
	{
		$gprsId = $post['gprsId'];
		$motionIndex = $post['motionIndex'];
		$battery = $post['battery'];
		$cliaddr = $post['cliaddr'];
		$position = $post["position"];
		Yii::trace("position=" . $position, 'hardware\rawdata');

		$position = json_decode($position, true);
		Yii::trace($position, 'hardware\rawdata');
		$positionGeo9 = GeoHash::encode($position["lng"], $position["lat"]);
		Yii::trace($positionGeo9, 'hardware\rawdata');

		$positionGeo9Expand = GeoHash::expand($positionGeo9);
		Yii::trace($positionGeo9Expand, 'hardware\rawdata');

		//实时传感器数据
		$sql = 'select id from hardware where time = (select max(time) from hardware where gprsId = "' . $gprsId . '") and gprsId = "' . $gprsId . '"';
		$max = Hardware::findBySql($sql)->one();
		//一周数据量 3 * 24 * 7 = 504
		$max = $max ? ($max->id + 1) : 1;
		#$max = $max ? ($max->id > 503 ? 1 : ($max->id + 1)) : 1;
		Yii::trace("max=" . $max, 'hardware\rawdata');

		$ripeData = Hardware::find()->where(['gprsId' => $gprsId, 'id' => $max])->one();
		if(!$ripeData)
		{
			$ripeData = new Hardware;
			$ripeData->id = $max;
			$ripeData->gprsId = $post["gprsId"];
		}
		$ripeData->position = $post["position"];
		$ripeData->positionGeo9 = $positionGeo9;
		$ripeData->positionGeo8 = substr($positionGeo9, 0, 8);
		$ripeData->positionGeo7 = substr($positionGeo9, 0, 7);
		$ripeData->positionGeo6 = substr($positionGeo9, 0, 6);
		$ripeData->positionGeo5 = substr($positionGeo9, 0, 5);
		$ripeData->motionIndex = $motionIndex;
		$ripeData->battery = $battery;
		date_default_timezone_set('Asia/Shanghai');
		$ripeData->time = "" . date("Y-m-d H:i:s");
		Yii::trace($ripeData->attributes, 'hardware\rawdata');
		if($ripeData->save())
		{
			Yii::trace("set rawdata succeed", 'hardware\rawdata');
	        	//return json_encode(array("errcode"=>0, "errmsg"=>"set raw data succeed"));
		}else{
			Yii::trace($account->getErrors(), 'hardware\rawdata');
			//return json_encode(array("errcode"=>20602, "errmsg"=>"set raw data failed"));
		}

		//最新数据写入snapshot
		$snapshot = Snapshot::find()->where(['gprsId' => $gprsId])->one();
		if (!$snapshot)
		{
			$snapshot = new Snapshot;
			$snapshot->gprsId = $gprsId;
		}
		$snapshot->position = $ripeData->position;
		$snapshot->positionGeo9 = $ripeData->positionGeo9;
		$snapshot->positionGeo8 = $ripeData->positionGeo8;
		$snapshot->positionGeo7 = $ripeData->positionGeo7;
		$snapshot->positionGeo6 = $ripeData->positionGeo6;
		$snapshot->positionGeo5 = $ripeData->positionGeo5;
		$snapshot->motionIndex = $motionIndex;
		$snapshot->battery = $battery;
		$snapshot->cliaddr = $cliaddr;
		$snapshot->time = "" . date("Y-m-d H:i:s");
		if(!$snapshot->save())
		{
			Yii::trace($snapshot->getErrors(), 'hardware\rawdata');
			Yii::trace("save snapshot failed", 'hardware\rawdata');
		}

		//统计每日消耗量
		$motion = Motion::find()->where(['gprsId' => $gprsId, 'day' => "" . date("Y-m-d")])->one();
		if ($motion)
		{
			$motion->motionIndex += $motionIndex;
		}else{
			$motion = new Motion;
			$motion->gprsId = $gprsId;
			$motion->motionIndex = $motionIndex;
			$motion->day = "" . date("Y-m-d");
		}
		if(!$motion->save())
		{
			Yii::trace($motion->getErrors(), 'hardware\rawdata');
			Yii::trace("save everyday motion failed", 'hardware\rawdata');
		}

		//实时消耗量和口粮
		$consumption = Consumption::find()->where(['gprsId' => $gprsId])->one();
		if (!$consumption)
		{
			$consumption = new Consumption;
			$consumption->gprsId = $gprsId;
		}
		$consumption->consumption = $motionIndex * 0.8;
		$consumption->ration = $motionIndex * 0.2;
		if(!$consumption->save())
		{
			Yii::trace($consumption->getErrors(), 'hardware\rawdata');
			Yii::trace("save real consumption failed", 'hardware\rawdata');
		}
	}

	public function actionOperate()
	{
		$account = new Account;
		$account->setScenario('verify');
		$post = $_POST ? $_POST : json_decode(Yii::$app->request->getRawBody(), true);
		Yii::trace($post, 'hardware\operation');
		$account->attributes = $post;
		$account->skey = $post["skey"];
		Yii::trace($account->attributes, 'hardware\operation');
		
		$opcode = $post["opcode"];
		//验证skey
		if($account->validate())
		{
			switch($opcode)
			{
				case 0:
					return $this->bindGprs($post);
				case 1:
					return $this->unbindGprs($post);
				case 2:
					return $this->gprsNearbyWhenBind($post);
				case 10:
					return $this->petPosition($post);
				case 11:
					//附近的宠物
					return $this->petsListNearby($post);
				case 12:
					//附近的宠物
					return $this->petsListNearbyOfPet($post);
				case 13:
					return $this->petOrbit($post);
				case 20:
					//设置点子围栏信息
					return $this->setFence($post);
				case 21:
					//更新电子围栏关闭状态
					return $this->setFenceStatus($post);
				case 22:
					//读取点子围栏信息
					return $this->fence($post);
				case 30:
					//日消耗量
					return $this->dayMotionIndex($post);
				case 31:
					//周消耗量
					return $this->weekMotionIndex($post);
				case 32:
					return $this->realTimeConsumption($post);
				case 40:
					//读取一些固定参数
					return $this->fixParameters($post);
				case 41:
					//查询狗粮
					return $this->queryFood($post);
				case 42:
					//修改狗粮
					//return $this->setFood($post);
				case 50:
					return $this->manageGprsRawData($post);
				default:
					//不支持的操作不回包
					break;
			}
		}else{
			Yii::trace($opcode, 'hardware\operation');
			Yii::trace("adf", 'hardware\operation');
			if(50 == $opcode)
			{
				return $this->manageGprsRawData($post);
			}
		}
	}
}

