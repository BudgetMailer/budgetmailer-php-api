<?php

/**
 * BudgetMailer API REST-JSON Client Implementation Class
 * 
 * This File contains Client Class for BudgetMailer API Client
 * 
 * @author BudgetMailer <info@budgetmailer.nl>
 * @copyright (c) 2015 - BudgetMailer
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL2
 * @package BudgetMailer\API
 * @version 1.0
 * 
 * BudgetMailer API PHP Client is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * BudgetMailer API PHP Client is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BudgetMailer API PHP Client. If not, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt.
 */

/**
 * Namespace
 * @package BudgetMailer\Api
 */
namespace BudgetMailer\Api;

use BudgetMailer\Api\Client\RestJson;

/**
 * Implementation of BudgetMailers REST-JSON API
 * 
 * @package BudgetMailer\Api
 */
class Client
{
    const CACHE_KEY_CONTACT = 'bm-contact-';
    const CACHE_KEY_LIST = 'bm-list-';
    const CONTENT_TYPE = 'application/json';
    const LIMIT = 1000;

    /**
     * @var Cache Simple File Cache
     */
    protected $cache;
    
    /**
     * @var Config Configuration
     */
    protected $config;
    
    /**
     * @var array HTTP Headers for REST-JSON Request
     */
    protected $headers;
    
    /**
     * @var Client\RestJson REST-JSON Client
     */
    protected $restJson;
    
    /**
     * @var string Signatures Salt
     */
    protected $salt;
    
    /**
     * @var string Requests Signature
     */
    protected $signature;
    
    /**
     * @var string Encoded Signature
     */
    protected $signatureEncoded;
    
    /**
     * Create new Instance of the BudgetMailer API Client
     * @param Cache $cache Cache
     * @param Config $config Configuration
     * @param RestJson $restJson RestJson Client or null
     */
    public function __construct(Cache $cache, Config $config, RestJson $restJson = null)
    {
        $this->setConfig($config);
        
        if (!$cache) {
            $cache = new Cache();
        }
        
        if (!$restJson) {
            $restJson = new RestJson($config);
        }
        
        $this->setCache($cache)
            ->setRestJson($restJson);
    }
    
    /**
     * Get Salt for Request Signature. Salt is only regenerated if its not set 
     * already. 
     * 
     * @return string
     */
    protected function getSalt()
    {
        if (!$this->salt) {
            $this->salt = md5(microtime(true));
        }
        
        return $this->salt;
    }
    
    /**
     * Get Signature for Request
     * @return string
     */
    protected function getSignature()
    {
        $this->signature = hash_hmac(
            'sha256',
            $this->getSalt(),
            $this->getConfig()->getSecret(), 
            true
        );
        
        return $this->signature;
    }
    
    /**
     * Get encoded Signature (base64)
     * @return string
     */
    protected function getSignatureEncoded()
    {
        $this->signatureEncoded = rawurlencode(
            base64_encode($this->getSignature())
        );
        
        return $this->signatureEncoded;
    }
    
    /**
     * Get current Configuration
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Set Configuration
     * @param Config $config Configuration
     * @return Client self
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }
    
    /**
     * Get Cache
     * @return Cache 
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Get REST JSON Client
     * @return Client\RestJson REST JSON Client
     */
    public function getRestJson()
    {
        return $this->restJson;
    }
    
    /**
     * Set REST JSON Client
     * @param Client\RestJson $restJson REST JSON Client 
     * @return \BudgetMailer\Api\Client self
     */
    public function setRestJson(Client\RestJson $restJson)
    {
        $this->restJson = $restJson;
        return $this;
    }
    
    /**
     * Set Cache
     * @param \BudgetMailer\Api\Cache $cache cache 
     * @return \BudgetMailer\Api\Client self
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
        return $this;
    }
    
    /**
     * Set HTTP Headers for next Request
     * @param array $headers HTTP Headers
     * @return Client self
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }
    
    /**
     * Return HTTP Headers for next Request
     * @return array HTTP Headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Generate Headers for next Request
     */
    protected function beforeRequest()
    {
        $this->setHeaders(array(
            'Accept' => self::CONTENT_TYPE,
            'apikey' => $this->getConfig()->getKey(),
            //'apisecret' => $this->getConfig()->getSecret(),
            'Content-Type' => self::CONTENT_TYPE,
            'signature' => $this->getSignatureEncoded(),
            'salt' => $this->getSalt(),
        ));
        $this->salt = null; // INFO will regenerate salt for next request
    }
    
    /**
     * Filter Contact List ID - if null, then use configured Contact List.
     * 
     * @param mixed $list List ID or anything else
     */
    protected function normalizeList($list)
    {
        return rawurlencode( is_null($list) ? $this->getConfig()->getList() : $list );
    }
    
    /**
     * Filter API URL (convert relative to absolute).
     * 
     * @param mixed $url relative URL
     */
    protected function normalizeUrl($url)
    {
        return $this->getConfig()->getEndPoint() . $url;
    }
    
