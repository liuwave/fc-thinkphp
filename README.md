# fc-thinkphp


这是一个使用阿里云函数计算 php runtime搭建serverless thinkphp6.0项目的插件，只需要在函数计算的http触发器入口函数添加几行代码就能让thinkphp6.0运行在函数计算的php运行环境下。

示例：[http://test-think.oldmen.cn/](http://test-think.oldmen.cn/)


## 快速开始

首先在thinkphp6.0项目中安装插件：

```shell script
composer require liuwave/fc-thinkphp
```

修改thinkphp项目的入口文件`/tp/public/index.php`(仅在cgi模式下需要修改(支持cli模式，默认cgi模式)):

```php
<?php

// [ 应用入口文件 ]
namespace think;


require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
// $http = (new App())->http;
$app=new App();
$app->setRuntimePath(getenv('PHP_RUNTIME_PATH')?:'/tmp/');
$http = $app->http;

$response = $http->run();

$response->send();

$http->end($response);
```


函数计算入口`index.php`中，添加：

```php

function handler($request, $context) : Response
{   
       //设置thinkphp根目录
    $appPath=__DIR__ . '/tp';
    require $appPath . '/vendor/autoload.php';
    return (new FcThink($request, $context,['root'=> $appPath, 'runtime_path'=>'/tmp/']))->run();
}
```



## API Reference

### FcThink::__construct

`\Psr\Http\Message\ServerRequestInterface $fcRequest,array $context,$config = []`

#### $fcRequest 

函数计算中http触发器传入的$request参数，遵循 PSR（HTTP message interfaces）标准。更多详情请参见 [PSR-7-http-message](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-7-http-message.md?spm=a2c4g.11186623.2.16.2ed5114fpZwWPC&file=PSR-7-http-message.md)。具体的代码定义遵循 [PSR Http Message](https://github.com/php-fig/http-message?spm=a2c4g.11186623.2.17.2ed5114fpZwWPC)。

#### $context

函数计算中http触发器传入的context 参数，参见[PHP 事件函数 中的 $context参数](https://help.aliyun.com/document_detail/89029.html?source=5176.11533457&userCode=re2rax3m&type=copy)

#### $config

配置参数，默认值为：


```php

 [
      'is_cli'       => false,//是否为cli模式，默认为cgi模式
      'ignore_file'  => false,//是否检测请求路径为存在的文件，如果忽略，则交由thinkphp 入库函数处理，默认为false，即若请求路径为文件(php后缀名除外)，直接返回文件内容
      'root'         => '/code/tp',//thinkphp项目的root_path
      'runtime_path' => '/tmp/',//缓存目录
      'host' => '',//自定义域名，默认为 HTTP_HOST
    ];
```





## 新手入门

首次使用函数计算，可以参照一下步骤：


### 1、准备

- 开通 [函数计算](https://www.aliyun.com/product/fc?source=5176.11533457&userCode=re2rax3m&type=copy),每月免费100万次请求，免费40万(CU-秒)执行时间，日IP在1000以下的博客站足够用。
- (可选)开通oss
- (可选)开通NAS
- 域名

> 注：NAS和OSS主要用于储存上传文件，如无需要可不开通


### 2、安装和配置fun工具

安装和配置参见：[安装Fun](https://help.aliyun.com/document_detail/161136.html?source=5176.11533457&userCode=re2rax3m&type=copy)


### 3、在函数计算控制台创建服务

![进入控制台创建服务1.png](https://assets.oldmen.cn/fc-thinkphp/docs/images/1.png)

输入名称`test-think`,可选择绑定日志,点击创建。

![输入名称2.png](https://assets.oldmen.cn/fc-thinkphp/docs/images/2.png)

创建成功后，可在服务配置中修改相关配置(角色权限、日志、NAS等等)，具体参见函数计算和RAM相关文档。


### 4、创建函数和http触发器

在上个步骤中创建的`test-think`服务器下创建一个函数`fc`

![创建函数4-1.png](https://assets.oldmen.cn/fc-thinkphp/docs/images/4-1.png)

选择HTTP函数

![选择HTTP函数4-2.png](https://assets.oldmen.cn/fc-thinkphp/docs/images/4-2.png)

输入函数名称`test-fun`,选择php7.2运行环境，其他可默认，触发器中，输入触发器名称`test-fun`,认证方式选择`anonymous`,请求方式按照需求选择，这里全选。

![配置函数和触发器4-3.png](https://assets.oldmen.cn/fc-thinkphp/docs/images/4-3.png)



### 5、配置自定义域名

在控制台自定义域名下，创建一个自定义域名，绑定到上个步骤中创建的函数

![配置自定义域名5-1.png](https://assets.oldmen.cn/fc-thinkphp/docs/images/5-1.png)


### 6、下载代码安装

回到第4步创建的函数`test-fun`概览界面，点击导出，选择导出配置和代码。

![导出配置和代码6-1.png](https://assets.oldmen.cn/fc-thinkphp/docs/images/6-1.png)

下载并解压到本地，目录结构如下：

```
    /
      test-think
        test-fun
          index.php
      template.yml
```

进入 `test-fun`目录：

```shell script
cd test-think/test-fun
```

创建tp应用(如果已有项目，可跳过此步骤，直接复制项目到此`test-think/test-fun`目录下)

```shell script
composer create-project topthink/think tp
``` 

> 注：thinkphp 6.0.3中，session的file驱动中缓存目录指定为`/rumtime`，而这个目录在函数计算时不可写的，会出现错误。
>最新的`6.0.x-dev`开发版中，已经修复这个bug，在发布新版本之前需要使用这个版本:`composer require topthink/framework 6.0.x-dev`
>参见：https://github.com/top-think/framework/pull/2300/commits/264a79521b3062788f5e6cd3c603974ceb63df7e

转到项目目录`tp`,并安装`liuwave/fc-thinkphp`

```shell script
cd tp
composer require liuwave/fc-thinkphp
```


### 7.修改`test-think/test-fun/index.php`:

```php
<?php
use RingCentral\Psr7\Response;
use liuwave\fc\think\FcThink;

function handler($request, $context) : Response
{
        //设置thinkphp根目录
    $appPath=__DIR__ . '/tp';
    require $appPath . '/vendor/autoload.php';
    return (new FcThink($request, $context,['root'=> $appPath, 'runtime_path'=>'/tmp/']))->run();
}
```
修改tp入口文件`test-think/test-fun/tp/public/index.php`(仅在cgi模式下需要修改):

```php
<?php

// [ 应用入口文件 ]
namespace think;


require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
// $http = (new App())->http;
$app=new App();
$app->setRuntimePath(getenv('PHP_RUNTIME_PATH')?:'/tmp/');
$http = $app->http;

$response = $http->run();

$response->send();

$http->end($response);
```


### 8、部署代码

转到根目录`/`:

```shell script
# 当前是`test-think/test-fun/tp`
cd ../../../

```

执行部署命令(权限策略配置请参考阿里云相关文档)：

```shell script
fun deploy -y
```

![执行结果8-1.png](https://assets.oldmen.cn/fc-thinkphp/docs/images/8-1.png)


访问`http://test-think.oldmen.cn/`,已成功安装：

![执行结果8-2.png](https://assets.oldmen.cn/fc-thinkphp/docs/images/8-2.png)


## 参考

### Cli模式 vs cgi模式

默认为cgi模式，可切换为cli模式。

#### CGI模式

cgi不能在thinkphp项目中调用函数计算内置功能的对象如fcLogger/fcPhpCgiProxy/fcSysLogger。

cgi是通过函数计算提供的$GLOBALS['fcPhpCgiProxy'] 对象实现：

```php

 requestPhpCgi($request, $docRoot, $phpFile = "index.php", $fastCgiParams = [], $options = []);

```

- $request: 跟 php http 触发器 入口的参数一致
- $docRoot: thinkphp项目的根目录
- $phpFile: 用于拼接 cgi 参数中的 SCRIPT_FILENAME 的默认参数
- $fastCgiParams: 函数计算内部尽量根据fastCgiParams`覆盖一些参数 (reference: [cgi](https://en.wikipedia.org/wiki/Common_Gateway_Interface))
- $options: array类型，可选参数， debug_show_cgi_params 设为 true ，会打印每次请求 php 解析时候的 cgi 参数， 默认为 false ；readWriteTimeout 设置解析的时间， 默认为 5 秒

#### CLI模式

CLI模式支持在thinkphp项目中调用函数计算内置功能的对象如fcLogger/fcPhpCgiProxy/fcSysLogger。

参见[liuwave/think-log-driver-sls](https://github.com/liuwave/think-log-driver-sls)



### VPC权限

如需访问OSS、NAS等资源，需要配置VPC访问权限：[配置 VPC 功能](https://help.aliyun.com/knowledge_detail/72959.html?source=5176.11533457&userCode=re2rax3m&type=copy)

### 使用OSS上传文件

参考：[liuwave/think-filesystem-driver-oss](https://github.com/liuwave/think-filesystem-driver-oss)


### 使用NAS

参见：[挂载NAS访问](https://help.aliyun.com/document_detail/87401.html?source=5176.11533457&userCode=re2rax3m&type=copy)

### 日志服务

兼容函数计算

参考：[liuwave/think-log-driver-sls](https://github.com/liuwave/think-log-driver-sls)


### 开启调试、数据库配置

使用fun部署代码时，会忽略`.env`，若要开启thinkphp的调试功能、配置数据库，可参照以下方法：

方法一、在部署之后手动添加`env`文件

在函数计算管理后台，将相关配置写入`.env`文件(`test-think/test-fun/tp/.env`(以上文为例))。


方法二、在函数计算项目的根目录下的`/template.yml`设置环境变量（以上文为例）：

>注： 需要在添加`PHP_`前缀，同时需要把`.`换成`_`, 并转化成大写，如 `PHP_APP_DEBUG` 、`PHP_DATABASE_TYPE`、`PHP_DATABASE_HOSTNAME`

```yaml
ROSTemplateFormatVersion: '2015-09-01'
Transform: 'Aliyun::Serverless-2018-04-03'
Resources: 
  test-think: #这是服务
    Type: 'Aliyun::Serverless::Service'
    Properties:
      Role: 'acs:ram::****:role/fc-think-test-role'
      LogConfig:
        Project: aliyun-fc-cn-beijing-*****
        Logstore: function-log
      InternetAccess: true
    test-fun: # 函数名
      Type: 'Aliyun::Serverless::Function'
      Properties:
        Handler: index.handler
        Runtime: php7.2
        Timeout: 60
        MemorySize: 128
        EnvironmentVariables: #环境变量
          LD_LIBRARY_PATH: >-
            /code/.fun/root/usr/local/lib:/code/.fun/root/usr/lib:/code/.fun/root/usr/lib/x86_64-linux-gnu:/code/.fun/root/usr/lib64:/code/.fun/root/lib:/code/.fun/root/lib/x86_64-linux-gnu:/code/.fun/root/python/lib/python2.7/site-packages:/code/.fun/root/python/lib/python3.6/site-packages:/code:/code/lib:/usr/local/lib
          NODE_PATH: '/code/node_modules:/usr/local/lib/node_modules'
          PATH: >-
            /code/.fun/root/usr/local/bin:/code/.fun/root/usr/local/sbin:/code/.fun/root/usr/bin:/code/.fun/root/usr/sbin:/code/.fun/root/sbin:/code/.fun/root/bin:/code:/code/node_modules/.bin:/code/.fun/python/bin:/code/.fun/node_modules/.bin:/usr/local/bin:/usr/local/sbin:/usr/bin:/usr/sbin:/sbin:/bin
          PYTHONUSERBASE: /code/.fun/python
          PHP_APP_DEBUG: true
          PHP_DATABASE_TYPE: mysql
          PHP_DATABASE_HOSTNAME: 127.0.0.1
          PHP_DATABASE_DATABASE: 'test'
          PHP_DATABASE_USERNAME: root
          PHP_DATABASE_PASSWORD: 'test'
          PHP_DATABASE_HOSTPORT: 3306
          PHP_DATABASE_CHARSET: utf8
          PHP_DATABASE_PREFIX: 'p_'
        CodeUri: ./test-think/test-fun
      Events:
        test-fun:
          Type: HTTP
          Properties:
            AuthType: anonymous
            Methods:
              - GET
              - POST
              - PUT
              - DELETE
              - HEAD
              - PATCH



```
    

方法三、在函数计算后台更改

函数 `概览` 界面中，点击 `配置` ，在最下方 添加 或 删除环境变量，`键`的格式 参照 `方法二`。



若使用并开启了[liuwave/think-log-driver-sls](https://github.com/liuwave/think-log-driver-sls)可在函数计算的对应函数的日志查询功能中查看错误日志。


### PHP环境配置

可在根目录 添加 `extension`文件夹，在其中放入额外的配置 `.ini`,比如修改文件上传大小的限制：


```ini
#文件 /extension/filesize.ini
upload_max_filesize = 6m
```

### 文件上传，支持单文件和多文件上传

参考：
- [thinkphp6.0上传文件](https://www.kancloud.cn/manual/thinkphp6_0/1037639)
- [liuwave/think-filesystem-driver-oss](https://github.com/liuwave/think-filesystem-driver-oss)

> 注，函数计算限制，仅支持不超过6M大小的文件上传或下载，参见[使用限制](https://help.aliyun.com/document_detail/51907.html?source=5176.11533457&userCode=re2rax3m&type=copy)

> 默认环境中，upload_max_filesize为 2M,可以参照上文[PHP环境配置](#PHP环境配置)进行更改


## BUG提交

[Issue](https://github.com/liuwave/fc-thinkphp/issues)

## 开源许可

The MIT License

