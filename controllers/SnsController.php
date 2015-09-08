<?php

/*
 * 朋友圈新增
 * 先制作功能 后做用户权限设置
 */
namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Account;
use app\models\SnsAdd;
use yii\web\UploadedFile;

class SnsController extends Controller{
    
    public function actionAdd() {
        header('Content-type:text/html;charset=utf-8');
        $this->is_logined();
        if($_POST != NULL){
            $json = $_POST['imgdata'];
            file_put_contents("imgdata_array.txt",$_POST['imgdata']);
            $add_data=json_decode($json,true); 
            $img_num=count($add_data['imgdata'][0]);
            $ymd =date("Ymd",time())."/";
            $path = "../data/circle/".$ymd;
            if($img_num>0 && $img_num <11){
                $result2 = $this->imgdata_save($add_data['imgdata'][0],$path,$img_num,$ymd);
                if($result2 != false){
                    $pic_url_set = implode("|",$result2);
                }
            }
            if(!isset($pic_url_set)){
                $pic_url_set = "";
            }
            
            $connection = \Yii::$app->db;
            $text_arr= $this->text_sql($add_data);
            $userid_sql = "SELECT `id` FROM `account` WHERE phoneNumber='".$add_data["phoneNumber"]."'";
            $command=$connection->createCommand($userid_sql);
            $userid=$command->queryOne();
            if($userid){
                $user_id=$userid['id'];
            };
            $sql = "INSERT INTO `circle` (`ownerId`, `type`,`releaseTime` , `detailText`, `detailImagesPath`,`detailImagesCount` , `location`) VALUES ('".$user_id."', '".$text_arr['type']."', '".date("Y-m-d h:i:s")."', '".$text_arr['detailText']."','".$pic_url_set."',".$img_num.",'".$text_arr['location']."') ";
            $command=$connection->createCommand($sql);
                   if($command->execute()){
                       echo '{"code":"10000","msg":"success"}';
            };
        }else{
            echo '{"code":"10001","msg":"error"}';
        }
        
    }
    
