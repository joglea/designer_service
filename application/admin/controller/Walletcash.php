<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 订单管理
 * Enter description here ...
 * @author jogle
 * @created on 20170906
 */
class Order extends Admin{

	/**
	 *  用户列表
	 * @param number $in 是否内部调用 0不是  1是
     * @return string
     */
	public function index($view='index'){
        //var_dump($this->rolelist);exit;
        $this->setPageHeaderRightButton(
            array(
                array(
                    'class'=>'btn btn-fit-height grey-salt refresh',
                    'icon'=>"<i class='fa fa-plus'></i>",
                    'text'=>'刷新')
            )
        );

        if(IS_AJAX&&$view=='index'){
            $where = array();

            $orderid = input('post.orderid','');
            if($orderid !='')
            {
                $where['orderid'] = $orderid;
            }
            $userid = input('post.userid','');
            if($userid !='')
            {
                $where['userid'] = $userid;
            }
            $out_order_id= input('post.out_order_id','');
            if($out_order_id !='')
            {
                $where['out_order_id'] = $out_order_id;
            }
            $taskid= input('post.taskid','');
            if($taskid !='')
            {
                $where['taskid'] = $taskid;
            }

            $pay_rate = input('post.pay_rate','-1');
            if($pay_rate !='-1')
            {
                $where['pay_rate'] = $pay_rate;
            }



            $state = input('post.state','-1');;  //
            if($state!=-1 )
            {
                $where['state'] = $state;
            }

            $createstarttime = input('post.createstarttime','');  //开始时间
            $createendtime = input('post.createendtime','');  //结束时间
            if('' != $createstarttime )
            {
                if('' != $createendtime ){
                    $where['createtime'] = array('between',array(($createstarttime),($createendtime)));
                }
                else{
                    $where['createtime'] = array('egt',($createstarttime));
                }
            }
            else{
                if('' != $createendtime )
                {
                    $where['createtime'] = array('elt',($createendtime));
                }
            }

            $draw = input('post.draw','1');
            $start = input('post.start','0');
            $pagesize = input('post.length',config('PAGE_SIZE')>0?config('PAGE_SIZE'):10);

            //tp3.1 I方法获取数据时  提交参数为[]数组时  会取不到高维数据
            $ordercolumn=isset($_POST['order'])?$_POST['order']:[];

            $order='';
            if(count($ordercolumn)>0){
                if(0==$ordercolumn[0]['column']){
                    $order=' orderid '.$ordercolumn[0]['dir'].' ';
                }
                elseif(1==$ordercolumn[0]['column']){
                    $order=' userid '.$ordercolumn[0]['dir'].' ';
                }
                else if(2==$ordercolumn[0]['column']){
                    $order=' out_order_id '.$ordercolumn[0]['dir'].' ';
                }
                else if(3==$ordercolumn[0]['column']){
                    $order=' taskid '.$ordercolumn[0]['dir'].' ';
                }
                else if(4==$ordercolumn[0]['column']){
                    $order=' total_price '.$ordercolumn[0]['dir'].' ';
                }
                else if(5==$ordercolumn[0]['column']){
                    $order=' pay_rate '.$ordercolumn[0]['dir'].' ';
                }
                else if(6==$ordercolumn[0]['column']){
                    $order=' state '.$ordercolumn[0]['dir'].' ';
                }
                else if(7==$ordercolumn[0]['column']){
                    $order=' createtime '.$ordercolumn[0]['dir'].' ';
                }
            }
            $where['delflag']=0;
            $userlist=model('Order')->where($where)->limit($start,$pagesize)
                ->order($order)->select();
            //var_dump(model('Order')->getLastSql());exit;
            $usercount=model('Order')->where($where)->count();
            $recordsfiltered=$usercount;
            $recordstotal=$usercount;
            //var_dump($userlist);exit;
            $data=array();
            $this->getAllControl();
            foreach($userlist as $k=>$v){
                $data[$k][]=$v['orderid'];
                $data[$k][]=$v['userid'];
                $data[$k][]=$v['out_order_id'];
                $data[$k][]=$v['taskid'];
                if($v['state']==1){
                    $data[$k][]='<input style="width:70px;" type="text" name="total_price_'.$v['orderid'].'"  class="total_price_input_'.$v['orderid'].'" value="'.$v['total_price'].'"/>'.
                        '<a title=""  class="total_price_btn btn green btn-xs" href="javascript:;" onclick="changetotalprice('.$v['orderid'].')"><i class="fa fa-12px fa-edit"></i>保存</a>';
                }
                else{
                    $data[$k][]=$v['total_price'];
                }

                if($v['state']==1){
                    $data[$k][]='<input style="width:40px;" type="text" name="pay_rate_'.$v['orderid'].'"  class="pay_rate_input_'.$v['orderid'].'" value="'.$v['pay_rate'].'"/>'.
                        '<a title=""  class="pay_rate_btn btn green btn-xs" href="javascript:;" onclick="changepayrate('.$v['orderid'].')"><i class="fa fa-12px fa-edit"></i>保存</a>';
                }
                else{
                    $data[$k][]=$v['pay_rate'];
                }

                $btn='';
                if($v['state']==1){
                    $state = '未支付';
                    $btn = '<a title="" id="already_pay_'.$v['orderid'].'" class="btn green btn-xs" href="javascript:;" onclick="alreadypay('.$v['orderid'].',)"><i class="fa fa-12px fa-edit"></i>改为已支付</a>';
                    $btn .= '<a title="" id="cancel_pay_'.$v['orderid'].'" class="btn green btn-xs" href="javascript:;" onclick="cancelpay('.$v['orderid'].',)"><i class="fa fa-12px fa-edit"></i>取消订单</a>';

                }
                elseif($v['state']==2){
                    $state = '已支付定金';
                    //$btn = '<a title="" id="tail_pay_'.$v['orderid'].'" class="btn green btn-xs" href="javascript:;" onclick="tailpay('.$v['orderid'].',)"><i class="fa fa-12px fa-edit"></i>支付尾款</a>';

                }
                elseif($v['state']==3){
                    $state = '已取消';

                }
                elseif($v['state']==4){
                    $state = '尾款到账';

                }
                else{
                    $state='-';
                }
                $data[$k][]=$state.$btn;
                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.
                   '<a title="查看报名" id="signuplist_'.$v['orderid'].'" class="btn green btn-xs" href="javascript:;" onclick="signuplist('.$v['orderid'].',2)"><i class="fa fa-12px fa-search"></i>查看报名</a>'.
                    //'<a title="修改" id="editorder_'.$v['orderid'].'" class="btn green btn-xs" href="javascript:;" onclick="editorder('.$v['orderid'].',)"><i class="fa fa-12px fa-edit"></i>修改</a>';
                    '';
                    //'<a id="remove_user_'.$v['userid'].'" class="btn btn-danger btn-xs" href="javascript:;" onclick="removeuser('.$v['userid'].')"><i class="fa fa-12px fa-trash-o"></i>删除</a></div>';
            }
            //var_dump($data);exit;
            //var_dump($where,$this->usercount,$this->userlist);exit;
            echo json_encode(array("data"=>$data,"draw"=>$draw,"recordsTotal"=>$recordstotal,"recordsFiltered"=>$recordsfiltered));exit;
        }
        else{
            return $this->fetch($view);
        }
	}




