<?php
include 'vendor/autoload.php';
use p33rs\LastFM\Client\Config;
Config::read(__DIR__.'/config.dist.php', true);
Config::read(__DIR__.'/config.local.php', true);
$client = new \p33rs\LastFM\Client\Client();
$artists = $client('user', 'getWeeklyArtistChart', ['user'=>'decaffeinated']);
var_export($artists);