<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Material;
use Easemob\Easemob;

class EasemobController extends Controller
{
	//将已上线的APP的现有用户集成到环信
	public function actionRegister()
	{
		$users = Material::find()->where(['easemob'=>0])->limit(60)->with('account')->asArray()->all();

		//Yii::trace($users, 'easemob\register');
	    	foreach($users as &$user)
	    	{
			$user = array('username'=>$user['phoneNumber'], 'password'=>$user['account']['passwordMd5'], 'nickname'=>$user['nickname']);
		}
		Yii::trace($users, 'easemob\register');
		//Yii::trace(count($users), 'easemob\register');

		$easemob = new Easemob;
		$result = $easemob->accreditRegister($users);

		return var_export($result);
	}

	public function actionCheckOnline($user)
	{
		if(empty($user)) return "lost user";

		$easemob = new Easemob;
		$result = $easemob->isOnline($user);

		return var_export($result);
	}
}

?>
