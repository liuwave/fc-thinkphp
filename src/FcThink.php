<?php
/**
 * Created by PhpStorm.
 * User: liuwave
 * Date: 2020/7/5 21:33
 * Description:
 */

namespace liuwave\fc\think;

use liuwave\fc\think\multipart\Parser;
use liuwave\fc\think\multipart\UploadedFile;
use Psr\Http\Message\ServerRequestInterface;
use RingCentral\Psr7\Request;
use RingCentral\Psr7\Response;
use think\App;
use think\facade\Cookie;

/**
 * Class FcThink
 */
class FcThink
{
    /**
     * @var \think\App
     */
    protected $app;
    
    /**
     * @var \think\Request
     */
    protected $request;
    
    /**
     * @var Request $fcRequest
     */
    protected $fcRequest;
    
    /**
     * @var array
     */
    protected $config = [
      'is_cli'       => false,
      'ignore_file'  => false,
      'root'         => '/code/tp',
      'runtime_path' => '/tmp/',
    ];
    
    /**
     * @var array
     */
    protected $context = [];
    
    /**
     * @var null
     */
    private $filename;
    
    /**
     * FcThink constructor.
     *
     * @param ServerRequestInterface $fcRequest
     * @param array                  $context
     * @param array                  $config
     */
    public function __construct(
      ServerRequestInterface $fcRequest,
      array $context,
      $config = []
    ) {
        $this->fcRequest = $fcRequest;
        $this->context   = $this->parseContext($context);
        
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->config[ 'root' ]         = rtrim($this->config[ 'root' ], '/');
        $this->config[ 'runtime_path' ] = rtrim($this->config[ 'runtime_path' ], '/').'/';
        
        if (!$this->config[ 'ignore_file' ]) {
            $path     = $this->fcRequest->getAttribute('path');
            $filename = rawurldecode($this->config[ 'root' ].'/public'.$path);
            $pathInfo = pathinfo($filename);
            if (isset($pathInfo[ 'extension' ]) &&
              strtolower($pathInfo[ 'extension' ]) !== 'php' &&
              file_exists($this->filename) &&
              is_file($this->filename)) {
                $this->filename = $filename;
                
                return;
            }
        }
        
        $this->filename = null;
    }
    
    /**
     * @return Response
     */
    public function run()
    {
        if ($this->filename) {
            $handle   = fopen($this->filename, "r");
            $contents = fread($handle, filesize($this->filename));
            fclose($handle);
            
            return new Response(
              200, [
              'Content-Type'  => $GLOBALS[ 'fcPhpCgiProxy' ]->getMimeType($this->filename),
              'Cache-Control' => "max-age=8640000",
              'Accept-Ranges' => 'bytes',
            ], $contents
            );
        }
        
        if ($this->config[ 'is_cli' ]) {
            //本地测试环境适配
            if (getenv('local')) {
                putenv('accessKeyID='.(getenv('FC_ACCESS_KEY_ID') ? : ""));
                putenv('accessKeySecret='.(getenv('FC_ACCESS_KEY_SECRET') ? : ""));
                putenv('topic='.(getenv('FC_SERVICE_NAME') ? : ""));
                putenv('FC_QUALIFIER=LOCAL_TEST');
            }
            
            $this->app = new App($this->config[ 'root' ]);
            $this->app->setRuntimePath($this->config[ 'runtime_path' ]);
            $this->request = $this->app->request;
            $this->parse();
            $http     = $this->app->http;
            $response = $http->run($this->request);
            $http->end($response);
            $response->header(['Set-Cookie' => $this->getHeaderCookie()]);
            
            return new Response($response->getCode(), $response->getHeader(), $response->getContent());
        }
        else {
            //设置runtime_path
            $parseServerParams = $this->parseServerParams();
            
            //本地测试环境适配
            if (getenv('local')) {
                $parseServerParams[ 'accessKeyID' ]     = getenv('FC_ACCESS_KEY_ID') ? : "";
                $parseServerParams[ 'accessKeySecret' ] = getenv('FC_ACCESS_KEY_SECRET') ? : "";
                $parseServerParams[ 'topic' ]           = getenv('FC_SERVICE_NAME') ? : "";
                $parseServerParams[ 'FC_QUALIFIER' ]    = 'LOCAL_TEST';
            }
            $parseServerParams[ 'PHP_RUNTIME_PATH' ] = $this->config[ 'runtime_path' ];
            
            return $GLOBALS[ 'fcPhpCgiProxy' ]->requestPhpCgi(
              $this->fcRequest,
              $this->config[ 'root' ].'/public',
              "index.php",
              $parseServerParams,
              ['debug_show_cgi_params' => false, 'readWriteTimeout' => 15000]
            );
        }
    }
    
