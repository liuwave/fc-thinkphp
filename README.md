# fc-thinkphp


这是一个使用阿里云函数计算 php runtime搭建serverless thinkphp6.0项目的插件，只需要在函数计算的http触发器入口函数添加几行代码就能让thinkphp6.0运行在函数计算的php运行环境下。

## 快速开始

首先在thinkphp6.0项目中安装插件：

```shell script
composer require liuwave/fc-thinkphp
```

函数计算入口`index.php`中，添加：

```php

function handler($request, $context) : Response
{   
       //设置thinkphp根目录
    $appPath=__DIR__ . '/tp';
    require $appPath . '/vendor/autoload.php';
    return (new FcThink($request, $appPath, '/tmp/'))
      ->withHeader(['context' => $context])
      ->run();
}
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
        return (new FcThink($request, $appPath, '/tmp/'))
          ->withHeader(['context' => $context])
          ->run();
}
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


### VPC权限

如需访问OSS、NAS等资源，需要配置VPC访问权限：[配置 VPC 功能](https://help.aliyun.com/knowledge_detail/72959.html?source=5176.11533457&userCode=re2rax3m&type=copy)

### 使用OSS上传文件

参考：[liuwave/think-filesystem-driver-oss](https://github.com/liuwave/think-filesystem-driver-oss)


### 使用NAS

参见：[挂载NAS访问](https://help.aliyun.com/document_detail/87401.html?source=5176.11533457&userCode=re2rax3m&type=copy)

### 使用函数计算自带的日志功能

参考：[liuwave/think-log-driver-fc](https://github.com/liuwave/think-log-driver-fc)


### 开启调试

使用fun部署代码时，会忽略`.env`，若要开启thinkphp的调试功能，需要在`test-think/test-fun/tp`(以上文为例)下添加`.env`文件，写入`APP_DEBUG = true`。

若使用并开启了[liuwave/think-log-driver-fc](https://github.com/liuwave/think-log-driver-fc)可在函数计算的对应函数的日志查询功能中查看日志。


## BUG提交

[Issue](https://github.com/liuwave/fc-thinkphp/issues)

## 开源许可

The MIT License

