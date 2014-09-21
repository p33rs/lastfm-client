<?php

namespace p33rs\LastFM\Client\Storage\Mongo;
use \MongoClient;
use \MongoBinData;
use p33rs\LastFM\Client\Config;
use p33rs\LastFM\Client\Storage\CacheInterface;

class Adapter implements CacheInterface {

    const DOCUMENT_COLLECTION = 'cached_call';
    private $mongoClient;

    public function __construct() {
        $this->mongoClient = new MongoClient($this->getUrl());
    }

    private function getUrl() {
        $user = Config::get(self::CFG_STORAGE_USER);
        $pass = Config::get(self::CFG_STORAGE_PASS);
        $port = Config::get(self::CFG_STORAGE_PORT);
        $url = Config::get(self::CFG_STORAGE_URL);
        $host = 'mongodb://';
        if ($user && $pass) {
            $host .= $user . ':' . $pass . '@';
        }
        $host .= $url;
        if ($port) {
            $host .= ':' . $port;
        }
        return $host;
    }

    /**
     * @param $hash
     * @param $object
     * @param $method
     * @param $args
     * @param $result
     * @return this
     */
    public function save($hash, $object, $method, $args, $result) {
        $this->cleanup($hash);
        $cachedCall = new Document();
        $cachedCall
            ->setObject($object)
            ->setMethod($method)
            ->setArgs($args)
            ->setResult($result)
            ->setHash(new MongoBinData($hash));
        $collection = $this->collection();
        $collection->insert($cachedCall->toArray());
        return $this;
    }

    /**
     * @param $requestHash
     * @return CachedCall
     */
    public function retrieve($requestHash) {
        $collection = $this->collection();
        $result = $collection
            ->find(['hash' => new MongoBinData($requestHash)])
            ->sort(['timestamp' => -1])
            ->limit(1)
            ->getNext();
        if ($result) {
            $result = new Document($result);
            $result = $result->getResult();
        }
        return $result;
    }

    private function cleanup($requestHash) {
        $collection = $this->collection();
        $collection->remove([
            'hash' => new MongoBinData($requestHash)
        ]);
        return $this;
    }

    /**
     * @return \MongoCollection
     */
    private function collection() {
        return $this->db()->{Document::collection};
    }

    /**
     * @return \MongoDB
     * @throws \Exception
     */
    private function db() {
        return $this->mongoClient->selectDB(Config::get(self::CFG_STORAGE_NAME));
    }

}