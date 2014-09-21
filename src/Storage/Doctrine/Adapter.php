<?php

namespace p33rs\LastFM\Client\Storage\Doctrine;
use \Doctrine\MongoDB\Connection;
use \Doctrine\ODM\MongoDB\Configuration as DoctrineConfig;
use \Doctrine\ODM\MongoDB\DocumentManager;
use \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use p33rs\LastFM\Client\Config;
use p33rs\LastFM\Client\Document\CachedCall;
use p33rs\LastFM\Client\Storage\CacheInterface;

class Adapter implements CacheInterface {

    const CFG_DOCTRINE_CACHE = 'doctrineCache';
    const DOCUMENT_PATH = '/Doctrine/Documents';

    /**
     * @var DocumentManager
     */
    private $dm;

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

        $this->dm = DocumentManager::create(new Connection(), $config);

    }

    /**
     * @param CachedCall $cachedCall
     * @return this|void
     */
    public function save($cachedCall) {
        // save the cached call
        // run cleanup
    }

    /**
     * @param $requestHash
     * @return CachedCall|void
     */
    public function retrieve($requestHash) {
        return $this->dm
            ->getRepository($this->DOCUMENT_NS . 'CachedCall')
            ->findOneBy(['hash' => $requestHash]);
    }

    private function cleanup($requestHash) {
        return $this->dm
            ->getRepository($this->DOCUMENT_NS . 'CachedCall')
            ->findOneBy(['hash' => $requestHash]);
    }

}