<?php

namespace app\controller;

use app\BaseController;

class Index extends BaseController
{
    public function index()
    {
        $sapi = php_sapi_name();
        
        return <<<EHO
<!DOCTYPE html>
<html lang='zh-CN'>
<head>
<title>fc-thinkphp: 这是一个使用阿里云函数计算 php runtime搭建serverless thinkphp6.0项目的插件，只需要在函数计算的http触发器入口函数添加几行代码就能让thinkphp6.0运行在函数计算的php运行环境下。</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/4.0.0/github-markdown.min.css" media="screen" rel="stylesheet" />


</head>

<body>
<div style="    width: 1040px;padding: 0 20px;    margin: 0 auto;">
<div style="    font-size: 16px;    padding: 1rem 2rem 1rem 2rem;"  class="file_content markdown-body">
<h1>
<a id="fc-thinkphp" class="anchor" href="#fc-thinkphp"></a>fc-thinkphp</h1>
<p>这是一个使用阿里云函数计算 php runtime搭建serverless thinkphp6.0项目的插件，只需要在函数计算的http触发器入口函数添加几行代码就能让thinkphp6.0运行在函数计算的php运行环境下。</p>

<h2>
 php runtime环境（当前运行模式:{$sapi}）
</h2>

<p>
<a href="/info" target="_blank">查看当前函数计算 phpinfo();</a>

</p>

<h2>
<a id="快速开始" class="anchor" href="#%E5%BF%AB%E9%80%9F%E5%BC%80%E5%A7%8B"></a>快速开始</h2>
<p>首先在thinkphp6.0项目中安装插件：</p>
<div class="white"><div class="highlight"><pre><span id="LC1" class="line">composer require liuwave/fc-thinkphp</span></pre></div></div>
<p>函数计算入口<code>index.php</code>中，添加：</p>
<div class="white"><div class="highlight"><pre><span id="LC1" class="line"></span>
<span id="LC2" class="line"><span class="k">function</span> <span class="nf">handler</span><span class="p">(</span><span class="nv">\$request</span><span class="p">,</span> <span class="nv">\$context</span><span class="p">)</span> <span class="o">:</span> <span class="nx">Response</span></span>
<span id="LC3" class="line"><span class="p">{</span>   </span>
<span id="LC4" class="line">       <span class="c1">//设置thinkphp根目录</span></span>
<span id="LC5" class="line">    <span class="nv">\$appPath</span><span class="o">=</span><span class="k">__DIR__</span> <span class="o">.</span> <span class="s1">'/tp'</span><span class="p">;</span></span>
<span id="LC6" class="line">    <span class="k">require</span> <span class="nv">\$appPath</span> <span class="o">.</span> <span class="s1">'/vendor/autoload.php'</span><span class="p">;</span></span>
<span id="LC7" class="line">    <span class="k">return</span> <span class="p">(</span><span class="k">new</span> <span class="nx">FcThink</span><span class="p">(</span><span class="nv">\$request</span><span class="p">,</span> <span class="nv">\$appPath</span><span class="p">,</span> <span class="s1">'/tmp/'</span><span class="p">))</span></span>
<span id="LC8" class="line">      <span class="o">-&gt;</span><span class="na">withHeader</span><span class="p">([</span><span class="s1">'context'</span> <span class="o">=&gt;</span> <span class="nv">\$context</span><span class="p">])</span></span>
<span id="LC9" class="line">      <span class="o">-&gt;</span><span class="na">run</span><span class="p">();</span></span>
<span id="LC10" class="line"><span class="p">}</span></span></pre></div></div>
<h2>
<a id="新手入门" class="anchor" href="#%E6%96%B0%E6%89%8B%E5%85%A5%E9%97%A8"></a>新手入门</h2>
<p>首次使用函数计算，可以参照一下步骤：</p>
<h3>
<a id="1准备" class="anchor" href="#1%E5%87%86%E5%A4%87"></a>1、准备</h3>
<ul>
<li>开通 <a href="https://www.aliyun.com/product/fc?source=5176.11533457&amp;userCode=re2rax3m&amp;type=copy">函数计算</a>,每月免费100万次请求，免费40万(CU-秒)执行时间，日IP在1000以下的博客站足够用。</li>
<li>(可选)开通oss</li>
<li>(可选)开通NAS</li>
<li>域名</li>
</ul>
<blockquote>
<p>注：NAS和OSS主要用于储存上传文件，如无需要可不开通</p>
</blockquote>
<h3>
<a id="2安装和配置fun工具" class="anchor" href="#2%E5%AE%89%E8%A3%85%E5%92%8C%E9%85%8D%E7%BD%AEfun%E5%B7%A5%E5%85%B7"></a>2、安装和配置fun工具</h3>
<p>安装和配置参见：<a href="https://help.aliyun.com/document_detail/161136.html?source=5176.11533457&amp;userCode=re2rax3m&amp;type=copy">安装Fun</a></p>
<h3>
<a id="3在函数计算控制台创建服务" class="anchor" href="#3%E5%9C%A8%E5%87%BD%E6%95%B0%E8%AE%A1%E7%AE%97%E6%8E%A7%E5%88%B6%E5%8F%B0%E5%88%9B%E5%BB%BA%E6%9C%8D%E5%8A%A1"></a>3、在函数计算控制台创建服务</h3>
<p><img src="https://assets.oldmen.cn/fc-thinkphp/docs/images/1.png" alt="进入控制台创建服务1.png"></p>
<p>输入名称<code>test-think</code>,可选择绑定日志,点击创建。</p>
<p><img src="https://assets.oldmen.cn/fc-thinkphp/docs/images/2.png" alt="输入名称2.png"></p>
<p>创建成功后，可在服务配置中修改相关配置(角色权限、日志、NAS等等)，具体参见函数计算和RAM相关文档。</p>
<h3>
<a id="4创建函数和http触发器" class="anchor" href="#4%E5%88%9B%E5%BB%BA%E5%87%BD%E6%95%B0%E5%92%8Chttp%E8%A7%A6%E5%8F%91%E5%99%A8"></a>4、创建函数和http触发器</h3>
<p>在上个步骤中创建的<code>test-think</code>服务器下创建一个函数<code>fc</code></p>
<p><img src="https://assets.oldmen.cn/fc-thinkphp/docs/images/4-1.png" alt="创建函数4-1.png"></p>
<p>选择HTTP函数</p>
<p><img src="https://assets.oldmen.cn/fc-thinkphp/docs/images/4-2.png" alt="选择HTTP函数4-2.png"></p>
<p>输入函数名称<code>test-fun</code>,选择php7.2运行环境，其他可默认，触发器中，输入触发器名称<code>test-fun</code>,认证方式选择<code>anonymous</code>,请求方式按照需求选择，这里全选。</p>
<p><img src="https://assets.oldmen.cn/fc-thinkphp/docs/images/4-3.png" alt="配置函数和触发器4-3.png"></p>
<h3>
<a id="5配置自定义域名" class="anchor" href="#5%E9%85%8D%E7%BD%AE%E8%87%AA%E5%AE%9A%E4%B9%89%E5%9F%9F%E5%90%8D"></a>5、配置自定义域名</h3>
<p>在控制台自定义域名下，创建一个自定义域名，绑定到上个步骤中创建的函数</p>
<p><img src="https://assets.oldmen.cn/fc-thinkphp/docs/images/5-1.png" alt="配置自定义域名5-1.png"></p>
<h3>
<a id="6下载代码安装" class="anchor" href="#6%E4%B8%8B%E8%BD%BD%E4%BB%A3%E7%A0%81%E5%AE%89%E8%A3%85"></a>6、下载代码安装</h3>
<p>回到第4步创建的函数<code>test-fun</code>概览界面，点击导出，选择导出配置和代码。</p>
<p><img src="https://assets.oldmen.cn/fc-thinkphp/docs/images/6-1.png" alt="导出配置和代码6-1.png"></p>
<p>下载并解压到本地，目录结构如下：</p>
<div class="white"><div class="highlight"><pre><span id="LC1" class="line">    /</span>
<span id="LC2" class="line">      test-think</span>
<span id="LC3" class="line">        test-fun</span>
<span id="LC4" class="line">          index.php</span>
<span id="LC5" class="line">      template.yml</span></pre></div></div>
<p>进入 <code>test-fun</code>目录：</p>
<div class="white"><div class="highlight"><pre><span id="LC1" class="line"><span class="nb">cd </span>test-think/test-fun</span></pre></div></div>
<p>创建tp应用(如果已有项目，可跳过此步骤，直接复制项目到此<code>test-think/test-fun</code>目录下)</p>
<div class="white"><div class="highlight"><pre><span id="LC1" class="line">composer create-project topthink/think tp</span></pre></div></div>
<blockquote>
<p>注：thinkphp 6.0.3中，session的file驱动中缓存目录指定为<code>/rumtime</code>，而这个目录在函数计算时不可写的，会出现错误。
最新的<code>6.0.x-dev</code>开发版中，已经修复这个bug，在发布新版本之前需要使用这个版本:<code>composer require topthink/framework 6.0.x-dev</code>
参见：<a href="https://github.com/top-think/framework/pull/2300/commits/264a79521b3062788f5e6cd3c603974ceb63df7e">https://github.com/top-think/framework/pull/2300/commits/264a79521b3062788f5e6cd3c603974ceb63df7e</a></p>
</blockquote>
<p>转到项目目录<code>tp</code>,并安装<code>liuwave/fc-thinkphp</code></p>
<div class="white"><div class="highlight"><pre><span id="LC1" class="line"><span class="nb">cd </span>tp</span>
<span id="LC2" class="line">composer require liuwave/fc-thinkphp</span></pre></div></div>
<h3>
<a id="7修改test-thinktest-funindexphp" class="anchor" href="#7%E4%BF%AE%E6%94%B9test-thinktest-funindexphp"></a>7.修改<code>test-think/test-fun/index.php</code>:</h3>
<div class="white"><div class="highlight"><pre><span id="LC1" class="line"><span class="cp">&lt;?php</span></span>
<span id="LC2" class="line"><span class="kn">use</span> <span class="nn">RingCentral\Psr7\Response</span><span class="p">;</span></span>
<span id="LC3" class="line"><span class="kn">use</span> <span class="nn">liuwave\fc\think\FcThink</span><span class="p">;</span></span>
<span id="LC4" class="line"></span>
<span id="LC5" class="line"><span class="k">function</span> <span class="nf">handler</span><span class="p">(</span><span class="nv">\$request</span><span class="p">,</span> <span class="nv">\$context</span><span class="p">)</span> <span class="o">:</span> <span class="nx">Response</span></span>
<span id="LC6" class="line"><span class="p">{</span></span>
<span id="LC7" class="line">        <span class="c1">//设置thinkphp根目录</span></span>
<span id="LC8" class="line">        <span class="nv">\$appPath</span><span class="o">=</span><span class="k">__DIR__</span> <span class="o">.</span> <span class="s1">'/tp'</span><span class="p">;</span></span>
<span id="LC9" class="line">        <span class="k">require</span> <span class="nv">\$appPath</span> <span class="o">.</span> <span class="s1">'/vendor/autoload.php'</span><span class="p">;</span></span>
<span id="LC10" class="line">        <span class="k">return</span> <span class="p">(</span><span class="k">new</span> <span class="nx">FcThink</span><span class="p">(</span><span class="nv">\$request</span><span class="p">,</span> <span class="nv">\$appPath</span><span class="p">,</span> <span class="s1">'/tmp/'</span><span class="p">))</span></span>
<span id="LC11" class="line">          <span class="o">-&gt;</span><span class="na">withHeader</span><span class="p">([</span><span class="s1">'context'</span> <span class="o">=&gt;</span> <span class="nv">\$context</span><span class="p">])</span></span>
<span id="LC12" class="line">          <span class="o">-&gt;</span><span class="na">run</span><span class="p">();</span></span>
<span id="LC13" class="line"><span class="p">}</span></span></pre></div></div>
<h3>
<a id="8部署代码" class="anchor" href="#8%E9%83%A8%E7%BD%B2%E4%BB%A3%E7%A0%81"></a>8、部署代码</h3>
<p>转到根目录<code>/</code>:</p>
<div class="white"><div class="highlight"><pre><span id="LC1" class="line"><span class="c"># 当前是`test-think/test-fun/tp`</span></span>
<span id="LC2" class="line"><span class="nb">cd</span> ../../../</span>
<span id="LC3" class="line"></span></pre></div></div>
<p>执行部署命令(权限策略配置请参考阿里云相关文档)：</p>
<div class="white"><div class="highlight"><pre><span id="LC1" class="line">fun deploy <span class="nt">-y</span></span></pre></div></div>
<p><img src="https://assets.oldmen.cn/fc-thinkphp/docs/images/8-1.png" alt="执行结果8-1.png"></p>
<p>访问<code>http://test-think.oldmen.cn/</code>,已成功安装：</p>
<p><img src="https://assets.oldmen.cn/fc-thinkphp/docs/images/8-2.png" alt="执行结果8-2.png"></p>
<h2>
<a id="参考" class="anchor" href="#%E5%8F%82%E8%80%83"></a>参考</h2>
<h3>
<a id="vpc权限" class="anchor" href="#vpc%E6%9D%83%E9%99%90"></a>VPC权限</h3>
<p>如需访问OSS、NAS等资源，需要配置VPC访问权限：<a href="https://help.aliyun.com/knowledge_detail/72959.html?source=5176.11533457&amp;userCode=re2rax3m&amp;type=copy">配置 VPC 功能</a></p>
<h3>
<a id="使用oss上传文件" class="anchor" href="#%E4%BD%BF%E7%94%A8oss%E4%B8%8A%E4%BC%A0%E6%96%87%E4%BB%B6"></a>使用OSS上传文件</h3>
<p>参考：<a href="https://github.com/liuwave/think-filesystem-driver-oss">liuwave/think-filesystem-driver-oss</a></p>
<h3>
<a id="使用nas" class="anchor" href="#%E4%BD%BF%E7%94%A8nas"></a>使用NAS</h3>
<p>参见：<a href="https://help.aliyun.com/document_detail/87401.html?source=5176.11533457&amp;userCode=re2rax3m&amp;type=copy">挂载NAS访问</a></p>
<h3>
<a id="使用函数计算自带的日志功能" class="anchor" href="#%E4%BD%BF%E7%94%A8%E5%87%BD%E6%95%B0%E8%AE%A1%E7%AE%97%E8%87%AA%E5%B8%A6%E7%9A%84%E6%97%A5%E5%BF%97%E5%8A%9F%E8%83%BD"></a>使用函数计算自带的日志功能</h3>
<p>参考：<a href="https://github.com/liuwave/think-log-driver-fc">liuwave/think-log-driver-fc</a></p>
<h3>
<a id="开启调试" class="anchor" href="#%E5%BC%80%E5%90%AF%E8%B0%83%E8%AF%95"></a>开启调试</h3>
<p>使用fun部署代码时，会忽略<code>.env</code>，若要开启thinkphp的调试功能，需要在<code>test-think/test-fun/tp</code>(以上文为例)下添加<code>.env</code>文件，写入<code>APP_DEBUG = true</code>。</p>
<p>若使用并开启了<a href="https://github.com/liuwave/think-log-driver-fc">liuwave/think-log-driver-fc</a>可在函数计算的对应函数的日志查询功能中查看日志。</p>
<h3>
<a id="文件上传支持单文件和多文件上传" class="anchor" href="#%E6%96%87%E4%BB%B6%E4%B8%8A%E4%BC%A0%E6%94%AF%E6%8C%81%E5%8D%95%E6%96%87%E4%BB%B6%E5%92%8C%E5%A4%9A%E6%96%87%E4%BB%B6%E4%B8%8A%E4%BC%A0"></a>文件上传，支持单文件和多文件上传</h3>
<p>参考：</p>
<ul>
<li><a href="https://www.kancloud.cn/manual/thinkphp6_0/1037639">thinkphp6.0上传文件</a></li>
<li><a href="https://github.com/liuwave/think-filesystem-driver-oss">liuwave/think-filesystem-driver-oss</a></li>
</ul>
<blockquote>
<p>注，函数计算限制，仅支持不超过6M大小的文件上传或下载，参见<a href="https://help.aliyun.com/document_detail/51907.html?source=5176.11533457&amp;userCode=re2rax3m&amp;type=copy">使用限制</a></p>
</blockquote>
<h2>
<a id="bug提交" class="anchor" href="#bug%E6%8F%90%E4%BA%A4"></a>BUG提交</h2>
<p><a href="https://github.com/liuwave/fc-thinkphp/issues">Issue</a></p>
<h2>
<a id="开源许可" class="anchor" href="#%E5%BC%80%E6%BA%90%E8%AE%B8%E5%8F%AF"></a>开源许可</h2>
<p>The MIT License</p></div></div>
</body>
</html>
EHO;
    }
    
    public function info()
    {
        ob_start();
        
        var_dump($this->request->server('context_credentials_accessKeyID'));
        var_dump($this->request->server('context_credentials_accessKeySecret'));
        var_dump($this->request->server('context_credentials_securityToken'));
        var_dump($this->app->getRuntimePath());
        var_dump($this->request->server());
        var_dump($this->request->header());
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
    
    public function upload()
    {
        $file       = \request()->file('file');
        $filesystem = \think\facade\Filesystem::disk('oss');
        $saveName   = $filesystem->putFile('/path/to/save/file', $file, 'md5');
        $saveName   = str_replace('\\', '/', $saveName);
        $fullName   = \think\facade\Filesystem::getDiskConfig('oss', 'url').'/'.$saveName;
        
        return json(['full_url' => $fullName]);
    }
}
