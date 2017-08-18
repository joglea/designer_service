<?php
namespace app\index\controller;

use app\common\controller\Front;
use Qiniu\Auth;

/**
 * Class Qiniu
 *
 * @classdesc 七牛接口类
 * @package app\index\controller
 */
class Qiniu extends Front
{

    /**
     * ---------------------------------------------------------------------------------------------
     * @desc    生成七牛token接口
     * @url     /qiniu/generateToken
     * @method  POST
     * @version 1000
     * @params  type 1 INT 七牛不同bucket类型,默认1 YES
     * @params  sid 'c16551f3986be2768e632e95767f6574' STRING 当前混淆串 YES
     * @params  ct '' STRING 当前时间戳 YES
     *
     */
    public function generateToken() {

        $data = array ();

        $type = intval (input ('type'));

        //验证参数是否为空
        if (!in_array ($type , array (1))) {
            $this->returndata (14001 , 'param error' , $this->curTime , $data);
        }

        $this->logResult ('begin generateToken');

        try {

            require_once __DIR__ . '/../../../extend/vendor/qiniu/php-sdk/autoload.php';

            $accessKey = config ('QINIU_ACCESSKEY');
            $secretKey = config ('QINIU_SECRETKEY');
            //有效时间
            $expires   = config ('QINIU_EXPIRES');
            $auth      = new Auth($accessKey , $secretKey);
            $mediahash = null;

            //图音视
            if ($type == 1) {
                $bucket = 'jzimg';

                $this->logResult ('bucket:' . $bucket);
                $this->logResult ('key:' . $mediahash);
                $this->logResult ('expires:' . $expires);

                $data['itoken'] = $auth->uploadToken ($bucket , $mediahash , $expires);
            }

            $this->logResult ('data:' . json_encode($data));

            $this->logResult ('end generateToken');

            $data['expires'] = $this->curTime + $expires;

            $this->returndata (10000 , 'do success' , $this->curTime , $data);
        } catch (Exception $e) {
            $this->returndata (11000 , 'server error' . $e->getMessage () , $this->curTime , []);
        }
    }



}
