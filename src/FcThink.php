<?php
/**
 * Created by PhpStorm.
 * User: liuwave
 * Date: 2020/7/5 21:33
 * Description:
 */

use liuwave\fc\think\multipart\Parser;
use liuwave\fc\think\multipart\UploadedFile;
use Psr\Http\Message\ServerRequestInterface;

class FcThink
{
    
    private $request = null;
    
    private $app = null;
    
    /**
     * @var string
     */
    private $rootDir = '/';
    
    private $runtimePath = '/tmp';
    /**
     * @var
     */
    private $queryString;
    
    public function __construct(
      string $root = DIRECTORY_SEPARATOR.'code'.DIRECTORY_SEPARATOR.'api',
      string $runtimePath = DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR
    ) {
        //
        $this->rootDir     = $root.DIRECTORY_SEPARATOR.'public';
        $this->runtimePath = $runtimePath;
        $this->app         = new \think\App($root);
        $this->app->setRuntimePath($this->runtimePath);
        $this->request = $this->app->request;
    }
    
    public function run(ServerRequestInterface $fcRequest)
    {
        $http     = $this->app->http;
        $response = $http->run($this->request);
        /**
         *解决 Cannot modify header information - headers already sent by (output started at xx的问题
         * @link https://developer.aliyun.com/article/683415
         */
        $cookies = [];
        foreach (\think\facade\Cookie::getCookie() as $name => $val) {
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
    
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $fcRequest
     *
     * @return $this
     */
    public function parse(ServerRequestInterface $fcRequest)
    {
        $requestURI    = $fcRequest->getAttribute('requestURI');
        $type          = \strtolower($fcRequest->getHeaderLine('Content-Type'));
        [$type] = \explode(';', $type);
        $uriArray          = explode('?', $requestURI);
        $this->queryString = $uriArray[ 1 ] ?? '';
        
        $this->parseHeaders($fcRequest)
          ->parseServerParams($fcRequest)
          ->parseCookie($fcRequest);
        
        if ($fcRequest->getMethod() === "POST" && $type === 'multipart/form-data') {
            $this->parseFiles($fcRequest);
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
     * @param \Psr\Http\Message\ServerRequestInterface $fcRequest
     *
     * @return $this
     */
    private function parseHeaders(ServerRequestInterface $fcRequest)
    {
        $headers = array_merge(
          $this->request->header(),
          array_map(
            function ($item) {
                return $item[ 0 ] ?? '';
            },
            $fcRequest->getHeaders()
          )
        );
        
        $this->request->withHeader($headers);
        
        return $this;
    }
    
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $fcRequest
     *
     * @return $this
     */
    private function parseCookie(ServerRequestInterface $fcRequest)
    {
        $line    = preg_replace('/^Set-Cookie: /i', '', trim($fcRequest->getHeaderLine('Cookie')));
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
        
        $this->request->withCookie(array_merge($fcRequest->getCookieParams(), $cookies[ 'cookies' ]));
        
        return $this;
    }
    
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $fcRequest
     *
     * @return $this
     */
    private function parseServerParams(ServerRequestInterface $fcRequest)
    {
        $servers = array_merge(
          (array)$fcRequest->getServerParams(),
          [
            'DOCUMENT_ROOT'                  => $this->rootDir,
              //'REMOTE_ADDR'                    => $request->getAttribute('clientIP'),
              // 'REMOTE_PORT'                    => FC_CGI_REMOTE_PORT,
            'SERVER_SOFTWARE'                => FC_CGI_SERVER_SOFTWARE,
            'SERVER_PROTOCOL'                => FC_CGI_SERVER_PROTOCOL,
            'SERVER_NAME'                    => $fcRequest->getHeaderLine('host'),
            'SERVER_PORT'                    => '80',
            'REQUEST_URI'                    => $fcRequest->getAttribute('requestURI'),
            'REQUEST_METHOD'                 => $fcRequest->getMethod(),
            'SCRIPT_NAME'                    => "/index.php",
            'SCRIPT_FILENAME'                => $this->rootDir."/index.php",
            'PATH_INFO'                      => $fcRequest->getAttribute('path'),
            'PHP_SELF'                       => "/index.php?s=".($fcRequest->getAttribute('path')),
            'QUERY_STRING'                   => $this->queryString,
            'HTTP_HOST'                      => $fcRequest->getHeaderLine('host'),
              //'HTTP_CONNECTION'=>'',
            'HTTP_CACHE_CONTROL'             => $fcRequest->getHeaderLine('Cache-Control'),
            'HTTP_UPGRADE_INSECURE_REQUESTS' => $fcRequest->getHeaderLine('Upgrade-Insecure-Requests'),
            'HTTP_USER_AGENT'                => $fcRequest->getHeaderLine('User-Agent'),
            'HTTP_ACCEPT'                    => $fcRequest->getHeaderLine('Accept'),
            'HTTP_ACCEPT_LANGUAGE'           => $fcRequest->getHeaderLine('Accept-Language'),
            'HTTP_COOKIE'                    => $fcRequest->getHeaderLine('Cookie'),
          ]
        );
        
        $this->request->withServer($servers);
        
        return $this;
    }
    
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $fcRequest
     *
     * @return $this
     * @link https://github.com/vangie/fc-file-transfer/blob/master/php/index.php
     */
    private function parseFiles(ServerRequestInterface $fcRequest)
    {
        $UPLOADED_DIR = $this->runtimePath.DIRECTORY_SEPARATOR.'uploaded';
        if (!file_exists($UPLOADED_DIR)) {
            mkdir($UPLOADED_DIR, 0755, true);
        }
        $parsedRequest = (new Parser())->parse($fcRequest);
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