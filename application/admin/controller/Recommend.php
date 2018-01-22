<?php
namespace app\admin\controller;

use app\common\controller\Admin;
/**
 * 推荐管理
 * Enter description here ...
 * @author jogle
 * @created on 20170813
 */
class Recommend extends Admin{

	/**
	 *  推荐列表
	 * @param number $in 是否内部调用 0不是  1是
     * @return string
     */
	public function index($view='index'){
        //var_dump($this->rolelist);exit;
        $this->setPageHeaderRightButton(
            array(array(
                'class'=>'btn btn-fit-height grey-salt',
                'onclick'=>"onclick='recommendadd()'",
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

            $sort = input('post.sort','');;  //
            if($sort>0 )
            {
                $where['sort'] = $sort;
            }
            $object_value = input('post.object_value','');;  //
            if($object_value )
            {
                $where['object_value'] = $object_value;
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
                    $where['create_time'] = array('between',array(($createstarttime),($createendtime)));
                }
                else{
                    $where['create_time'] = array('egt',($createstarttime));
                }
            }
            else{
                if('' != $createendtime )
                {
                    $where['create_time'] = array('elt',($createendtime));
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
                    $order=' recommend_id '.$ordercolumn[0]['dir'].' ';
                }
                else if(2==$ordercolumn[0]['column']){
                    $order=' sort '.$ordercolumn[0]['dir'].' ';
                }
                else if(3==$ordercolumn[0]['column']){
                    $order=' object_value '.$ordercolumn[0]['dir'].' ';
                }
                else if(4==$ordercolumn[0]['column']){
                    $order=' state '.$ordercolumn[0]['dir'].' ';
                }
                else if(5==$ordercolumn[0]['column']){
                    $order=' create_time '.$ordercolumn[0]['dir'].' ';
                }
            }
            $where['del_flag']=0;
            $recommendlist=model('Recommend')->where($where)->limit($start,$pagesize)
                ->order($order)->select();
            //var_dump($where,$recommendlist);exit;
            $recommendcount=model('Recommend')->where($where)->count();
            $recordsfiltered=$recommendcount;
            $recordstotal=$recommendcount;

            //var_dump($this->allControl);exit;
            $data=array();
            foreach($recommendlist as $k=>$v){
                $data[$k][]=$v['recommend_id'];
                if(substr($v['image_url'],0,4)=='http'){
                    $newimgurl=$v['image_url'];
                }
                else{
                    $newimgurl=$this->allControl['recommend_image_url'].$v['image_url'];
                }
                $picturestr=
                    '<a class="fancybox" style="margin:0 3px" href="'.$newimgurl .
                    '" data-fancybox-group="recommendpicurl" ><img style="margin:2px 0;max-width:36px;max-height:36px;" src="'.$newimgurl.'" /></a>'.
                    '';
                $data[$k][]=$picturestr;


                $data[$k][]=$v['sort'];
                $data[$k][]=$v['object_value'];
                //上架状态
                if($v['state']==1){
                    $state= '未上架';
                }
                elseif($v['state']==2){
                    $state= '已上架';
                }
                else{
                    $state='-';
                }
                $data[$k][]=$state;

                $data[$k][]=date('Y-m-d H:i:s',$v['create_time']);
                $data[$k][]='<div style="text-align:center;">'.
                    '<a title="修改" id="edit_recommend_'.$v['recommend_id'].'" class="btn btn-warning btn-xs" href="javascript:;" onclick="recommendedit('.$v['recommend_id'].')"><i class="fa fa-12px fa-edit"></i>修改</a>'.
                    '<a id="remove_recommend_'.$v['recommend_id'].'" class="btn btn-danger btn-xs" href="javascript:;" onclick="recommendremove('.$v['recommend_id'].')"><i class="fa fa-12px fa-trash-o"></i>删除</a></div>';

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
     * 添加推荐
     */
    public function recommendadd(){
        if(IS_POST){
            $recommendinfo = array();
            $recommendinfo["sort"] = input("post.sort",'');
            $recommendinfo["object_value"] = input("post.object_value",'');
            $recommendinfo["image_url"] = input("post.image_url",'');

            if(''==$recommendinfo['sort']){
                $code=-1;
                $msg='排序不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$recommendinfo['object_value']){
                $code=-2;
                $msg='跳转对象不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$recommendinfo['image_url']){
                $code=-5;
                $msg='图片不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{


                $recommendinfo["state"] = 1;
                $recommendinfo["create_time"] = time();
                $recommendinfo["update_time"] = time();
                $recommendinfo["del_flag"] = 0;
                $recommend_id = model("recommend")->insertGetId($recommendinfo);
                if($recommend_id>0){
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
            $ret=array('code'=>$code,'msg'=>$msg,'msg_type'=>$msgtype);

            echo json_encode($ret);exit;
        }
        else{
            echo $this->fetch();
        }
    }



    /**
     * 修改推荐
     */
    public function recommendedit(){
        if(IS_POST){

            $recommendinfo = array();
            $recommendinfo["recommend_id"] = input("post.recommend_id",'');
            $recommendinfo["sort"] = input("post.sort",'');
            $recommendinfo["object_value"] = input("post.object_value",'');
            $recommendinfo["state"] = input("post.state",'');
            $recommendinfo["image_url"] = input("post.image_url",'');

            if(0>=$recommendinfo['recommend_id']){
                $code=-1;
                $msg='id不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$recommendinfo['sort']){
                $code=-1;
                $msg='排序不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$recommendinfo['object_value']){
                $code=-2;
                $msg='跳转对象不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(!in_array($recommendinfo['state'],[1,2])){
                $code=-4;
                $msg='上架状态不合法';
                $msgtype=MSG_TYPE_WARNING;
            }
            elseif(''==$recommendinfo['image_url']){
                $code=-5;
                $msg='图片不能为空';
                $msgtype=MSG_TYPE_WARNING;
            }
            else{

                $recommendinfo["update_time"] = time();

                $ret = model("Recommend")->where(array('recommend_id'=>$recommendinfo["recommend_id"],'del_flag'=>0))->update($recommendinfo);
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
            $recommend_id = input("get.recommend_id",0);
            if(!($recommend_id>0)){
                echo '参数错误';exit;
            }
            $recommendinfo=model('Recommend')->where(array('recommend_id'=>$recommend_id,'del_flag'=>0))->find();
            if(!$recommendinfo){
                echo '记录已被删除或不存在';exit;
            }
            $this->getAllControl();

            $image_url_initialpreview=$this->allControl['recommend_image_url'].$recommendinfo['image_url'];
            $this->assign('image_url_initialpreview',$image_url_initialpreview);
            $this->assign('recommendinfo',$recommendinfo);

            //var_dump($recommendinfo);exit;
            echo $this->fetch();
        }
    }

    /**
     * 删除推荐
     */
    public function recommendremove(){
        $recommend_id = input("post.recommend_id",0);
        if($recommend_id>0){
            $isExist=model('Recommend')->where(array('recommend_id'=>$recommend_id,'del_flag'=>0))->find();
            if($isExist){
                $ret=model("Recommend")->where(array('recommend_id'=>$recommend_id,'del_flag'=>0))
                ->update(['del_flag'=>1,'update_time'=>time()]);
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
    public function recommendaddimg(){
        if(IS_POST){
            $recommendimginfo = array();
            $recommendimginfo["recommend_id"] = input("post.recommend_id",'');
            //var_dump($_FILES);exit;
            $initialpreview= [];
            $initialpreviewconfig = [];

            if(!$_FILES||''==$recommendimginfo['recommend_id']){
                $msg='不合法';
            }
            elseif(''==$_FILES['image_url']){
                $msg='图片不能为空';
            }
            else {

                if ((($_FILES["image_url"]["type"][0] == "image/gif")
                        || ($_FILES["image_url"]["type"][0] == "image/jpeg")
                        || ($_FILES["image_url"]["type"][0] == "image/pjpeg")|| ($_FILES["image_url"]["type"][0] == "image/png"))
                    && ($_FILES["image_url"]["size"][0] < 10000000)
                ) {
                    if ($_FILES["image_url"]["error"][0] > 0) {
                        $code    = - 11;
                        $msg     = '上传错误' . $_FILES["image_url"]["error"][0];
                        $msgtype = MSG_TYPE_DANGER;
                    } else {
                        $imgname = $_FILES["image_url"]["name"][0];
                        $imgurl  = date ('YmdHis') . $_FILES["image_url"]["name"][0];

                        move_uploaded_file ($_FILES["image_url"]["tmp_name"][0] ,
                            config('TASK_UPLOAD_IMAGE_DIR') . $imgurl);


                       /*//上传七牛
                       if($_FILES["image_url"]["type"][0] != "image/gif"){
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
                        $newimgurl=$this->allControl['recommend_image_url']. $imgurl ;


                        $recommendimgid= model("recommend")->where (array('recommend_id'=>$recommendimginfo["recommend_id"]))
                            ->update(array('image_url'=>$imgurl,'update_time'=>time()));


                        $initialpreview       = $newimgurl;
                        $initialpreviewconfig = array (
                            array(
                                "caption"=> $newimgurl,
                                "width"=> '120px',
                                "url"=> 'recommendremoveimg', // server delete action
                                "key"=>100,
                                "extra"=>array("recommend_id"=>$recommendimginfo["recommend_id"])
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

            $recommend_id=input('get.recommend_id');
            $recommendinfo=model('recommend')->where(array('recommend_id'=>$recommend_id,'del_flag'=>0))
                ->find();
            $initialpreview=array();
            $initialpreviewconfig=array();

            if($recommendinfo&&$recommendinfo['image_url']!=''){
                if(substr($recommendinfo['image_url'],0,4)=='http'){
                    $newimgurl=$recommendinfo['image_url'];
                }
                else{
                    $newimgurl=$this->allControl['recommend_image_url'].$recommendinfo['image_url'];
                }
                //$initialpreview[]='<img style="width: auto;height: 160px;" src="'.$imgurl.'" class="kv-preview-data file-preview-image" alt="'.$onerecommendimg['image_url'].'" sort="'.$onerecommendimg['image_url']. '">';
                $initialpreview[]=$newimgurl;
                $initialpreviewconfig[]=array(
                    "caption"=> $newimgurl,
                    "width"=> '120px',
                    "url"=> 'recommendremoveimg', // server delete action
                    "key"=>100,
                    "extra"=>array("recommend_id"=>$recommend_id)
                );

            }


            $this->assign('initialpreview',json_encode($initialpreview));
            $this->assign('initialpreviewconfig',json_encode($initialpreviewconfig));


            $this->assign('recommend_id',$recommend_id);
            //$this->defaultsort=model('Product')->getdefaultsort()+1;
            echo $this->fetch();exit;
        }
    }



    /**
     * 删除图片
     */
    public function recommendremoveimg(){
        $recommend_id = input("post.recommend_id",0);
        if($recommend_id>0){
            $ret=model("recommend")->where(array('recommend_id'=>$recommend_id,'del_flag'=>0))
                ->update(array('image_url'=>'','update_time'=>time()));
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