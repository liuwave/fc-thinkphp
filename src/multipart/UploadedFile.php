<?php
/**
 * Created by PhpStorm.
 * User: liuwave
 * Date: 2020/7/5 9:01
 * Description:
 */

namespace liuwave\fc\think\multipart;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;

final class UploadedFile implements UploadedFileInterface
{
    /**
     * @var StreamInterface
     */
    private $stream;
    
    /**
     * @var int
     */
    private $size;
    
    /**
     * @var int
     */
    private $error;
    
    /**
     * @var string
     */
    private $filename;
    
    /**
     * @var string
     */
    private $mediaType;
    
    protected $moved = false;
    
    /**
     * @param StreamInterface $stream
     * @param int $size
     * @param int $error
     * @param string $filename
     * @param string $mediaType
     */
    public function __construct(StreamInterface $stream, $size, $error, $filename, $mediaType)
    {
        $this->stream = $stream;
        $this->size = $size;
        
        if (!\is_int($error) || !\in_array($error, array(
            \UPLOAD_ERR_OK,
            \UPLOAD_ERR_INI_SIZE,
            \UPLOAD_ERR_FORM_SIZE,
            \UPLOAD_ERR_PARTIAL,
            \UPLOAD_ERR_NO_FILE,
            \UPLOAD_ERR_NO_TMP_DIR,
            \UPLOAD_ERR_CANT_WRITE,
            \UPLOAD_ERR_EXTENSION,
          ))) {
            throw new \InvalidArgumentException(
              'Invalid error code, must be an UPLOAD_ERR_* constant'
            );
        }
        $this->error = $error;
        $this->filename = $filename;
        $this->mediaType = $mediaType;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getStream()
    {
        if ($this->error !== \UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }
        
        if ($this->moved) {
            throw new \RuntimeException(sprintf('Uploaded file %1s has already been moved', $this->name));
        }
        
        return $this->stream;
    }
    
    /**
     * {@inheritdoc}
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new \RuntimeException('Uploaded file already moved');
        }
        
        if (!is_writable(dirname($targetPath))) {
            throw new \InvalidArgumentException('Upload target path is not writable');
        }
        
        file_put_contents($targetPath, $this->stream);
        
        $this->moved = true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return $this->size;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        return $this->error;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getClientFilename()
    {
        return $this->filename;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getClientMediaType()
    {
        return $this->mediaType;
    }
}