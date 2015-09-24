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