    /**
     * Delete existing Contact from BudgetMailer API
     * 
     * @param string $emailOrId BudgetMailer ID or Email
     * @param null|string $list Contact List Name or BudgetMailer ID, null for default
     * @return boolean|null null in Case the Record doesn't exist, otherwise true
     * @throws \RuntimeException In Case of Error other than not found.
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function deleteContact($emailOrId, $list = null)
    {
        $this->beforeRequest();
        
        try {
            $data = $this->restJson->delete(
                $this->normalizeUrl('contacts/' . $this->normalizeList($list) . '/' . rawurlencode($emailOrId)), 
                $this->getHeaders(), null, Client\Http::NO_CONTENT
            );
            $this->getCache()->remove(self::CACHE_KEY_CONTACT . $emailOrId);
        } catch(\RuntimeException $e) {
            if (Client\Http::NOT_FOUND == $e->getCode()) {
                return null;
            }
            if (Client\Http::BAD_REQUEST == $e->getCode()) { // INFO old ways, not needed
                return null;
            }
        }
        
        return true;
    }
    
    #
    # ACTUAL API IMPLEMENTATION:
    #
    
    /**
     * Delete Tag from Contact Tags in BudgetMailer API
     * 
     * @param string $emailOrId email or budgetmailer id
     * @param string $tag Tag Name to delete
     * @param null|string $list Contact List Name or BudgetMailer ID, null for default
     * @return boolean True in Case everything was OK
     * @throws \RuntimeException In Case the Request fails
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function deleteTag($emailOrId, $tag, $list = null)
    {
        $this->beforeRequest();
        
        $this->restJson->delete(
            $this->normalizeUrl('contacts/' . $this->normalizeList($list) . '/' . rawurlencode($emailOrId) . '/tags/' . rawurlencode($tag)), 
            $this->getHeaders(), null, Client\Http::NO_CONTENT
        );
        $this->getCache()->remove(self::CACHE_KEY_CONTACT . $emailOrId);
        
        // XXX handle not found
        
        return true;
    }
    
    /**
     * Get single Contact from Contact List
     * 
     * @param string $emailOrId Unique Contact Identifier (both e-mail and BudgetMailer Contact ID is OK)
     * @param string $list null or list id
     * @throws \RuntimeException In Case the Request fails (except not found)
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function getContact($emailOrId, $list = null)
    {
        $this->beforeRequest();
        
        $contact = null;
        
        try {
            $contact = $this->cache->get(self::CACHE_KEY_CONTACT . $emailOrId);
            
            if (!$contact) {
                $contact = $this->restJson->get(
                    $this->normalizeUrl('contacts/' . $this->normalizeList($list) . '/' . rawurlencode($emailOrId)), 
                    $this->getHeaders(), null, Client\Http::OK
                );
                
                $this->getCache()->set(self::CACHE_KEY_CONTACT . $emailOrId, $contact);
            }
        } catch(\RuntimeException $e) {
            if (Client\Http::NOT_FOUND == $e->getCode()) {
                return null;
            }
        }
        
        return $contact;
    }

    /**
     * Get multiple Contacts from Contact List
     * 
     * @param integer $offset Starting Position
     * @param integer $limit Record Limit (Max. 1000)
     * @param string $sort ASC or DESC
     * @param boolean $unsubscribed Filter subscribed / unsubscribed Contacts
     * @param null|string $list list name or id or null for default
     * @return array
     * @throws \RuntimeException In Case the Request fails
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function getContacts(
        $offset = 0, $limit = 20, $sort = 'ASC', $unsubscribed = null, $list = null
    ) {
        $this->beforeRequest();
        
        $query = array(
            'sort' => $sort
        );
        
        if ($offset > 0) {
            $query['offset'] = $offset;
        }
        if ($limit > 0) {
            $query['limit'] = $limit;
        }
        if (!is_null($unsubscribed)) {
            $query['unsubscribed'] = $unsubscribed ? 'True' : 'False';
        }
        
        $data = $this->restJson->get(
            $this->normalizeUrl('contacts/' . $this->normalizeList($list) . '/?' . http_build_query($query)),
            $this->getHeaders(), null, Client\Http::OK
        );
        
        return $data;
    }
    
    /**
     * Get available Contact Lists
     * @return array Array of available Lists (Objects)
     * @throws \RuntimeException In Case the Request fails
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function getLists()
    {
        $this->beforeRequest();
        
        $lists = $this->cache->get(self::CACHE_KEY_LIST);
        
        if (!$lists) {
            $lists = $this->restJson->get(
                $this->normalizeUrl('lists'), $this->getHeaders(), null, Client\Http::OK
            );

            $this->cache->set(self::CACHE_KEY_LIST, $lists);
        }
        
        return $lists;
    }
    
    /**
     * Get Tags of Contact from BudgetMailer API
     * 
     * @param string $id Email or BudgetMailer ID
     * @param null|string $list Contact List Name or null for default
     * @return boolean|array
     * @throws \RuntimeException In Case the Request fails
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function getTags($emailOrId, $list = null)
    {
        $this->beforeRequest();
        
        $data = $this->restJson->get(
            $this->normalizeUrl('contacts/' . $this->normalizeList($list) . '/' . rawurlencode($emailOrId) . '/tags'),
            $this->getHeaders(), null, Client\Http::OK
        );
        
        return $data;
    }
    
    /**
     * Create new contact in BudgetMailer Contact List
     * 
     * @param object $contact New Contact
     * @param null|string $list Contact List Name or ID, null for default List
     * @return object false or returned record from API
     * @throws \RuntimeException In Case the Request fails
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function postContact($contact, $list = null)
    {
        $this->beforeRequest();
        
        $contact = $this->restJson->post(
            $this->normalizeUrl('contacts/' . $this->normalizeList($list)), 
            $this->getHeaders(), $contact, Client\Http::CREATED
        );
        
        $this->cache->set(self::CACHE_KEY_CONTACT . $contact->email, $contact);
        
        return $contact;
    }
    
    /**
     * Insert multiple Contacts to BudgetMailer API
     * 
     * @param array $contacts Array of Contact Objects
     * @param null|string $list Contact List Name or null for default
     * @return boolean
     * @throws \RuntimeException In Case the Request fails
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function postContacts($contacts, $list = null)
    {
        $this->beforeRequest();
        
        $this->restJson->post(
            $this->normalizeUrl('contacts/' . $this->normalizeList($list) . '/bulk'),
            $this->getHeaders(), $contacts, Client\Http::OK
        );
        
        foreach($contacts as $contact) {
            $this->cache->remove(self::CACHE_KEY_CONTACT . $contact->email);
        }
        
        return true;
    }
    
    /**
     * Add Tags to existing Contact
     * 
     * @param string $emailOrId Email or BudgetMailer ID
     * @param array $tags tags
     * @param null|string $list Contact List Name or null for default
     * @return boolean
     * @throws \RuntimeException In Case the Request fails
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function postTags($emailOrId, $tags, $list = null)
    {
        $this->beforeRequest();
        
        $this->restJson->post(
            $this->normalizeUrl('contacts/' . $this->normalizeList($list) . '/' . rawurlencode($emailOrId) . '/tags'),
            $this->getHeaders(), $tags, Client\Http::CREATED
        );
        
        $this->cache->remove(self::CACHE_KEY_CONTACT . $emailOrId);
        
        return true;
    }
    
    /**
     * Update Contact in BudgetMailer API
     * 
     * @param string $emailOrId Email or BudgetMailer ID
     * @param object $contact Contact Data 
     * @param null|string $list Contact List Name or null for default
     * @param null|boolean $subscribe Force Subscribe or Unsubscribe, null let API handle it
     * @return boolean true
     * @throws \RuntimeException In Case the Request fails (except not found error)
     * @throws \InvalidArgumentException In Case the URL is unparsable
     */
    public function putContact($emailOrId, $contact, $list = null, $subscribe = null)
    {
        $this->beforeRequest();
        $url = $this->normalizeUrl('contacts/' . $this->normalizeList($list) . '/' . rawurlencode($emailOrId));
        
        if (!is_null($subscribe)) {
            $url .= '?subscribe=' . ( $subscribe ? 'True' : 'False' );
        }

        try {
            $this->restJson->put(
                $url, 
                $this->getHeaders(), $contact, Client\Http::OK
            );
            $this->cache->remove(self::CACHE_KEY_CONTACT . $emailOrId);
        } catch (\RuntimeException $e) {
            if (Client\Http::NOT_FOUND == $e->getCode()) {
                return null;
            }
        }
        
        return true;
    }
}



