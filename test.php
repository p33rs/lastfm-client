<?php
namespace p33rs\LastFM\Client;
include 'vendor/autoload.php';
Config::read(__DIR__.'/config.dist.php', true);
Config::read(__DIR__.'/config.local.php', true);
$client = new Client();
$artists = $client('user', 'getWeeklyArtistChart', ['user'=>'decaffeinated']);
var_export($artists);