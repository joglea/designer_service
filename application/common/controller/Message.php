<?php
/**
 * Created by PhpStorm.
 * User: xx
 * Date: 2016/11/28
 * Time: 下午4:53
 */

namespace app\common\controller;


use ClientException;
use phpmailer\PHPMailer;
use ServerException;

class Message
{
    public function sendMail($toemail, $title, $content)
    {

        //include_once EXTEND_PATH . 'phpmailer/PHPMailerAutoload.php';
        include_once EXTEND_PATH . 'phpmailer/phpmailer.php';
        $mail = new PHPMailer();

        $mail->isSMTP();// 使用SMTP服务
        $mail->CharSet = "utf8";// 编码格式为utf8，不设置编码的话，中文会出现乱码
        $mail->Host = "smtp.exmail.qq.com";// 发送方的SMTP服务器地址
        $mail->SMTPAuth = true;// 是否使用身份验证
        $mail->Username = "account@gravii.cc";// 发送方的163邮箱用户名，就是你申请163的SMTP服务使用的163邮箱</span><span style="color:#333333;">
        $mail->Password = "123@abC";// 发送方的邮箱密码，注意用163邮箱这里填写的是“客户端授权密码”而不是邮箱的登录密码！</span><span style="color:#333333;">
        $mail->SMTPSecure = "ssl";// 使用ssl协议方式</span><span style="color:#333333;">
        $mail->Port = 465;// 163邮箱的ssl协议方式端口号是465/994

        $mail->setFrom("account@gravii.cc", "account");// 设置发件人信息，如邮件格式说明中的发件人，这里会显示为Mailer(xxxx@163.com），Mailer是当做名字显示
        $mail->addAddress($toemail, '');// 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)
        $mail->addReplyTo("service@gravii.cc", "Reply");// 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
        //$mail->addCC("xxx@163.com");// 设置邮件抄送人，可以只写地址，上述的设置也可以只写地址(这个人也能收到邮件)
        //$mail->addBCC("xxx@163.com");// 设置秘密抄送人(这个人也能收到邮件)
        //$mail->addAttachment("bug0.jpg");// 添加附件


        //"您的验证码是：" . $code . "，哈哈哈！"
        $mail->Subject = $title;// 邮件标题
        $mail->Body = $content;// 邮件正文
        //$mail->AltBody = "This is the plain text纯文本";// 这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用

        if (!$mail->send()) {// 发送邮件
            return  $mail->ErrorInfo;
        } else {
            return true;
        }

    }
    /**
     * @param $text     为短信内容
     * @param $mobile   为接受短信的手机号
     * @param $tplid    对应默认模板 【#company#】您的验证码是#code#
     *
     * @return mixed
     */
    public function sendYunpianMsg($text,$mobile,$tplid=0){
        //请用自己的apikey代替
        $apikey = "5fa1a66f6393b198c8a3a8cce4e9c0e1";

        if($tplid==0){
            $url="http://yunpian.com/v1/sms/send.json";
            $encoded_text = urlencode("$text");
            $mobile = urlencode("$mobile");
            $post_string="apikey=$apikey&text=$encoded_text&mobile=$mobile";
        }
        else{
            $url="http://yunpian.com/v1/sms/tpl_send.json";
            $encoded_tpl_value = urlencode("$text");  //tpl_value需整体转义
            $mobile = urlencode("$mobile");
            $post_string="apikey=$apikey&tpl_id=$tplid&tpl_value=$encoded_tpl_value&mobile=$mobile";
        }


        $data = "";
        $info=parse_url($url);
        $fp=fsockopen($info["host"],80,$errno,$errstr,30);
        if(!$fp){
            return '';
        }
        $head="POST ".$info['path']." HTTP/1.0\r\n";
        $head.="Host: ".$info['host']."\r\n";
        $head.="Referer: http://".$info['host'].$info['path']."\r\n";
        $head.="Content-type: application/x-www-form-urlencoded\r\n";
        $head.="Content-Length: ".strlen(trim($post_string))."\r\n";
        $head.="\r\n";
        $head.=trim($post_string);
        fputs($fp,$head);
        $header = "";
        while ($str = trim(fgets($fp,4096))) {
            $header.=$str;
        }
        while (!feof($fp)) {
            $data .= fgets($fp,4096);
        }

        $ret=json_decode($data,true);
        //var_dump($ret);
        //0 成功  -3 IP没有权限
        if($ret['code']==0){
            return true;
        }
        if(config('ENV_VALUE')==1){
            return json_encode($ret);
        }
        else{
            return $ret['code'];
        }
    }

    public function sendAliMsg($mobile,$templateCode,$param)
    {
        //发送验证码到手机
        include_once EXTEND_PATH . '/aliyun-php-sdk-sms/aliyun-php-sdk-core/Config.php';

        $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", "LTAI09b3hr9yZ9VW", "qMtR9l9pPCAx6oRzn53j996yw8xR6f");
        $client = new \DefaultAcsClient($iClientProfile);
        $request = new SingleSendSmsRequest();
        $request->setSignName("杭州高善网络科技");//签名名称
        $request->setTemplateCode($templateCode);//模板code "SMS_27750080"
        $request->setRecNum($mobile);//目标手机号
//        $param = ['code' => $code, 'product' => 'gravii'];
        $request->setParamString(json_encode($param));//模板变量，数字一定要转换为字符串

        $data['type'] = 1;
        try {
            $response = $client->getAcsResponse($request);
            if ($response) {
                return true;
            }
        } catch (ClientException  $e) {
            return $e->getErrorMessage();
        } catch (ServerException  $e) {
            return $e->getErrorMessage();
        }
    }
}
