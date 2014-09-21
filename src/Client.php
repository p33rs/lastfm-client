<?php
<?php

/**

This is a really, really simple LFM accessor.
There are a LOT of open-source LFM libraries
already available. This one is meant to be
as simple as possible; It doesn't support
authentication and has only one method.

It handles caching of results, given the
following table:

CREATE TABLE `lfmcache` (
`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
`object` VARCHAR(255) NULL DEFAULT NULL ,
`method` VARCHAR(255) NULL DEFAULT NULL ,
`args` TEXT NULL DEFAULT NULL ,
`result` TEXT NULL DEFAULT NULL ,
`timestamp` INT(10) UNSIGNED NULL DEFAULT NULL ,
`hash` TEXT NOT NULL ,
PRIMARY KEY (`id`)
)

 */


class Lastfm {

    // result of the last call
    public $response = null;
    // containers for our DB/API connections
    private $_api = null;
    private $_database = null;
    // information about the query
    private $_object = null;
    private $_method = null;
    private $_args = null;
    // this configuration info is pulled from elsewhere
    private $_config = array (
        'apiUrl' => '',
        'apiKey' => '',
        'databaseUrl' => '',
        'databaseUser' => '',
        'databasePass' => '',
        'databaseName' => '',
        'cachePersist' => 0,
    );
    private $test = '';

    /**
     * Open connections to the Last.fm API and the DB.
     */
    public function __construct() {

        // include the config info from another file.
        ob_start();
        require ('access.php');
        ob_end_clean();
        $this->_config = array (
            'apiUrl' => $apiUrl,
            'apiKey' => $apiKey,
            'databaseUrl' => $databaseUrl,
            'databaseUser' => $databaseUser,
            'databasePass' => $databasePass,
            'databaseName' => $databaseName,
            'cachePersist' => $cachePersist,
        );

        // establish the curl connection
        $this->_api = curl_init();
        curl_setopt_array($this->_api, array (
            CURLOPT_HTTPHEADER => array (
                'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0',
                'Accept: application/json',
            ),
            CURLOPT_RETURNTRANSFER => true,
        ));

        // establish a database connection.
        try {
            $this->_database = new PDO(
                'mysql:host='.$this->_config['databaseUrl']
                .';dbname='.$this->_config['databaseName'],
                $this->_config['databaseUser'],
                $this->_config['databasePass']
            );
            $this->_cleanCache();
        }
        catch (PDOException $e) {
            $this->_database = null;
        }

    } // end construct

    /**
     * Make a request to the last.fm API and return the result.
     * @todo This doesn't support authentication or writes.
     * @param string $object The object component of the API call.
     * @param string $method The method component of the API call.
     * @param array $args Additional data to send with the query.
     * @return SimpleXML The server response. False if problems arise.
     */
    public function query($object, $method, $args = array()) {

        if (!$this->_api || !$this->_database) return false;

        $result = false;

        // accept well-formed vars.
        $this->_response = null;
        $this->_method = $method;
        $this->_object = $object;
        $this->_args = $args;
        if (!$object || !$method || !is_string($object) || !is_string($method) || !is_array($args)) {
            return false;
        }
        // sort and serialize the args array.
        ksort ($this->_args);
        $this->_args = http_build_query($this->_args);

        // no db connect? fail now.
        if (!$this->_database) return false;

        // response is cached? pop that back.
        if ($result = $this->_getCache()) {
            $parsed = simplexml_load_string ($result);
            if ($parsed) return $parsed;
        }

        // we have to make an api call, so sleep a second
        usleep(800000);
        // create the query string by prepending defaults with
        //   the user-supplied args. insert '&' as a separator
        //   if args were provided.
        $query = ($this->_args ? ($this->_args . '&') : '') .
            http_build_query(array(
                'method' => $object.'.'.$method,
                'api_key' => $this->_config['apiKey'],
            ));

        // fire the request to the lastfm api
        curl_setopt(
            $this->_api,
            CURLOPT_URL,
            $this->_config['apiUrl'].'?'.$query
        );
        $xml = curl_exec($this->_api);

        // return false if we couldn't get parsed XML.
        if (!$xml || !$xmlParsed = simplexml_load_string ($xml)) return false;
        // if we got well-formed XML, save it.
        else {
            $this->_setCache($xml);
            $this->response = $xmlParsed;
            return $xmlParsed;
        }

    } // end query

    /**
     * See if a query has been stored in cache. If it has, retrieve it.
     * @return mixed False, or the saved SimpleXML
     */
    private function _getCache() {

        // see if this query's been run in the last week
        $hash = $this->_hash();
        $timestamp = time() - $this->_config['cachePersist'];

        $sql = $this->_database->prepare('SELECT * FROM `lfmcache` WHERE `hash` = :hash AND `timestamp` > :timestamp');
        $sql->bindParam(':hash', $hash, PDO::PARAM_STR, strlen($hash));
        $sql->bindParam(':timestamp', $timestamp, PDO::PARAM_INT);

        $sql->execute();

        $result = $sql->fetchAll();

        // if we got results, pull the data.
        $cached = false;
        if ($result) {
            // look through the results that we got back.
            // there should only be one, but hash collisions
            // happen, so we should iterate the set just in case.
            foreach ($result as $record) {
                // grab the record that matches our query.
                if (
                    $record['object'] == $this->_object &&
                    $record['method'] == $this->_method &&
                    $record['args'] == $this->_args
                ) {
                    return $record['result'];
                }
            }
        }

        return false;

    } // end _getCache

    /**
     * If a query was successful, commit it to our DB.
     * @return bool Whether the commit was successful.
     */
    private function _setCache($result) {

        // prepare an insert statement
        // args, hash, method, object, result, timestamp
        $record = array(
            'object' => $this->_object,
            'method' => $this->_method,
            'args' => $this->_args,
            'result' => $result,
            'timestamp' => time(),
            'hash' => $this->_hash()
        );

        $sql = $this->_database->prepare('INSERT INTO lfmcache (`args`, `hash`, `method`, `object`, `result`, `timestamp`) VALUES (:args, :hash, :method, :object, :result, :timestamp)');
        $sql->bindParam(':args', $record['args'], PDO::PARAM_STR, strlen($record['args']));
        $sql->bindParam(':hash', $record['hash'], PDO::PARAM_STR, strlen($record['hash']));
        $sql->bindParam(':method', $record['method'], PDO::PARAM_STR, strlen($record['method']));
        $sql->bindParam(':object', $record['object'], PDO::PARAM_STR, strlen($record['object']));
        $sql->bindParam(':result', $record['result'], PDO::PARAM_STR, strlen($record['result']));
        $sql->bindParam(':timestamp', $record['timestamp'], PDO::PARAM_INT);

        $sql->execute();

    }

    /**
     * Remove outdated cache items.
     */
    private function _cleanCache() {
        $sql = $this->_database->prepare(
            'DELETE FROM `lfmcache` WHERE `timestamp` < ' .
            (time() - $this->_config['cachePersist'])
        );
        return $sql->execute();
    }

    /**
     * Hash the query arguments so that they're easier to cache.
     * @return string The hash for this query
     */
    private function _hash() {
        $string = $this->_object . '||' . $this->_method . '||' . $this->_args;
        return sha1($string);
    } // end _hash

} // end class
