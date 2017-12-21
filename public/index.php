<?php

if($_SERVER['HTTP_HOST']=='www.mrdgr.com'){
    require __DIR__ . '/mrdgr_index.php';
}
elseif($_SERVER['HTTP_HOST']=='api.mrdgr.com'){
    require __DIR__ . '/ds_index.php';
}
else{
    echo 'access deny';exit;
}
