<?php
namespace p33rs\LastFM\Client\Storage\Mongo;
use \MongoDate;
use \MongoBinData;
use \MongoId;

class Document {

    /**
     * @var MongoId
     */
    private $_id;
    /**
     * @var string
     */
    private $object;
    /**
     * @var string
     */
    private $method;
    /**
     * @var array
     */
    private $args;
    /**
     * @var string
     */
    private $result;
    /**
     * @var MongoDate
     */
    private $timestamp;
    /**
     * @var MongoBinData
     */
    private $hash;

    const collection = 'cached_call';

    public function __construct(Array $values = []) {
        $this->timestamp = new MongoDate(time());
        if ($values) {
            $this->hydrate($values);
        }
    }

    private function hydrate($values) {
        if (array_key_exists('_id', $values)) {
            $this->setId($values['_id']);
        }
        if (array_key_exists('object', $values)) {
            $this->setObject($values['object']);
        }
        if (array_key_exists('method', $values)) {
            $this->setMethod($values['method']);
        }
        if (array_key_exists('args', $values)) {
            $this->setArgs($values['args']);
        }
        if (array_key_exists('result', $values)) {
            $this->setResult($values['result']);
        }
        if (array_key_exists('timestamp', $values)) {
            $this->setTimestamp($values['timestamp']);
        }
        if (array_key_exists('hash', $values)) {
            $this->setHash($values['hash']);
        }
    }

    public function toArray() {
        $result = [
            'object' => $this->getObject(),
            'method' => $this->getMethod(),
            'args' => $this->getArgs(),
            'result' => $this->getResult(),
            'timestamp' => $this->getTimestamp(),
            'hash' => $this->getHash(),
        ];
        if ($this->getId()) {
            $result['_id'] = $this->getId();
        }
        return $result;
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
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param string $result
     * @return this
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return MongoDate
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    private function setTimestamp(MongoDate $timestamp) {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return MongoBinData
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param MongoBinData $hash
     * @return this
     */
    public function setHash(MongoBinData $hash)
    {
        $this->hash = $hash;
        return $this;
    }

    private function setId(MongoId $id)
    {
        $this->_id = $id;
    }
    private function getId()
    {
        return $this->_id;
    }

}
