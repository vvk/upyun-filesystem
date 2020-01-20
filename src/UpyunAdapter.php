<?php

namespace Vvk\Upyun\FileSystem;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config as FlySystemConfig;
use phpDocumentor\Reflection\Types\Collection;
use Upyun\Upyun;
use Upyun\Config;

class UpyunAdapter extends AbstractAdapter
{
    protected $client;
    protected $service;
    protected $operator;
    protected $password;
    protected $domain;
    protected $protocol;

    public function __construct(array $config)
    {
        $this->service = $config['service'];
        $this->operator = $config['operator'];
        $this->password = $config['password'];
        $this->domain = $config['domain'];
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param FlySystemConfig $config   Config object
     *
     * @return array|false false on failure file meta data on success
     * @throws \Exception
     */
    public function write($path, $contents, FlySystemConfig $config)
    {
        $config = $this->objectToArray($config);
        return $this->getClient()->write($path, $contents, $config);
    }

    /**
     * 对象转成数据，第一次为 FlySystemConfig 类型
     * @param FlySystemConfig|array $data
     * @return array|void
     */
    public function objectToArray($data)
    {
        $data = (array)$data;
        $result = [];
        foreach ($data as $k => $v) {
            if (strpos($k, 'fallback') !== false) {
                continue;
            }
            if (gettype($v) == 'resource') {
                return;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array') {
                if (strpos($k, 'settings') !== false) {
                    $k = 'settings';
                }
                $result[$k] = (array)$this->objectToArray($v);
            } else {
                $result[$k] = $v;
            }
        }
        return isset($result['settings']) ? $result['settings'] : $result;
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     * @throws \Exception
     */
    public function writeStream($path, $resource, FlySystemConfig $config)
    {
        return $this->write($path, $resource, $config);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     * @throws \Exception
     */
    public function update($path, $contents, FlySystemConfig $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     * @throws \Exception
     */
    public function updateStream($path, $resource, FlySystemConfig $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        $this->copy($path, $newpath);
        return $this->delete($path);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        /*$file = $this->read($path);
        if (isset($file['contents']) || empty($file['contents'])) {
            return false;
        }*/
        return $this->writeStream($newpath, fopen('http:'.$this->getUrl($path), 'r'), new FlySystemConfig());
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        return $this->getClient()->delete($path);
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteDir($dirname)
    {
        return $this->getClient()->deleteDir($dirname);
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     * @throws \Exception
     */
    public function createDir($dirname, FlySystemConfig $config)
    {
        return $this->getClient()->createDir($dirname);
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        return true;
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     * @throws \Exception
     */
    public function has($path)
    {
        return $this->getClient()->has($path);
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        $contents = $this->getClient()->read($path);
        return ['contents' => $contents];
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        return $this->read($path);
    }

    /**
     * List contents of a directory.
     * @see https://help.upyun.com/knowledge-base/rest_api/#e88eb7e58f96e79baee5bd95e69687e4bbb6e58897e8a1a8
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $list = [];
        $params = ['X-List-Limit' => 100, 'x-list-iter' => null];
        while (true) {
            $result = $this->getClient()->read($directory, null, $params);
            $list = array_merge($list, $this->formatListContents($directory, $result['files']));

            if ($result['is_end'] == true || $result['iter'] == 'g2gCZAAEbmV4dGQAA2VvZg') {
                break;
            }
            $params['x-list-iter'] = $result['iter'];
        }

        return $list;
    }

    public function getUrl($path)
    {
        $url = $this->domain .'/'. $path;
        return '//'.$url;
    }

    /**
     * @param $directory
     * @param $list
     * @return array
     */
    protected function formatListContents($directory, $list)
    {
        $result = [];
        if (empty($list)) {
            return $result;
        }

        foreach ($list as $item) {
            $filePath = ltrim($directory . '/' . $item['name'], '/');

            $result[] = [
                'type' => $this->getType($filePath)['type'],
                'path' => ltrim($directory . '/' . $item['name'], '/'),
                'timestamp' => $item['time'],
                'size' => $item['size'],
            ];
        }

        return $result;
    }

    /**
     * @param string $path
     */
    public function getType($path)
    {
        $response = $this->getMetadata($path);

        return ['type' => $response['x-upyun-file-type']];
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        return $this->getClient()->info($path);
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        $response = $this->getMetadata($path);
        return ['size' => $response['x-upyun-file-size']];
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        $mimetype = $this->getClient()->getMimetype($path);
        return ['path' => $path, 'mimetype' => $mimetype];
    }

    /**
     * Get the last modified time of a file as a timestamp.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        $response = $this->getMetadata($path);
        return ['timestamp' => $response['x-upyun-file-date']];
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        return ['visibility' => self::VISIBILITY_PUBLIC];
    }

    /**
     * @return Upyun
     */
    protected function getClient()
    {
        if (empty($this->client)) {
            $config = new Config($this->service, $this->operator, $this->password);
            $this->client = new Upyun($config);
        }

        return $this->client;
    }
}
