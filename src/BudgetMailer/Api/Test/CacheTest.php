<?php

/**
 * BudgetMailer API Cache Test File
 * 
 * This File contains Tests for Cache Class
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
use BudgetMailer\Api\Config;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    const CLS_CACHE = 'BudgetMailer\Api\Cache';
    const KEY1 = 'key1';
    const VALUE1 = 'value1';
    const KEY2 = 'key2';
    const VALUE2 = 'value2';
    
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
    }
    
    public function testHtaccess()
    {
        $this->assertTrue(
            is_file($this->config->getCacheDir() . '.htaccess')
        );
    }
    
    public function testClass()
    {
        $this->assertTrue(
            self::CLS_CACHE == get_class($this->cache)
        );
    }
    
    public function testSet()
    {
        $this->assertTrue(
            $this->cache->set(self::KEY1, self::VALUE1)
        );
    }
    
    public function testSerialization()
    {
        $a = array(self::KEY1 => self::VALUE1, self::KEY2 => self::VALUE2);
        
        $this->assertTrue(
            $this->cache->set('array', $a)
        );
        
        $this->assertEquals(
            $a, $this->cache->get('array')
        );
    }
    
    public function testHas()
    {
        $this->assertTrue(
            $this->cache->has(self::KEY1)
        );
    }
    
    public function testGet()
    {
        $this->assertEquals(
            self::VALUE1, $this->cache->get(self::KEY1)
        );
    }
    
    public function testRemove()
    {
        $this->assertTrue(
            $this->cache->remove(self::KEY1)
        );
        
        $this->assertFalse(
            $this->cache->has(self::KEY1)
        );
    }
    
    public function testPurge()
    {
        $this->assertTrue(
            $this->cache->set(self::KEY1, self::VALUE1)
        );
        
        $this->assertTrue(
            $this->cache->set(self::KEY2, self::VALUE2)
        );
        
        $this->cache->purge();
        
        $this->assertFalse(
            $this->cache->has(self::KEY1)
        );
        
        $this->assertFalse(
            $this->cache->has(self::KEY2)
        );
    }
}
