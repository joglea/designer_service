<?php

namespace common;

class CurlService {
    public function RamDel($url,$arr)
    {
        if($arr!=''){
            $ar=array();
            foreach($arr as $k=>$v){
                $ar[]=$k.'='.$v;
               }
            $url=$url.'?'.implode('&',$ar); 
        }
        return $this->curl($url,$arr,'DELETE',FALSE,FALSE);
    }
     //get方法
    public function RamGet($url,$arr)
    {
        if($arr!=''){
            $ar=array();
            foreach($arr as $k=>$v){
                $ar[]=$k.'='.$v;
               }
            $url=$url.'?'.implode('&',$ar); 
        }
        return $this->curl($url,$arr,'GET',FALSE,FALSE);
    }
    //get方法
    public function RamGets($url,$arr)
    {
        if($arr!=''){
            $ar=array();
            foreach($arr as $k=>$v){
                $ar[]=$k.'='.$v;
               }
            $url=$url.'?'.implode('&',$ar); 
        }
        return $this->curls($url,$arr,'GET',FALSE,FALSE);
    }
    //post方法
    public function RamPost($url,$arr,$data_type='')
    {
        return $this->curl($url,$arr,'POST',$data_type);
    }
    //curl方法
    function curl($url,$post_data=[],$type='GET',$data_type=''){

        //这是一个创建频道的示例，修改相关的参数可以实现相应的接口调用
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); //信任任何证书
        // post数据
        if($type == 'POST'){
            if($data_type=='json'){
                //json $_POST=json_decode(file_get_contents('php://input'), TRUE);
                $headers = array("Content-type: application/json;charset=UTF-8","Accept: application/json","Cache-Control: no-cache", "Pragma: no-cache");
                $post_data=json_encode($post_data);
                curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            }
            //var_dump($post_data);
            curl_setopt($ch, CURLOPT_POST, 1);
                //postheader
                // post的变量

            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }elseif($type == 'DELETE'){
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE"); 
        }else{    
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
        }

        //print_r($post_data);exit;
        $output = curl_exec($ch);
        $errno = curl_errno( $ch );
        $info  = curl_getinfo( $ch );
        curl_close($ch);
        //打印获得的数据
        return json_decode($output,true);
    }
    function curls($url,$post_data,$type){
        //这是一个创建频道的示例，修改相关的参数可以实现相应的接口调用
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //信任任何证书
        // post数据
        if($type == 'POST'){
            curl_setopt($ch, CURLOPT_POST, 1);
                //postheader
                // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }else{
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
        }

        //print_r($post_data);exit;
        $output = curl_exec($ch);
        $errno = curl_errno( $ch );
        $info  = curl_getinfo( $ch );
        //print_r($info);exit;
        curl_close($ch);
        return  $output;
        //打印获得的数据
    }
}
    