/**
 * BudgetMailer API Config Class File
 * 
 * This File contains Config Class for BudgetMailer API Client
 * 
 * @author BudgetMailer <info@budgetmailer.nl>
 * @copyright (c) 2015 - BudgetMailer
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL2
 * @package BudgetMailer\API
 * @version 1.0
 * 
 * BudgetMailer API PHP Client is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * BudgetMailer API PHP Client is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BudgetMailer API PHP Client. If not, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt.
 */

/**
 * Namespace
 * @package BudgetMailer\Api
 */
namespace BudgetMailer\Api;

/**
 * BudgetMailer API Client Config Wrapper
 * 
 * This Class provides simple Interface for BudgetMailer API Client Configuration.
 * You can use either access Configuration Values as Object Properties ($o->cache)
 * thanks to Magic Functions __get() and __set(). Or with getters, listed below.
 * 
 * @method boolean getCache()
 * @method string getCacheDir()
 * @method string getEndPoint()
 * @method string getKey()
 * @method string getList()
 * @method string getSecret()
 * @method integer getTtl()
 * @method integer getTimeOutSocket()
 * @method integer getTimeOutStream()
 * @package BudgetMailer\Api
 */
class Config
{
    /**
     * @var array Associative Array of the Configuration Values
     */
    protected $config;

