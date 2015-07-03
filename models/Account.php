<?php

/*
 *用户账号信息
 */
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Account extends ActiveRecord
{
	public $skey;
	public function checkMobile($phoneNumber)
	{
		$MOBILE = "/^1(3[0-9]|5[0-35-9]|8[025-9])\\d{8}$/";
		$CM = "/^1(34[0-8]|(3[5-9]|5[017-9]|8[278])\\d)\\d{7}$/";
		$CU = "/^1(3[0-2]|5[256]|8[56])\\d{8}$/";
		$CT = "/^1((33|53|8[09])[0-9]|349)\\d{7}$/";

		if(preg_match($MOBILE, $phoneNumber) ||
			preg_match($CM, $phoneNumber) ||
			preg_match($CU, $phoneNumber) ||
			preg_match($CT, $phoneNumber))
		{
			return true;
		}else{
			return false;
		}
	}

	//already registered mobile phone number?
	public function registeredPhoneNumber($phoneNumber)
	{
		$count = $this->find()->where(['phoneNumber' => $phoneNumber])->count();
		if($count)
		{
			return true;
		}
		return false;
	}

	//检查电话号码有效性，不可重复
	public function isValidPhoneNumber($attribute)
	{
		$phoneNumber = $this->$attribute;
		if(!$this->checkMobile($phoneNumber))
		{
			//Yii::trace('invalid mobile phone number', 'login\invalid-mobile-phone');
			$this->addError($attribute, "invalid mobile phone number");
		}else{
			if($this->registeredPhoneNumber($phoneNumber))
			{
				//$this->addError($attribute, "already registered mobile phone number");
				$this->addError("duplicate", "already registered mobile phone number");
			}
		}
	}
	
	//生成4位随机验证码
	public function generateVerifyCode()
	{
		$str = '0123456789';
		return substr(str_shuffle($str), 0, 4);
	}

	public function cacheVerifyCode($verifyCode, $expireSecond)
	{
		Yii::$app->cache->set($this->phoneNumber, $verifyCode, $expireSecond);
	}

	public function isValidVerifyCode($verifyCode)
	{
		$value = Yii::$app->cache->get($this->phoneNumber);
		if($verifyCode == $value)
		{
			return true;
		}
		return false;
	}

	//check passwordMd5=md5(md5(md5(password)))
	public function registerAuth($attribute)
	{
		if($this->passwordMd5 != md5(md5(md5($this->password))))
		{
			$this->addError('checkmd5', "invalid password");
		}
	}

	public function verifySkey($attribute)
	{
		if(!$this->checkSkey($this->skey))
		{
			$this->addError('verifySkey', 'invalid skey');
		}
	}

	public function rules()
	{
		return [
			['phoneNumber', 'required'],
			[['password', 'passwordMd5'], 'required', 'on' => ['register', 'modifyPassword']],
			['passwordMd5', 'required', 'on' => 'login'],

			['phoneNumber', 'isValidPhoneNumber', 'on' => ['fetchVCode', 'register']],
			//['phoneNumber', 'isValidPhoneNumber', 'on' => ['fetchVCode', 'verifyVCode', 'register']],
			['phoneNumber', 'exist', 'on' => ['verify', 'modifyPassword', 'fetchVCodeWhenModPwd']],
			[['password', 'passwordMd5'], 'registerAuth', 'on' => ['register', 'modifyPassword']],
			['passwordMd5', 'exist', 'targetAttribute' => ['phoneNumber', 'passwordMd5'], 'on' => 'login'],
			['phoneNumber', 'verifySkey', 'on' => 'verify'],
		];
	}

	//skey = base64_encode(tea(timestamp+phonenumber))
	//10位时间 ＋ 账号
	public function generateSkey()
	{
		$td = mcrypt_module_open('xtea', '', 'ecb', '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, "jJe8f6I9", $iv);
		$plain = sprintf("%010u%s", time(), $this->phoneNumber);
		$skey = mcrypt_generic($td, $plain);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return base64_encode($skey);
	}

	public function checkSkey($skey)
	{
		$td = mcrypt_module_open('xtea', '', 'ecb', '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, "jJe8f6I9", $iv);
		$plain = mdecrypt_generic($td, base64_decode($skey));
		$phoneNumber = substr($plain, 10);
		//Yii::trace($phoneNumber, 'login\skey');
		//Yii::trace($this->phoneNumber, 'login\skey');
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		//这个相同字符串比较死活不一致，奔溃了，求解脱
		return ($phoneNumber + 0 == $this->phoneNumber + 0);
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
		return 'account';
	}
}

?>
