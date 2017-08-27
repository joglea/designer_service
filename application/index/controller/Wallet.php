<?php
namespace app\index\controller;

use app\common\controller\Front;
/**
 * Class Wallet
 *
 * @classdesc 钱包接口类
 * @package app\index\controller
 */
class Wallet extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    钱包详情接口
     * @url     /wallet/walletView
     * @method  GET
     * @version 1000
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function walletView(){


        //返回结果
        $data = [];

        try{

            //新建可以报名的任务列表
            $walletWhere = ['userid'=>$this->curUserInfo['userid']];


            $wallet = model('wallet')->where($walletWhere)->find();

            $data['wallet'] = [
                'now_money'=>$wallet?$wallet['now_money']:0
            ];
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    收支明细接口
     * @url     /wallet/walletRecordList
     * @method  GET
     * @version 1000
     * @params  page 1 INT 当前请求的是第几页数据 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function walletRecordList(){


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

            //新建可以报名的任务列表
            $walletRecordWhere = [
                'userid'=>$this->curUserInfo['userid'],'delflag'=>0];

            //$taskWhere['limittime']=['gt',time()];
            $order = 'createtime desc';

            $walletRecordList = model('walletrecord')->where($walletRecordWhere)->order($order)
                ->limit((($page-1)*$pageSize).','.$pageSize)->select();


            $newWalletRecordList = [];
            foreach($walletRecordList as $oneWalletRecord){

                $newWalletRecordList[] = [
                    'objectid'=>$oneWalletRecord['objectid'],
                    'objecttype'=>$oneWalletRecord['objecttype'],//对象类型1支出2收入3充值
                    'price'=>$oneWalletRecord['price'],
                    'desc'=>$oneWalletRecord['desc'],
                    'createtime'=>$oneWalletRecord['createtime'],
                ];
            }

            $data['walletRecordList'] = $newWalletRecordList;
            $this->returndata(10000, '获取成功', $this->curTime, $data);

        }catch (Exception $e){
            $this->returndata(11000, 'server error', $this->curTime, $data);
        }

    }


}