    /**
     * Create new instance of Config
     * @param array $config Configuration as an Associative Array
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }
    
    /**
     * Set Configuration
     * @param array $config Configuration as an Associative Array
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Magic Method Implementation
     * 
     * Converts Part of called Method after "get" to Configuration Key,
     * e.g. "getCache" to "cache", and returns the Value or null.
     * 
     * @param string $method Method Name
     * @param array $args Method Arguments
     * @return mixed Configuration Value or null
     * @see \BudgetMailer\Api\Config::__get()
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        $g = 'get';
        
        if (preg_match('/^' . $g . '/i', $method)) {
            $property = lcfirst(str_replace($g, '', $method));
            
            return $this->$property;
        }
        
        throw new \BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $method . '().');
    }
    
    /**
     * Magic Method Implementation - get Object Property.
     * 
     * @param string $k requested Property Name
     * @return mixed Property Value or null
     */
    public function __get($k)
    {
        return isset($this->config[$k]) ? $this->config[$k] : null;
    }
    
    /**
     * Magic Method Implementation - set Object Property.
     * 
     * @param string $k Property Name
     * @param mixed $v Property Value
     */
    public function __set($k, $v)
    {
        $this->config[$k] = $v;
    }
}



/**
 * BudgetMailer File Cache
 * 
 * This File contains File Cache for BudgetMailer API Client
 * 
 * @author BudgetMailer <info@budgetmailer.nl>
 * @copyright (c) 2015 - BudgetMailer
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL2
 * @package BudgetMailer\API
 * @version 1.0
 * 
 * BudgetMailer API PHP Client is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * BudgetMailer API PHP Client is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BudgetMailer API PHP Client. If not, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt.
 */

/**
 * Namespace
 * @package BudgetMailer\Api
 */
namespace BudgetMailer\Api;

/**
 * Cache for BudgetMailer API Client
 * 
 * @package BudgetMailer\Api
 */
class Cache
{
    const HTACCESS = '.htacess';
    const HTACCESS_CONTENT = 'Deny from all';
    const SUFFIX = '.cache';
    
    /**
     * @var string Cache Directory (Must be writeable)
     */
    protected $dir;
    /**
     * @var boolean Enabled Flag
     */
    protected $enabled;
    /**
     * @var integer Time to live for cached Data
     */
    protected $ttl;
    
    /**
     * Create new Instance of Cache
     * @param Config $config Configuration
     */
    public function __construct(Config $config)
    {
        $this->setDir($config->getCacheDir());
        $this->setEnabled($config->getCache());
        $this->setTtl($config->getTtl());
    }
    
    /**
     * Get Enabled Flag
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
    
    /**
     * Set Cache Directory
     * @param boolean $enabled Cache Directory (Must be writeable)
     * @return \BudgetMailer\Api\Cache self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
    
    /**
     * Get Cache Directory (Absolute Path)
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }
    
    /**
     * Set Cache Directory
     * @param string $dir Cache Directory (Must be writeable)
     * @return \BudgetMailer\Api\Cache self
     * @throws \InvalidArgumentException In Case the Directory is not writeable, or .htaccess File can't be created
     */
    public function setDir($dir)
    {
        if ( ( !is_dir($dir) && !mkdir($dir) ) || !is_writable($dir) ) {
            throw new \InvalidArgumentException('Cache directory is not writeable.');
        }
        
        $this->checkHtaccess($dir);
        $this->dir = $dir;
        
        return $this;
    }
    
    /**
     * Set Cache Time to live
     * @param integer $ttl Time to live
     * @return \BudgetMailer\Api\Cache self
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }
    
    /**
     * Get Cache Time to live
     * @return integer
     */
    public function getTtl()
    {
        return $this->ttl;
    }
    
