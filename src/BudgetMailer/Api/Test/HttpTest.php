<?php

/**
 * BudgetMailer API HTTP Client Test File
 * 
 * This File contains Tests for HTTP Client Class
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

use BudgetMailer\Api\Config;
use BudgetMailer\Api\Client\Http;

class HttpTest extends \PHPUnit_Framework_TestCase
{
    const CLS_HTTP = 'BudgetMailer\Api\Client\Http';
    
    protected $config;
    protected $configData;
    protected $configFile;
    protected $http;
    
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
        
        $this->http = new Http($this->config);
    }
    
    public function testHttp()
    {
        $this->assertTrue(
            self::CLS_HTTP == get_class($this->http)
        );
    }
    
    public function testGet()
    {
        $this->http->get('http://morf.cz');
        
        $this->assertEquals(Http::OK, $this->http->getResponseCode());
        $this->assertNotEmpty($this->http->getResponseBody());
        $this->assertNotEmpty($this->http->getResponseMessage());
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testNonExistantDomain()
    {
        $this->http->get('http://asdfjoweijfoweifjwe.com');
        
        $this->assertTrue(empty($this->http->getResponseBody()));
        $this->assertTrue(empty($this->http->getResponseCode()));
        $this->assertTrue(empty($this->http->getResponseMessage()));
    }
}
