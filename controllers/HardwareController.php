<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
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
use app\models\Sos;
use app\models\Seqno;

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
			//更新之前绑定设备宠物主人的助养标志
			self::updateUserSponsor($pet->ownerId);
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
			//更新绑定设备用户的助养标志，必然是打开助养，没必要做检查
			$material = Material::find()->where(['id' => $userId])->one();
			$material->sponsor = 1;
			$material->save();
			//self::updateUserSponsor($userId);

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
			//更新解除绑定设备用户的助养标志
			self::updateUserSponsor($userId);

			Yii::trace("unbind gprs succeed", 'hardware\unbindGprs');
	        return json_encode(array("errcode"=>0, "errmsg"=>"unbind gprs succeed", "gprsId"=>$hardware->gprsId));
		}else{
			Yii::trace($account->getErrors(), 'hardware\unbindGprs');
			return json_encode(array("errcode"=>20602, "errmsg"=>"call save failed when unbind gprs"));
		}
	}

	public function queryGprs($post)
	{
		$seqno = Seqno::find()->where(["gprsId" => $post["gprsId"]])->one();
		if($seqno && $seqno->enable)
		{
			//有效硬件序列号
			return json_encode(array("errcode"=>0, "valid"=>1));
		}
		//无效
		return json_encode(array("errcode"=>0, "valid"=>0));
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
		$position = Snapshot::find()->where(['gprsId'=>$pet["gprsId"], 'closed'=>0])->select('position, time, battery')->asArray()->one();
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
		//seq = 0表示0～2点 seq=1表示2～4点
		$sql = 'select position, (timestampdiff(hour, curdate(), time) div 2) as seq from hardware where gprsId = ' . $pet["gprsId"] .' and time > curdate()';
		//$sql = select position, (timestampdiff(hour, curdate(), time) div 2) as seq from hardware where gprsId = 860719120000038 and time > curdate();
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
		//时间方法
		//$sql = "select motionIndex, (timestampdiff(minute, curdate(), time) div 20) as seq from hardware where gprsId = " . $gprsId ." and time > curdate()";
		//序号方法
		$sql = "select motionIndex, seq - 1 as seq from hardware where gprsId = " . $gprsId ." and time > curdate() and seq > 0 limit 72";
		$motion = Hardware::findBySql($sql)->asArray()->all();
		//$motion = Hardware::find()->where(["gprsId" => $gprsId])->select('motionIndex, seq')->asArray()->all();
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
               /* 
                //配置 图片文件 在服务器的目录
                $ip = "http://182.254.159.219/";
                $img_path = "basic/data/petfood/";
                $url =$ip.$img_path;
				*/
                
		$foods = array();
		foreach($cookFoods as $key=>$value)
		{
                 /*       foreach ($value as $key2 => $value2) {
                            if(!empty($value2['pic'])){
                                $value[$key2]['pic'] = $url.$value2['pic'].".png";
                            }
                        }*/
			array_push($foods, array("section"=>$key, "foods"=>$value));
		}
		Yii::trace($foods, 'pet\queryFoods');
	    return json_encode(array("errcode"=>0, "errmsg"=>"query pet foods succeed", "foods"=>$foods));

		$para = PetFood::find()->asArray()->one();

		return json_encode(array("errcode"=>0, "parameters"=>$para));
	}

	//每天可以发3次求救，发送求救间隔时间为1小时
	public function querySOS($post)
	{
		$sos = Sos::findOne(['petId' => $post["petId"]]);
		if($sos)
		{
			Yii::trace($sos->attributes, 'hardware\operation');

			$now = date("Y-m-d");
			$lastTime = substr($sos->lastTime, 0, 10);

			if($now == $lastTime)
			{
				$lastTime = new \DateTime($sos->lastTime);
				$now = new \DateTime();
				$before1Hour = $now->sub(new \DateInterval('PT1H'));

				$times = $sos->times;
				Yii::trace($times, 'hardware\operation');
				Yii::trace($lastTime, 'hardware\operation');
				Yii::trace($before1Hour, 'hardware\operation');
				if(($times > 2) || ($lastTime > $before1Hour))
				{
					//不可发送SOS
					return json_encode(array("errcode"=>0, "enable"=>0));
				}
			}
		}

		//可以发送SOS
		return json_encode(array("errcode"=>0, "enable"=>1));
	}

	public function setSOS($post)
	{
		$sos = Sos::findOne(['petId' => $post["petId"]]);
		if(null == $sos)
		{
			$sos = new Sos;
			$sos->petId = $post["petId"];
			$sos->times = 1;
		}else{
			//判断是否同一天
			$now = date("Y-m-d");
			$lastTime = substr($sos->lastTime, 0, 10);
			Yii::trace($now, 'hardware\operation');
			Yii::trace($lastTime, 'hardware\operation');
			$sos->times = ($now == $lastTime) ? ($sos->times + 1) : 1;
			//$sos->times += 1;
		}
		$sos->lastTime = "" . date("Y-m-d H:i:s");
		Yii::trace($sos->attributes, 'hardware\operation');

		if($sos->save())
		{
			return json_encode(array("errcode"=>0, "errmsg"=>"set sos succeed"));
		}else{
			return json_encode(array("errcode"=>20801, "errmsg"=>"set sos failed"));
		}
	}

	private function udpSendMsg($msg = '', $ip = '127.0.0.1', $port = '9527')
	{
		$fp = stream_socket_client("udp://{$ip}:{$port}", $errno, $errstr, 30);
		//$fp = stream_socket_client("tcp://www.example.com:80", $errno, $errstr, 30);
		if (!$fp) {
			Yii::trace("create udp socket failed", 'hardware\udp');
			return false;
		} 
		Yii::trace($msg, 'hardware\udp');
		if(false == fwrite($fp, $msg)) {
			Yii::trace("udp send msg failed", 'hardware\udp');
			return false;
		}
		//while (!feof($fp)) {
		//   echo fgets($fp, 1024);
		//}
		fclose($fp);
		return true;
	}

	public function manageOrder($post)
	{
		//TODO... 有效性检查，避免读 $post数据失败
		$petId = $post["petId"];
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;

		//检查用户拥有或助养该宠物
		if(false == $this->canFetchPosition($petId, $userId))
		{
			Yii::trace("manage gprs order failed, user not own this pet", 'hardware\order');
			return json_encode(array("errcode"=>20902, "errmsg"=>"fetch position failed, user not own or sponsor this pet"));
		}
		//检查宠物是否绑定硬件
		$pet = Pet::find()->where(["id" => $petId])->select('gprsId, isBindGprs')->asArray()->one();
		if(!$pet["isBindGprs"])
		{
			Yii::trace("this pet not bind gprs", 'hardware\order');
			return json_encode(array("errcode"=>20902, "errmsg"=>"pet not bind gprs"));
		}
		//下发指令，格式[GPRSID, 7, IP, PORT]
		$dst = Snapshot::find()->where(['gprsId'=>$pet["gprsId"], 'closed'=>0])->select('cliAddr')->asArray()->one();
		$dst = json_decode($dst["cliAddr"], true);
		$msg = implode(",", array($pet["gprsId"], "7", $dst["ip"], $dst["port"]));
		Yii::trace($msg, 'hardware\order');
		if($this->udpSendMsg($msg))
		{
			return json_encode(array("errcode"=>0, "errmsg"=>"send order success"));
		}else{
			return json_encode(array("errcode"=>20903, "errmsg"=>"send order failed"));
		}
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
		//date_default_timezone_set('Asia/Shanghai');
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
	/*
        //走动步数
        public function MoveIndex($post){
           $userid = $this->getActiveUserid($post['phoneNumber']);
            $gprsid = $this->userid2gprsid($userid);
            if(!empty($gprsid)){
                //开始读取当日数据
                $today_data = $this->get_today_data($gprsid);
                $today_data2 = $this->check_times($today_data);
                $MoveIndex = array();
                $MoveIndex['gprsId'] = $today_data2[0]['gprsId'];
                foreach ($today_data2 as $key => $value) {
                    $MoveIndex['Motion'][$value['time']] = $value['motionIndex'];
                }
                echo json_encode($MoveIndex);
            }else{
                echo '{"code":"1201"},"msg":"请先绑定小宝"}';
            }
        }
        //今天运动轨迹
        public function MoveTrack($post){
            $userid = $this->getActiveUserid($post['phoneNumber']);
            $gprsid = $this->userid2gprsid($userid);
            if(!empty($gprsid)){
                //开始读取当日数据
                $today_data = $this->get_today_data($gprsid);
                $today_data2 = $this->check_times($today_data);
                $snapshot_data = $this->get_snapshot_data($gprsid);
                $snapshot_data_r = $this->format_data($snapshot_data);
                //将今日快照的地理位置 合并起来。
                $today_data3 = array_merge($today_data2,$snapshot_data_r);
                $MoveTrack = array();
                $MoveTrack['gprsId'] = $today_data3[0]['gprsId'];
                $time_point_arr = array(); 
                foreach ($today_data3 as $key => $value) {
                    if($value['position'] != ""){
                        $time_point_arr[$value['time']] = $value['position'];
                    }
                }
                ksort($time_point_arr);
                $MoveTrack['Track']=$time_point_arr;
                echo json_encode($MoveTrack);
            }else{
                echo '{"code":"1201"},"msg":"请先绑定小宝"}';
            }
            
        }*/
        /*
         * 获取当前phoneNumber的ID
         *//*
        function getActiveUserid($pnumber){
            $userid_sql = "SELECT `id` FROM `account` WHERE phoneNumber='".$pnumber."'";
            $userid = account::findBySql($userid_sql)->one();
            return $userid['id'];
        }*/
        
        /*
         * 当前phoneNumber转gprsId
         */
            /*
             * 这里应该有一个默认读取哪一个宠物的gprs编号,这个gprs的默认显示他的数据指数图形。
             * 赞未被启用，因为数据库未有isDefault字段 ,此时默认取最后一个pet的gprsid
             */
			 /*
        public function userid2gprsid($userid,$petid = "default"){
            if($petid == 'default'){
                $petid = " AND isDefault = 1";
            }else{
                $petid = " AND `id` =".$petid;
            }
            
            $gprsId_sql = "SELECT `gprsId` FROM `pet` WHERE ownerId='".$userid."' ORDER BY id DESC";
            
            $gprsId = pet::findBySql($gprsId_sql)->one();
            return $gprsId['gprsId'];
        }*/
       /* 
        // 找出今天截止目前数据库中的所有数据。包括
        function get_today_data($gprsid){
            $connection = \Yii::$app->db;
            //每个设备必须被唯一的账号绑定
            //每个账号可以有多个宠物，多个硬件设备。
            $today=  date("Y-m-d")." 00:00:00";
            //$today=  "2015-08-26 00:00:00";
            $sql = "SELECT * FROM `hardware` WHERE `gprsId` = '".$gprsid."' AND `time`>'".$today."' ORDER BY time ASC";
            $command=$connection->createCommand($sql);
            $gprs_data_arr=$command->queryAll();
            return $gprs_data_arr;
        }
        
        // 读取快照定位
        public function get_snapshot_data($gprsid){
            $today=  date("Y-m-d")." 00:00:00";
            $connection = \Yii::$app->db;
            $sql = "SELECT * FROM `snapshot` WHERE `gprsId` = '".$gprsid."' AND `time`>'".$today."' ORDER BY time ASC";
            $command=$connection->createCommand($sql);
            $gprs_data_arr=$command->queryAll();
            return $gprs_data_arr;
        }
        // 检查截至目前每隔20分钟的数据是否完整，若不完整则处理
        public function check_times($today_data){
            $error_i = 0;
            $send_i = intval(date("H",time())) * 3;//当前时间的hour小时,必须是24小时制,$send_i是最终需要循环的次数，也就是当日硬件设备应该向数据库发送的数据次数
            $timeI = date("i",time());//当前时间的minute分钟
            switch ($timeI){
                case $timeI>20 && $timeI<40:
                    $send_i += 1;
                break;
                case $timeI>40:
                    $send_i += 2;
                break;
            }
            //print_r($today_data);
            $times_total = count($today_data);//数据库查询出来的发送次数
            if(($times_total < $send_i) || ($times_total > $send_i)){
                //如果收到的数据次数小于应该收到的数据次数，那么返回数据错误。
                $exception = 1;
            }else{
                $exception = 0;
            }

            $i = 0;//$i循环次数，若等于$send_i 则数据完整，没有异常。若循环缺少应该用有的时间，则自动补充，并计算补充时间点的替补数据。
            if(!isset($today_data[0]['time'])){
                exit('{"code":"1022","msg","数据库没有任何记录"}');
            }
            $f_time = substr($today_data[0]['time'], 14,2);//这个是今日第一次发送数据时候的分钟。作为分钟点
            if($f_time != ""){
                $now_YmdHis = date("Y-m-d",time())." 00:".$f_time;
            }  else {
                $now_YmdHis = date("Y-m-d",time())." 00:00";
            }
            //$now_YmdHis = "2015-08-26 00:00:00";
            $point_time = strtotime($now_YmdHis);//起点时间，不包含秒 例如：2015-08-26 07:20
            
            $key_i = $times_total-1; 
            $time_existed = array();
            foreach ($today_data as $key => $time) {
                $rs=strpos($time['time'],date("Y-m-d H:i",$point_time));
                $time_existed[]=$point_time;
                if($rs !== false){
                    $time2 = date("Y-m-d H:i",strtotime($time['time']));
                }else{
                    //出现找不当应该有的时间点时候，先试试是否在1分钟时间内有数据，如果没有则找替补时间。
                    //判断是否有相邻时间点
                    $rs2=$this->is_adjacent($time['time'],$today_data);
                    if($rs2){
                        $today_data[$key]['time'] = date("Y-m-d H:i",$point_time);
                    }
                }
                $today_data[$key]['position'] = json_decode($today_data[$key]['position'],true);
                $point_time = strtotime('+20 minute',$point_time);
            }
            
            if($exception = 1){
                //数据条数有误，则补充时间，值取nu
                $point_time2 = strtotime($now_YmdHis);
                $time_arr = array();
                for($i =0; $i<=$send_i;$i++,$point_time2 = strtotime('+20 minute',$point_time2)){
                    if(!in_array($point_time2, $time_existed)){
                        $time_arr[$i]['gprsId']=$today_data[0]['gprsId'];
                        $time_arr[$i]['position']="";
                        $time_arr[$i]['motionIndex']="";
                        $time_arr[$i]['battery']="";
                        $time_arr[$i]['time']=date("Y-m-d H:i",$point_time2);
                    }
                }
            }
            return array_merge($today_data,$time_arr);
        }
        // 去除时间秒，将经纬度转换为array
        
        public function format_data($data){
            foreach ($data as $key => $value) {
                $data[$key]['time'] = date("Y-m-d H:i",strtotime($value['time']));
                $data[$key]['position'] = json_decode($value['position']);
            }
            return $data;
        }
        // 判断在应该的时间点3分钟内是否有数据，如果有则取该值作为数据值
        public function is_adjacent($time,$today_data_arr){
            $array = array();
            foreach ($today_data_arr as $key => $val) {
                $time1 = substr($val['time'], 14,2);
                $time2 = substr($time, 14,2);
                if(abs($time1-$time2)<3){
                    $val['time'] = $time;
                    return $val;
                }
            }
        }
*/

    public function actionOperate()
	{
            
		$account = new Account;
		$account->setScenario('verify');
		$post = $_POST ? $_POST : json_decode(Yii::$app->request->getRawBody(), true);
                //$post = $_POST;
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
					//绑定小宝
					return $this->bindGprs($post);
				case 1:
					return $this->unbindGprs($post);
				case 2:
					//绑定前查找附近的小宝
					return $this->gprsNearbyWhenBind($post);
				case 3:
					//检查小宝序列号有效
					return $this->queryGprs($post);
				case 10:
					//宠物位置
					return $this->petPosition($post);
				case 11:
					//附近的宠物
					return $this->petsListNearby($post);
				case 12:
					//附近的宠物
					return $this->petsListNearbyOfPet($post);
				case 13:
					//宠物轨迹
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
				case 51:
					//处理客户端定位指令
					return $this->manageOrder($post);
				case 60:
					//当前是否可以发送SOS 
					return $this->querySOS($post);
				case 61:
					//发送SOS后更新
					return $this->setSOS($post);/*
                                case 62:
                                    return $this->MoveTrack($post);
                                case 63:
                                    return $this->MoveIndex($post);*/
				default:
					//不支持的操作不回包
					break;
			}
		}else{
			if(50 == $opcode)
			{
				return $this->manageGprsRawData($post);
			}
		}
	}
}

