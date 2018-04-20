<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 提现管理
 * Enter description here ...
 * @author jogle
 * @created on 20180126
 */
class Walletcash extends Admin{

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

            $walletcashid = input('post.walletcashid','');
            if($walletcashid !='')
            {
                $where['walletcashid'] = $walletcashid;
            }
            $userid = input('post.userid','');
            if($userid !='')
            {
                $where['userid'] = $userid;
            }
            $contact= input('post.contact','');
            if($contact !='')
            {
                $where['contact'] = ['like','%'.$contact.'%'];
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
                    $order=' walletcashid '.$ordercolumn[0]['dir'].' ';
                }
                elseif(1==$ordercolumn[0]['column']){
                    $order=' userid '.$ordercolumn[0]['dir'].' ';
                }
                else if(2==$ordercolumn[0]['column']){
                    $order=' money '.$ordercolumn[0]['dir'].' ';
                }
                else if(4==$ordercolumn[0]['column']){
                    $order=' state '.$ordercolumn[0]['dir'].' ';
                }
                else if(5==$ordercolumn[0]['column']){
                    $order=' createtime '.$ordercolumn[0]['dir'].' ';
                }
            }
            $where['delflag']=0;
            $userlist=model('Walletcash')->where($where)->limit($start,$pagesize)
                ->order($order)->select();
            //var_dump(model('Walletcash')->getLastSql());exit;
            $usercount=model('Walletcash')->where($where)->count();
            $recordsfiltered=$usercount;
            $recordstotal=$usercount;
            //var_dump($userlist);exit;
            $data=array();
            $this->getAllControl();
            foreach($userlist as $k=>$v){
                $data[$k][]=$v['walletcashid'];
                $data[$k][]=$v['userid'];
                $data[$k][]=$v['money'];
                $data[$k][]=$v['contact'];


                if($v['state']==1){
                    $editbtn = '<a title=""  class=" btn green btn-xs" href="javascript:;" onclick="editwalletcash('.$v['walletcashid'].')"><i class="fa fa-12px fa-edit"></i>修改</a>';
                    $data[$k][]='新创建<br/>';
                }
                elseif($v['state']==2){
                    $editbtn = '';
                    $data[$k][]='提现成功';
                }
                elseif($v['state']==3){
                    $editbtn = '';
                    $data[$k][]='提现失败';
                }
                else{
                    $editbtn = '';
                    $data[$k][]='-';
                }
                $data[$k][]=$v['desc'];

                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.
                    $editbtn.
                    //'<a title="查看报名" id="signuplist_'.$v['walletcashid'].'" class="btn green btn-xs" href="javascript:;" onclick="signuplist('.$v['walletcashid'].',2)"><i class="fa fa-12px fa-search"></i>查看报名</a>'.
                    //'<a title="修改" id="editwalletcash_'.$v['walletcashid'].'" class="btn green btn-xs" href="javascript:;" onclick="editwalletcash('.$v['walletcashid'].',)"><i class="fa fa-12px fa-edit"></i>修改</a>';
                    '</div>';
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
     * 修改提现状态
     */
    public function editwalletcash(){
        if(IS_POST){

            $walletcashinfo = array();
            $walletcashinfo["walletcashid"] = input("post.walletcashid",'');
            $walletcashinfo["state"] = input("post.state",'');
            $walletcashinfo["desc"] = input("post.desc",'');

            if(0>=$walletcashinfo['walletcashid']){
                $code=-1;
                $msg='id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!in_array($walletcashinfo['state'],[2,3])){
                $code=-5;
                $msg='状态不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{

                $walletcashinfo["updatetime"] = time();
                $isExist=model("Walletcash")->where(array('walletcashid'=>$walletcashinfo["walletcashid"],'state'=>1,'delflag'=>0))
                    ->find();

                if(!$isExist){
                    $code=-8;
                    $msg='已经不存在或状态已修改';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    model('Walletcash')->startTrans();
                    $ret = model("Walletcash")->where(array('walletcashid'=>$walletcashinfo["walletcashid"],'delflag'=>0))
                        ->update($walletcashinfo);
                    //var_dump($ret);exit;
                    if($ret>0||0===$ret){
                        if($walletcashinfo["state"]==2){
                            //提现成功  直接插入余额变更记录
                            model('walletrecord')->insertGetId(
                                [
                                    'userid'=>$isExist['userid'],
                                    'objectid'=>$walletcashinfo["walletcashid"],
                                    'objecttype'=>3,//对象类型1支出2收入3提现
                                    'price'=>$isExist['money'],
                                    'desc'=>'提现：'.$isExist['money'],
                                    'createtime'=>$this->curTime,
                                    'updatetime'=>$this->curTime,
                                    'delflag'=>0,

                                ]
                            );

                        }
                        elseif($walletcashinfo["state"]==3){
                            //提现失败需要加会余额
                            $wallet = model('wallet')
                                ->where(['userid'=>$isExist['userid']])->find();

                            if($wallet){
                                $savewallet = [
                                    'now_money'=>bcadd($wallet['now_money'],$isExist['money'],2),
                                    'updatetime'=>$this->curTime,
                                ];
                                //更新余额
                                model('wallet')
                                    ->where(['userid'=>$isExist['userid']])->update($savewallet);
                            }
                            else{
                                $data = [
                                    'userid'=>$isExist['userid'],
                                    'now_money'=>$isExist['money'],
                                    'createtime'=>$this->curTime,
                                    'updatetime'=>$this->curTime,
                                ];
                                //更新余额
                                model('wallet')->insertGetId($data);
                            }

                        }
                        model('Walletcash')->commit();
                        $code=0;
                        $msg='保存成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        model('Walletcash')->rollback();
                        $code=-9;
                        $msg='保存失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }
            }
            $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{
            $walletcashid = input("get.walletcashid",0);
            if(!($walletcashid>0)){
                echo '参数错误';exit;
            }
            $walletcashinfo=model('Walletcash')->where(array('walletcashid'=>$walletcashid,'delflag'=>0))->find();
            if(!$walletcashinfo){
                echo '记录已被删除或不存在';exit;
            }
            $this->assign('walletcashinfo',$walletcashinfo);

            echo $this->fetch();
        }
    }




}