    public function actionRead($p=1,$who="all",$pnumber,$thenumber=0){
        $this->is_logined();
        $connection = \Yii::$app->db;
        //配置 图片文件 在服务器的目录
        $ip = "http://182.254.159.219/";
        $img_path = "basic/data/circle/";
        $url =$ip.$img_path;
        
        //获取总条数
        $total_sql = "SELECT count(*) AS total FROM `circle`";
        $command=$connection->createCommand($total_sql);
        $post_all_num = $command->queryOne()['total'];
        
        $row_per_page = 15;
        
        //计算共多少页
        
        $page_num = ceil($post_all_num/$row_per_page);
        
        //如果请求的页数大于 总页数，则返回没有更多信息了。
        if($p > $page_num){
            echo '{"code":"1003","no more posts"}';
            exit();
        }
        if($post_all_num == 0){
            echo '{"code":"1004","empty"}';
            exit();
        }
        
        //如果是第一页则从0开始
        if($p == 1){
            $start_row = 0;
        }  else {
            $start_row = ($p-1)*$row_per_page;
        }
        
        
        /*
         * 根据user_id判断要读取的范围;先通过phonenumber查询到user_id,通过user_id查询自己的好友
         */
        
        //查询自己的user_id
        $userid=  $this->getUserIdByPNubmer($pnumber);
        if($userid){
            $myself_id=$userid['id'];
        };
        
        /*
         * 查询好友的user_id,由于关系表relation设计太差了，关系型数据库竟然这样设计数据库
         */
        $friend_result= $this->myfriend($pnumber);
        //print_r($friend_result);exit();
        if($friend_result){//如果为true，有好友。
            $myfriend_arr = array();//所有好友中每个人的资料 打包的数组；
            $myfriend_number = array();//所有好友的手机号码，格式化后打包成数组，用于查询好友的帖子。
            foreach ($friend_result as $key => $value) {
                $myfriend_arr[] = $this->getFriendBypnumber($value['number']);
                $myfriend_number[]="`ownerId` = ".$this->getUserIdByPNubmer($value['number'])['id']."";
            }
        }else {
            echo '{"code":"1005","it is empty!"}';
        };
        
        
        if($who == "all"){
            $where = " WHERE `deleted`=0";
        }
        if($who == "myfriend"){
            if($friend_result){
               $where = " WHERE `deleted`=0 AND ".implode(" OR ", $myfriend_number); 
            }
        }
        if($who == "the"){
            if($friend_result){
                if($thenumber !=0){
                    //查询此人的ID
                    $the_userid=  $this->getUserIdByPNubmer($thenumber);
                    $where = " WHERE `deleted`=0 AND `ownerId` = ".$the_userid['id'];
                }  else {
                    $msg=array("code"=>"10020","msg"=>"如果是读取指定的手机号码的帖子，第4个参数必须填写！");
                    echo json_encode($msg);exit();
                }
                
            }
        }
        if($who == "myself"){
            $where = " WHERE `deleted`=0 AND `ownerId` = ".$myself_id;
        }
        if($who == "recycle"){
            $where = " WHERE `deleted`=1 AND `ownerId` = ".$myself_id;
        }
        $sql = "SELECT `id`, `ownerId`, `type`, `releaseTime`, `detailText`, `detailImagesPath`, `detailImagesCount`, `deleted`, `thumb`, `thumbOwnerIds`, `commentCount`, `commentDeleteCount`, `location` FROM `circle`".$where." order by releaseTime DESC LIMIT ".$start_row.", ".$row_per_page;
        $command=$connection->createCommand($sql);
        $post_all = $command->queryAll();
        foreach ($post_all as $key => $value) {
            if(!empty($value['detailImagesPath'])){
                $img_path_arr=explode("|",$value['detailImagesPath']);
                //定义带有url的图片数组
                $img_arr = array();
                $thumb_img_arr =array();
                foreach ($img_path_arr as $key2 => $value2) {
                    $img_arr["pic".$key2] = $url.$value2; 
                    $thumb_path = substr($value2,0,strpos($value2,"/" ));
                    $thumb_pic = substr($value2,strpos($value2,"/" ));
                    //print_r($value2);
                    $thumb_img_arr["thumb".$key2] = $url.$thumb_path."/thumb".$thumb_pic;
                }
            }  else {
                $img_arr = "";
                $thumb_img_arr = "";
            }
            
            //读取thumbOwnerIds字段的值，转换成数组。
            $own_id_str = str_replace("[", "", $value['thumbOwnerIds']);
            $own_id_str = str_replace("]", "", $own_id_str);
            $own_id_arr = explode(",",$own_id_str);
            if(empty($value['thumbOwnerIds']) || $value['thumbOwnerIds'] == "[]" || !in_array($myself_id, $own_id_arr)){
                $is_like="0";
            }  else {
                $is_like="1";
            }
            $post_all[$key]['islike']=$is_like;
            $post_all[$key]['releaseTime']=strtotime($post_all[$key]['releaseTime']);
            $post_all[$key]['detailImagesPath']=$img_arr;
            $post_all[$key]['thumbpic']=$thumb_img_arr;
            
            //按照循环出来的ownerId查询phonenumber，然后用它查出此条帖子的用户资料。

            $pnumber_arr = $this->getPNubmerIdByUser($value['ownerId']);
            if($pnumber_arr){
                $post_all[$key]['profile'] = $this->getFriendBypnumber($pnumber_arr['phoneNumber'], "*");
            }else{
                $post_all[$key]['profile'] = "";
            }
        }
        echo json_encode($post_all);
    }

