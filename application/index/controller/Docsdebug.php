<?php
namespace app\index\controller;

use app\common\controller\Front;

/**
 * Class Docsdebug
 *
 * @classdesc 自动生成接口文档页面和接口调试页面
 * @package app\index\controller
 */
class Docsdebug extends Front{

    public function index(){
        //如果是生产环境就直接退出
        if(config('ENV_VALUE')==2){
            echo 'Access deny';exit;
        }

        $controllermaplist=config('controllermap.CONTROLLER_LIST');
        //var_dump(config(''),$controllermaplist);exit;

        $resultlist=array();
        foreach($controllermaplist as $onecontroller){

            $controllerclass  =  controller($onecontroller);

            $reflector = new \ReflectionClass($controllerclass);

            $classcomment=$reflector->getDocComment();
            $classcommentarray=explode('*',$classcomment);
            $classdesc='';
            foreach($classcommentarray as $oneclasscomment){
                $classdescpos=strpos($oneclasscomment,'@classdesc');
                if($classdescpos!==false){
                    $classdesc=substr($oneclasscomment,$classdescpos+10);
                }
            }
            $tempresult=array(
                'controllerdesc'=>$classdesc
            );
            $classmethodlist=$reflector->getMethods(\ReflectionMethod::IS_PUBLIC);

            $commonmethodlist=(new \ReflectionClass(new \app\common\controller\Front()))->getMethods(\ReflectionMethod::IS_PUBLIC);

            //排除common基类中的公共方法列表
            $excludemethods=array();
            foreach($commonmethodlist as $onecommonmethod){
                $excludemethods[]=$onecommonmethod->name;
            }
            foreach($classmethodlist as $oneclassmethod){
                if(in_array($oneclassmethod->name,$excludemethods)){
                    continue;
                }

                $tempmethod=array();
                $methodcomment=$oneclassmethod->getDocComment();
                $methodcommentarray=explode('*',$methodcomment);
                //var_dump($methodcommentarray);

                $tempmethod['desc']='未描述';
                $tempmethod['url']='未描述';
                $tempmethod['method']='未描述';
                $tempmethod['version']='未描述';
                foreach($methodcommentarray as $onemethodcomment){
                    $methoddescpos=strpos($onemethodcomment,'@desc');
                    if($methoddescpos!==false){
                        $tempmethod['desc']=substr($onemethodcomment,$methoddescpos+5);
                    }

                    $methoddescpos=strpos($onemethodcomment,'@url');
                    if($methoddescpos!==false){
                        $tempmethod['url']=trim(substr($onemethodcomment,$methoddescpos+4));
                    }

                    $methoddescpos=strpos($onemethodcomment,'@method');
                    if($methoddescpos!==false){
                        $tempmethod['method']=substr($onemethodcomment,$methoddescpos+7);
                    }

                    $methoddescpos=strpos($onemethodcomment,'@version');
                    if($methoddescpos!==false){
                        $tempmethod['version']=substr($onemethodcomment,$methoddescpos+8);
                    }

                }
                $tempresult['methodlist'][]=$tempmethod;

            }
            $resultlist[]=$tempresult;

        }

        $jztoken = session('logintoken')?session('logintoken'):md5('');
        $jzversion = 'a10101';
        $sid =md5(time());
        $ct =time();

        $data = array(
            'jztoken'=>$jztoken,
            'jzversion'=>$jzversion,
            'jzverify'=>md5($jztoken.'_'.$jzversion.'_'.$sid.'_'.$ct),
            'ct'=>$ct,
            'sid'=>$sid,
            'resultlist'=>$resultlist
        );


        $this->assign($data);


        return $this->fetch();
    }


    public function detail(){
        //如果是生产环境就直接退出
        if(config('ENV_VALUE')==2){
            echo 'Access deny';exit;
        }

        $testurl=strtolower(input('request.testurl'));
        if($testurl){
            $testurlarr=explode('/',($testurl));
            //var_dump($testurlarr[1],config('controllermap.CONTROLLER_LIST'),$testurlarr[2]);
            if(in_array($testurlarr[1],config('controllermap.CONTROLLER_LIST'))&&$testurlarr[2]){

                $controllerclass  =  controller($testurlarr[1]);

                $reflector = new \ReflectionClass($controllerclass);

                $tempmethod=array();
                $methodcomment=$reflector->getMethod($testurlarr[2])->getDocComment();

                $methodcommentarray=explode('*',$methodcomment);

                $tempmethod['desc']='未描述';
                $tempmethod['url']='未描述';
                $tempmethod['method']='未描述';
                $tempmethod['version']='未描述';
                $tempmethod['return']='未描述';
                foreach($methodcommentarray as $onemethodcomment){

                    $methoddescpos=strpos($onemethodcomment,'@desc');
                    if($methoddescpos!==false){
                        $tempmethod['desc']=substr($onemethodcomment,$methoddescpos+5);
                    }

                    $methoddescpos=strpos($onemethodcomment,'@url');
                    if($methoddescpos!==false){
                        $tempmethod['url']=trim(substr($onemethodcomment,$methoddescpos+4));
                    }

                    $methoddescpos=strpos($onemethodcomment,'@method');
                    if($methoddescpos!==false){
                        $tempmethod['method']=substr($onemethodcomment,$methoddescpos+7);
                    }

                    $methoddescpos=strpos($onemethodcomment,'@version');
                    if($methoddescpos!==false){
                        $tempmethod['version']=substr($onemethodcomment,$methoddescpos+8);
                    }

                    $methoddescpos=strpos($onemethodcomment,'@params');
                    if($methoddescpos!==false){
                        $tempmethod['params'][]=explode(' ',trim(substr($onemethodcomment,$methoddescpos+7)));
                    }

                    $methoddescpos=strpos($onemethodcomment,'@return');
                    if($methoddescpos!==false){
                        $temp = (substr($onemethodcomment,$methoddescpos+7));
                        $order   = array("\r\n", "\n", "\r");
                        $replace = '<br/>';
                        $newstr = str_replace($order, $replace, $temp);
                        //$tempmethod['return']=$newstr;
                        $tempmethod['return']=str_replace(' ','&nbsp;',$newstr);
                        //$tempmethod['return']=preg_replace('/\s/','&nbsp;',$newstr);
                    }
                }
                $dstoken = session('logintoken')?session('logintoken'):md5('');

                $curUserInfo = cache($dstoken);

                $dsversion = 'a10101';
                $sid =md5(time());
                $ct =time();

                $data = array(
                    'dstoken'=>$dstoken,
                    'dsversion'=>$dsversion,
                    'dsverify'=>md5($dstoken.'_'.$dsversion.'_'.$sid.'_'.$ct),
                    'ct'=>$ct,
                    'sid'=>$sid,
                    'methodinfo'=>$tempmethod,
                    'userinfo'=>$curUserInfo
                );
                //var_dump($curUserInfo);exit;
                //var_dump($dstoken.'_'.$dsversion.'_'.$sid.'_'.$ct,md5($dstoken.'_'.$dsversion.'_'.$sid.'_'.$ct));

                $this->assign($data);

                return $this->fetch();
            }
            else{
                echo 'Not exist';exit;
            }
        }
        else{
            echo 'Param error';exit;
        }


    }

    public function test(){
        return $this->fetch();
    }
}