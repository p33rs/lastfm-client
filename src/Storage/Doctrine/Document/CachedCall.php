<?php
namespace p33rs\LastFM\Client\Document;
use \Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class CachedCall {

    /**
     * @ODM\Id
     * @var string
     */
    private $id;
    /**
     * @ODM\String
     * @var string
     */
    private $object;
    /**
     * @ODM\String
     * @var string
     */
    private $method;
    /**
     * @ODM\Hash
     * @var array
     */
    private $args;
    /**
     * @ODM\Hash
     * @var array
     */
    private $result;
    /**
     * @ODM\Date
     * @var int
     */
    private $timestamp;
    /**
     * @ODM\BinMD5
     * @var string
     *
     */
    private $hash;

    public function __construct() {
        $this->timestamp = time();
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param array $args
     * @return this
     */
    public function setArgs(Array $args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $object
     * @return this
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param array $result
     * @return this
     */
    public function setResult(Array $result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return this
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

}
