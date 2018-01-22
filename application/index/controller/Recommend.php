<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Recommend
 *
 * @classdesc 推荐接口类
 * @package app\index\controller
 */
class Recommend extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    推荐列表接口
     * @url     /Recommend/RecommendList
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
    "RecommendList|推荐列表":[
    {
    "recommend_id|推荐id":16,
    "image_url|推荐图片":"http://www.ds.com/statics/Image/task//20180122185116body_bg_page.jpg",
    "object_value|跳转任务id":"111"
    }
    ]
    }
        }
     *
     */
    public function RecommendList(){
        //返回结果
        $data = [];

        try{

            $RecommendWhere = ['state'=>2,'del_flag'=>0];
            $order = 'sort desc,recommend_id desc';

            $RecommendList = model('Recommend')->where($RecommendWhere)->order($order)
                ->select();
            $this->getAllControl();
            $newRecommendList = [];
            foreach($RecommendList as $oneRecommend){

                $newRecommendList[]=[
                    'recommend_id'=>$oneRecommend['recommend_id'],
                    'image_url'=>'http://'.config('server_host').'/'.config('TASK_UPLOAD_IMAGE_DIR').'/'.$oneRecommend['image_url'],
                    'object_value'=>$oneRecommend['object_value']
                ];
            }

            $data['RecommendList'] = $newRecommendList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }



}
