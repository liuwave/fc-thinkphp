<?php

namespace app\controller;

use app\BaseController;
use think\facade\Log;
use think\facade\View;

/**
 * Class Index
 * @package app\controller
 */
class Index extends BaseController
{
    /**
     * @return string
     */
    public function index()
    {
        $sapi = php_sapi_name();
        return View::fetch('index', ['sapi' => $sapi]);
    }
    
    /**
     * @return string
     */
    public function info()
    {
        ob_start();
        
//        var_dump($this->request->server('context_credentials_accessKeyID'));
//        var_dump($this->request->server('context_credentials_accessKeySecret'));
//        var_dump($this->request->server('context_credentials_securityToken'));
//        var_dump($this->app->getRuntimePath());
//        var_dump($this->request->server());
//        var_dump($this->request->header());
        phpinfo();
        $result = ob_get_contents();
        ob_end_clean();
        
        if ($this->request->isCli()) {
            $array = explode(PHP_EOL, $result);
            
            foreach ($array as $key => $value) {
                $value = strtoupper($value);
                if (strpos($value, 'ACCESSKEY') !== false || strpos($value, 'SECURITYTOKEN') !== false) {
                    [$header] = explode('=>', $array[ $key ]);
                    $array[ $key ] = $header.'=> It is a secret!';
                }
            }
            
            $result = '<!DOCTYPE html>
<html lang=\'zh-CN\'>
<head>
<title>当前运行环境</title>


</head>

<body>
<div style="    width: 1040px;padding: 0 20px;    margin: 0 auto;">
<div style="    font-size: 16px;    padding: 1rem 2rem 1rem 2rem;">
'.implode('<br/>', $array).'
</div>
</div>
</body>
</html>';
        }
        else {
            // 回调函数
            $array = explode(PHP_EOL, $result);
            foreach ($array as $key => $value) {
                $value = strtoupper($value);
                
                if (strpos($value, 'ACCESSKEY') !== false || strpos($value, 'SECURITYTOKEN') !== false) {
                    [$header] = explode('</td><td', $array[ $key ]);
                    $array[ $key ] = $header.'</td><td class="v"> It is a secret!</td></tr>';
                }
            }
            $result = implode(PHP_EOL, $array);
        }
        
        return $result;
    }
    
    /**
     * @return \think\response\Json
     */
    public function upload()
    {
        $file       = \request()->file('file');
        $filesystem = \think\facade\Filesystem::disk('oss');
        $saveName   = $filesystem->putFile('/path/to/save/file', $file, 'md5');
        $saveName   = str_replace('\\', '/', $saveName);
        $fullName   = \think\facade\Filesystem::getDiskConfig('oss', 'url').'/'.$saveName;
        return json(['full_url' => $fullName]);
    }
    
    /**
     * @throws \Exception
     */
    public function log(){
        
        
        Log::error('测试错误日志');
        Log::info('测试信息日志');
        Log::warning('测试警告日志');
        Log::alert('测试测试alert日志');
        Log::debug('测试debug环境');
        
        throw new \Exception('日志插件记录抛出异常测试,请在 函数计算控制台->对应函数界面->日志查询->高级查询中，查询日志结果');
    
    
    
    }
    
    
}
