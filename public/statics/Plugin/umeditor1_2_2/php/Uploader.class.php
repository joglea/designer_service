<?php
// 引入鉴权类
use Qiniu\Auth;

// 引入上传类
use Qiniu\Storage\UploadManager;

/**
 * Created by JetBrains PhpStorm.
 * User: taoqili
 * Date: 12-7-18
 * Time: 上午11: 32
 * UEditor编辑器通用上传类
 */
class Uploader
{
    private $fileField;            //文件域名
    private $file;                 //文件上传对象
    private $config;               //配置信息
    private $oriName;              //原始文件名
    private $fileName;             //新文件名
    private $fullName;             //完整文件名,即从当前配置目录开始的URL
    private $fileSize;             //文件大小
    private $fileType;             //文件类型
    private $stateInfo;            //上传状态信息,
    private $stateMap = array(    //上传状态映射表，国际化用户需考虑此处数据的国际化
        "SUCCESS" ,                //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制" ,
        "文件大小超出 MAX_FILE_SIZE 限制" ,
        "文件未被完整上传" ,
        "没有文件被上传" ,
        "上传文件为空" ,
        "POST" => "文件大小超出 post_max_size 限制" ,
        "SIZE" => "文件大小超出网站限制" ,
        "TYPE" => "不允许的文件类型" ,
        "DIR" => "目录创建失败" ,
        "IO" => "输入输出错误" ,
        "UNKNOWN" => "未知错误" ,
        "MOVE" => "文件保存时出错",
        "DIR_ERROR" => "创建目录失败"
    );

    /**
     * 构造函数
     * @param string $fileField 表单名称
     * @param array $config  配置项
     * @param bool $base64  是否解析base64编码，可省略。若开启，则$fileField代表的是base64编码的字符串表单名
     */
    public function __construct( $fileField , $config , $base64 = false )
    {
        $this->fileField = $fileField;
        $this->config = $config;
        $this->stateInfo = $this->stateMap[ 0 ];
        $this->upFile( $base64 );
    }

    /**
     * 上传文件的主处理方法
     * @param $base64
     * @return mixed
     */
    private function upFile( $base64 )
    {
        //处理base64上传
        if ( "base64" == $base64 ) {
            $content = $_POST[ $this->fileField ];
            $this->base64ToImage( $content );
            return;
        }

        //处理普通上传
        $file = $this->file = $_FILES[ $this->fileField ];
        if ( !$file ) {
            $this->stateInfo = $this->getStateInfo( 'POST' );
            return;
        }
        if ( $this->file[ 'error' ] ) {
            $this->stateInfo = $this->getStateInfo( $file[ 'error' ] );
            return;
        }
        if ( !is_uploaded_file( $file[ 'tmp_name' ] ) ) {
            $this->stateInfo = $this->getStateInfo( "UNKNOWN" );
            return;
        }

        $this->oriName = $file[ 'name' ];
        $this->fileSize = $file[ 'size' ];
        $this->fileType = $this->getFileExt();

        if ( !$this->checkSize() ) {
            $this->stateInfo = $this->getStateInfo( "SIZE" );
            return;
        }
        if ( !$this->checkType() ) {
            $this->stateInfo = $this->getStateInfo( "TYPE" );
            return;
        }

        $folder = $this->getFolder();

        if ( $folder === false ) {
            $this->stateInfo = $this->getStateInfo( "DIR_ERROR" );
            return;
        }

        $this->fullName = $folder . '/' . $this->getName();

        if ( $this->stateInfo == $this->stateMap[ 0 ] ) {

            if ( !move_uploaded_file( $file[ "tmp_name" ] , $this->fullName ) ) {
                $this->stateInfo = $this->getStateInfo( "MOVE" );
            }
            if($this->fileType!='.gif'){
                $maxwidth=800;
                $maxheight=1500;
                $show_pic_scal=$this->show_pic_scal($maxwidth, $maxheight, $this->fullName);
                $this->resize($this->fullName,$show_pic_scal[0],$show_pic_scal[1]);
            }
            //$this->uploadtoqiniu($this->fullName,$this->getName());
        }
    }

    public function uploadtoqiniu($filedir,$filename){

        require_once __DIR__ . '/../../../../ThinkPHP/Library/Vendor/qiniu_sdk/autoload.php';

        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = 'KF-dcaCxqCSwTLoG4ZJuYC1Vbxt_DAi8-MfvRdXE';
        $secretKey = 'SLg0vORh-8upbQphZ87Y4h1JVE_ORFTpF-MDkCyx';

        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);

        // 要上传的空间
        $bucket = 'fdh123';

        // 生成上传 Token
        $token = $auth->uploadToken($bucket);

        // 要上传文件的本地路径
        $filePath = $filedir;

