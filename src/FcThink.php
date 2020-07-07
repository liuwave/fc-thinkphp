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
     * @var \think\Request
     */
    private $request;
    
    /**
     * @var Request $fcRequest
     */
    private $fcRequest;
    
    /**
     * @var \think\App
     */
    private $app;
    
    /**
     * @var string
     */
    private $rootDir = '/code/tp';
    
    /**
     * @var string
     */
    private $runtimePath = '/tmp/';
    /**
     * @var
     */
    private $queryString;
    
    /*
     * @var bool
     *
     *
     * */
    
    /**
     * @var null
     */
    private $filename;
    
    /**
     * FcThink constructor.
     *
     * @param ServerRequestInterface $fcRequest
     * @param string                 $root
     * @param string                 $runtimePath
     * @param bool                   $checkFile
     */
    public function __construct(
      ServerRequestInterface $fcRequest,
      string $root = '/code/tp',
      string $runtimePath = '/tmp/',
      bool $checkFile = true
    ) {
        $this->rootDir     = rtrim($root, '/');
        $this->runtimePath = rtrim($runtimePath, '/').'/';
        $this->fcRequest   = $fcRequest;
        if ($checkFile) {
            $path     = $this->fcRequest->getAttribute('path');
            $filename = rawurldecode($this->rootDir.'/public'.$path);
            $pathinfo = pathinfo($filename);
            if (isset($pathinfo[ 'extension' ]) &&
              strtolower($pathinfo[ 'extension' ]) !== 'php' &&
              file_exists($this->filename) &&
              is_file($this->filename)) {
                $this->filename = $filename;
                
                return;
            }
        }
        $this->filename = null;
        $this->app      = new App($this->rootDir);
        $this->app->setRuntimePath($this->runtimePath);
        $this->request = $this->app->request;
        $this->parse();
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
        $http     = $this->app->http;
        $response = $http->run($this->request);
        $http->end($response);
        
        $response->header(['Set-Cookie' => $this->getHeaderCookie()]);
        
        return new Response($response->getCode(), $response->getHeader(), $response->getContent());
    }
    
    /**
     * @param array $headers
     *
     * @return $this
     */
    public function withHeader(array $headers = [])
    {
        if ($this->request) {
            $this->request->withHeader(array_merge($this->request->header(), $headers));
        }
        
        return $this;
    }
    
    private function getHeaderCookie()
    {
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
        
        return $cookies;
    }
    
    /**
     *
     * @return $this
     */
    private function parse()
    {
        $requestURI = $this->fcRequest->getAttribute('requestURI');
        $type       = strtolower($this->fcRequest->getHeaderLine('Content-Type'));
        [$type] = explode(';', $type);
        $uriArray          = explode('?', $requestURI);
        $this->queryString = $uriArray[ 1 ] ?? '';
        
        $this->parseHeaders()
          ->parseCookie()
          ->parseServerParams();
        
        if ($this->fcRequest->getMethod() === "POST" && $type === 'multipart/form-data') {
            $this->parseFiles();
        }
        $this->request->withGet($this->fcRequest->getQueryParams())
          ->setMethod($this->fcRequest->getMethod())
          ->withInput(
            $this->fcRequest->getBody()
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
            'DOCUMENT_ROOT'                  => $this->rootDir.'/public',
              //'REMOTE_ADDR'                    => $request->getAttribute('clientIP'),
              // 'REMOTE_PORT'                    => FC_CGI_REMOTE_PORT,
            'SERVER_SOFTWARE'                => FC_CGI_SERVER_SOFTWARE,
            'SERVER_PROTOCOL'                => FC_CGI_SERVER_PROTOCOL,
            'SERVER_NAME'                    => $this->fcRequest->getHeaderLine('host'),
            'SERVER_PORT'                    => '80',
            'REQUEST_URI'                    => $this->fcRequest->getAttribute('requestURI'),
            'REQUEST_METHOD'                 => $this->fcRequest->getMethod(),
            'SCRIPT_NAME'                    => "/index.php",
            'SCRIPT_FILENAME'                => $this->rootDir."/public/index.php",
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
        $UPLOADED_DIR = $this->runtimePath.'/uploaded';
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
        
        $this->request->withFiles($files);
        
        return $this;
    }
    
}