<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 前台用户管理
 * Enter description here ...
 * @author jogle
 * @created on 20170903
 */
class Userbase extends Admin{

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

            $userid = input('post.userid','');  //操作人ID
            if($userid !='')
            {
                $where['a.userid'] = $userid;
            }
            $tel = input('post.tel','');  //操作人ID
            if($tel !='')
            {
                $where['a.tel'] = $tel;
            }

            $nickname = input('post.nickname','');
            if($nickname !='')
            {
                $where['b.nickname'] = ['like','%'.$nickname.'%'];
            }
            $sex = input('post.sex','-1');;  //
            if($sex!=-1 )
            {
                $where['b.sex'] = $sex;
            }
            $verify_state = input('post.verify_state','-1');;  //
            if($verify_state!=-1 )
            {
                $where['b.verify_state'] = $verify_state;
            }

            $status = input('post.status','-1');;  //
            if($status!=-1 )
            {
                $where['b.status'] = $status;
            }



            $createstarttime = input('post.createstarttime','');  //开始时间
            $createendtime = input('post.createendtime','');  //结束时间
            if('' != $createstarttime )
            {
                if('' != $createendtime ){
                    $where['b.createtime'] = array('between',array(($createstarttime),($createendtime)));
                }
                else{
                    $where['b.createtime'] = array('egt',($createstarttime));
                }
            }
            else{
                if('' != $createendtime )
                {
                    $where['b.createtime'] = array('elt',($createendtime));
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
                    $order=' a.userid '.$ordercolumn[0]['dir'].' ';
                }
                else if(1==$ordercolumn[0]['column']){
                    $order=' a.tel '.$ordercolumn[0]['dir'].' ';
                }
                else if(3==$ordercolumn[0]['column']){
                    $order=' b.nickname '.$ordercolumn[0]['dir'].' ';
                }
                else if(4==$ordercolumn[0]['column']){
                    $order=' b.sex '.$ordercolumn[0]['dir'].' ';
                }
                else if(5==$ordercolumn[0]['column']){
                    $order=' b.verify_state '.$ordercolumn[0]['dir'].' ';
                }
                else if(6==$ordercolumn[0]['column']){
                    $order=' b.status '.$ordercolumn[0]['dir'].' ';
                }
                else if(7==$ordercolumn[0]['column']){
                    $order=' b.createtime '.$ordercolumn[0]['dir'].' ';
                }
            }
            //$where['delflag']=0;
            $userlist=model('Userbase')->alias('a')
                ->join('ds_wallet c',' a.userid = c.userid ','LEFT')
                ->join('ds_userinfo b',' a.userid = b.userid ')->where($where)->limit($start,$pagesize)
                ->order($order)->field('a.tel,b.*,c.now_money')->select();

