<?php

/**
 * BudgetMailer API Client Test File
 * 
 * This File contains Tests for Client Class
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
 * @package BudgetMailer\Api\Test
 */
namespace BudgetMailer\Api\Test;

use BudgetMailer\Api\Cache;
use BudgetMailer\Api\Client;
use BudgetMailer\Api\Config;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    const CLS_CLIENT = 'BudgetMailer\Api\Client';
    const EMAIL = 'e@ma.il';
    const EMAIL2 = 'e2@ma.il';
    const TAG1 = 'Tag 1';
    const TAG2 = 'Tag 2';
    
    protected $cache;
    protected $config;
    protected $configData;
    protected $configFile;
    
    public function setUp()
    {
        $this->configFile = PHP_BM_ROOT . 'example/config.php';
        
        if (!is_readable($this->configFile)) {
            throw new \Exception('Example config file not found.');
        }
        
        $this->configData = include $this->configFile;
        
        if (!is_array($this->configData) || !count($this->configData)) {
            throw new \Exception('Example configuration is empty.');
        }
        
        $this->config = new Config($this->configData);
        $this->cache = new Cache($this->config);
        $this->cache->purge();
        $this->client = new Client($this->cache, $this->config);
        $this->client->getRestJson()->getHttp()->setPrintRequestResponse(true);
    }
    
    public function testClient()
    {
        $this->assertTrue(
            self::CLS_CLIENT == get_class($this->client)
        );
    }
    
    /**
     * @expectedException BadMethodCallException
     */
    public function testStaticClientException()
    {
        Client::getInstance(array(
            // missing required keys
        ));
    }
    
    /**
     * @depends testStaticClientException
     */
    public function testStaticClient()
    {
        $configData = array(
            'key' => $this->config->getKey(),
            'list' => $this->config->getList(),
            'secret' => $this->config->getSecret(),
        );
        
        $this->assertTrue(
            self::CLS_CLIENT == get_class(Client::getInstance($configData))
        );
    }
    
    /**
     * @depends testStaticClient
     */
    public function testStaticClient2()
    {
        $this->assertTrue(
            self::CLS_CLIENT == get_class(Client::getInstance()) // return existing instance
        );
    }
    
    /**
     * @depends testClient
     */
    public function testGetLists()
    {
        $lists = $this->client->getLists();
        
        $this->assertTrue(is_array($lists));
        $this->assertTrue(count($lists) >= 1);
        
        foreach($lists as $list) {
            $this->assertTrue(isset($list->id) && !empty($list->id));
            $this->assertTrue(isset($list->list) && !empty($list->list));
            $this->assertTrue(isset($list->primary) && ( $list->primary === false || $list->primary === true));
        }
    }
    
    /**
     * @depends testGetLists
     */
    public function testGetEmptyContacts()
    {
        $contacts = $this->client->getContacts();
        
        $this->assertTrue(is_array($contacts));
        
        $zero = count($contacts) == 0;
        
        $this->assertTrue($zero);
    }
    
    /**
     * @depends testGetEmptyContacts
     */
    public function testGetNonExistentContact()
    {
        $contact = $this->client->getContact(self::EMAIL);
        
        $this->assertTrue(is_null($contact));
    }
    
    /**
     * @depends testGetNonExistentContact
     */
    public function testPostContact()
    {
        $contact = new \stdClass();
        $contact->email = self::EMAIL;
        
        $newContact = $this->client->postContact($contact);
        
        $this->assertTrue(is_object($newContact));
        
        $this->assertTrue(isset($newContact->email) && $newContact->email == $contact->email);
        $this->assertTrue(isset($newContact->id) && !empty($newContact->id));
        $this->assertTrue(isset($newContact->list) && !empty($newContact->list));
    }
    
    /**
     * @depends testPostContact
     */
    public function testGetContact()
    {
        $contact = $this->client->getContact(self::EMAIL);
        
        $this->assertTrue(is_object($contact));
        
        $this->assertTrue(isset($contact->email) && !empty($contact->email));
        $this->assertTrue(isset($contact->id) && !empty($contact->id));
    }
    
    /**
     * @depends testGetContact
     */
    public function testPutContact()
    {
        $testName = 'test';
        
        $contact = $this->client->getContact(self::EMAIL);
        
        $newContact = clone $contact;
        $newContact->firstName = $testName;
        
        // update
        $this->assertTrue(
            $this->client->putContact(self::EMAIL, $newContact, null)
        );
        
        $contact = $this->client->getContact(self::EMAIL);
        
        $this->assertTrue(is_object($contact));
        $this->assertTrue(isset($newContact->firstName));
        $this->assertTrue(isset($contact->firstName));
        
        // check if updated
        $this->assertTrue(
            $newContact->firstName == $contact->firstName
        );
        
        $newContact = clone $contact;
        $newContact->unsubscribed = true;
        
        // unsubscribe
        $this->assertTrue(
            $this->client->putContact(self::EMAIL, $newContact, null, false)
        );
        
        $contact = $this->client->getContact(self::EMAIL);
        
        // check if unsubscribed
        $this->assertTrue($contact->unsubscribed);
        
        $newContact->unsubscribed = false;
        
        // subscribe
        $this->assertTrue(
            $this->client->putContact(self::EMAIL, $newContact, null, true)
        );
        
        $contact = $this->client->getContact(self::EMAIL);
        
        // check if subscribed
        $this->assertFalse($contact->unsubscribed);
        
        $contact = new \stdClass();
        $contact->email = self::EMAIL2;
        $contact->firstName = 'name';
        
        $rs = $this->client->putContact(self::EMAIL2, $contact, null);
        
        $this->assertTrue(is_null($rs));
    }
    
    /**
     * @depends testPutContact
     */
    public function testTags()
    {
        $tagsApi = $this->client->getTags(self::EMAIL);
        
        $this->assertTrue(is_array($tagsApi));
        $this->assertTrue(count($tagsApi) == 0);
        
        $tags = array(self::TAG1, self::TAG2);
        
        $this->client->postTags(self::EMAIL, $tags);
        
        $tagsApi = $this->client->getTags(self::EMAIL);
        
        $this->assertTrue(is_array($tagsApi));
        $this->assertTrue(count($tagsApi) == 2);
        $this->assertTrue(in_array(self::TAG1, $tagsApi));
        $this->assertTrue(in_array(self::TAG2, $tagsApi));
        
        $rs = $this->client->deleteTag(self::EMAIL, self::TAG1);
        $this->assertTrue($rs);
        
        $tagsApi = $this->client->getTags(self::EMAIL);
        
        $this->assertTrue(is_array($tagsApi));
        $this->assertTrue(count($tagsApi) == 1);
        $this->assertTrue(!in_array(self::TAG1, $tagsApi));
        $this->assertTrue(in_array(self::TAG2, $tagsApi));
    }
    
    /**
     * @depends testTags
     */
    public function testDeleteContact()
    {
        $rs = $this->client->deleteContact(self::EMAIL);
        
        $this->assertTrue($rs);
        
        $contact = $this->client->getContact(self::EMAIL);
        
        $this->assertTrue(is_null($contact));
    }
    
    /**
     * @depends testDeleteContact
     */
    public function testBulkPostContact()
    {
        $contact = new \stdClass();
        $contact->email = self::EMAIL;
        
        $contact2 = new \stdClass();
        $contact2->email = self::EMAIL2;
        
        $contacts = array($contact, $contact2);
        
        $rs = $this->client->postContacts($contacts);
        
        $this->assertTrue($rs);
        
        $newContacts = $this->client->getContacts();
        
        $this->assertTrue(is_array($newContacts));
        $this->assertTrue(count($newContacts) == 2);
    }
    
    /**
     * @depends testBulkPostContact
     */
    public function testCleanup()
    {
        // keep the test empty for future testing
        $this->client->deleteContact(self::EMAIL);
        $this->client->deleteContact(self::EMAIL2);
    }
}