    /**
     *
     */
    public function signuplist(){



        $allControl = $this->getAllControl();
        $orderid = input("get.orderid",'');
        $orderinfo = model('order')->where(['orderid'=>$orderid,'delflag'=>0])->find();
        if(!$orderinfo){
            echo '订单不存在';
        }
        else{
            $signuplist = model('orderdetail')->alias('a')
                ->join('ds_tasksignup b',' a.signupid = b.signupid ')
                ->where(['a.orderid'=>$orderid,'a.delflag'=>0,'b.delflag'=>0])
                ->field('a.orderid,a.createtime  paytime,b.*')
                ->select();
            $this->assign('orderid',$orderid);
            $this->assign('orderinfo',$orderinfo);
            $this->assign('signuplist',$signuplist);
            $this->assign('allControl',$allControl);

            $this->assign('allControl',$allControl);
            //var_dump($signuplist);exit;
            echo $this->fetch();
        }


    }



    public function changetotalprice(){
        $orderinfo = array();
        $orderinfo["orderid"] = input("post.orderid",'');
        $orderinfo["total_price"] = input("post.total_price",'');

        if(0>=$orderinfo['orderid']){
            $code=-1;
            $msg='id不合法';
            $msgtype=MSG_TYPE_WARNING;
        }
        else if(0>=$orderinfo['total_price']){
            $code=-1;
            $msg='价格不合法';
            $msgtype=MSG_TYPE_WARNING;
        }
        else{

            $orderinfo["updatetime"] = time();

            $isExist=model("order")->where(array('orderid'=>$orderinfo["orderid"],'delflag'=>0))
                ->find();

            if(!$isExist){
                $code=-8;
                $msg='订单不存在';
                $msgtype=MSG_TYPE_DANGER;
            }
            else{
                if($isExist['state']!=1){
                    $code=-8;
                    $msg='此订单状态不能修改价格';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    $ret = model("order")->where(array('orderid'=>$orderinfo["orderid"],'delflag'=>0))->update($orderinfo);
                    //var_dump($ret);exit;
                    if($ret>0||0===$ret){
                        $code=0;
                        $msg='保存成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-9;
                        $msg='保存失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }

            }
        }
        $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

        echo json_encode($ret);exit;
    }

    public function changepayrate(){
        $orderinfo = array();
        $orderinfo["orderid"] = input("post.orderid",'');
        $orderinfo["pay_rate"] = input("post.pay_rate",'');

        if(0>=$orderinfo['orderid']){
            $code=-1;
            $msg='id不合法';
            $msgtype=MSG_TYPE_WARNING;
        }
        else if(0>=$orderinfo['pay_rate']||$orderinfo['pay_rate']>0.5){
            $code=-1;
            $msg='定金比例不合法';
            $msgtype=MSG_TYPE_WARNING;
        }
        else{

            $orderinfo["updatetime"] = time();

            $isExist=model("order")->where(array('orderid'=>$orderinfo["orderid"],'delflag'=>0))
                ->find();

            if(!$isExist){
                $code=-8;
                $msg='订单不存在';
                $msgtype=MSG_TYPE_DANGER;
            }
            else{
                if($isExist['state']!=1){
                    $code=-8;
                    $msg='此订单状态不能修改价格';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    $ret = model("order")->where(array('orderid'=>$orderinfo["orderid"],'delflag'=>0))->update($orderinfo);
                    //var_dump($ret);exit;
                    if($ret>0||0===$ret){
                        $code=0;
                        $msg='保存成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-9;
                        $msg='保存失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }

            }
        }
        $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

        echo json_encode($ret);exit;
    }
    
    public function alreadypay(){

        $orderid = input("post.orderid",'');

        if(0>=$orderid){
            $code=-1;
            $msg='id不合法';
            $msgtype=MSG_TYPE_WARNING;
        }
        else{


            $isExist=model("order")->where(array('orderid'=>$orderid,'delflag'=>0))
                ->find();

            if(!$isExist){
                $code=-8;
                $msg='订单不存在';
                $msgtype=MSG_TYPE_DANGER;
            }
            else{
                if($isExist['state']!=1){
                    $code=-8;
                    $msg='此订单状态已不是未支付，不能改为已支付';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{

                    $ret = model("order")
                        ->where(array('orderid'=>$orderid,'delflag'=>0,'state'=>1))
                        ->update(['state'=>2,'updatetime'=>$this->curTime]);
                    //var_dump($ret);exit;
                    if($ret>0||0===$ret){

                        $change_price = bcmul($isExist['pay_rate'],$isExist['total_price'],2);

                        $orderdetaillist = model('orderdetail')->alias('a')
                            ->join('ds_tasksignup b' ,'a.signupid=b.signupid')
                            ->where([
                                'a.orderid'=>$orderid,'a.delflag'=>0
                            ])->field('b.userid')->select();

                        foreach($orderdetaillist as $oneorderdetail){

                            $wallet = model('wallet')
                                ->where(['userid'=>$oneorderdetail['userid']])->find();

                            if($wallet){
                                $data = [
                                    'now_money'=>bcadd($wallet['now_money'],$change_price,2),
                                    'updatetime'=>$this->curTime,
                                ];
                                //更新余额
                                model('wallet')
                                    ->where(['userid'=>$oneorderdetail['userid']])->update($data);
                            }
                            else{
                                $data = [
                                    'userid'=>$oneorderdetail['userid'],
                                    'now_money'=>$change_price,
                                    'createtime'=>$this->curTime,
                                    'updatetime'=>$this->curTime,
                                ];
                                //更新余额
                                model('wallet')->insertGetId($data);
                            }

                            //余额变更记录
                            model('walletrecord')->insertGetId(
                                [
                                    'userid'=>$oneorderdetail['userid'],
                                    'objectid'=>$orderid,
                                    'objecttype'=>2,//对象类型1支出2收入3充值
                                    'price'=>$change_price,
                                    'desc'=>'任务：'.$isExist['taskid'].'的支付定金',
                                    'createtime'=>$this->curTime,
                                    'updatetime'=>$this->curTime,
                                    'delflag'=>0,

                                ]
                            );
                        }

                        $code=0;
                        $msg='保存成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-9;
                        $msg='保存失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }

            }
        }
        $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

        echo json_encode($ret);exit;

    }

    public function cancelpay(){
        $orderid = input("post.orderid",'');

        if(0>=$orderid){
            $code=-1;
            $msg='id不合法';
            $msgtype=MSG_TYPE_WARNING;
        }
        else{


            $isExist=model("order")->where(array('orderid'=>$orderid,'delflag'=>0))
                ->find();

            if(!$isExist){
                $code=-8;
                $msg='订单不存在';
                $msgtype=MSG_TYPE_DANGER;
            }
            else{
                if($isExist['state']!=1){
                    $code=-8;
                    $msg='此订单状态已不是未支付,不能取消';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{

                    $ret = model("order")
                        ->where(array('orderid'=>$orderid,'delflag'=>0,'state'=>1))
                        ->update(['state'=>3,'updatetime'=>$this->curTime]);
                    //var_dump($ret);exit;
                    if($ret>0||0===$ret){

                        $orderdetaillist = model('orderdetail')
                            ->where([
                                'orderid'=>$orderid,'delflag'=>0
                            ])->field('signupid')->select();

                        foreach($orderdetaillist as $oneorderdetail){

                            //更新报名状态为最终未选中
                            model('tasksignup')
                                ->where(
                                    ['signupid'=>$oneorderdetail['signupid']]
                                )
                                ->update(['suit_state'=>4,'updatetime'=>$this->curTime]);
                        }

                        $code=0;
                        $msg='保存成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-9;
                        $msg='保存失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }

            }
        }
        $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

        echo json_encode($ret);exit;
    }


    public function tailpay(){
        $orderid = input("post.orderid",'');
        $signupid = input("post.signupid",'');

        if(0>=$orderid||0>=$signupid){
            $code=-1;
            $msg='id不合法';
            $msgtype=MSG_TYPE_WARNING;
        }
        else{


            $isExistOrder=model("order")
                ->where(array('orderid'=>$orderid,'delflag'=>0))
                ->find();
            $isExistOrderDetail=model("orderdetail")
                ->where(array('orderid'=>$orderid,'signupid'=>$signupid,'delflag'=>0))
                ->find();

            $isExisttasksignup=model("tasksignup")
                ->where(array('signupid'=>$signupid,'delflag'=>0))
                ->find();
            if(!$isExistOrder){
                $code=-7;
                $msg='订单不存在';
                $msgtype=MSG_TYPE_DANGER;
            }
            elseif(!$isExistOrderDetail){
                $code=-8;
                $msg='报名订单详情不存在';
                $msgtype=MSG_TYPE_DANGER;
            }
            elseif(!$isExisttasksignup){
                $code=-8;
                $msg='报名不存在';
                $msgtype=MSG_TYPE_DANGER;
            }
            elseif($isExistOrder['state']!=2){
                $code=-8;
                $msg='此订单状态已不是已经支付状态,不能支付尾款';
                $msgtype=MSG_TYPE_DANGER;
            }
            else{

                $ret = model("order")
                    ->where(array('orderid'=>$orderid,'delflag'=>0,'state'=>2))
                    ->update(['state'=>4,'updatetime'=>$this->curTime]);
                //var_dump($ret);exit;
                if($ret>0){

                    //更新报名状态为最终选中
                    model('tasksignup')
                        ->where(
                            ['signupid'=>$signupid]
                        )
                        ->update(['suit_state'=>3,'updatetime'=>$this->curTime]);


                    $orderdetailcount = model('orderdetail')
                        ->where([
                            'orderid'=>$orderid,'delflag'=>0
                        ])->count();
                    $change_price = $isExistOrder['total_price'] - bcmul(bcmul($isExistOrder['pay_rate'],$isExistOrder['total_price'],2),$orderdetailcount,2);



                        $wallet = model('wallet')
                            ->where(['userid'=>$isExisttasksignup['userid']])->find();

                        if($wallet){
                            $data = [
                                'now_money'=>bcadd($wallet['now_money'],$change_price,2),
                                'updatetime'=>$this->curTime,
                            ];
                            //更新余额
                            model('wallet')
                                ->where(['userid'=>$isExisttasksignup['userid']])->update($data);
                        }
                        else{
                            $data = [
                                'userid'=>$isExisttasksignup['userid'],
                                'now_money'=>$change_price,
                                'createtime'=>$this->curTime,
                                'updatetime'=>$this->curTime,
                            ];
                            //更新余额
                            model('wallet')->insertGetId($data);
                        }

                        //余额变更记录
                        model('walletrecord')->insertGetId(
                            [
                                'userid'=>$isExisttasksignup['userid'],
                                'objectid'=>$orderid,
                                'objecttype'=>2,//对象类型1支出2收入3充值
                                'price'=>$change_price,
                                'desc'=>'任务：'.$isExistOrder['taskid'].'的支付定金',
                                'createtime'=>$this->curTime,
                                'updatetime'=>$this->curTime,
                                'delflag'=>0,

                            ]
                        );


                    $code=0;
                    $msg='保存成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-9;
                    $msg='保存失败';
                    $msgtype=MSG_TYPE_DANGER;
                }


            }
        }
        $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

        echo json_encode($ret);exit;
    }



    public function editorder(){
        if(IS_POST){

            $orderinfo = array();
            $orderinfo["orderid"] = input("post.orderid",'');
            if(0>=$orderinfo['orderid']){
                $code=-1;
                $msg='id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!in_array(input("post.state",''),[1,2,3,4])){
                $code=-1;
                $msg='状态不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(input("post.total_price",'')<=0){
                $code=-2;
                $msg='价格不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(input("post.pay_rate",'')<=0||input("post.pay_rate",'')>=1){
                $code=-2;
                $msg='定金比例不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{

                model('order')->startTrans();
                $oldOrderInfo = model('order')->where(['orderid'=>$orderinfo["orderid"],'delflag'=>0])->find();
                if($oldOrderInfo){
                    if($oldOrderInfo["state"]==1){

                        if(input("post.state",'')==1){
                            if(input("post.total_price",'')!= 0 ){
                                $orderinfo["total_price"] = input("post.total_price",'');
                            }
                            if(input("post.pay_rate",'')!= 0 ){
                                $orderinfo["pay_rate"] = input("post.pay_rate",'');
                            }
                        }

                        if(input("post.state",'')==2||input("post.state",'')==3){
                            $orderinfo["state"] = input("post.state",'');
                        }
                        if($orderinfo["state"]==2){
                            $orderdetaillist = model('orderdetail')->alias('a')
                                ->join('ds_tasksignup b' ,'a.signupid=b.signupid')
                                ->where([
                                'orderid'=>$orderinfo["orderid"],'delflag'=>0
                            ])->field('b.userid')->select();

                            foreach($orderdetaillist as $oneorderdetail){

                                $wallet = model('wallet')
                                    ->where(['userid'=>$oneorderdetail['userid']])->find();
                                $change_price = bcmul($oldOrderInfo['total_price'],$oldOrderInfo['pay_rate'],2);
                                if($wallet){
                                    $data = [
                                        'now_money'=>bcadd($wallet['now_money'],$change_price,2),
                                        'updatetime'=>$this->curTime,
                                    ];
                                    //更新余额
                                    $walletUpdate = model('wallet')
                                        ->where(['userid'=>$oneorderdetail['userid']])->update($data);
                                }
                                else{
                                    $data = [
                                        'userid'=>$this->curUserInfo['userid'],
                                        'now_money'=>$change_price,
                                        'createtime'=>$this->curTime,
                                        'updatetime'=>$this->curTime,
                                    ];
                                    //更新余额
                                    $walletUpdate = model('wallet')->insertGetId($data);
                                }

                                //余额变更记录
                                model('walletrecord')->insertGetId(
                                    [
                                        'userid'=>$this->curUserInfo['userid'],
                                        'objectid'=>$oldOrderInfo['orderid'],
                                        'objecttype'=>2,//对象类型1支出2收入3充值
                                        'price'=>$change_price,
                                        'desc'=>'任务：'.$oldOrderInfo['taskid'].'的支付定金',
                                        'createtime'=>$this->curTime,
                                        'updatetime'=>$this->curTime,
                                        'delflag'=>0,

                                    ]
                                );
                            }

                        }
                        if($orderinfo["state"]==3){
                            $orderdetaillist = model('orderdetail')->alias('a')
                                ->join('ds_tasksignup  b' ,'a.signupid=b.signupid')
                                ->where([
                                    'orderid'=>$orderinfo["orderid"],'delflag'=>0
                                ])->field('b.userid,b.signupid')->select();

                            foreach($orderdetaillist as $oneorderdetail){

                                //更新报名状态
                                model('tasksignup')
                                    ->where(
                                        ['signupid'=>$oneorderdetail['signupid']]
                                    )
                                    ->update(['suit_state'=>4,'updatetime'=>$this->curTime]);
                            }
                        }


                    }
                    elseif($oldOrderInfo["state"]==2){
                        unset($orderinfo["total_price"]);
                        unset($orderinfo["pay_rate"]);
                        if(input("post.state",'')==3){
                            $orderinfo["state"] = input("post.state",'');

                            $orderdetaillist = model('orderdetail')->alias('a')
                                ->join('ds_tasksignup  b' ,'a.signupid=b.signupid')
                                ->where([
                                    'orderid'=>$orderinfo["orderid"],'delflag'=>0
                                ])->field('b.userid,b.signupid')->select();

                            foreach($orderdetaillist as $oneorderdetail){

                                //更新报名状态
                                model('tasksignup')
                                    ->where(
                                        ['signupid'=>$oneorderdetail['signupid']]
                                    )
                                    ->update(['suit_state'=>4,'updatetime'=>$this->curTime]);
                            }
                        }
                    }
                    elseif($oldOrderInfo["state"]==3){

                    }
                    else{

                    }
                }

                $orderinfo["updatetime"] = time();
                //更新订单
                model('order')
                ->where(['order'=>$orderinfo["orderid"] ])->update($orderinfo);


                $code=0;
                $msg='保存成功';
                $msgtype=MSG_TYPE_SUCCESS;

            }
            model('order')->commit();

            $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{
            $taskid = input("get.taskid",0);
            if(!($taskid>0)){
                echo '参数错误';exit;
            }
            $orderinfo=model('Task')->where(array('taskid'=>$taskid,'delflag'=>0))->find();
            if(!$orderinfo){
                echo '记录已被删除或不存在';exit;
            }
            $orderinfo['desc']=htmlspecialchars_decode($orderinfo['desc']);
            $orderinfo['limittime']=date('Y-m-d',$orderinfo['limittime']);
            $this->assign('$orderinfo',$orderinfo);
            //var_dump($orderinfo);exit;
            echo $this->fetch();
        }
    }


}