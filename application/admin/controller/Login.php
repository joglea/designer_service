<?php
namespace app\admin\controller;

use app\common\controller\Base;
use app\common\controller\Message;
use anerg\OAuth2\OAuth;
use anerg\helper\Exception;

class Login extends Base
{

    /**
     * 登录页
     */
    public function index(){

        $lifetime=7200;//保存120分钟
        session_start();
        setcookie(session_name(),session_id(),time()+$lifetime,"/");

        //静态资源加载域名 暂时没用
        $this->staticUrl='//'.config('STATIC_HOST').'/';
        $data = ['staticUrl'=>$this->staticUrl];
        $msg = '';
        //有post数据时
        if($_POST){
            $username=input('post.username');		//账号
            $passwd=input('post.password');		//密码
            $remember=input('post.remember');	//是否记住密码  不存在表示没勾记住密码  1新勾了记住密码  2之前勾过记住密码
            $autologin=input('post.auto_login');//是否自动登录

            if($username&&$passwd){
                //之前勾过记住密码
                if($remember==2){
                    $check=input('cookie.check');
                    //验证记住密码的加密串
                    if(!$this->checkrememberpasswd($username,$check)){
                        $msg='记住密码验证失败';
                    }
                    else{
                        $ret=$this->getuserinfo($username,$passwd);
                        if($ret){
                            setcookie('username', $username, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                            setcookie('check', $this->rememberpasswdmd5($username), time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                            setcookie('encrypted', $passwd, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                            setcookie('remember', 1, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                            //勾了自动登录
                            if($autologin==1){
                                setcookie('auto_login', $autologin, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                            }
                            else{
                                setcookie('auto_login', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
                            }
                            //登录成功跳
                            $this->loginsuccess();
                        }
                        else{
                            $msg='账号密码不匹配01';
                        }
                    }
                }	//之前没勾过记住密码
                else{
                    //表单post密码md5
                    $md5passwd=$this->passwdmd5($passwd);

                    $ret=$this->getuserinfo($username,$md5passwd);
                    if($ret){
                        //勾了记住密码
                        if($remember==1){
                            setcookie('username', $username, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                            setcookie('check', $this->rememberpasswdmd5($username), time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                            setcookie('encrypted', $md5passwd, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                            setcookie('remember', $remember, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                        }
                        else{
                            setcookie('username', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
                            setcookie('check', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
                            setcookie('encrypted', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                            setcookie('remember', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
                        }
                        //勾了自动登录
                        if($autologin==1){
                            setcookie('auto_login', $autologin, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                        }
                        else{
                            setcookie('auto_login', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
                        }
                        $this->loginsuccess();
                    }
                    else{
                        $msg='账号密码不匹配02';
                    }
                }
            }
            else{
                $msg='账号、密码都不能为空';
            }
        }
        else{
            $sessisexpire = input('get.sess_is_expire',0,'intval');
            if($sessisexpire==1 && isset($_SERVER['HTTP_REFERER'])){
                session('login_referer',$_SERVER['HTTP_REFERER']);
            }
            $username='';		//账号
            $passwd='';		//密码
            $remember=0;	//是否记住密码  不存在表示没勾记住密码  1新勾了记住密码  2之前勾过记住密码
            $autologin=0;//是否自动登录
        }

        if($this->checkremember()){
            $remember=input('cookie.remember');
            $username=input('cookie.username');
            $check=input('cookie.check');
            $encrypted=input('cookie.encrypted');

            if(!$this->checkrememberpasswd($username,$check)){
                $msg='记住密码验证失败';
                setcookie('remember', 1, time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
                setcookie('username', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
                setcookie('check', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
                setcookie('encrypted', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */

                $data['remember']=$remember;
                $data['autologin']=0;
                $data['username']='';
                $data['passwd']='';
            }
            else{

                $data['remember']=2;
                $data['autologin']=0;
                $data['username']=$username;
                $data['passwd']=$encrypted;

                if($this->checkautologin()){
                    $autologin=input('cookie.auto_login');
                    $data['autologin']=$autologin;
                    $ret=$this->getuserinfo($username,$encrypted);
                    if($ret){
                        setcookie('remember', 1, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                        setcookie('username', $username, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                        setcookie('check', $this->rememberpasswdmd5($username), time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                        setcookie('encrypted', $encrypted, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */

                        if($autologin==1){
                            setcookie('auto_login', $autologin, time()+7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire in 7 day */
                        }
                        else{
                            setcookie('auto_login', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
                        }
                        $this->loginsuccess();
                    }
                    else{
                        $msg='账号密码不匹配03';
                    }
                }
            }
        }
        else{

            $data['remember']=$remember;
            $data['autologin']=$autologin;
            $data['username']=$username;
            $data['passwd']=$passwd;
        }

        if($msg==''){

            $data['msg']='<div class="alert alert-danger display-hide" >
                            <button class="close" data-close="alert"></button>
                            <span>账号密码不能为空. </span>
                        </div>';
        }
        else{
            $data['msg']='<div class="alert alert-danger display-hide" style="display: block;">
                            <button class="close" data-close="alert"></button>
                            <span>'.$msg.'</span>
                        </div>';
        }

        $this->assign($data);
        return $this->fetch();
    }

    /**
     * 登出
     */
    public function logout(){
        setcookie('auto_login', '', time()-7*86400,'/','.'.config('TOP_DOMAIN'));  /* expire before 7 day */
        session('userinfo',null);

        $this->redirect('/index.php/admin/Index/index');
        exit;
    }

    /**
     * 根据账号密码获取完整的账号信息
     * @param $username 账号
     * @param $passwd   密码
     * @return array 完整的用户信息
     */
    private function getuserinfo($username,$passwd){
        $userinfo=model('adminuser')->where(['username'=>$username,'passwd'=>$passwd])->find();

        if($userinfo){
            $userinfo['adminrole'] = model('adminrole')->where(['roleid'=>$userinfo['role_id']])->find();
            $userinfo['rolemoduleinfo']=model('adminrolemodule')->where(['role_id'=>$userinfo['role_id']])->find();
            $userinfo['modulerightinfo']=model('adminrolemodule')->getrolemodulerightbyroleid($userinfo['role_id']);
            session('userinfo',$userinfo);
           //var_dump($userinfo);exit;
            return true;
        }
        else{
            return false;
        }
    }
    /**
     * 检查之前是否有勾过记住密码
     * @return boolean
     */
    private function checkremember(){
        if(isset($_COOKIE['remember'])&&$_COOKIE['remember']==1&&
            isset($_COOKIE['username'])&&$_COOKIE['username']&&
            isset($_COOKIE['check'])&&$_COOKIE['check']){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * 检查记住的密码是否合法
     * @param string $username
     * @param string $passwd
     * @return boolean
     */
    private function checkrememberpasswd($username,$passwd){
        if(substr(
                md5(config('REMEMBER_PASSWD_PREFIX').$username.config('REMEMBER_PASSWD_SUFFIX')),
                config('REMEMBER_PASSWD_START'),
                config('REMEMBER_PASSWD_LENGTH')
            )==$passwd){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * 检查是否勾选过自动登录
     * @return boolean
     */
    private function checkautologin(){
        if(isset($_COOKIE['auto_login'])&&$_COOKIE['auto_login']==1){
            return true;
        }
        else{
            return false;
        }
    }



    /**
     * 记住密码md5
     * @param string $username
     * @return string
     */
    private function rememberpasswdmd5($username){
        return substr(
            md5(config('REMEMBER_PASSWD_PREFIX').$username.config('REMEMBER_PASSWD_SUFFIX')),
            config('REMEMBER_PASSWD_START'),
            config('REMEMBER_PASSWD_LENGTH')
        );
    }

    /**
     * 登录成功跳转
     */
    private function loginsuccess(){
        if(session('login_referer')!=''){
            if(strpos(session('login_referer'), '/index.php/admin/Index/index')!=-1){
                session('login_referer',null);
                $this->redirect('//'.config('MAIN_HOST').'/index.php/admin/Index/index');
            }
            else{
                $loginReferer=session('login_referer');
                session('login_referer',null);
                $this->redirect($loginReferer);
            }
        }
        else{
            session('login_referer',null);
            //var_dump(session(''));exit;//var_dump('//'.config('MAIN_HOST').'/index.php/admin/Index/index');exit;
            $this->redirect('http://'.config('MAIN_HOST').'/index.php/admin/Index/index');
        }
        exit;
    }

    /**
     * 通过邮件重置密码
     */
    public function resetpasswd(){
        if(IS_POST){
            $username = input("post.username",'');
            $email = input("post.email",'');
            if(''==$username){
                $code=-1;
                $msg='账号不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else if(''==$email){
                $code=-2;
                $msg='邮箱不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            /*else if(){
                $code=-5;
                $msg='新密码长度需要再6~32之间';
                $msgtype=MSG_TYPE_WARNING;
            }*/
            else {


                $check=model('user')->where(array('status'=>0,'username'=>$username,'email'=>$email))->find();

                if($check){

                    $s=[0,2,3,4,5,6,7,8,'a','b','c','d','e','f','g','h','j','k','l','m','n','p','q','r','s','t','u','v','w','x','y','z'];

                    $newpasswd=$s[rand(0,32)].$s[rand(0,32)].$s[rand(0,32)].$s[rand(0,32)].$s[rand(0,32)].$s[rand(0,32)];
                    //var_dump($newpasswd);exit;
                    $ret=model('user')->where(['username'=>$username,'email'=>$email])->update(['passwd'=>$newpasswd,'updatetime'=>time()]);
                    if($ret){
                        $config = config('THINK_EMAIL');
                        vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
                        $mail             = new PHPMailer(); //PHPMailer对象
                        $mail->CharSet    = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
                        $mail->IsSMTP();  // 设定使用SMTP服务
                        $mail->SMTPDebug  = 0;                     // 关闭SMTP调试功能
                        // 1 = errors and messages
                        // 2 = messages only
                        $mail->SMTPAuth   = true;                  // 启用 SMTP 验证功能
                        $mail->SMTPSecure = 'ssl';                 // 使用安全协议
                        $mail->Host       = $config['SMTP_HOST'];  // SMTP 服务器
                        $mail->Port       = $config['SMTP_PORT'];  // SMTP服务器的端口号
                        $mail->Username   = $config['SMTP_USER'];  // SMTP服务器用户名
                        $mail->Password   = $config['SMTP_PASS'];  // SMTP服务器密码
                        $mail->SetFrom($config['FROM_EMAIL'], $config['FROM_NAME']);
                        $replyEmail       = $config['REPLY_EMAIL']?$config['REPLY_EMAIL']:$config['FROM_EMAIL'];
                        $replyName        = $config['REPLY_NAME']?$config['REPLY_NAME']:$config['FROM_NAME'];
                        $mail->AddReplyTo($replyEmail, $replyName);
                        $mail->Subject    = 'juju找回密码';
                        $mail->MsgHTML('你的新密码为：'.$newpasswd);
                        $mail->AddAddress($email, $email);
                        $attachment=array();
                        if(is_array($attachment)){ // 添加附件
                            foreach ($attachment as $file){
                                is_file($file) && $mail->AddAttachment($file);
                            }
                        }
                        $ret1= $mail->Send() ? true : false;
                        //var_dump($mail->ErrorInfo);exit;
                        if($ret1){
                            $code=0;
                            $msg='邮件发送成功';
                            $msgtype=MSG_TYPE_SUCCESS;
                        }
                        else{
                            $code=-3;
                            $msg='邮件发送失败'.$mail->ErrorInfo;
                            $msgtype=MSG_TYPE_WARNING;
                        }
                    }
                    else{
                        $code=-4;
                        $msg='重置密码失败';
                        $msgtype=MSG_TYPE_WARNING;
                    }
                }
                else{
                    $code=-5;
                    $msg='原密码错误';
                    $msgtype=MSG_TYPE_WARNING;
                }
            }
            //echo $msg;exit;
            echo json_encode(array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype));exit;
        }
        else{
            echo $this->fetch();exit;
        }
    }



    /**
     * 上传图片到OSS
     */
    public function uploadImg(){


        $newimgurl='';
        if(!$_FILES){
            $msg='不合法';
        }
        elseif(''==$_FILES['image_url_upload']){
            $msg='图片不能为空';
        }
        else {

            if ((($_FILES["image_url_upload"]["type"] == "image/gif")
                    || ($_FILES["image_url_upload"]["type"] == "image/jpeg")
                    || ($_FILES["image_url_upload"]["type"] == "image/pjpeg")|| ($_FILES["image_url_upload"]["type"] == "image/png"))
                && ($_FILES["image_url_upload"]["size"] < 10000000)
            ) {
                if ($_FILES["image_url_upload"]["error"] > 0) {
                    $code    = - 11;
                    $msg     = '上传错误' . $_FILES["image_url_upload"]["error"];
                    $msgtype = MSG_TYPE_DANGER;
                } else {
                    $imgname = $_FILES["image_url_upload"]["name"];
                    $imgurl  = date ('YmdHis') . $_FILES["image_url_upload"]["name"];

                    move_uploaded_file ($_FILES["image_url_upload"]["tmp_name"] ,
                        config('TASK_UPLOAD_IMAGE_DIR') . $imgurl);



                    $newimgurl='http://'.config('server_host').'/'.config('TASK_UPLOAD_IMAGE_DIR').'/'. $imgurl ;

                    $msg     = '上传成功' ;
                }
            } else {
                $msg     = '上传错误Invalid file';
            }
        }


        $ret = [];
        if(!$newimgurl){

            $ret['error']=$msg;
        }
        else{
            $ret['img_data']=[
                'storage_name'=>$imgurl,
                'url'=>$newimgurl
            ];
            $ret['initialPreviewConfig']=[
                'caption'=>'333',
                'key'=>1
            ];
        }

        echo json_encode($ret);exit;



    }





}
