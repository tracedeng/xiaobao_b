<?php

/*
 * 消息推送服务器
 */

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Account;
use app\models\Material;
use app\models\Apns;

class ApnsController extends Controller
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
				case 0:
					//更新用户DeviceToken
					return $this->updateDeviceToken($post);
				case 1:
					//推送消息条数变更
					return $this->updateApnsBadge($post);
				case 2:
					//读取推送消息条数
					return $this->queryApnsBadge($post);
				case 10:
					//消息推送
					return $this->pushMessage($post);
				default:
					//不支持的操作，不回包
					break;
			}
		}else{
			switch($opcode)
			{
				case 0:
					//更新用户DeviceToken，暂时允许不带登录态
					return $this->updateDeviceToken($post);
				case 1:
					$errcode = 20301;
					break;
				case 10:
					$errcode = 20310;
					break;
				default:
					//不支持的操作
					return;
			}
			Yii::trace($account->getErrors(), 'relation\operation');
			return json_encode(array("errcode"=>$errcode, "errmsg"=>"invalid skey"));
		}
 	}

	public function updateDeviceToken($post)
	{
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		$apns = Apns::find()->where(['phoneNumber' => $phoneNumber])->one();
		if(!$apns)
		{
			$apns= new Apns;
		}
		$apns->token = $post["token"];
		$apns->phoneNumber = $post["phoneNumber"];
		$apns->ownerId = $post["ownerId"];
		$apns->time = "" . date("Y-m-d H:i:s");
		Yii::trace($apns->attributes, 'apns\updatetoken');

		if($apns->save())
		{
			Yii::trace("add device token succeed", 'apns\updatetoken');
			return json_encode(array("errcode"=>0, "errmsg"=>"add device token succeed"));
		}else{
			Yii::trace($relation->getErrors(), 'apns\updatetoken');
			return json_encode(array("errcode"=>20602, "errmsg"=>"add device token failed"));
		}
	}

	public function pushMessage($post)
	{
		$apns = Apns::find()->where(["ownerId"=>$post["to"]])->one();
		if(!apns)
		{
			Yii::trace("no apns to destination", 'apns\pushmessage');
			return json_encode(array("errcode"=>20601, "errmsg"=>"push message failed, no destination"));
		}

		$deviceToken = $apns->token;
		//$deviceToken = '41326e4f90b8aa1ea0ea5c0dc75a274509cfc56d146b019cb44ece868702cf9a';
		$passphrase = "123456";
		$ckpem = "ck.pem";

		// 连接apns服务器
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', 'ck.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		if (!$fp)
		{
			Yii::trace("failed to connect to apns server", 'apns\pushmessage');
			return json_encode(array("errcode"=>20601, "errmsg"=>"failed to connect to apns server"));
			//exit("Failed to connect: $err $errstr" . PHP_EOL);
		}

		$type = $post["type"];
		$message = $post["msg"];
		//$message = "This is a message from xiaobao";
		$body['aps'] = array('badge' => $apns->badge + 1, 'alert' => message, 'sound' => 'default');
		$payload = json_encode($body);
		
		// 推送消息编码成二进制
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		
		// 发送给apns服务器
		$result = fwrite($fp, $msg, strlen($msg));
		fclose($fp);
		if (!$result)
		{
			Yii::trace("deliver message to apns failed", 'apns\pushmessage');
			return json_encode(array("errcode"=>20601, "errmsg"=>"failed to deliver to apns server"));
		}

		$apns->badge = $apns->badge + 1;
		if(!$apns->save())
		{
			Yii::trace("update badge failed failed", 'apns\pushmessage');
		}

		return json_encode(array("errcode"=>0, "errmsg"=>"deliver to apns server successed"));
	}

	public function queryApnsBadge($post)
	{
		$apns = Apns::find()->where(['phoneNumber' => $phoneNumber])->one();

		$badge = 0;
		if($apns)
		{
			$badge = $apns->badge;
		}
		Yii::trace("badge = " . $badge, 'apns\query apns badge');

		return json_encode(array("errcode"=>0, "badge"=>$badge));
	}

	public function updateApnsBadge($post)
	{
		$apns = Apns::find()->where(['phoneNumber' => $phoneNumber])->one();

		$badge = -1;
		if($apns)
		{
			//减去已读的推送消息
			$badge = $apns->badge - $post["badge"];
		}

		if($badge < 0)
		{
			Yii::trace("update apns badge failed", 'apns\updatebadge');
			return json_encode(array("errcode"=>20601, "errmsg"=>"failed to update badge"));
		}

		$apns->badge = $badge;
		//存储数据库失败
		if(!$apns->save())
		{
			Yii::trace("update badge failed", 'apns\updatbadge');
		}

		return json_encode(array("errcode"=>0, "badge"=>$badge));
	}
}