    /**
     * @param array $context
     *
     * @return array
     */
    protected function parseContext(array $context = []) : array
    {
        $result = [];
        
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $result[ "context_{$key}_{$subKey}" ] = (string)$subValue;
                }
            }
            else {
                $result[ "context_{$key}" ] = (string)$value;
            }
        }
        
        return $result;
    }
    
    /**
     * @return array
     */
    protected function getHeaderCookie()
    {
        /**
         *解决 Cannot modify header information - headers already sent by (output started at xx的问题
         * @link https://developer.aliyun.com/article/683415
         */
        
        $cookies = [];
        
        $sourceCookies = Cookie::getCookie();
        foreach ($sourceCookies as $name => $val) {
            [$value, $expire, $option] = $val;
            $cookies[] = "{$name}={$value}".
              ($expire > 0 ? "; expires=".gmstrftime("%A, %d-%b-%Y %H:%M:%S GMT", $expire + (86400 * 365)) : '').
              "; path={$option[ 'path' ]}".
              (!empty($option[ 'domain' ]) ? "; domain={$option[ 'domain' ]}" : "").
              ($option[ 'secure' ] ? "; secure" : '').
              ($option[ 'httponly' ] ? "; httponly" : '');
        }
        
        return $cookies;
    }
    
    /**
     *
     * @return $this
     */
    protected function parse()
    {
        $type = strtolower($this->fcRequest->getHeaderLine('Content-Type'));
        [$type] = explode(';', $type);
        
        if ($this->fcRequest->getMethod() === "POST" && $type === 'multipart/form-data') {
            $this->request->withFiles($this->parseFiles());
        }
        $this->request->withGet($this->fcRequest->getQueryParams())
          ->setMethod($this->fcRequest->getMethod())
          ->withInput(
            $this->fcRequest->getBody()
              ->getContents()
          )
          ->withHeader($this->parseHeaders())
          ->withServer($this->parseServerParams())
          ->withCookie($this->parseCookies());
        
        return $this;
    }
    
    /**
     * @return array
     */
    protected function parseHeaders()
    {
        return array_merge(
          ($this->request ? $this->request->header() : []),
          array_map(
            function ($item) {
                return $item[ 0 ] ?? '';
            },
            $this->fcRequest->getHeaders()
          )
        );
    }
    
    /**
     * @return array
     */
    protected function parseCookies()
    {
        $line    = preg_replace('/^Set-Cookie: /i', '', trim($this->fcRequest->getHeaderLine('Cookie')));
        $array   = explode(';', $line);
        $cookies = [
          'cookies' => [],
        ];
        foreach ($array as $data) {
            $temp      = explode('=', $data);
            $temp[ 0 ] = trim($temp[ 0 ]);
            
            if (empty($temp[ 0 ])) {
                continue;
            }
            
            $temp[ 1 ] = $temp[ 1 ] ?? '';
            
            if ($temp[ 0 ] == 'expires') {
                $temp[ 1 ] = strtotime($temp[ 1 ]);
            }
            if ($temp[ 0 ] == 'secure') {
                $temp[ 1 ] = "true";
            }
            if (in_array($temp[ 0 ], ['domain', 'expires', 'path', 'secure', 'comment'])) {
                $cookies[ $temp[ 0 ] ] = $temp[ 1 ];
            }
            else {
                $cookies[ 'cookies' ][ $temp[ 0 ] ] = $temp[ 1 ];
            }
        }
        
        return array_merge($this->fcRequest->getCookieParams(), $cookies[ 'cookies' ]);
    }
    
    /**
     * @return array
     */
    protected function parseServerParams()
    {
        return array_change_key_case(
          array_merge(
            (array)($this->request ? $this->request->server() : []),
            (array)$this->fcRequest->getServerParams(),
            [
              'DOCUMENT_ROOT'                  => $this->config[ 'root' ].'/public',
              'SERVER_SOFTWARE'                => FC_CGI_SERVER_SOFTWARE,
              'SERVER_PROTOCOL'                => FC_CGI_SERVER_PROTOCOL,
              'SERVER_NAME'                    => $this->fcRequest->getHeaderLine('host'),
              'SERVER_PORT'                    => '80',
              'REQUEST_URI'                    => $this->fcRequest->getAttribute('requestURI'),
              'REQUEST_METHOD'                 => $this->fcRequest->getMethod(),
              'SCRIPT_NAME'                    => "/index.php",
              'SCRIPT_FILENAME'                => $this->config[ 'root' ]."/public/index.php",
              'PATH_INFO'                      => $this->fcRequest->getAttribute('path'),
              'PHP_SELF'                       => "/index.php?s=".($this->fcRequest->getAttribute('path')),
              'QUERY_STRING'                   => array_pad(
                explode('?', $this->fcRequest->getAttribute('requestURI')),
                2,
                ''
              )[ 1 ],
              'HTTP_HOST'                      => $this->fcRequest->getHeaderLine('host'),
              'HTTP_CACHE_CONTROL'             => $this->fcRequest->getHeaderLine('Cache-Control'),
              'HTTP_UPGRADE_INSECURE_REQUESTS' => $this->fcRequest->getHeaderLine('Upgrade-Insecure-Requests'),
              'HTTP_USER_AGENT'                => $this->fcRequest->getHeaderLine('User-Agent'),
              'HTTP_ACCEPT'                    => $this->fcRequest->getHeaderLine('Accept'),
              'HTTP_ACCEPT_LANGUAGE'           => $this->fcRequest->getHeaderLine('Accept-Language'),
              'HTTP_COOKIE'                    => $this->fcRequest->getHeaderLine('Cookie'),
            ],
            $this->context
          ),
          CASE_UPPER
        );
    }
    
    /**
     * @return array
     * @link https://github.com/vangie/fc-file-transfer/blob/master/php/index.php
     */
    protected function parseFiles()
    {
        $UPLOADED_DIR = $this->config[ 'runtime_path' ].'/uploaded';
        if (!file_exists($UPLOADED_DIR)) {
            mkdir($UPLOADED_DIR, 0755, true);
        }
        $parsedRequest = (new Parser())->parse($this->fcRequest);
        $uploadsFiles  = $parsedRequest->getUploadedFiles();
        $files         = [];
        
        foreach ($uploadsFiles as $key => $uploadsFile) {
            if (is_array($uploadsFile)) {
                $files[ $key ] = [
                  'tmp_name' => [],
                  'name'     => [],
                  'error'    => [],
                  'type'     => [],
                  'size'     => [],
                ];
                foreach ($uploadsFile as $k => $f) {
                    /**@var $f UploadedFile */
                    $filename = $f->getClientFilename();
                    $tmpName  = $UPLOADED_DIR.'/'.md5($filename).'.tmp';
                    $f->moveTo($tmpName);
                    $files[ $key ][ 'tmp_name' ][] = $tmpName;
                    $files[ $key ][ 'name' ][]     = $filename;
                    $files[ $key ][ 'error' ][]    = $f->getClientMediaType();
                    $files[ $key ][ 'type' ][]     = $f->getError();
                    $files[ $key ][ 'size' ][]     = $f->getSize();
                }
            }
            else {
                /**@var $uploadsFile UploadedFile */
                
                $filename = $uploadsFile->getClientFilename();
                
                $tmpName = $UPLOADED_DIR.'/'.md5($filename).'.tmp';
                
                $uploadsFile->moveTo($tmpName);
                $files[ $key ] = [
                  'tmp_name' => $tmpName,
                  'name'     => $filename,
                  'size'     => $uploadsFile->getSize(),
                  'error'    => $uploadsFile->getError(),
                  'type'     => $uploadsFile->getClientMediaType(),
                ];
            }
        }
        
        return $files;
    }
    
}