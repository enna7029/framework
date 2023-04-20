<?php
declare(strict_types=1);

namespace Enna\Framework\Response;

use Enna\Framework\Exception;
use Enna\Framework\Response;

class File extends Response
{
    /**
     * 缓存有效时间
     * @var int
     */
    protected $expire = 360;

    /**
     * 文件名
     * @var string
     */
    protected $name;

    /**
     * 是否强制下载
     * @var bool
     */
    protected $force = true;

    public function __construct($data = '', int $code = 200)
    {
        $this->init($data, $code);
    }

    protected function output($data)
    {
        if (!is_file($data)) {
            throw new Exception('file not exists:' . $data);
        }

        if (!empty($this->name)) {
            $name = $this->name;
        } else {
            $name = pathinfo($data, PATHINFO_BASENAME);
        }

        $mimeType = $this->getMimeType($data);
        $size = filesize($data);

        $header['Pragma'] = 'public';
        $header['Content-Type'] = $mimeType;
        $header['Cache-control'] = 'max-age=' . $this->expire;
        $header['Content-Disposition'] = ($this->force ? 'attachment; ' : '') . 'filename=' . $name;
        $header['Content-length'] = $size;
        $header['Content-Transfer-Encoding'] = 'binary';
        $header['Expires'] = gmdate('D,d M Y H:i:s', time() + $this->expire) . ' GMT';

        $this->lastModified(gmdate('D, d M Y H:i:s', time()) . ' GMT');

        return file_get_contents($data);

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
     * Note: 获取文件类型
     * Date: 2023-03-13
     * Time: 17:23
     * @param string $filename 文件名
     * @return string
     */
    protected function getMimeType(string $filename)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($finfo, $filename);
    }

    /**
     * Note: 设置有效期
     * Date: 2023-03-13
     * Time: 18:19
     * @param int $expire 有效期
     * @return $this
     */
    public function expire(int $expire)
    {
        $this->expire = $expire;

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
}