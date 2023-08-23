<?php
declare(strict_types=1);

namespace Enna\Framework\Response;

use Enna\Framework\Exception;
use Enna\Framework\Response;

/**
 * FILE格式响应
 * Class File
 * @package Enna\Framework\Response
 */
class File extends Response
{
    /**
     * 文件名
     * @var string
     */
    protected $name;

    /**
     * 缓存有效时间
     * @var int
     */
    protected $expire = 360;

    /**
     * 是否为内容
     * @var bool
     */
    protected $isContent = false;

    /**
     * mimeType类型
     * @var string
     */
    protected $mimeType;

    /**
     * 是否强制下载
     * @var bool
     */
    protected $force = true;

    public function __construct($data = '', int $code = 200)
    {
        $this->init($data, $code);
    }

    /**
     * Note: 处理数据
     * Date: 2023-08-21
     * Time: 11:28
     * @param mixed $data 要处理的数据
     * @return false|string
     * @throws Exception
     */
    protected function output($data)
    {
        if (!$this->isContent && !is_file($data)) {
            throw new Exception('file not exists:' . $data);
        }

        if (!empty($this->name)) {
            $name = $this->name;
        } else {
            $name = !$this->isContent ? pathinfo($data, PATHINFO_BASENAME) : '';
        }

        if ($this->isContent) {
            $mimeType = $this->mimeType;
            $size = strlen($data);
        } else {
            $mimeType = $this->getMimeType($data);
            $size = filesize($data);
        }


        $this->header['Pragma'] = 'public';
        $this->header['Content-Type'] = $mimeType ?: 'application/octet-stream';
        $this->header['Cache-Control'] = 'max-age=' . $this->expire;
        $this->header['Content-Disposition'] = ($this->force ? 'attachment; ' : '') . 'filename=' . $name;
        $this->header['Content-length'] = $size;
        $this->header['Content-Transfer-Encoding'] = 'binary';
        $this->header['Expires'] = gmdate('D,d M Y H:i:s', time() + $this->expire) . ' GMT';

        $this->lastModified(gmdate('D, d M Y H:i:s', time()) . ' GMT');

        return $this->isContent ? $data : file_get_contents($data);

    }

    /**
     * Note: 设置文件名
     * Date: 2023-03-13
     * Time: 17:14
     * @param string $name 文件名
     * @param bool $extension 后缀
     * @return $this
     */
    public function name(string $name, bool $extension = true)
    {
        $this->name = $name;

        if ($extension && strpos($name, '.') === false) {
            $this->name .= '.' . pathinfo($data, PATHINFO_EXTENSION);
        }

        return $this;
    }

    /**
     * Note: 设置缓存有效期
     * Date: 2023-08-21
     * Time: 11:55
     * @param int $expire 有效期
     * @return $this
     */
    public function expire(int $expire)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Note: 设置是否为内容
     * Date: 2023-08-21
     * Time: 11:52
     * @param bool $content 是否为内容
     * @return $this
     */
    public function isContent(bool $content = true)
    {
        $this->isContent = $content;

        return $this;
    }

    /**
     * Note: 设置文件类型
     * Date: 2023-08-21
     * Time: 11:56
     * @param string $mimeType 类型
     * @return $this
     */
    public function mimeType(string $mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Note: 设置文件强制下载
     * Date: 2023-03-13
     * Time: 18:21
     * @param bool $force 是否强制下载
     * @return $this
     */
    public function force(bool $force)
    {
        $this->force = $force;

        return $this;
    }

    /**
     * Note: 获取文件类型
     * Date: 2023-03-13
     * Time: 17:23
     * @param string $filename 文件名
     * @return string
     */
    protected function getMimeType(string $filename)
    {
        if (!empty($this->mimeType)) {
            return $this->mimeType;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($finfo, $filename);
    }
}