    function actionLike($id,$pnumber){
        $this->is_logined();
        $connection = \Yii::$app->db;
        //判断是否已经点赞
        $sql = "SELECT `thumbOwnerIds` FROM `circle` WHERE id = ".$id;
        $command=$connection->createCommand($sql);
        $like_id=$command->queryOne();
        if(!empty($like_id['thumbOwnerIds'])){
            
            $user_id=$this->getUserIdByPNubmer($pnumber)['id'];
            //判断登陆user_id是否在里面
            $like_user_id_str = str_replace("[", "", $like_id['thumbOwnerIds']);
            $like_user_id_str = str_replace("]", "", $like_user_id_str);
            $like_user_id_arr = explode(",", $like_user_id_str);
            if(in_array($user_id, $like_user_id_arr)){
                $this->Like(-1,$id);
                //取消赞时，同时从已赞用户组字段中删除自己的user_id,下次再点就+赞
                foreach ($like_user_id_arr as $key => $value) {
                    if($value == $user_id){
                        unset($like_user_id_arr[$key]);
                    }
                }
                $like_user_id_str2 = "[".implode(",", $like_user_id_arr)."]";
                $sql ="UPDATE `circle` SET `thumbOwnerIds`='".$like_user_id_str2."' WHERE `id` = ".$id;
                $command=$connection->createCommand($sql);
                $like=$command->execute();
                if(!$like){
                    echo '{"code":"1008","msg":"like failure"}';
                }
            }else{//新增赞的情况
                $this->Like(1,$id);
                $like_user_id_arr[]=$user_id;
                $like_user_id_str3 = "[".implode(",", $like_user_id_arr)."]";
                $sql ="UPDATE `circle` SET `thumbOwnerIds`='".$like_user_id_str3."' WHERE `id` = ".$id;
                $command=$connection->createCommand($sql);
                $like=$command->execute();
                if(!$like){
                    echo '{"code":"1008","msg":"like failure"}';
                }
            }
        }else{//第一次赞的情况
            $this->Like(1,$id);
            $own_user_id_str="[".$this->getUserIdByPNubmer($pnumber)['id']."]";
            if(($this->update_own_id($id,$pnumber,$own_user_id_str)) == false){
                echo '{"code":"1008","msg":"update ownid failture"}';
            }
        };
    }
    
    function actionDelete($id,$real,$which="this",$pnumber) {
        $this->is_logined();
        if($which == "this"){
            $where = " WHERE `id`=".$id;
        }
        if($which == "mypost"){
            $ownerid=$this->getUserIdByPNubmer($pnumber)['id'];
            $where = " WHERE `ownerId`=".$ownerid;
        }
        if($real == 0){
            $sql ="UPDATE circle SET `deleted`= 1".$where;
        }
        if($real == 1){
            $sql ="DELETE FROM circle".$where;
        }
        if($real == "recover"){
            $sql ="UPDATE circle SET `deleted`= 0".$where;
        }
        
        $connection = \Yii::$app->db;
        $command=$connection->createCommand($sql);
        $like=$command->execute();
        if($like){
            echo '{"code":"1000","msg":"ok"}';
        }  else {
            echo '{"code":"1011","msg":"failure"}';
        }
    }
    /*
     * 赞和取消
     */
    function Like($arg,$id){
        $connection = \Yii::$app->db;
        $sql ="UPDATE `circle` SET thumb=thumb+".($arg)." WHERE `id` = ".$id;
        $command=$connection->createCommand($sql);
        $like_add=$command->execute();
        if($like_add){
            echo '{"code":"1000","msg":"success'.$arg.'"}';
        }else{
            return '{"code":"1007","msg":"unkown"}';
            exit();
        }
    }
    
    /*
     *赞或取消的同时 改变  thumbOwnerIds 的值。
     */
    function update_own_id($id,$pnumber,$val){
        $connection = \Yii::$app->db;
        $sql ="UPDATE `circle` SET `thumbOwnerIds`='".$val."' WHERE `id` = ".$id;
        $command=$connection->createCommand($sql);
        $like=$command->execute();
        if($like){
            return true;
        }  else {
            return false;
        }
    }
    /*
     * 通过手机号码查询user_id
     */
    function getUserIdByPNubmer($pnumber){
        $connection = \Yii::$app->db;
        $userid_sql = "SELECT `id` FROM `account` WHERE phoneNumber='".$pnumber."'";
        $command=$connection->createCommand($userid_sql);
        $userid=$command->queryOne();
        if(count($userid) > 0){
            return $userid;
        }else{
            return false;
        }
    }
    
    /*
     * 通过user_id查询手机号码
     */
    
    function getPNubmerIdByUser($user_id){
        $connection = \Yii::$app->db;
        $pnumber_sql = "SELECT `phoneNumber` FROM `account` WHERE id='".$user_id."'";
        $command=$connection->createCommand($pnumber_sql);
        $pnumber=$command->queryOne();
        if(count($pnumber) > 0){
            return $pnumber;
        }else{
            return false;
        }
    }
    
