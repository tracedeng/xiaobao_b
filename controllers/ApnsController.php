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
use app\models\Pushmsg;

class ApnsController extends Controller
{
	public function actionOperate()
	{
		$account = new Account;
		$account->setScenario('verify');
		$post = $_POST ? $_POST : json_decode(Yii::$app->request->getRawBody(), true);
		Yii::trace($post, 'apns\operation');
		$account->attributes = $post;
		$account->skey = isset($post["skey"]) ? $post["skey"] : "";
		Yii::trace($account->attributes, 'apns\operation');
		
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
				case 20:
					//读取助养推送消息列表
					return $this->querySponsorPushMessage($post);
				case 21:
					//处理了助养消息后删除消息
					return $this->deleteSponsorPushMessage($post);
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
			Yii::trace($account->getErrors(), 'apns\operation');
			return json_encode(array("errcode"=>$errcode, "errmsg"=>"invalid skey"));
		}
 	}

	public function updateDeviceToken($post)
	{
		$userId = Account::findOne(['phoneNumber' => $post["phoneNumber"]])->id;
		$apns = Apns::find()->where(['phoneNumber' => $post["phoneNumber"]])->one();
		if(!$apns)
		{
			$apns= new Apns;
		}
		$apns->token = $post["token"];
		$apns->phoneNumber = $post["phoneNumber"];
		$apns->ownerId = $userId;
		$apns->time = "" . date("Y-m-d H:i:s");
		Yii::trace($apns->attributes, 'apns\updatetoken');

		if($apns->save())
		{
			Yii::trace("add device token succeed", 'apns\updatetoken');
			return json_encode(array("errcode"=>0, "errmsg"=>"add device token succeed"));
		}else{
			Yii::trace($apns->getErrors(), 'apns\updatetoken');
			return json_encode(array("errcode"=>20602, "errmsg"=>"add device token failed"));
		}
	}

	public function queryApnsBadge($post)
	{
		$apns = Apns::find()->where(['phoneNumber' => $post["phoneNumber"]])->one();

		$badge = 0;
		if($apns)
		{
			$badge = $apns->badge;
		}
		Yii::trace("badge = " . $badge, 'apns\querybadge');

		return json_encode(array("errcode"=>0, "badge"=>$badge));
	}

	public function querySponsorPushMessage($post)
	{
		$messages = Pushmsg::find()->select('id, from, message, addOn as petId, time')->where(['phoneNumber' => $post["phoneNumber"], 'deleted' => 0])->asArray()->all();
		Yii::trace($messages, 'apns\querySponsor');

		return json_encode(array("errcode"=>0, "message"=>$messages));
	}

	public function deleteSponsorPushMessage($post)
	{
		$message = Pushmsg::find()->where(['id' => $post["messageId"], 'deleted' => 0])->one();

		if(!$message)
		{
			Yii::trace("sponsor message not exist", 'apns\deleteSponsor');
			return json_encode(array("errcode"=>20601, "errmsg"=>"sponsor message not exist"));
		}

		$message->deleted = 1;
		if(!$message->save())
		{
			Yii::trace("delete sponsor message failed", 'apns\deleteSponsor');
			return json_encode(array("errcode"=>20602, "errmsg"=>"failed to delete sponsor message"));
		}

		return json_encode(array("errcode"=>0, "errmsg"=>"delete sponsor message successed"));
	}

	public function updateApnsBadge($post)
	{
		$apns = Apns::find()->where(['phoneNumber' => $post["phoneNumber"]])->one();

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

	public function pushMessage($post)
	{
		//$apns = Apns::find()->where(["ownerId"=>$post["to"]])->one();
		$apns = Apns::find()->where(["phoneNumber"=>$post["to"]])->one();
		if(!$apns)
		{
			Yii::trace("no apns to destination", 'apns\pushmessage');
			return json_encode(array("errcode"=>20601, "errmsg"=>"push message failed, no destination"));
		}

		$deviceToken = $apns->token;
		$type = $post["type"];
		$message = $post["message"];
		//$message = "This is a message from xiaobao";
		$body['aps'] = array('badge' => $apns->badge + 1, 'alert' => $message, 'sound' => 'default');
		Yii::trace($body, 'apns\pushmessage');
		$payload = json_encode($body);
		Yii::trace($payload, 'apns\pushmessage');

		if(0 == $type)
		{
			//助养消息，需要保存到列表中
			$pushmsg = new Pushmsg;
			$pushmsg->phoneNumber = $post["to"];
			$pushmsg->from = $post["phoneNumber"];
			$pushmsg->message = $message;
			$pushmsg->time = "" . date("Y-m-d H:i:s");
			$pushmsg->addOn = $post["addOn"];
			Yii::trace($pushmsg, 'apns\pushmessage');
			if(!$pushmsg->save())
			{
				//保存推送消息失败，认为整个推送失败
				Yii::trace("failed to save push message", 'apns\pushmessage');
				return json_encode(array("errcode"=>20601, "errmsg"=>"push to apns server failed"));
			}
		}
		
		//$deviceToken = '<41326e4f 90b8aa1e a0ea5c0d c75a2745 09cfc56d 146b019c b44ece86 8702cf9a>';
		//$deviceToken = '<41326e4f 90b8aa1e a0ea5c0d c75a2745 09cfc56d146b019cb44ece868702cf9a';
		// 推送消息编码成二进制
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

		$passphrase = "Qiuwei2015";
		//$passphrase = "123456";
		$ckpem = "/media/basic/controllers/ck.pem";

		// 连接apns服务器
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', $ckpem);
		//stream_context_set_option($ctx, 'ssl', 'local_cert', 'ck.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		if (!$fp)
		{
			Yii::trace("failed to connect to apns server", 'apns\pushmessage');
			return json_encode(array("errcode"=>20601, "errmsg"=>"failed to connect to apns server"));
			//exit("Failed to connect: $err $errstr" . PHP_EOL);
		}
		
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
}

