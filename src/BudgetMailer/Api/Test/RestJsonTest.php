<?php

/**
 * BudgetMailer API REST-JSON Cliet Test File
 * 
 * This File contains Tests for REST-JSON Client Class
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

use BudgetMailer\Api\Client\RestJson;
use BudgetMailer\Api\Config;

class RestJsonTest extends \PHPUnit_Framework_TestCase
{
    const CLS_RESTJSON = 'BudgetMailer\Api\Client\RestJson';
    
    protected $configData;
    protected $configFile;
    protected $restJson;
    
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
        $this->restJson = new RestJson($this->config);
    }
    
    public function testRestJson()
    {
        $this->assertTrue(
            self::CLS_RESTJSON == get_class($this->restJson)
        );
    }
}
