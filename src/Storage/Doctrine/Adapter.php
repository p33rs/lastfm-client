<?php

namespace p33rs\LastFM\Client\Storage\Doctrine;
use \Doctrine\MongoDB\Connection;
use \Doctrine\ODM\MongoDB\Configuration as DoctrineConfig;
use \Doctrine\ODM\MongoDB\DocumentManager;
use \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use p33rs\LastFM\Client\Config;
use p33rs\LastFM\Client\Storage\Doctrine\Document\CachedCall;
use p33rs\LastFM\Client\Storage\CacheInterface;

class Adapter implements CacheInterface {

    const CFG_DOCTRINE_CACHE = 'doctrineCache';
    const DOCUMENT_PATH = '/Doctrine/Documents';
    const DOCUMENT_NAME_CACHED_CALL = 'CachedCall';

    /** @var DocumentManager */
    private $dm;

    /** @var string */
    private $documentNS;

    public function __construct() {

        $this->documentNS = __NAMESPACE__ . '\\Document\\';

        $config = new DoctrineConfig();
        $config->setProxyDir(Config::get(self::CFG_DOCTRINE_CACHE));
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(Config::get(self::CFG_DOCTRINE_CACHE));
        $config->setHydratorNamespace('Hydrators');
        $config->setMetadataDriverImpl(
            $config->newDefaultAnnotationDriver([__DIR__ . self::DOCUMENT_PATH])
        );
        $config->setDefaultDB('doctrine_odm');
        AnnotationDriver::registerAnnotationClasses();

        $user = Config::get(self::CFG_STORAGE_USER);
        $pass = Config::get(self::CFG_STORAGE_PASS);
        $name = Config::get(self::CFG_STORAGE_NAME);
        $port = Config::get(self::CFG_STORAGE_PORT);
        $url = Config::get(self::CFG_STORAGE_URL);
        $host = 'mongodb://';
        if ($user && $pass) {
            $host .= $user . ':' . $pass . '@';
        }
        $host .= $url . ':' . $port;
        if ($name) {
            $host .= '/' . $name;
        }

        $this->dm = DocumentManager::create(new Connection($host), $config);

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
        $cachedCallName = $this->documentNS . self::DOCUMENT_NAME_CACHED_CALL;
        $cachedCall = new $cachedCallName();
        $cachedCall
            ->setObject($object)
            ->setMethod($method)
            ->setArgs($args)
            ->setResult($result)
            ->setHash($hash);
        $this->dm->persist($cachedCall);
        $this->dm->flush();
        return $this;
    }

    /**
     * @param $requestHash
     * @return CachedCall
     */
    public function retrieve($requestHash) {
        return $this->dm
            ->getRepository($this->documentNS . 'CachedCall')
            ->findOneBy(['hash' => $requestHash]);
    }

    private function cleanup($requestHash) {
        $this->dm
            ->getDocumentCollection($this->documentNS . 'CachedCall')
            ->remove(['hash' => $requestHash]);
        $this->dm->flush();
        return $this;
    }

}