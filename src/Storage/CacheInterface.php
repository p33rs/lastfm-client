<?php
namespace p33rs\LastFM\Client\Storage;
interface CacheInterface {

    const CFG_STORAGE_URL = 'storageUrl';
    const CFG_STORAGE_PASS = 'storagePass';
    const CFG_STORAGE_NAME = 'storageName';
    const CFG_STORAGE_USER = 'storageUser';
    const CFG_STORAGE_PORT = 'storagePort';

    /**
     * Store a cached call. This should call CacheInterface::cleanup().
     * @param $hash
     * @param $object
     * @param $method
     * @param $args
     * @param $result
     * @return this
     */
    function save($hash, $object, $method, $args, $result);

    /**
     * Retrieve a cached call.
     * Should not retrieve calls older than cachePersist in config.
     * @param string $requestHash
     * @return this
     */
    function retrieve($requestHash);

}