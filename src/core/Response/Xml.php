<?php
declare(strict_types=1);

namespace Enna\Framework\Response;

use Enna\Framework\Cookie;
use Enna\Framework\Helper\Collection;
use Enna\Framework\Response;
use Enna\Orm\Model;

/**
 * XML格式影响
 * Class Xml
 * @package Enna\Framework\Response
 */
class Xml extends Response
{
    protected $options = [
        'root_node' => 'enna', //根节点名
        'root_attr' => '', //根节点属性
        'item_node' => 'item', //数字节点索引节点名
        'item_key' => '', //数字节点属性名
        'encoding' => 'utf-8', //数据编码
    ];

    protected $contentType = 'text/xml';

    public function __construct(Cookie $cookie, $data = '', int $code = 200)
    {
        $this->init($data, $code);
        $this->cookie = $cookie;
    }

    /**
     * Note: 处理数据
     * Date: 2023-08-21
     * Time: 16:12
     * @param mixed $data 要处理的数据
     * @return string
     */
    protected function output($data)
    {
        if (is_string($data)) {
            if (strpos($data, '<?xml') !== 0) {
                $encoding = $this->options['encoding'];
                $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
                $data = $xml . $data;
            }

            return $data;
        }

        return $this->xmlEncode($data, $this->options['root_node'], $this->options['item_node'], $this->options['root_attr'], $this->options['item_key'], $this->options['encoding']);
    }

    /**
     * Note: XML编码
     * Date: 2023-08-21
     * Time: 16:20
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param mixed $attr 根节点属性
     * @param string $id 数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    protected function xmlEncode($data, string $root, string $item, string $attr, string $id, string $encoding)
    {
        if (is_array($attr)) {
            $array = [];
            foreach ($attr as $key => $value) {
                $array[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $array);
        }

        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml .= "<{$root}{$attr}>";
        $xml .= $this->dataToXml($data, $item, $id);
        $xml .= "</{$root}>";

        return $xml;
    }

    /**
     * Note: 数据XML编码
     * Date: 2023-08-21
     * Time: 16:28
     * @param mixed $data 数据
     * @param string $item 数字索引时的节点名称
     * @param string $id 数字索引key转换为的属性名
     * @return string
     */
    protected function dataToXml($data, string $item, string $id)
    {
        $xml = $attr = '';

        if ($data instanceof Collection || $data instanceof Model) {
            $data = $data->toArray();
        }

        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $id && $attr = " {$id}=\"{$key}\"";
                $key         = $item;
            }
            $xml .= "<{$key}{$attr}>";
            $xml .= (is_array($val) || is_object($val)) ? $this->dataToXml($val, $item, $id) : $val;
            $xml .= "</{$key}>";
        }

        return $xml;
    }
}