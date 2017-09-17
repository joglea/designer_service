<?php

namespace common;

class WeixinService {

    public function get_pcregister($code)
    {
        $token_data = $this->get_token($code,self::$pc_appid,self::$pc_secret);
        $weixindata = $this->get_weixindata($token_data);
        $nickname = $this->filter($weixindata['nickname']);
        $weixindata['nickname'] = $nickname;
        return $weixindata;

    }
    /*
    * 获取 access_token
    */
    public function get_token($code,$appid,$secret){
        $data = array(
            'appid' => $appid,
            'secret' => $secret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        );
        $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
        $token_data = service('common/curl')->RamGet($token_url,$data);
        return $token_data;
    }
    /*
    * 获取 微信用户信息
    */
    public function get_weixindata($token_data){
        if(!$token_data||!isset($token_data['access_token'])||
            $token_data['access_token']==''){
            $this->CI->response(NULL, ErrorCode::$GET_ACCESS_TOKEN_ERROR);
        }
        $data = array(
            'access_token' => $token_data['access_token'],
            'openid' => $token_data['openid'],
            'lang' => 'zh_CN'
        );
        $data_url = 'https://api.weixin.qq.com/sns/userinfo';
        $weixin_data = service('common/curl')->RamGet($data_url,$data);
        return $weixin_data;
    }

}
    