    /*
     * 查询自己的好友
     */
    function myfriend($user_pnumber){
        $connection2 = \Yii::$app->db;
        
        //判断relation表phonenumberA中是否有自己的手机号,找出B
        $sqlA = "SELECT `phoneNumberB` AS number FROM `relation` WHERE `phoneNumberA` = ".$user_pnumber;
        $command2=$connection2->createCommand($sqlA);
        $friend_id=$command2->queryAll(); 
        
        //判断relation表phonenumberB中是否有自己的手机号，找出A
        $sqlB = "SELECT `phoneNumberA` AS number FROM `relation` WHERE `phoneNumberB` = ".$user_pnumber;
        $command2=$connection2->createCommand($sqlB);
        $friend_id2=$command2->queryAll(); 
        $friend_number = array_merge($friend_id,$friend_id2);
        
        //如果查询到了好友号码,则用这些手机号去获取他的资料
        if(count($friend_number) > 0){
            return $friend_number;
        }  else {
            return false;
        };
    }
    
    /*
     * 通过手机号码查询好友资料。手机号码也是唯一的。material表反而没有用户唯一的user_id.
     */
    function getFriendBypnumber($pnumber,$col="*"){
        $connection2 = \Yii::$app->db;
        $sql = "SELECT ".$col." FROM `material` WHERE `phoneNumber` =".$pnumber;
        $command2=$connection2->createCommand($sql);
        $friend_id=$command2->queryOne(); 
        return $friend_id;
    }
    
    /*
    * 除base64的文本数据转换成sql
    */
   function text_sql($add_data){
       $text_array = array();
       foreach ($add_data as $key => $value) {
           if($key != "imgdata"){
              $text_array[$key]= $value;
           }
       }
       return $text_array;
   }

   /*
    * base64数组保存 返回路径
    */
   function imgdata_save($imgdata_array,$path,$img_num,$ymd){
       $i=0;
       $imgurl=array();
       foreach ($imgdata_array as $key => $base64_pic) {
           $result = $this->base64_to_save($base64_pic,$path,1,$i);
           if($result != false){
               $imgurl[] = $ymd.$result;
               $i++;

           }else{
               return false;
           };
       }
       if($i == $img_num){
           return $imgurl;
       }else{
           return false;
       }
   }

   /*
    * base64单个保存
    */

   function base64_to_save($base64_pic,$path,$index,$i){
       $base64_pic = stripslashes($base64_pic);
       if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_pic, $result)){
           $type = $result[2];
           if($type == "jpeg" || $type == "pjpeg"){
               $type = "jpg";
           }
           if (!is_dir($path)){  
               $this->makedir($path);
               $this->makedir($path."thumb");
           };
           $rand = mt_rand(10, 99);
           $today = date("mdHis"); 
           $new_file = $today."_".$i."_".$rand.".".$type;
           $new_path = $path.$new_file;
           $thumb_path = $path."thumb/";
           if (!is_dir($thumb_path)){  
               $this->makedir($thumb_path,true);
           };
           $new_thumb_path = $thumb_path.$new_file;
           if (file_put_contents($new_path, base64_decode(str_replace($result[1], '', $base64_pic)))){
               if($this->img2thumb($new_path, $new_thumb_path, $type,200,200)){
                   return $new_file;
               }  else {
                   return false;
               }
           }else {
               return false;
           };
       }else{
           return false;
       }
   }

   /*
    * 创建目录
    */

   function makedir($path){
               //第三个参数是“true”表示能创建多级目录，iconv防止中文目录乱码
               $res=mkdir(iconv("UTF-8", "GBK", $path),0777,true); 
               if ($res == false){
                   return false;
               }else{
                   return true;
               }
   }
   
    /*
    * @param string     源图绝对完整地址{带文件名及后缀名}
    * @param string     目标图绝对完整地址{带文件名及后缀名}
    * @param string     扩展名
    * @param int        缩略图宽{0:此时目标高度不能为0，目标宽度为源图宽*(目标高度/源图高)}
    * @param int        缩略图高{0:此时目标宽度不能为0，目标高度为源图高*(目标宽度/源图宽)}
    */
    function img2thumb($src_img, $dst_img, $dst_ext, $width = 300, $height = 320)
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
    /*
     * 判断是否登陆
     */
    public function is_logined(){
        $skey = $_GET['skey'];
        $td = mcrypt_module_open('xtea', '', 'ecb', '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, "jJe8f6I9", $iv);
        $plain = mdecrypt_generic($td, base64_decode($skey));
        $phoneNumber = substr($plain, 10);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $rs = ((intval($phoneNumber) + 0) == (intval($_GET['pnumber']) + 0));//这本身就是一种sign签名对比，以mcrypt作为签名类型
        if($rs == true)
        {
            return true;
        }  else {
            exit('{"code":"10001","msg":"fail"}');
        }
    }
}
?>
