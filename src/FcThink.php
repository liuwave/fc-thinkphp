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
use think\App;
use think\facade\Cookie;

/**
 * Class FcThink
 */
class FcThink
{
    
    /**
     * @var \think\Request
     */
    private $request;
    
    /**
     * @var ServerRequestInterface $fcRequest
     */
    private $fcRequest;
    
    /**
     * @var \think\App
     */
    private $app;
    
    /**
     * @var string
     */
    private $rootDir = '/code';
    
    /**
     * @var string
     */
    private $runtimePath = '/tmp';
    /**
     * @var
     */
    private $queryString;
    
    /**
     * FcThink constructor.
     *
     * @param string $root
     * @param string $runtimePath
     */
    public function __construct(
      string $root = DIRECTORY_SEPARATOR.'code'.DIRECTORY_SEPARATOR.'api',
      string $runtimePath = DIRECTORY_SEPARATOR.'tmp'
    ) {
        //
        $this->rootDir     = $root.DIRECTORY_SEPARATOR.'public';
        $this->runtimePath = $runtimePath;
        $this->app         = new App($root);
        $this->app->setRuntimePath($this->runtimePath);
        $this->request = $this->app->request;
    }
    
    /**
     * @return \think\Response
     */
    public function run()
    {
        $http     = $this->app->http;
        $response = $http->run($this->request);
        $http->end($response);
        /**
         *解决 Cannot modify header information - headers already sent by (output started at xx的问题
         * @link https://developer.aliyun.com/article/683415
         */
        $cookies = [];
        foreach (Cookie::getCookie() as $name => $val) {
            [$value, $expire, $option] = $val;
            $cookies[] = "{$name}={$value}".
              ($expire > 0 ? "; expires=".gmstrftime("%A, %d-%b-%Y %H:%M:%S GMT", $expire + (86400 * 365)) : '').
              "; path={$option[ 'path' ]}".
              (!empty($option[ 'domain' ]) ? "; domain={$option[ 'domain' ]}" : "").
              ($option[ 'secure' ] ? "; secure" : '').
              ($option[ 'httponly' ] ? "; httponly" : '');
        }
        $response->header(['Set-Cookie' => $cookies]);
        
        return $response;
    }
    
    public function withHeader(array $headers = [])
    {
        $this->request->withHeader(array_merge($this->request->header(), $headers));
        
        return $this;
    }
    
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $fcRequest
     *
     * @return $this
     */
    public function parse(ServerRequestInterface $fcRequest)
    {
        $this->fcRequest = $fcRequest;
        $requestURI      = $this->fcRequest->getAttribute('requestURI');
        $type            = strtolower($this->fcRequest->getHeaderLine('Content-Type'));
        [$type] = explode(';', $type);
        $uriArray          = explode('?', $requestURI);
        $this->queryString = $uriArray[ 1 ] ?? '';
        
        $this->parseHeaders()
          ->parseCookie()
          ->parseServerParams();
        
        if ($fcRequest->getMethod() === "POST" && $type === 'multipart/form-data') {
            $this->parseFiles();
        }
        $this->request->withGet($fcRequest->getQueryParams())
          ->setMethod($fcRequest->getMethod())
          ->withInput(
            $fcRequest->getBody()
              ->getContents()
          );
        
        return $this;
    }
    
    /**
     * @return $this
     */
    private function parseHeaders()
    {
        return $this->withHeader(
          array_map(
            function ($item) {
                return $item[ 0 ] ?? '';
            },
            $this->fcRequest->getHeaders()
          )
        );
    }
    
    /**
     * @return $this
     */
    private function parseCookie()
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
        
        $this->request->withCookie(array_merge($this->fcRequest->getCookieParams(), $cookies[ 'cookies' ]));
        
        return $this;
    }
    
    /**
     * @return $this
     */
    private function parseServerParams()
    {
        $servers = array_merge(
          (array)$this->request->server(),
          (array)$this->fcRequest->getServerParams(),
          [
            'DOCUMENT_ROOT'                  => $this->rootDir,
              //'REMOTE_ADDR'                    => $request->getAttribute('clientIP'),
              // 'REMOTE_PORT'                    => FC_CGI_REMOTE_PORT,
            'SERVER_SOFTWARE'                => FC_CGI_SERVER_SOFTWARE,
            'SERVER_PROTOCOL'                => FC_CGI_SERVER_PROTOCOL,
            'SERVER_NAME'                    => $this->fcRequest->getHeaderLine('host'),
            'SERVER_PORT'                    => '80',
            'REQUEST_URI'                    => $this->fcRequest->getAttribute('requestURI'),
            'REQUEST_METHOD'                 => $this->fcRequest->getMethod(),
            'SCRIPT_NAME'                    => "/index.php",
            'SCRIPT_FILENAME'                => $this->rootDir."/index.php",
            'PATH_INFO'                      => $this->fcRequest->getAttribute('path'),
            'PHP_SELF'                       => "/index.php?s=".($this->fcRequest->getAttribute('path')),
            'QUERY_STRING'                   => $this->queryString,
            'HTTP_HOST'                      => $this->fcRequest->getHeaderLine('host'),
              //'HTTP_CONNECTION'=>'',
            'HTTP_CACHE_CONTROL'             => $this->fcRequest->getHeaderLine('Cache-Control'),
            'HTTP_UPGRADE_INSECURE_REQUESTS' => $this->fcRequest->getHeaderLine('Upgrade-Insecure-Requests'),
            'HTTP_USER_AGENT'                => $this->fcRequest->getHeaderLine('User-Agent'),
            'HTTP_ACCEPT'                    => $this->fcRequest->getHeaderLine('Accept'),
            'HTTP_ACCEPT_LANGUAGE'           => $this->fcRequest->getHeaderLine('Accept-Language'),
            'HTTP_COOKIE'                    => $this->fcRequest->getHeaderLine('Cookie'),
          ]
        );
        
        $this->request->withServer($servers);
        
        return $this;
    }
    
    /**
     * @return $this
     * @link https://github.com/vangie/fc-file-transfer/blob/master/php/index.php
     */
    private function parseFiles()
    {
        $UPLOADED_DIR = $this->runtimePath.DIRECTORY_SEPARATOR.'uploaded';
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
                    $tmpName  = $UPLOADED_DIR.DIRECTORY_SEPARATOR.md5($filename).'.tmp';
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
                
                $tmpName = $UPLOADED_DIR.DIRECTORY_SEPARATOR.md5($filename).'.tmp';
                
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
        
        $this->request->withFiles($files);
        
        return $this;
    }
    
}