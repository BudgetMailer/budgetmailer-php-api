<?php

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
        $this->setEnabled($config->getCache());
        
        if ($config->getEnabled()) {
            $this->setDir($config->getCacheDir());
            $this->setTtl($config->getTtl());
        }
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