    /**
     * Check and if not exist create .htaccess File protecting Cache Files
     * @param string $dir Cache Directory (Must be writeable)
     * @return boolean
     * @throws \RuntimeException In case the .htaccess File cannot be written
     */
    protected function checkHtaccess($dir)
    {
        $htaccess = $dir . '.htaccess';
        
        if (!is_file($htaccess)) {
            if (!file_put_contents($htaccess, self::HTACCESS_CONTENT)) {
                throw new \RuntimeException('Couldn\'t create .htaccess file in cache directory.');
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize File Name
     * @param string $filename Un-sanitized File Name
     * @return string Sanitized File Name
     */
    protected function sanitizeFileName($filename)
    {
        $special_chars = array(
            "?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", 
            "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0)
        );
        
        $filename = preg_replace( "#\x{00a0}#siu", ' ', $filename );
        $filename = str_replace( $special_chars, '', $filename );
        $filename = str_replace( array( '%20', '+' ), '-', $filename );
        $filename = preg_replace( '/[\r\n\t -]+/', '-', $filename );
        $filename = trim( $filename, '.-_' );
        
        return $this->getDir() . $filename;
    }
    
    /**
     * Get all Cache Files and delete them.
     * @return boolean
     */
    public function purge()
    {
        if (!$this->getEnabled()) {
            return null;
        }
        
        $files = scandir($this->getDir());
        
        if (is_array($files) && count($files)) {
            foreach($files as $file) {
                if (!in_array($file, array('.', '..', '.htaccess'))) {
                    unlink($this->getDir() . $file);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Remove Cached data identified by Key $key from Cache
     * @param string $key Cache ID
     * @return boolean
     * @throws \RuntimeException In case the Cache File cannot be deleted.
     */
    public function remove($key)
    {
        if (!$this->getEnabled()) {
            return null;
        }
        
        $filename = $this->sanitizeFileName($key);
        
        if (is_file($filename)) {
            if (!unlink($filename)) {
                throw new \RuntimeException('Couldn\'t remove the cache key.');
            }
        }
        
        return true;
    }
    
    /**
     * Set Cache Key
     * @param string $key Cache ID
     * @param mixed $value Any value except false obviously
     * @return boolean
     * @throws \RuntimeException In case the Key cannot be stored
     */
    public function set($key, $value)
    {
        if (!$this->getEnabled()) {
            return null;
        }
        
        $filename = $this->sanitizeFileName($key);
        
        if (!file_put_contents($filename, serialize($value))) {
            throw new \RuntimeException('Couldn\t write key to cache.');
        }
        
        return true;
    }
    
    /**
     * Get Cache Key
     * @param string $key Cache ID
     * @return mixed Cached Data
     * @throws \RuntimeException In case the file cannot be open, or Content unserialized
     */
    public function get($key)
    {
        if (!$this->getEnabled()) {
            return null;
        }
        
        $filename = $this->sanitizeFileName($key);
        $value = false;
        
        if ($this->has($key)) {
            $content = file_get_contents($filename);

            if (!$content) {
                throw new \RuntimeException('Couldn\'t read cache key.');
            }
            
            $value = unserialize($content);
            
            if (!$value) {
                throw new \RuntimeException('Couldn\'t unserialize cache key.');
            }
        }
        
        return $value;
    }
    
    /**
     * Check if Key is cached and not too old
     * @param string $key Cache ID
     * @return boolean yes / no
     */
    public function has($key)
    {
        if (!$this->getEnabled()) {
            return null;
        }
        
        $filename = $this->sanitizeFileName($key);
        
        if (!is_file($filename)) {
            return false;
        }
        
        $ttl = time() - $this->getTtl();
        
        if ($ttl >= filemtime($filename)) {
            return false;
        }
        
        return true;
    }
}



/**
 * BudgetMailer API HTTP Client Class File
 * 
 * This File contains HTTP Client Class for BudgetMailer API Client
 * 
 * @author BudgetMailer <info@budgetmailer.nl>
 * @copyright (c) 2015 - BudgetMailer
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL2
 * @package BudgetMailer\API\Client
 * @version 1.0
 * 
 * BudgetMailer API PHP Client is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * BudgetMailer API PHP Client is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BudgetMailer API PHP Client. If not, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt.
 */

/**
 * Namespace
 * @package BudgetMailer\Api\Client
 */
namespace BudgetMailer\Api\Client;

use BudgetMailer\Api\Config;

/**
 * Simplified Socket based HTTP Client
 * 
 * @method array delete(string $url, array $headers = array(), string $body = null)
 * @method array get(string $url, array $headers = array(), string $body = null)
 * @method array post(string $url, array $headers = array(), string $body = null)
 * @method array put(string $url, array $headers = array(), string $body = null)
 * @package BudgetMailer\Api\Client
 */
class Http
{
    const HEAD_KV_SEP = ':';
    const EOL = "\r\n";
    const EOL2 = "\r\n\r\n";
    const SPACE = ' ';
    
    const PORT_HTTP = 80;
    const PORT_HTTPS = 443;
    
    const V_10 = 'HTTP/1.0';
    const V_11 = 'HTTP/1.1';
    
    const HTTP = 'http';
    const HTTPS = 'https';
    const HOST = 'host';
    const PATH = 'path';
    const PORT = 'port';
    const PROTOCOL = 'scheme';
    const QUERY = 'query';
    
    const DELETE = 'DELETE';
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    
    const OK = 200;
    const CREATED = 201;
    const NO_CONTENT = 204;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const NOT_FOUND = 404;
    
    /**
     * @var array Allowed HTTP Methods
     */
    protected $allowedMethods = array(
        self::DELETE, self::GET, self::POST, self::PUT
    );
    
    /**
     * @var integer Socket Error Number
     */
    protected $errorCode;
    
    /**
     * @var string Socket Error Message
     */
    protected $errorMessage;
    
    /**
     * @var boolean Print HTTP Request and Response
     */
    protected $printRr;
    
    /**
     * @var string Current HTTP Request
     */
    protected $request;
    
    /**
     * @var string Last HTTP Response
     */
    protected $response;
    
    /**
     * @var string Last HTTP Response Body
     */
    protected $responseBody;
    
    /**
     * @var array Last HTTP Response Status Headers
     */
    protected $responseHeaders;
    
    /**
     * @var integer Last HTTP Response Status Code
     */
    protected $responseCode;
    
    /**
     * @var string Last HTTP Response Status Message
     */
    protected $responseStatus;
    
    /**
     * @var resource Socket
     */
    protected $socket;
    
    /**
     * @var array Parsed URL as an associative Array
     */
    protected $urlParsed;
    
    /**
     * Create new instance of HTTP Client
     * @param Config $config Config Instance
     */
    public function __construct(Config $config)
    {
        $this->setConfig($config);
    }
    
    /**
     * Set Configuration
     * @param \BudgetMailer\Api\Config $config Configuration 
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }
    
    /**
     * Get Configuration
     * @return \BudgetMailer\Api\Config
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Magic Method Implementation
     * 
     * Allows short-hand Methods for HTTP Calls: delete(), get(), post(), put()
     * by wrapping-up the request() Method.
     * 
     * @param string $method Method Name
     * @param array $args Method Arguments
     * @return mixed Configuration Value or null
     * @see \BudgetMailer\Api\Config::__get()
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if ($this->isAllowedMethod($method)) {
            $args[0] = isset($args[0]) ? $args[0] : '';
            $args[1] = isset($args[1]) ? $args[1] : array();
            $args[2] = isset($args[2]) ? $args[2] : null;
            
            return $this->request($args[0], $method, $args[1], $args[2]);
        }
        
        throw new \BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $method . '().');
    }
    
    /**
     * Check if the Method is allowed.
     * @param string $method Method Name
     * @return boolean
     */
    public function isAllowedMethod($method)
    {
        $method = strtoupper($method);
        return in_array($method, $this->allowedMethods);
    }
    
    /**
     * Do HTTP Request.
     * 
     * This Method do HTTP Request, and stores parsed HTTP Response
     * as this Object Properties. You can do both simple Requests, and or send
     * HTTP Headers and or Body.
     * Workflow: reset Object Properties (e.g. Response), parse URL, 
     * normalize URL, open Socket, create HTTP Request, send HTTP Request, 
     * read HTTP Response, close Socket, and finally parse HTTP Response.
     * 
     * @param string $url Request URL
     * @param string $method HTTP Method
     * @param array $headers HTTP Headers
     * @param string $body HTTP Body
     * @return boolean True in Case the Method get to the End
     * @throws \InvalidArgumentException If the URL is invalid
     * @throws \RunTimeException If there is unexpected value returned in function calls
     */
    public function request($url, $method = self::GET, array $headers = array(), $body = null)
    {
        $this->reset();
        $this->parseUrl($url);
        $this->normalizeUrl();
        $this->openSocket();
        $this->createRequest($method, $headers, $body);
        
        if ($this->printRr) {
            echo PHP_EOL . 'HTTP Request: ' . PHP_EOL . $this->request . PHP_EOL;
        }
        
        $this->sendRequest($method, $headers, $body);
        $this->readResponse();
        
        if ($this->printRr) {
            echo PHP_EOL . 'HTTP Response: ' . PHP_EOL . $this->response . PHP_EOL;
        }
        
        $this->closeSocket();
        $this->parseResponse();
        
        return true;
    }
    
    protected function reset()
    {
        $this->errorCode = $this->errorMessage = 
        $this->request = $this->response = 
        $this->responseBody = $this->responseCode = $this->responseHeaders = 
        $this->socket = $this->urlParsed = null;
    }
    
    /**
     * Parse given URL and stores the associative Array.
     * @param string $url URL to parse
     * @throws \InvalidArgumentException In case the URL is not parsable.
     */
    protected function parseUrl($url)
    {
        $this->urlParsed = parse_url($url);
    
        if (!is_array($this->urlParsed)) {
            throw new \InvalidArgumentException('Unparsable URL.');
        }
    }
    
    /**
     * Check the parsed URL, and prepare it for HTTP Request.
     * 
     * Workflow: check if Hostname is not missing in parsed URL,
     * check if Port is not missing in parsed URL, check URL Protocol 
     * and set the Port accordingly, and prefix URL Query with "?" or set 
     * it to null.
     * 
     * @throws \InvalidArgumentException In case any URL validation test fails
     */
    protected function normalizeUrl()
    {
        if (!isset($this->urlParsed[self::HOST]) || !$this->urlParsed[self::HOST]) {
            throw new \InvalidArgumentException('URL is missing Hostname.');
        }
        
        if (!isset($this->urlParsed[self::PATH]) || !$this->urlParsed[self::PATH]) {
            $this->urlParsed[self::PATH] = DIRECTORY_SEPARATOR;
        }
        
        if (!isset($this->urlParsed[self::PROTOCOL]) || !$this->urlParsed[self::PROTOCOL]) {
            throw new \InvalidArgumentException('URL is missing Protocol.');
        }
        
        if (!isset($this->urlParsed[self::PORT]) || !$this->urlParsed[self::PORT]) {
            
            switch($this->urlParsed[self::PROTOCOL]) {
                case self::HTTP:
                    $this->urlParsed[self::PORT] = self::PORT_HTTP;
                    break;
                case self::HTTPS:
                    $this->urlParsed[self::PORT] = self::PORT_HTTPS;
                    break;
                default:
                    throw new \InvalidArgumentException('Allowed URL Protocols are "http" and "https".');
            }
        }
        
        $this->urlParsed[self::QUERY] = isset($this->urlParsed[self::QUERY])
            ? '?' . $this->urlParsed[self::QUERY] : null;
    }

    /**
     * Open Socket for HTTP Connection.
     * @return resource The newly created Socket
     * @throws \RuntimeException In Case the Socket or its Configuration fails
     */
    protected function openSocket()
    {
        $host = (self::HTTPS == $this->urlParsed[self::PROTOCOL]) 
            ? 'tls://' . $this->urlParsed[self::HOST] 
            : $this->urlParsed[self::HOST];
        
        $this->socket = @fsockopen(
            $host, $this->urlParsed[self::PORT], 
            $this->errorCode, $this->errorMessage, 
            $this->getConfig()->getTimeOutSocket()
        );
        
        if (!$this->socket) {
            throw new \RuntimeException(sprintf('Couldn\'t open Socket for HTTP Connection (Socket Error: %d - %s).', $this->errorCode, $this->errorMessage));
        }
        
        if (!stream_set_timeout($this->socket, $this->getConfig()->getTimeOutStream())) {
            throw new \RuntimeException('Couldn\'t set Stream Time-out for HTTP Connection Socket.');
        }
        
        return $this->socket;
    }
    
    /**
     * Close HTTP Connection Socket.
     * @throws \RuntimeException In Case the Sockect cannot be closed
     */
    protected function closeSocket()
    {
        if (!fclose($this->socket)) {
            throw new \RuntimeException('Couldn\'t close HTTP Connection Socket.');
        }
    }
    
    /**
     * Create HTTP Request String from given Arguments.
     * @param string $method HTTP Method
     * @param array $headers HTTP Headers
     * @param string $body HTTP Body
     * @return string HTTP Request
     */
    protected function createRequest($method = self::GET, array &$headers = array(), &$body = null)
    {
        $this->request = strtoupper($method) . self::SPACE . $this->urlParsed['path']
            . $this->urlParsed[self::QUERY] . self::SPACE . self::V_11 . self::EOL 
            . 'Host' . self::HEAD_KV_SEP . self::SPACE . $this->urlParsed['host'] . self::EOL
            . 'Connection' . self::HEAD_KV_SEP . self::SPACE . 'Close' . self::EOL;
        
        if (in_array(strtoupper($method), array(self::POST, self::PUT))) {
            $this->request .= 'Content-Length' . self::HEAD_KV_SEP
                . self::SPACE . strlen($body) . self::EOL;
        }
        
        if (count($headers)) {
            foreach($headers as $k => $v) {
                $this->request .= $k . self::HEAD_KV_SEP . self::SPACE . $v . self::EOL;
            }
        }
        
        $this->request .= self::EOL;
        
        if (!is_null($body)) {
            $this->request .= $body;
        }

        return $this->request;
    }
    
    /**
     * Send HTTP Request through openned Socket.
     * @throws \RuntimeException In Case the HTTP Request Send fails
     */
    protected function sendRequest()
    {
        $rs = fwrite($this->socket, $this->request);

        if (!$rs) {
            throw new \RuntimeException('Couldn\'t send HTTP Request.');
        }
    }
    
    /**
     * Read HTTP Response from Socket
     * @throws \RuntimeException In case the Read fails, or Time-out
     */
    protected function readResponse()
    {
        $this->response = null;
        $limit = time() + $this->getConfig()->getTimeOutHttp();

        // INFO this should only read up to content-length... but WTH
        while(!feof($this->socket)) {
            if ( !( $rs = fgets($this->socket, 128) ) && empty($this->response) ) {
                throw new \RuntimeException('Couldn\'t read from HTTP Socket.');
            }
            
            if ($rs) {
                $this->response .= $rs;
            }
        }
        
        if (!$this->response) {
            throw new \RuntimeException('Empty HTTP Response.');
        }
    }
    
    /**
     * Parse HTTP Response string
     * @throws \RuntimeException In Case the Headers or Body is empty or HTTP Status Code or Message cannot be parsed.
     */
    public function parseResponse()
    {
        list($headers, $this->responseBody) = explode(self::EOL2, $this->response, 2);

        if (!$headers && !$this->responseBody) {
            throw new \RuntimeException('Invalid HTTP Response: empty Headers and Body.');
        }
        
        $headersLines = explode(self::EOL, $headers);
        $this->responseHeaders = array();

        foreach($headersLines as $i => $headerLine) {
            if (0 == $i) {
                list($protocol, $this->responseCode, $this->responseStatus) = 
                    explode(self::SPACE, $headerLine, 3);
                
                if (!$this->responseCode || !$this->responseStatus) {
                    throw new \RuntimeException('Invalid HTTP Response: unknown Status Code and or Message.');
                }
                
                continue;
            }

            list($name, $value) = explode(':', $headerLine, 2);
            $this->responseHeaders[trim($name)] = trim($value);
        }
    }
    
    /**
     * Get last Response Body
     * @return string
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }
    
    /**
     * Get last Response Status Code
     * @return string|integer
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }
    
    /**
     * Get last Reponse Status Message
     * @return string
     */
    public function getResponseMessage()
    {
        return $this->responseStatus;
    }
    
    /**
     * Set Print HTTP Requests and Responses Flag
     * @param type $value
     */
    public function setPrintRequestResponse($value)
    {
        $this->printRr = $value;
    }
}



/**
 * BudgetMailer API REST-JSON Client Class File
 * 
 * This File contains generic REST-JSON Class for BudgetMailer API Client
 * 
 * @author BudgetMailer <info@budgetmailer.nl>
 * @copyright (c) 2015 - BudgetMailer
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL2
 * @package BudgetMailer\API\Client
 * @version 1.0
 * 
 * BudgetMailer API PHP Client is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * BudgetMailer API PHP Client is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BudgetMailer API PHP Client. If not, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt.
 */

/**
 * Namespace
 * @package BudgetMailer\Api\Client
 */
namespace BudgetMailer\Api\Client;

use BudgetMailer\Api\Config;

/**
 * Simplified REST-JSON HTTP Client
 * 
 * @method mixed delete(string $url, array $headers = array(), mixed $body = null, integer $expectedCode = \BudgetMailer\Api\Client\Http::OK)
 * @method mixed get(string $url, array $headers = array(), mixed $body = null, integer $expectedCode = \BudgetMailer\Api\Client\Http::OK)
 * @method mixed post(string $url, array $headers = array(), mixed $body = null, integer $expectedCode = \BudgetMailer\Api\Client\Http::OK)
 * @method mixed put(string $url, array $headers = array(), mixed $body = null, integer $expectedCode = \BudgetMailer\Api\Client\Http::OK)
 * @package BudgetMailer\Api\Client
 */
class RestJson
{
    /**
     * @var \BudgetMailer\Api\Client\Http HTTP Client
     */
    protected $http;
    
    /**
     * Create new instance of REST-JSON Client.
     * 
     * @param Config $config Configuration
     * @param \BudgetMailer\Api\Client\Http $http HTTP Client or null
     */
    public function __construct(Config $config, Http $http = null)
    {
        if (is_null($http)) {
            $http = new Http($config);
        }
        
        $this->http = $http;
    }
    
    /**
     * Magic Method Implementation.
     * 
     * Implements possible HTTP Methods: delete, get, post, and put as 
     * virtual methods.
     * 
     * @param string $method Called Method Name
     * @param array $args Called Method Arguments
     * @return mixed Decoded JSON String
     * @throws \RuntimeException In Case the Expected Code is not null and HTTP Response Status Code doesn't match
     * @throws \BadMethodCallException In Case the Method is not HTTP Method
     */
    public function __call($method, $args)
    {
        if ($this->http->isAllowedMethod($method)) {
            $args[0] = isset($args[0]) ? $args[0] : ''; // URL
            $args[1] = isset($args[1]) ? $args[1] : array(); // headers
            $args[2] = isset($args[2]) ? $args[2] : null; // body
            $expectedCode = isset($args[3]) ? $args[3] : null; // expected response code
            
            if ($args[2]) {
                $args[2] = $this->encode($args[2]);
            }
            
            $this->http->request($args[0], $method, $args[1], $args[2]);
            
            if (!is_null($expectedCode) && $expectedCode != $this->http->getResponseCode()) {
                throw new \RuntimeException(
                    sprintf(
                        'REST-JSON Call failed. Expected Response Code %d, got %d - %s. URL: %s', 
                        $expectedCode, $this->http->getResponseCode(), $this->http->getResponseMessage(), $args[0]
                    ), $this->http->getResponseCode()
                );
            }
            
            return $this->decode($this->http->getResponseBody());
        }

        throw new \BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $method . '().');
    }
    
    /**
     * Decode JSON String
     * @param string $string JSON encoded String
     * @return mixed Decoded JSON String
     * @throws \RuntimeException In Case the decoding fails
     */
    public function decode($string)
    {
        $result = json_decode($string);
        
        if (false === $result) {
            throw new \RuntimeException('Can\'t decode JSON string.');
        }
        
        return $result;
    }
    
    /**
     * Encode JSON String
     * @param mixed $data Data to encode as JSON String
     * @return string JSON encoded Data
     * @throws \RuntimeException In case the encoding fails
     */
    public function encode($data)
    {
        $result = json_encode($data);
        
        if (false === $result) {
            throw new \RuntimeException('Can\'t encode data to JSON.');
        }
        
        return $result;
    }
    
    public function getHttp()
    {
        return $this->http;
    }
}


