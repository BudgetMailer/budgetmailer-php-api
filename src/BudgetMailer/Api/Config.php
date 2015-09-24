<?php

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
