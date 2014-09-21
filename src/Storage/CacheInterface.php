<?php
namespace p33rs\LastFM\Client\Storage;
interface CacheInterface {

    const CFG_STORAGE_URL = 'storageUrl';
    const CFG_STORAGE_PASS = 'storagePass';
    const CFG_STORAGE_NAME = 'storageName';
    const CFG_STORAGE_USER = 'storageUser';

    /**
     * Store a cached call. This should call CacheInterface::cleanup().
     * @param CachedCall $cachedCall
     * @return this
     */
    function save($cachedCall);

    /**
     * Retrieve a cached call.
     * Should not retrieve calls older than cachePersist in config.
     * @param string requestHash
     * @return CachedCall
     */
    function retrieve($requestHash);

}