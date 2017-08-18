<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Search
 *
 * @classdesc 搜索接口类
 * @package app\index\controller
 */
class Search extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    热门搜索内容列表接口
     * @url     /search/hotSearchValueList
     * @method  GET
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function hotSearchValueList(){

        //返回结果
        $data = [];

        $pageSize = config('page_size');
        //获取接口参数

        try{

            //热门搜索内容显示个数
            $hotNum = 10;

            $searchLogList = model('searchlog')->where(['delflag'=>0])
                ->order('counter desc')->limit('0,'.$hotNum)->select();


            $newSearchLogList = [];
            foreach($searchLogList as $oneSearchLog){

                $newSearchLogList[]=[
                    'search_value'=>$oneSearchLog['search_value'],
                    'type'=>$oneSearchLog['type'],
                ];
            }

            $data['hotsearch'] = $newSearchLogList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }
    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    搜索相似匹配接口
     * @url     /search/searchLike
     * @method  GET
     * @version 1000
     * @params  type 0 INT 搜索类型为0返回全部,2用户3服务4活动5专辑6动态 YES
     * @params  name '名称' STRING 搜索名称 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function searchLike(){

        //返回结果
        $data = [];

        //获取接口参数
        $type = input('request.type','');
        $name = input('request.name','');

        //验证参数是否为空
        if($name==''||!in_array($type,[0,2,3,4,5,6])){
            $this->returndata( 14001,  'params error', $this->curTime, $data);
        }

        try{

            $pageSize = config('page_size');
            if($type==0){
                $searchLogWhere = [
                    'search_value'=>['like',''.$name.'%'],
                    'delflag'=>0
                ];
            }
            else{
                $searchLogWhere = [
                    'type'=>$type,
                    'search_value'=>['like',''.$name.'%'],
                    'delflag'=>0
                ];
            }


            $searchLogList = model('searchlog')->where($searchLogWhere)
                ->order('counter desc')->limit('0,'.$pageSize)->select();

            if(!$searchLogList){
                $this->returndata( 14002, 'search like not  exist', $this->curTime, $data);
            }

            $newSearchLogList = [];
            foreach($searchLogList as $oneSearchLog){

                $newSearchLogList[]=[
                    'search_value'=>$oneSearchLog['search_value'],
                    'type'=>$oneSearchLog['type'],
                ];
            }

            $data['searchLikeList'] = $newSearchLogList;

            $this->returndata(10000, 'get success', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, []);
        }
    }



}
