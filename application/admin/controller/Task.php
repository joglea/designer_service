<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 后台日志管理
 * Enter description here ...
 * @author jogle
 * @created on 20170813
 */
class Task extends Admin{

	/**
	 *  用户列表
	 * @param number $in 是否内部调用 0不是  1是
     * @return string
     */
	public function index($view='index'){
        //var_dump($this->rolelist);exit;
        $this->setPageHeaderRightButton(
            array(array(
                'class'=>'btn btn-fit-height grey-salt',
                'onclick'=>"onclick='addtask()'",
                'icon'=>"<i class='fa fa-plus'></i>",
                'text'=>'添加'),
                array(
                    'class'=>'btn btn-fit-height grey-salt refresh',
                    'icon'=>"<i class='fa fa-plus'></i>",
                    'text'=>'刷新')
            )
        );

        if(IS_AJAX&&$view=='index'){
            $where = array();

            $title = input('post.title','');  //操作人ID
            if($title !='')
            {
                $where['title'] = ['like','%'.$title.'%'];
            }
            $price = input('post.price','0');;  //
            if($price>0 )
            {
                $where['price'] = $price;
            }
            $limitstarttime = input('post.limitstarttime','');  //开始时间
            $limitendtime = input('post.limitendtime','');  //结束时间
            if('' != $limitstarttime )
            {
                if('' != $limitendtime ){
                    $where['limittime'] = array('between',array(($limitstarttime),($limitendtime)));
                }
                else{
                    $where['limittime'] = array('egt',($limitstarttime));
                }
            }
            else{
                if('' != $limitendtime )
                {
                    $where['limittime'] = array('elt',($limitendtime));
                }
            }

            $desc = input('post.desc','');  //描述
            if('' != $desc )
            {
                $where['desc']=array('like','%'.$desc.'%');
            }

            $state = input('post.state','-1'); //
            if('-1' != $state )
            {
                $where['state']=$state;
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
                    $order=' taskid '.$ordercolumn[0]['dir'].' ';
                }
                else if(2==$ordercolumn[0]['column']){
                    $order=' title '.$ordercolumn[0]['dir'].' ';
                }
                else if(3==$ordercolumn[0]['column']){
                    $order=' price '.$ordercolumn[0]['dir'].' ';
                }
                else if(4==$ordercolumn[0]['column']){
                    $order=' limittime '.$ordercolumn[0]['dir'].' ';
                }
                else if(6==$ordercolumn[0]['column']){
                    $order=' state '.$ordercolumn[0]['dir'].' ';
                }
                else if(7==$ordercolumn[0]['column']){
                    $order=' check_state '.$ordercolumn[0]['dir'].' ';
                }
                else if(10==$ordercolumn[0]['column']){
                    $order=' createtime '.$ordercolumn[0]['dir'].' ';
                }
            }
            $where['delflag']=0;
            $tasklist=model('Task')->where($where)->limit($start,$pagesize)
                ->order($order)->select();
            //var_dump($where,$tasklist);exit;
            $taskcount=model('Task')->where($where)->count();
            $recordsfiltered=$taskcount;
            $recordstotal=$taskcount;
            //var_dump($this->allControl);exit;
            $data=array();
            foreach($tasklist as $k=>$v){
                $data[$k][]=$v['taskid'];
                if($v['task_pic']){
                    if(substr($v['task_pic'],0,4)=='http'){
                        $newimgurl=$v['task_pic'];
                    }
                    else{
                        $newimgurl=$this->allControl['task_image_url'].$v['task_pic'];
                    }
                    $picturestr=
                        '<a class="fancybox" style="margin:0 3px" href="'.$newimgurl .
                        '" data-fancybox-group="taskpicurl" ><img style="margin:2px 0;max-width:36px;max-height:36px;" src="'.$newimgurl.'" /></a>'.
                        '';
                    $data[$k][]=$picturestr.
                        '<a id="addtaskimg_'.$v['taskid'].'" class="btn blue btn-xs" href="javascript:;" onclick="addtaskimg('.$v['taskid'].')">
                            <i class="fa fa-12px fa-edit"></i>修改
                            </a>';
                }
                else{
                    $data[$k][]='<a id="addtaskimg_'.$v['taskid'].'" class="btn blue btn-xs" href="javascript:;" onclick="addtaskimg('.$v['taskid'].')">
                            <i class="fa fa-12px fa-plus"></i>添加
                            </a>';
                }

                $data[$k][]=$v['title'];
                $data[$k][]=$v['price'];
                $data[$k][]=date('Y-m-d H:i:s',$v['limittime']);
                //$data[$k][]=$v['desc'];
                $data[$k][]='<a class="btn red btn-xs" href="javascript:;" onclick="viewtask('.$v['taskid'].')" title="正文内容">查看</a>';
                //任务状态 1新建接受报名 2停止报名 3订金预付 4尾款支付 5关闭
                if($v['state']==1){
                    $state= '新建接受报名';
                }
                elseif($v['state']==2){
                    $state= '停止报名';
                }
                elseif($v['state']==3){
                    $state= '订金预付';
                }
                elseif($v['state']==4){
                    $state= '尾款支付';
                }
                elseif($v['state']==5){
                    $state= '关闭';
                }
                else{
                    $state='-';
                }
                $data[$k][]=$state;

                if($v['check_state']==1){
                    $checkstate= '未审核';
                }
                elseif($v['check_state']==2){
                    $checkstate= '审核通过';
                }
                elseif($v['check_state']==3){
                    $checkstate= '审核不通过';
                }
                else{
                    $checkstate='-';
                }

                $data[$k][]=$checkstate;
                $data[$k][]=$v['check_desc'];
                $data[$k][]=date('Y-m-d H:i:s',$v['createtime']);
                $data[$k][]='<div style="text-align:center;">'.
                    '<a title="修改" id="edit_task_'.$v['taskid'].'" class="btn btn-warning btn-xs" href="javascript:;" onclick="edittask('.$v['taskid'].')"><i class="fa fa-12px fa-edit"></i>修改</a>'.
                    '<a id="remove_task_'.$v['taskid'].'" class="btn btn-danger btn-xs" href="javascript:;" onclick="removetask('.$v['taskid'].')"><i class="fa fa-12px fa-trash-o"></i>删除</a></div>';

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
     * 查看屏蔽原因
     */
    public function viewtask(){
        $taskid = input("get.taskid",'0');
        $taskinfo=model('Task')->where(array('taskid'=>$taskid))->find();
        $desc = $taskinfo?htmlspecialchars_decode($taskinfo['desc']):'';
        $this->assign('desc',$desc);
        echo $this->fetch();
    }

    /**
     * 添加任务
     */
    public function addTask(){
        if(IS_POST){
            $taskinfo = array();
            $taskinfo["title"] = input("post.title",'');
            $taskinfo["price"] = input("post.price",'');
            $taskinfo["limittime"] = strtotime(input("post.limittime",''));
            $taskinfo["desc"] = input("post.desc",'');

            if(''==$taskinfo['title']){
                $code=-1;
                $msg='标题不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$taskinfo['price']){
                $code=-2;
                $msg='价格不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$taskinfo['limittime']){
                $code=-4;
                $msg='截止时间不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$taskinfo['desc']){
                $code=-5;
                $msg='描述内容不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{


                $taskinfo["createtime"] = time();
                $taskinfo["updatetime"] = time();
                $taskinfo["delflag"] = 0;
                $isExist=model("task")->where(array('title'=>$taskinfo["title"],'delflag'=>0))
                    ->find();

                if($isExist){
                    $code=-11;
                    $msg='相同标题已经存在';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    $taskid = model("task")->insertGetId($taskinfo);
                    if($taskid>0){
                        model("taskdata")->insertGetId(['taskid'=>$taskid]);
                        //$unique_id = MD5(C('PAGE_UNIQUE_CODE').$taskinfo["title"].time());
                        //model("Page")->editbywhere(array('taskid'=>$taskid),array('unique_id'=>$unique_id));
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
     * 修改任务
     */
    public function edittask(){
        if(IS_POST){

            $taskinfo = array();
            $taskinfo["taskid"] = input("post.taskid",'');
            $taskinfo["title"] = input("post.title",'');
            $taskinfo["price"] = input("post.price",'');
            $taskinfo["limittime"] = strtotime(input("post.limittime",''));
            $taskinfo["desc"] = input("post.desc",'');
            $taskinfo["check_state"] = input("post.check_state",'');
            $taskinfo["check_desc"] = input("post.check_desc",'');

            if(0>=$taskinfo['taskid']){
                $code=-1;
                $msg='id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$taskinfo['title']){
                $code=-1;
                $msg='标题不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$taskinfo['price']){
                $code=-2;
                $msg='价格不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$taskinfo['limittime']){
                $code=-4;
                $msg='截止时间不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$taskinfo['desc']){
                $code=-5;
                $msg='描述内容不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{

                $taskinfo["updatetime"] = time();

                $isExist=model("Task")->where(array('taskid'=>array('neq',$taskinfo["taskid"]),'title'=>$taskinfo["title"],'delflag'=>0))
                    ->find();

                if($isExist){
                    $code=-8;
                    $msg='标题已经存在';
                    $msgtype=MSG_TYPE_DANGER;
                }
                else{
                    $ret = model("Task")->where(array('taskid'=>$taskinfo["taskid"],'delflag'=>0))->update($taskinfo);
                    //var_dump($ret);exit;
                    if($ret>0||0===$ret){
                        $taskdata = model("taskdata")->where(['taskid'=>$taskinfo["taskid"]])->find();
                        if(!$taskdata){
                            model("taskdata")->insertGetId(['taskid'=>$taskinfo["taskid"]]);
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
            $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{
            $taskid = input("get.taskid",0);
            if(!($taskid>0)){
                echo '参数错误';exit;
            }
            $taskinfo=model('Task')->where(array('taskid'=>$taskid,'delflag'=>0))->find();
            if(!$taskinfo){
                echo '记录已被删除或不存在';exit;
            }
            $taskinfo['desc']=htmlspecialchars_decode($taskinfo['desc']);
            $taskinfo['limittime']=date('Y-m-d',$taskinfo['limittime']);
            $this->assign('taskinfo',$taskinfo);
            //var_dump($taskinfo);exit;
            echo $this->fetch();
        }
    }

    /**
     * 删除任务
     */
    public function removetask(){
        $taskid = input("post.taskid",0);
        if($taskid>0){
            $isExist=model('Task')->where(array('taskid'=>$taskid,'delflag'=>0))->find();
            if($isExist){
                $ret=model("Task")->where(array('taskid'=>$taskid,'delflag'=>0))
                ->update(['delflag'=>1,'updatetime'=>time()]);
                if($ret>0||$ret===0){
                    $code=0;
                    $msg='删除成功';
                    $msgtype=MSG_TYPE_SUCCESS;
                }
                else{
                    $code=-1;
                    $msg='删除失败';
                    $msgtype=MSG_TYPE_DANGER;
                }
            }
            else{
                $code=-2;
                $msg='您要删除的记录不存在或已被删除';
                $msgtype=MSG_TYPE_WARNING;
            }
        }
        else{
            $code=-3;
            $msg='参数错误';
            $msgtype=MSG_TYPE_DANGER;
        }
        $ret= array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);
        echo json_encode($ret);exit;
    }

    /**
     * 添加商品图片
     */
    public function addtaskimg(){
        if(IS_POST){
            $taskimginfo = array();
            $taskimginfo["task_id"] = input("post.task_id",'');
            //var_dump($_FILES);exit;
            $initialpreview= [];
            $initialpreviewconfig = [];

            if(!$_FILES||''==$taskimginfo['task_id']){
                $msg='不合法';
            }
            elseif(''==$_FILES['task_pic']){
                $msg='图片不能为空';
            }
            else {

                if ((($_FILES["task_pic"]["type"][0] == "image/gif")
                        || ($_FILES["task_pic"]["type"][0] == "image/jpeg")
                        || ($_FILES["task_pic"]["type"][0] == "image/pjpeg")|| ($_FILES["task_pic"]["type"][0] == "image/png"))
                    && ($_FILES["task_pic"]["size"][0] < 10000000)
                ) {
                    if ($_FILES["task_pic"]["error"][0] > 0) {
                        $code    = - 11;
                        $msg     = '上传错误' . $_FILES["task_pic"]["error"][0];
                        $msgtype = MSG_TYPE_DANGER;
                    } else {
                        $imgname = $_FILES["task_pic"]["name"][0];
                        $imgurl  = date ('YmdHis') . $_FILES["task_pic"]["name"][0];

                        move_uploaded_file ($_FILES["task_pic"]["tmp_name"][0] ,
                            config('TASK_UPLOAD_IMAGE_DIR') . $imgurl);


                       /*//上传七牛
                       if($_FILES["task_pic"]["type"][0] != "image/gif"){
                            $maxwidth=800;
                            $maxheight=1500;
                            //var_dump(33);exit;
                            $show_pic_scal=$this->show_pic_scal($maxwidth, $maxheight, C('UPLOAD_IMAGE') . $imgurl);
                            $this->resize(C('UPLOAD_IMAGE').$imgurl,$show_pic_scal[0],$show_pic_scal[1]);
                        }
                        $qiniuimgurl=$this->uploadtoqiniu(C('UPLOAD_IMAGE').$imgurl,$imgurl);
                        if($qiniuimgurl){
                            $newimgurl=$qiniuimgurl ;
                        }
                        else{
                            $newimgurl="http://" . C ('STATIC_HOST') . '/'.C('UPLOAD_IMAGE') . $imgurl ;
                        }*/
                        $newimgurl=$this->allControl['task_image_url']. $imgurl ;


                        $taskimgid= model("task")->where (array('taskid'=>$taskimginfo["task_id"]))
                            ->update(array('task_pic'=>$imgurl,'updatetime'=>time()));


                        $initialpreview       = $newimgurl;
                        $initialpreviewconfig = array (
                            array(
                                "caption"=> $newimgurl,
                                "width"=> '120px',
                                "url"=> 'removetaskimg', // server delete action
                                "key"=>100,
                                "extra"=>array("taskid"=>$taskimginfo["task_id"])
                            )
                        );
                        $msg     = '上传成功' ;
                    }
                } else {
                    $msg     = '上传错误Invalid file';
                }
            }


            $ret=array(
                'error'=>$msg,
                'initialPreview'=>$initialpreview,
                'initialPreviewConfig'=>$initialpreviewconfig
            );
            echo json_encode($ret);exit;

        }
        else{

            $taskid=input('get.taskid');
            $taskinfo=model('task')->where(array('taskid'=>$taskid,'delflag'=>0))
                ->find();
            $initialpreview=array();
            $initialpreviewconfig=array();

            if($taskinfo&&$taskinfo['task_pic']!=''){
                if(substr($taskinfo['task_pic'],0,4)=='http'){
                    $newimgurl=$taskinfo['task_pic'];
                }
                else{
                    $newimgurl=$this->allControl['task_image_url'].$taskinfo['task_pic'];
                }
                //$initialpreview[]='<img style="width: auto;height: 160px;" src="'.$imgurl.'" class="kv-preview-data file-preview-image" alt="'.$onetaskimg['task_pic'].'" title="'.$onetaskimg['task_pic']. '">';
                $initialpreview[]=$newimgurl;
                $initialpreviewconfig[]=array(
                    "caption"=> $newimgurl,
                    "width"=> '120px',
                    "url"=> 'removetaskimg', // server delete action
                    "key"=>100,
                    "extra"=>array("taskid"=>$taskid)
                );

            }


            $this->assign('initialpreview',json_encode($initialpreview));
            $this->assign('initialpreviewconfig',json_encode($initialpreviewconfig));


            $this->assign('taskid',$taskid);
            //$this->defaultsort=model('Product')->getdefaultsort()+1;
            echo $this->fetch();exit;
        }
    }



    /**
     * 删除图片
     */
    public function removetaskimg(){
        $taskid = input("post.taskid",0);
        if($taskid>0){
            $ret=model("task")->where(array('taskid'=>$taskid,'delflag'=>0))
                ->update(array('task_pic'=>'','updatetime'=>time()));
            if($ret>0||$ret===0){

                $msg='删除成功';
            }
            else{
                $msg='删除失败';
            }
        }
        else{
            $msg='参数错误';
        }

        $initialpreview       = array (
        );
        $initialpreviewconfig = array (
        );

        $ret=array(

        );
        echo json_encode($ret);exit;

    }



}