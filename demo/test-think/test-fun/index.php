<?php
use RingCentral\Psr7\Response;
use liuwave\fc\think\FcThink;

/*
To enable the initializer feature (https://help.aliyun.com/document_detail/89029.html)
please implement the initializer function as below：
function initializer($context) {
    echo 'initializing' . PHP_EOL;
}
*/

function handler($request, $context) : Response
{
    
    $appPath=__DIR__ . '/tp';
    require $appPath . '/vendor/autoload.php';
    return (new FcThink($request, $appPath, '/tmp/'))
      ->withHeader(['context' => $context])
      ->run();
}