            //var_dump(model('Userbase')->getLastSql());exit;
            $usercount=model('Userbase')->alias('a')
                ->join('ds_userinfo b','a.userid=b.userid')->where($where)->count();
            $recordsfiltered=$usercount;
            $recordstotal=$usercount;
            //var_dump($userlist);exit;
            $data=array();
            $this->getAllControl();
            foreach($userlist as $k=>$v){
                $data[$k][]=$v['userid'];
                $data[$k][]=$v['tel'];
                //$data[$k][]=$v['userid'];
                if($v['avatar']){
                    if(substr($v['avatar'],0,4)=='http'){
                        $newimgurl=$v['avatar'];
                    }
                    else{
                        $newimgurl=$this->allControl['avatar_url'].$v['avatar'];
                    }
                    $picturestr=
                        '<a class="fancybox" style="margin:0 3px" href="'.$newimgurl .
                        '" data-fancybox-group="userpicurl" ><img style="margin:2px 0;max-width:36px;max-height:36px;" src="'.$newimgurl.'" /></a>'.
                        '';
                    $data[$k][]=$picturestr;
                        //'<a id="adduserimg_'.$v['userid'].'" class="btn blue btn-xs" href="javascript:;" onclick="adduserimg('.$v['userid'].')">
                        //    <i class="fa fa-12px fa-edit"></i>修改
                        //    </a>';
                }
                else{
                    $data[$k][]='-';
                }

                $data[$k][]=$v['nickname'];
                if($v['sex']==1){
                    $sex = '男';
                }
                elseif($v['sex']==2){
                    $sex = '女';
                }
                else{
                    $sex='未知';
                }
                $data[$k][]=$sex;
                if($v['verify_state']==1){
                    $verify_state = '设计师';
                }
                elseif($v['verify_state']==2){
                    $verify_state = '商家';
                }
                else{
                    $verify_state='未认证';
                }
                $data[$k][]=$verify_state;
                if($v['status']==1){
                    $status = '正常';
                    $status_btn = '<a title="禁用" id="channge_status_'.$v['userid'].'" class="btn red btn-xs" href="javascript:;" onclick="changestatus('.$v['userid'].',2)"><i class="fa fa-12px fa-edit"></i>禁用</a>';
                }
                else{
                    $status='禁用';
                    $status_btn = '<a title="启用" id="channge_status_'.$v['userid'].'" class="btn green btn-xs" href="javascript:;" onclick="changestatus('.$v['userid'].',1)"><i class="fa fa-12px fa-edit"></i>启用</a>';
                }
                $data[$k][]=$status;

                $data[$k][]=$v['now_money'];

                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.
                    '<a title="查看" id="view_user_'.$v['userid'].'" class="btn default btn-xs" href="javascript:;" onclick="viewuserbase('.$v['userid'].')"><i class="fa fa-12px fa-edit"></i>查看</a>'.
                    $status_btn;
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
     * 添加用户
     */
    public function addUserbase(){
        if(IS_POST){
            $userinfo = array();
            $userinfo["title"] = input("post.title",'');
            $userinfo["price"] = input("post.price",'');
            $userinfo["limittime"] = strtotime(input("post.limittime",''));
            $userinfo["desc"] = input("post.desc",'');

            if(''==$userinfo['title']){
                $code=-1;
                $msg='标题不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$userinfo['price']){
                $code=-2;
                $msg='价格不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$userinfo['limittime']){
                $code=-4;
                $msg='截止时间不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$userinfo['desc']){
                $code=-5;
                $msg='描述内容不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{


                $userinfo["createtime"] = time();
                $userinfo["updatetime"] = time();
                $userinfo["delflag"] = 0;
                $isExist=model("user")->where(array('title'=>$userinfo["title"],'delflag'=>0))
                    ->find();

                if($isExist){
                    $code=-11;
                    $msg='相同标题已经存在';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    $userid = model("user")->insertGetId($userinfo);
                    if($userid>0){
                        //$unique_id = MD5(C('PAGE_UNIQUE_CODE').$userinfo["title"].time());
                        //model("Page")->editbywhere(array('userid'=>$userid),array('unique_id'=>$unique_id));
                        $code=0;
                        $msg='添加成功';
                        $msgtype=MSG_TYPE_SUCCESS;
                    }
                    else{
                        $code=-12;
                        $msg='添加失败';
                        $msgtype=MSG_TYPE_DANGER;
                    }
                }
            }
            $ret=array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{
            echo $this->fetch();
        }
    }



    /**
     * 修改用户
     */
    public function changestatus(){
        if(IS_POST){

            $userinfo = array();
            $userinfo["userid"] = input("post.userid",'');
            $userinfo["status"] = input("post.status",'');

            if(0>=$userinfo['userid']){
                $code=-1;
                $msg='id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$userinfo['status']){
                $code=-5;
                $msg='状态不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{

                $userinfo["updatetime"] = time();


                $ret = model("Userinfo")
                    ->where(array('userid'=>$userinfo["userid"]))
                    ->update($userinfo);
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
            $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{

            echo $this->fetch();
        }
    }



    /**
     * 查看
     */
    public function viewuserbase(){
        $userid = input("get.userid",'0');

        $userbase=model('userbase')->where(array('userid'=>$userid))->find();
        $userinfo=model('userinfo')->where(array('userid'=>$userid))->find();

        if(substr($userinfo['avatar'],0,4)=='http'){
            $newimgurl=$userinfo['avatar'];
        }
        else{
            $newimgurl=$this->allControl['avatar_url'].$userinfo['avatar'];
        }

        $userinfo['avatar']=$newimgurl;

        $this->assign('userbase',$userbase);
        $this->assign('userinfo',$userinfo);
        echo $this->fetch();
    }



}