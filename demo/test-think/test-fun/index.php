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
$GLOBALS[ 'fcThink' ]=null;

/**
 * @param $request
 * @param $context
 *
 * @return \RingCentral\Psr7\Response
 * @throws \Exception
 */
function handler($request, $context) : Response
{
    if(!$GLOBALS['fcThink']){
        $GLOBALS['fcThink']=getHandler($context);
    }
    return $GLOBALS[ 'fcThink' ]->setFcRequest($request)
      ->run();
}

/**
 * @param $context
 */
function initializer($context){

    $GLOBALS[ 'fcThink' ] = getHandler($context);
}

/**
 * @param $context
 *
 * @return \liuwave\fc\think\FcThink
 */
function getHandler($context){
    $appPath = __DIR__.'/tp';
    require $appPath.'/vendor/autoload.php';
    return new FcThink(null, $context, ['root' => $appPath, 'runtime_path' => '/tmp/', 'is_cli' => false]);
}