        // 上传到七牛后保存的文件名
        $key = $filename;

        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        //echo "\n====> putFile result: \n";
        if ($err !== null) {
            $this->stateInfo = $this->getStateInfo( "MOVE" );

        } else {
            $this->fullName= 'http://oe555q6n6.bkt.clouddn.com/'.$ret['key'];
        }

    }


    public function show_pic_scal($width, $height, $picpath) {
        $imginfo = getimagesize ( $picpath );
        $imgw = $imginfo [0];
        $imgh = $imginfo [1];

        $ra = number_format ( ($imgw / $imgh), 2 ); //宽高比
        $ra2 = number_format ( ($imgh / $imgw), 2 ); //高宽比

        $r3 = number_format ( ($width / $height), 2 ); //高宽比


        if ($imgw > $width && $ra>=$r3) {
            $newWidth = $width;
            $newHeight = round ( $newWidth / $r3 );

        } else if($imgh > $height &&$ra<=$r3){
            $newHeight = $height;
            $newWidth = round ( $newHeight * $r3 );
        }
        else{
            $newHeight = $imgh;
            $newWidth = $imgw;
        }
        //var_dump($newWidth,$newHeight);exit;
        $newsize [0] = $newWidth;
        $newsize [1] = $newHeight;

        return $newsize;
    }



    public function getImageInfo($src)
    {
        return getimagesize($src);
    }
    /**
     * 创建图片，返回资源类型
     * @param string $src 图片路径
     * @return resource $im 返回资源类型
     * **/
    public function create($src)
    {
        $info=$this->getImageInfo($src);
        switch ($info[2])
        {
            case 1:
                $im=imagecreatefromgif($src);
                break;
            case 2:
                $im=imagecreatefromjpeg($src);
                break;
            case 3:
                $im=imagecreatefrompng($src);
                break;
        }
        return $im;
    }
    /**
     * 缩略图主函数
     * @param string $src 图片路径
     * @param int $w 缩略图宽度
     * @param int $h 缩略图高度
     * @return mixed 返回缩略图路径
     * **/

    public function resize($src,$w,$h)
    {
        $temp=pathinfo($src);
        $name=$temp["basename"];//文件名
        $dir=$temp["dirname"];//文件所在的文件夹
        $extension=$temp["extension"];//文件扩展名
        $savepath="{$dir}/{$name}";//缩略图保存路径,新的文件名为*.thumb.jpg
        //$savepath="{$dir}/aa.jpg";
        //获取图片的基本信息
        $info=$this->getImageInfo($src);
        $width=$info[0];//获取图片宽度
        $height=$info[1];//获取图片高度
        $per1=round($width/$height,2);//计算原图长宽比
        $per2=round($w/$h,2);//计算缩略图长宽比

        //计算缩放比例
        if($per1>$per2||$per1==$per2)
        {
            //原图长宽比大于或者等于缩略图长宽比，则按照宽度优先
            $per=$w/$width;
        }
        if($per1<$per2)
        {
            //原图长宽比小于缩略图长宽比，则按照高度优先
            $per=$h/$height;
        }
        $temp_w=intval($width*$per);//计算原图缩放后的宽度
        $temp_h=intval($height*$per);//计算原图缩放后的高度
        $temp_img=imagecreatetruecolor($temp_w,$temp_h);//创建画布
        $im=$this->create($src);
        imagecopyresampled($temp_img,$im,0,0,0,0,$temp_w,$temp_h,$width,$height);
        if($per1>$per2)
        {
            imagejpeg($temp_img,$savepath, 100);
            imagedestroy($im);
            return $savepath;
            //宽度优先，在缩放之后高度不足的情况下补上背景
        }
        if($per1==$per2)
        {
            imagejpeg($temp_img,$savepath, 100);
            imagedestroy($im);
            return $savepath;
            //等比缩放
        }
        if($per1<$per2)
        {
            imagejpeg($temp_img,$savepath, 100);
            imagedestroy($im);
            return $savepath;
            //高度优先，在缩放之后宽度不足的情况下补上背景
        }
    }





    /**
     * 处理base64编码的图片上传
     * @param $base64Data
     * @return mixed
     */
    private function base64ToImage( $base64Data )
    {
        $img = base64_decode( $base64Data );
        $this->fileName = time() . rand( 1 , 10000 ) . ".png";
        $this->fullName = $this->getFolder() . '/' . $this->fileName;
        if ( !file_put_contents( $this->fullName , $img ) ) {
            $this->stateInfo = $this->getStateInfo( "IO" );
            return;
        }
        $this->oriName = "";
        $this->fileSize = strlen( $img );
        $this->fileType = ".png";
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo()
    {
        return array(
            "originalName" => $this->oriName ,
            "name" => $this->fileName ,
            "url" => $this->fullName ,
            "size" => $this->fileSize ,
            "type" => $this->fileType ,
            "state" => $this->stateInfo
        );
    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    private function getStateInfo( $errCode )
    {
        return !$this->stateMap[ $errCode ] ? $this->stateMap[ "UNKNOWN" ] : $this->stateMap[ $errCode ];
    }

    /**
     * 重命名文件
     * @return string
     */
    private function getName()
    {
        return $this->fileName = time() . rand( 1 , 10000 ) . $this->getFileExt();
    }

    /**
     * 文件类型检测
     * @return bool
     */
    private function checkType()
    {
        return in_array( $this->getFileExt() , $this->config[ "allowFiles" ] );
    }

    /**
     * 文件大小检测
     * @return bool
     */
    private function  checkSize()
    {
        return $this->fileSize <= ( $this->config[ "maxSize" ] * 1024 );
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    private function getFileExt()
    {
        return strtolower( strrchr( $this->file[ "name" ] , '.' ) );
    }

    /**
     * 按照日期自动创建存储文件夹
     * @return string
     */
    private function getFolder()
    {
        $pathStr = $this->config[ "savePath" ];
        if ( strrchr( $pathStr , "/" ) != "/" ) {
            $pathStr .= "/";
        }
        $pathStr .= date( "Ymd" );
        if ( !file_exists( $pathStr ) ) {
            if ( !mkdir( $pathStr , 0777 , true ) ) {
                return false;
            }
        }
        return $pathStr;
    }
}