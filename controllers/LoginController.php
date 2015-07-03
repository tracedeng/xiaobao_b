<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\Account;
use app\models\Material;
use Easemob\Easemob;

class LoginController extends Controller
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

	/*
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }*/

    public function login($post)
    {
	    $account = new Account;
	    $account->setScenario('login');
	    //$post = json_decode(Yii::$app->request->getRawBody(), true);
	    Yii::trace($post, 'login\login');
	    $account->attributes = $post;
	    Yii::trace($account->attributes, 'login\login');

	    if($account->validate())
	    {
		    $skey = $account->generateSkey();
		    //$account->checkSkey($skey);
		    Yii::trace('skey=' . $skey, 'login\login');
		    //获取用户资料
		    $material = Material::find()->where(['phoneNumber'=>$account->phoneNumber])->asArray()->one();
		    Yii::trace($material, 'login\login');
		    return json_encode(array("errcode"=>0, "errmsg"=>"login success", "skey"=>$skey, "user"=>$account->phoneNumber, "material"=>$material));
	    }else{
	    	Yii::trace($account->getErrors(), 'login\login');
		    return json_encode(array("errcode"=>10201, "errmsg"=>"login failed"));
	    }
    }

    public function verifyVCode($post)
	{
		$account = new Account;
		//$account->setScenario('verifyVCode');
		//$post = json_decode(Yii::$app->request->getRawBody(), true);
		Yii::trace($post, 'login\verifyVCode');
		$account->attributes = $post;
		Yii::trace($account->attributes, 'login\verifyVCode');

		if($account->validate())
		{
		    	//verify code match phone number?
		    if($account->isValidVerifyCode($post["verifyCode"]))
			{
		    		return json_encode(array("errcode"=>0, "errmsg"=>"verify VCode success", "user"=>$account->phoneNumber));
			}else{
		    		return json_encode(array("errcode"=>10302, "errmsg"=>"illegal VCode, maybe expired"));
			}
		}else{
			Yii::trace($account->getErrors(), 'login\verifyVCode');
			return json_encode(array("errcode"=>10301, "errmsg"=>"verify VCode failed"));
		}
	}

    public function register($post)
    {
	    $account = new Account;
	    $account->setScenario('register');
	    //$post = json_decode(Yii::$app->request->getRawBody(), true);
	    Yii::trace($post, 'login\register');
	    $account->attributes = $post;
	    Yii::trace($account->attributes, 'login\register');

	    if($account->validate())
	    {
		    //just valid nickname, add others at future
		    $material = new Material;
		    $material->setScenario('register');
		    $material->attributes = $post;
		    //$material->nickname = $post["nickname"];
		    //TODO... truncate nickname length
	    	Yii::trace($material->attributes, 'login\register');

	    	if(!$material->validate())
		    {
		    	return json_encode(array("errcode"=>10401, "errmsg"=>"invalid nickname"));
		    }

		    //verify code match phone number?
		    //if($account->isValidVerifyCode($post["verifyCode"]))
		    //{
			//TODO...  can do more safe
			$account->save();
			$material->id = $account->id;
			$material->phoneNumber = $account->phoneNumber;

			$easemob = new Easemob;
			$result = $easemob->accreditRegister(array('username'=>$account->phoneNumber, 'password'=>$account->passwordMd5, 'nickname'=>$material->nickname));
			//if($result != false)
			if(array_key_exists("error", $result))
			{
				//环信注册成功，另起一个进程扫描环信注册失败的用户重新注册环信
				$material->easemob = 0;
			}else{
				$material->easemob = 1;
			}
			$material->save();

			Yii::trace($result, 'login\register');
		    return json_encode(array("errcode"=>0, "errmsg"=>"register success", "user"=>$account->phoneNumber));
		    //}else{
		    	//return json_encode(array("errcode"=>10104, "errmsg"=>"verify code not match phone number"));
		    //}
	    }else{
	    	//Yii::trace($account->getErrors(), 'login\register');
		    return json_encode(array("errcode"=>10402, "errmsg"=>"register failed"));
	    }
    }
	/*
    public function getRadomStr($len){
	    $str = '0123456789';
	    return substr(str_shuffle($str),0,$len);
    }
	*/
	public function modifyPassword($post)
	{
		$account = Account::find()->where(['phoneNumber' => $post["phoneNumber"]])->one();
		$oldPassword = $account->passwordMd5;
		
		Yii::trace($post, 'login\modifyPassword');
		$account->password = $post["password"];
		$account->passwordMd5 = $post["passwordMd5"];
		Yii::trace($account->attributes, 'login\modifyPassword');
		
		if($account->save())
		{
			//更新环信密码
			$easemob = new Easemob;
			$result = $easemob->editPassword(array('username'=>$account->phoneNumber, 'password'=>$oldPassword, 'newpassword'=>$account->passwordMd5));
			if($result == false)
			{
				//修改环信密码失败，另起一个进程扫描修改环信密码失败的用户重新修改环信密码
				$material = Material::find()->where(['phoneNumber'=>$account->phoneNumber])->one();
				$material->easemobPassword = 1;
				$material->save();
			}
			Yii::trace($result, 'login\modifyPassword');
			//$account->save()
			return json_encode(array("errcode"=>0, "errmsg"=>"modify password success", "user"=>$account->phoneNumber));
		}else{
			return json_encode(array("errcode"=>10501, "errmsg"=>"modify password failed"));
		}
	}

    public function fetchVCode($post)
    {
	    $account = new Account;
	    //注册和修改密码时验证方式不相同
	    $post["isRegister"] ? $account->setScenario('fetchVCode') : $account->setScenario('fetchVCodeWhenModPwd');
	    //$account->attributes = json_decode(Yii::$app->request->getRawBody(), true);
	    //Yii::trace($account->attributes, 'login\getVerifyCode');
	    Yii::trace($post, 'login\getVerifyCode');
	    $account->attributes = $post;
	    Yii::trace($account->attributes, 'login\getVerifyCode');

	    if($account->validate())
	    {
		    //send verify code request to third party
		    //step1: get radom verify code length = 4
		    $verifyCode = $account->generateVerifyCode();
	    	Yii::trace("verifyCode=" . $verifyCode, 'login\getVerifyCode');
		    //step2: cache verifyCode, 60 second expire
		    $account->cacheVerifyCode($verifyCode, 6000);
		    //TODO... step3: send veirfy code according third party api

		    //return verify code if debug
		    if(YII_ENV_DEV)
		    {
		    	return json_encode(array("errcode"=>0, "errmsg"=>"get vierfy code success", "verifyCode"=>$verifyCode));
		    }

		    //valid phone number
		    return json_encode(array("errcode"=>0, "errmsg"=>"get vierfy code success"));
	    }else{
		    //no phone number field or already registered phone number
	    	    Yii::trace($account->getErrors(), 'login\getVerifyCode');
		    if($account->getErrors('duplicate'))
		    {
		    	return json_encode(array("errcode"=>10101, "errmsg"=>"already registered phone number"));
		    }
		    return json_encode(array("errcode"=>10102, "errmsg"=>"illegal phone number"));
	    }
    }

	public function actionOperate()
	{
		$post = $_POST ? $_POST : json_decode(Yii::$app->request->getRawBody(), true);
		$opcode = $post["opcode"];
		switch($opcode)
		{
			case 0:
				return $this->login($post);
			case 1:
				return $this->fetchVCode($post);
			case 2:
				return $this->verifyVCode($post);
			case 3:
				return $this->register($post);
			case 4:
				return $this->modifyPassword($post);
			default:
				//不支持的操作不回包
				break;
		}
	}
}

