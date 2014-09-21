<?php
namespace p33rs\LastFM\Client;
/**
 * LastFM Client with caching.
 */
class Client
{

    /** @var resource */
    private $ch;
    /** @var  Storage\CacheInterface */
    private $cache;
    /** @var int rate limiting */
    private $lastCall = 0;
    /**
     * @var string auth session. required to make authenticated calls.
     */
    private $authSession = null;

    /** @var HS separator for hash keys */
    const HS = '##';
    const CFG_URL = 'url';
    const CFG_KEY = 'key';
    const CFG_RATE = 'rate';

    /**
     * Open connections to the Last.fm API and the DB.
     */
    public function __construct()
    {

        // establish the curl connection
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);

    } // end construct

    /**
     * @param $object
     * @param $method
     * @param array $args
     * @return \SimpleXMLElement
     * @throws Exception
     */
    public function __invoke($object, $method, array $args = [])
    {
        ksort($args);
        $this->wait();
        // first, is there a cached answer?
        $hash = $this->buildHash($object, $method, $args);
        $cached = $this->cache->retrieve($hash);
        if ($cached) {
            return $cached->getResult();
        }
        // We gotta make a call, so ...
        $response = $this->request($object, $method, $args);
        if (!$parsed = simplexml_load_string($response)) {
            throw new Exception('corrupt data returned');
        }
        if ($parsed['status'] === 'ok') {
            $this->cache->save($hash, $object, $method, $args, $response);
        }
        return $parsed;
    }

    /**
     * Rate limiting.
     * @throws \Exception
     */
    private function wait()
    {
        $rateLimit = Config::get(self::CFG_RATE);
        $interval = microtime() - $this->lastCall;
        if ($interval < $rateLimit) {
            usleep($rateLimit - $interval);
        }
    }

    /**
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $forceApiSig create api sig even if not authenticated
     * @return string
     * @throws Exception
     */
    private function request($object, $method, array $args = [], $forceApiSig = false)
    {
        curl_setopt(
            $this->ch,
            CURLOPT_URL,
            $this->buildUrl($object, $method, $args, $forceApiSig)
        );
        $xml = curl_exec($this->ch);
        // update rate limiter
        $this->lastCall = time();
        if (!$xml) {
            throw new Exception('failed curl call');
        }
        return $xml;
    }

    /**
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $forceApiSig create api sig even if not authenticated
     * @return string
     * @throws \Exception
     */
    private function buildUrl($object, $method, array $args = [], $forceApiSig = false) {
        $args += [
            'method' => $object.'.'.$method,
            'api_key' => Config::get(self::CFG_KEY),
        ];
        if ($this->authSession) {
            $args['sk'] = $this->authSession;
            $args['api_sig'] = $this->generateApiSig($args);
        } elseif ($forceApiSig) {
            $args['api_sig'] = $this->generateApiSig($args);
        }
        return Config::get(self::CFG_URL) . '?' . http_build_query($args);
    }

    /**
     * @param $object
     * @param $method
     * @param array $args
     * @return string
     */
    private function buildHash($object, $method, array $args = []) {
        return md5(
            $object . self::HS . $method . self::HS . md5(json_encode($args))
        );
    }

    /**
     * generate the api_sig param, required after auth
     * @param array $args
     * @return string
     */
    private function generateApiSig(array $args) {
        ksort($args);
        $sig = '';
        foreach($args as $key => $value) {
            $sig .= $key.$value;
        }
        return $sig;
    }

    /**
     * Generate a session key, which will begin an authenticated session.
     * If you've already generated a key, you may set it directly.
     * @see Client::setAuthSession()
     * @see Client::authSession
     * @param $token
     * @throws Exception
     * @return string
     */
    public function generateSessionKey($token) {
        $this->wait();
        $object = 'auth';
        $method = 'getSession';
        $args = ['token' => $token];
        $response = $this->request($object, $method, $args, true);
        if (!$parsed = simplexml_load_string($response)) {
            throw new Exception('corrupt data returned');
        }
        if ($parsed['status'] !== 'ok') {
            throw new Exception('invalid token');
        }
        $key = $parsed->session->key;
        if (!$key || !is_string($key)) {
            throw new Exception('invalid key retrieved');
        }
        $this->authSession = $key;
        return $this->authSession;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return !!$this->authSession;
    }

    /**
     * @param string $authSession
     * @return this
     */
    public function setAuthSession($authSession)
    {
        $this->authSession = $authSession;
        return $this;
    }

} // end class
