<?php
require_once __DIR__ . '/../autoload.php';

use Qiniu\Auth;

$accessKey = 'DqH-gXntXxxAdovT-78GOwPMoWEAM-P-cT6CQHfJ';
$secretKey = 'PRMDbmNrB_PnC7h43Ykq-6fDUmJS9Sd7WPcsj7cR';
$auth = new Auth($accessKey, $secretKey);

$bucket = 'jzimg';
$upToken = $auth->uploadToken($bucket);

echo $upToken;
