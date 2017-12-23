<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Designworks
 *
 * @classdesc 设计作品接口类
 * @package app\index\controller
 */
class Designworks extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    设计作品列表接口
     * @url     /Designworks/DesignworksList
     * @method  GET
     * @version 1000
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
        {
            "code":10000,
            "message":"获取成功",
            "time":1492413087,
            "data":{
                "DesignworksList":[
                {
                    "Designworksid":2,
                    "userid":1000000005,
                    "nickname":"dd都是",
                    "invited_userid":1000000006,
                    "invited_avatar":"http://omsnjcbau.bkt.clouddn.com/avatar/default01",
                    "invited_nickname":"花满楼",
                    "invited_Designworkstypename":"",
                    "invited_exp":0,
                    "title":"1111",
                    "content":"1111",
                    "reward":"11.00",
                    "state":0,
                    "image":"http://omsnjcbau.bkt.clouddn.com/a.jpg",
                    "support_counter":0
                }
                ]
            }
        }
     *
     */
    public function DesignworksList(){
        //返回结果
        $data = [];
        $pageSize = config('page_size');
        //获取接口参数
        $page = input('request.page',1,'intval');
        //验证参数是否为空
        if($page<1){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $userId = $this->curUserInfo['userid'];

            $DesignworksWhere = ['userid'=>$userId,'delflag'=>0];
            $order = 'title desc,designworksid desc';

            $DesignworksList = model('educationexp')->where($DesignworksWhere)->order($order)
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();
            $DesignworksIds = [];
            $userIds = [];
            foreach($DesignworksList as $oneDesignworks){
                $DesignworksIds[]=$oneDesignworks['designworksid'];
            }

            if(!$DesignworksIds){
                $this->returndata( 14003, '', $this->curTime, $data);
            }

            $this->getAllControl();

            $newDesignworksList = [];
            foreach($DesignworksList as $oneDesignworks){
                $newDesignworksList[]=[
                    'designworksid'=>$oneDesignworks['designworksid'],
                    'title'=>$oneDesignworks['title'],
                    'pic'=>$oneDesignworks['pic'],
                    'link'=>$oneDesignworks['link'],
                    'desc'=>$oneDesignworks['desc'],
                ];
            }

            $data['DesignworksList'] = $newDesignworksList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }

    
    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    查看设计作品详情
     * @url     /Designworks/DesignworksView
     * @method  GET
     * @version 1000
     * @params  Designworksid 1 INT 设计作品id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     * @return
    {
        "code":10000,
        "message":"view success",
        "time":1492593379,
        "data":{
            "Designworks":{
            },
            "signup_userlist":{
            },
            
           
            
        }
    }
     *
     */
    public function DesignworksView(){

        //返回结果
        $data = [];

        //获取接口参数
        $DesignworksId = input('designworksid');

        if($DesignworksId <= 0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $Designworks = model('Designworks')->where(['designworksid'=>$DesignworksId,'delflag'=>0])->find();

            if(!$Designworks  ){
                $this->returndata( 14002, 'Designworks not exist', $this->curTime, $data);
            }

            $data['Designworks']=[
                'designworksid'                => $Designworks['designworksid'],
                'title'                 => $Designworks['title'],
                'pic'               => $Designworks['pic'],
                'link'            => $Designworks['link'],
                'desc'                => $Designworks['desc'],
            ];


            $this->returndata(10000, 'view success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }




    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    添加设计作品接口
     * @url     /Designworks/DesignworksAdd
     * @method  POST
     * @version 1000
     * @params  title 标题 STRING 标题 YES
     * @params  pic ["a.jpg"] STRING 图片json串 YES
     * @params  link 链接 STRING http://www.baidu.com YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function DesignworksAdd(){
        //返回结果
        $data = [];

        //获取接口参数
        $title = input('request.title','');
        $pic = input('request.pic','[]');
        $picList = json_decode($pic,true);
        $link = input('request.link','');


        //验证参数是否为空
        if($title==''||!$picList||$link==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $newDesignworks = [
                'title'=>$title,
                'pic'=>$pic,
                'link'=>$link
            ];
            $Designworksid = model('Designworks')->add($newDesignworks);

            if(!$Designworksid){
                $this->returndata( 14002, 'Designworks add fail', $this->curTime, $data);
            }

            $data['designworksid']=$Designworksid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    修改设计作品接口
     * @url     /Designworks/DesignworksEdit
     * @method  POST
     * @version 1000
     * @params  designworksid 1 INT 设计作品id YES
     * @params  title 20160101 STRING 标题 YES
     * @params  pic 20170101 STRING 图片列表json串 YES
     * @params  link 啊啊 STRING 链接 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function DesignworksEdit(){
        //返回结果
        $data = [];

        //获取接口参数
        $designworksid = input('request.designworksid','');
        $title = input('request.title','');
        $pic = input('request.pic','[]');
        $picList = json_decode($pic,true);
        $link = input('request.link','');


        //验证参数是否为空
        if($designworksid<=0||$title==''||!$picList||$link==''){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{
            $newDesignworks = [
                'title'=>$title,
                'pic'=>$pic,
                'link'=>$link
            ];
            $Designworksid = model('Designworks')->where(['designworksid'=>$designworksid])->save($newDesignworks);

            if(!$Designworksid){
                $this->returndata( 14002, 'Designworks edit fail', $this->curTime, $data);
            }

            $data['designworksid']=$Designworksid;
            $this->returndata(10000, 'do success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }



    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    删除自己的设计作品
     * @url     /Designworks/DesignworksDel
     * @method  POST
     * @version 1000
     * @params  designworksid 1 INT 设计作品id YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function DesignworksDel(){

        //返回结果
        $data = [];

        //获取接口参数
        $expId = input('designworksid',0);

        if($expId<0){
            $this->returndata(14001, 'params error', $this->curTime, $data);
        }

        try{

            $Designworks = model('Designworks')
                ->where(['userid'=>$this->curUserInfo['userid'],'designworksid'=>$expId,'delflag'=>0])
                ->find();
            if(!$Designworks){
                $this->returndata( 14002, 'exp not exist', $this->curTime, $data);
            }
            model('Designworks')
                ->where(['userid'=>$this->curUserInfo['userid'],'designworksid'=>$expId,'delflag'=>0])
                ->update(['delflag'=>1,'updatetime'=>$this->curTime]);


            $this->returndata(10000, 'do success', $this->curTime, $data);
        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }


}
