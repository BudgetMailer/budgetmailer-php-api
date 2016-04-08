<?php

namespace BudgetMailer\Api;


#
# PHP BudgetMailer API Client examples
#


error_reporting(E_ALL);
ini_set('display_errors', true);


#
# 0. USING SINGLE FILE VERSION OF THE CLIENT
#


require_once '../php-budgetmailer-api.php';

// minimal example
$client = Client::getInstance(array(
    'key'       => 'INSERTAPIKEY',      // API key
    'list'      => 'INSERTLISTNAME',    // list name or id
    'secret'    => 'INSERTAPISECRET'    // API secret
));

// cache enabled
$client = Client::getInstance(array(
    'cache'     => true,                                // enable response caching
    'cacheDir'  => realpath(__DIR__ . '/../') . '/',    // writeable cache directory, don't forget trailing /
    'key'       => 'INSERTAPIKEY',                      // API key
    'list'      => 'INSERTLISTNAME',                    // list name or id
    'secret'    => 'INSERTAPISECRET'                    // API secret
));


#
# 1. SETUP AUTOLOADING (not needed with composer)
#


require_once __DIR__ . '/../tests/AutoloaderPsr4.php';

define('PHP_BM_ROOT', realpath(__DIR__ . '/..') . '/');

$loader = new \AutoloaderPsr4;
$loader->register();
$loader->addNamespace('BudgetMailer\Api', PHP_BM_ROOT . 'src/BudgetMailer/Api');


#
# 2. GET CONFIGURATION (also optional, you may use Client::getInstance(array $config))
#


$configFile = __DIR__ . '/config.php';
$configData = include $configFile;
unset($configFile);

if (!is_array($configData) || !count($configData)) {
    die('Config not found.');
}


#
# 3. a) INITIATE CLIENT as singleton
#


$client = Client::getInstance(array(
    'key' => '',        // API key
    'list' => '',       // list name or id
    'secret' => ''      // API secret
));

// later you can get client again without config param


#
# 3. b) INITIATE CLIENT 
#


$config = new Config($configData);
unset($configData);

$cache = new Cache($config);
$client = new Client($cache, $config);


#
# 4. GET ALL LISTS
#


$lists = $client->getLists();
//var_dump($lists);


#
#
# 5. DELETE CONTACT
#
#


// you can delete contact by email or budgetmailer contact id
$emailOrId = 'e@ma.il';
// you can optionally send name or id of budgetmailer contact list id
// null = default list from configuration
$listName = null;

$client->deleteContact($emailOrId, $listName);


#
# 6. DELETE TAG FROM CONTACT
#


$tag = 'Tag';

$client->deleteTag($emailOrId, $tag, $listName);


#
# 7. GET SINGLE CONTACT
#


$contact = $client->getContact($emailOrId, $listName);


#
# 8. GET MULTIPLE CONTACTS
#


$offset = 0;
$limit = 20;
$sort = 'ASC';
$unsubscribed = null;
$list = null;

$client->getContacts($offset, $limit, $sort, $unsubscribed, $listName);


#
# 9. GET TAGS FROM CONTACT
#


$tags = $client->getTags($emailOrId, $listName);


#
# 10. CREATE NEW CONTACT IN LIST
#


$contact = new \stdClass();
$contact->email = 'somerandom@email.com';

$newContact = $client->postContact($contact, $listName);


#
# 11. CREATE MULTIPLE CONTACTS AT ONCE
#


$contact2 = new \stdClass();
$contact2->email = 'somerandom2@email.com';
$contact3 = new \stdClass();
$contact3->email = 'somerandom3@email.com';

$contacts = array(
    $contact2, $contact3
);

$client->postContacts($contacts, $listName);


#
# 12. ADD TAGS TO CONTACT
#


//tags may be both string, or array of strings

$tags = array(
    'Tag 1', 'Tag 2'
);

$client->postTags($emailOrId, $tags, $listName);


#
# 13. UPDATE CONTACT
#


$contact->firstName = 'newfirstname';

$client->putContact($emailOrId, $contact, $listName);
