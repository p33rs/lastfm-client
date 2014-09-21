<?php
return [
    'cachePersist' => 604800, // one week, in sec
    'doctrineCache' => __DIR__ . '/doctrine/cache',
    'storageUrl' => 'localhost',
    'storageUser' => '',
    'storagePass' => '',
    'storagePort' => 27017,
    'storageName' => 'lastfm',
    'url' => 'http://ws.audioscrobbler.com/2.0/',
    'rate' => 800000 // .8 sec, in usec
];