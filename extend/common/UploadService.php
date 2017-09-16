<?php
/**
 * 图片上传
 */

namespace common;
class UploadService
{
    protected $uploadDriver;

    private $config = array(
        'mimes' => array('image/jpeg','image/jpeg','image/png','image/gif','application/octet-stream'),
        'maxSize' => 0,//0表示不限制，以字节的方式表现
        'exts' => array('jpg','jpeg','png','gif'),//扩展名
        'rootPath' => '',
        'savePath' => '',
        'saveName' => array('md5', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        
        'is_force'  => FALSE,       //是否强制  TRUE 固定宽高       FALSE最大最小控制,正比例控制
        'is_square' => FALSE,       //是否正比例           TRUE 正比例 FALSE非正比
        'width'     => '*',         //固定宽度/最小宽度    is_force为TRUE时为固定宽度，FALSE为最小宽度
        'height'    => '*',         //固定高度/最小高度     is_force为TRUE时为固定高度，FALSE为最小高度
        'max_width' => '*',         //最大宽度
        'max_height' => '*'         //最大高度
    );

    function __construct()
    {
        if(!$this->uploadDriver){
            $this->uploadDriver = new \oss\ClientService();
        }
    }

    public function __set($name, $value)
    {
        if(isset($this->config[$name])){
            $this->config[$name] = $value;
        }
        return $this;
    }

    public function __get($name)
    {
        return $this->config[$name];
    }
    
    /**
     * 设置检测大小参数
     * @param unknown $config_name
     * @param unknown $arr
     * @return \mo\service\common\UploadService
     */
    public function setDetection($config_name = '', $arr = array()){
        if(empty($config_name) && empty($arr)){
            return $this;
        }
        
        if(empty($arr)){        //若是无外来控制参数，则去配置文件取
            $detection_arr = config_item('lxf_img');
            if(!isset($detection_arr[$config_name])){
                return $this;
            }
            $arr = $detection_arr[$config_name];
        }
        foreach ($arr as $key => $val){
            $this->$key = $val;     //将控制参数赋值给 方面变量参数
        }
        
        return $this;
    }

    public function setModule($module)
    {
        /*文件配置检测 */
        $storage = config('x_storage_root');
        $rootPath = $storage.DIRECTORY_SEPARATOR.$module;

        $this->rootPath = $rootPath;
        return $this;
    }

    public function upload($files = '')
    {
        $return = array();
        $return['result'] = FALSE;
        if($files == ''){
            $files = $_FILES;
        }
        if(empty($files)){
            $return['errmsg'] = '没有上传文件';
            return $return;
        }

        $file = '';
        foreach($files as $name => $_f){
            $_f['ext'] = pathinfo($_f['name'], PATHINFO_EXTENSION);
            $error = $this->check($_f);
            if($error){
                $return['errmsg'] = $error;
                return $return;
            }
            if($file){
                continue;
            }
            $file = $_f;
        }
        
        if(!$file){
            $return['errmsg'] = '文件未上传';
            return $return;
        }
        
        //防止上图断了一部分报的505错误
        if(!$file['tmp_name']){
            $return['errmsg'] = '图片未上传';
            return $return;
        }
        /* 检测图片大小 */
        $sizeArr = getimagesize($file['tmp_name']);
        if($this->is_force){            //强制为固定宽高
            if($this->width != '*' && $this->width != $sizeArr['0']){
                $return['errmsg'] = '图片宽度不对,固定宽度为:'.$this->width;
                return $return;
            }
            if($this->height != '*' && $this->height != $sizeArr['1']){
                $return['errmsg'] = '图片高度不对,固定高度为:'.$this->height;
                return $return;
            }
        }else{      //非强制
            if($this->is_square){       //是否正比例
                if($sizeArr['0'] != $sizeArr['1']){
                    $return['errmsg'] = '图片要求是正比例';
                    return $return;
                }
            }
            if($this->width != '*' && $sizeArr['0'] < $this->width){
                $return['errmsg'] = '图片宽度不对,最小宽度为:'.$this->width;
                return $return;
            }
            if($this->height != '*' && $sizeArr['1'] < $this->height){
                $return['errmsg'] = '图片高度不对,最小高度为:'.$this->height;
                return $return;
            }
            
            if($this->max_width != '*' && $sizeArr['0'] > $this->max_width){
                $return['errmsg'] = '图片宽度不对,最大宽度为:'.$this->max_width;
                return $return;
            }
            if($this->max_height != '*' && $sizeArr['1'] < $this->max_height){
                $return['errmsg'] = '图片高度不对,最大高度为:'.$this->max_height;
                return $return;
            }
        }

        /* 生成保存文件名 */
        $savename = $this->getSaveName($file);
        if (FALSE == $savename) {
            $savename = $file['name'];
        }
        $file['savename'] = $savename;
        $subpath = $this->getSubPath($savename);

        $file['storage_name'] = $subpath  . $savename;
        $file['storage_name'] = str_replace('\\', '/', $file['storage_name']);
        $filePath = $this->rootPath. DIRECTORY_SEPARATOR .$file['storage_name'];
        $filePath = str_replace('\\', '/', $filePath);
        /* OSS 文件上传 */
        $ossConfig = config('oss');
        $_content = file_get_contents($file['tmp_name']);
        if($file['type'] == 'application/octet-stream'){
            $file['type'] = $this->getMimes($file);
        }
        $options['Content-Type'] = $file['type'];
        //var_dump($this->uploadDriver,$ossConfig['bucket'], $filePath, $_content,$options);exit;
        $this->uploadDriver->putObject($ossConfig['bucket'], $filePath, $_content,$options);
        /* 回收error tmp_name 返回原始数据 */
        unset($file['error'], $file['tmp_name']);
        $file['image_url'] = $ossConfig['image_url'] . $filePath;
        $file['file_name'] = $filePath;
        $return['result'] = TRUE;
        $return['data'] = $file;
        return $return;
    }

    protected function getMimes($file)
    {
        $mime = '';
        switch($file['ext']){
            case 'jpg':
            case 'jpeg':
                $mime = 'image/jpeg';
                break;
            case 'png':
                $mime = 'image/png';
                break;
            case 'gif':
                $mime = 'image/gif';
                break;
            default :
                $mime = 'image/jpeg';
        }
        return $mime;
    }

    private function check($file)
    {
        $error = '';
        /* 文件上传失败错误码捕捉 */
        if($file['error']){
            $error = $this->error($file['error']);
            return $error;
        }
        /* 无效上传 */
        if(empty($file['name'])){
            $error = '未知上传文件';
            return $error;
        }
        if(!is_uploaded_file($file['tmp_name'])){
            $error = '非法上传文件';
            return $error;
        }
        if(!$this->checkSize($file['size'])){
            $error = '上传文件大小不符';
            return $error;
        }
        if(!$this->checkMime($file['type'])){
            $error = '此类型文件不允许上传';
            return $error;
        }

        if(!$this->checkExt(pathinfo($file['name'], PATHINFO_EXTENSION))){
            $error = '上传文件后缀不允许';
            return $error;
        }
        return $error;
    }

    /**
     * 检查上传的文件后缀是否合法
     * @param string $ext 后缀
     */
    private function checkExt($ext) {
        return empty($this->config['exts']) ? TRUE : in_array(strtolower($ext), $this->exts);
    }

    /**
     * 检查上传的文件MIME类型是否合法
     * @param string $mime 数据
     */
    private function checkMime($mime) {
        return empty($this->config['mimes']) ? TRUE : in_array(strtolower($mime), $this->mimes);
    }

    /**
     * @param $size
     * 检测文件大小是否合法
     */
    private function checkSize($size)
    {
        return !($size > $this->maxSize) || (0 == $this->maxSize);
    }

    /**
     * 获取错误代码信息
     * @param string $errorNo  错误号
     */
    private function error($errorNo) {
        switch ($errorNo) {
            case 1:
                $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值！';
                break;
            case 2:
                $this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值！';
                break;
            case 3:
                $this->error = '文件只有部分被上传！';
                break;
            case 4:
                $this->error = '没有文件被上传！';
                break;
            case 6:
                $this->error = '找不到临时文件夹！';
                break;
            case 7:
                $this->error = '文件写入失败！';
                break;
            default:
                $this->error = '未知上传错误！';
        }
    }

    /**
     * 根据指定的规则获取文件或目录名称
     * @param  array  $rule     规则
     * @param  string $filename 原文件名
     * @return string           文件或目录名称
     */
    private function getName($rule, $filename){
        $name = '';
        if(is_array($rule)){ //数组规则
            $func     = $rule[0];
            $param    = (array)$rule[1];
            foreach ($param as &$value) {
                $value = str_replace('__FILE__', $filename, $value);
            }
            $name = call_user_func_array($func, $param);
        } elseif (is_string($rule)){ //字符串规则
            if(function_exists($rule)){
                $name = call_user_func($rule);
            } else {
                $name = $rule;
            }
        }
        return $name;
    }

    /**
     * 根据上传文件命名规则取得保存文件名
     * @param string $file 文件信息
     */
    public function getSaveName($file) {

        $savename = md5($file['name'].time());

        /* 文件保存后缀，支持强制更改文件后缀 */

        return $savename . '.' . $file['ext'];
    }

    /**
     * 获取子目录的名称
     * @param array $file  上传的文件信息
     */
    public function getSubPath($filename) {
        $ossConfig = config('oss');
        $subpath = '';
        for($i = 0; $i < $ossConfig['storage_level']; $i++){
            $subpath .= $filename{$i} . DIRECTORY_SEPARATOR;
        }
        return $subpath;
    }

}