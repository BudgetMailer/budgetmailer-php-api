<?php

/**
 * BudgetMailer API build Script.
 * 
 * This Utility File concat all Classes to single distribution File.
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

error_reporting(E_ALL);
ini_set('display_errors', 1);

$distRoot = realpath(__DIR__ . '/../dist') . '/';
$srcRoot = realpath(__DIR__ . '/../src/BudgetMailer/Api') . '/';

if (!is_dir($distRoot)) {
    die('Dist root not found.');
}

if (!is_dir($srcRoot)) {
    die('Source root not found.');
}

$dist = '<?php' . PHP_EOL;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcRoot));
$it->rewind();

while($it->valid()) {
    if (!$it->isDot() && !substr_count($it->getSubPathName(), 'Test/')) {
        $contents = '# FILE: ' . $it->getSubPathName() . PHP_EOL . PHP_EOL;
        $contents = file_get_contents($it->key());
        
        if (!$contents) {
            die('file not found.');
        }
        
        $dist .= str_replace('<?php' . PHP_EOL, '', $contents) . PHP_EOL . PHP_EOL;
        
        //echo 'SubPathName: ' . $it->getSubPathName() . "\n";
        //echo 'SubPath:     ' . $it->getSubPath() . "\n";
        //echo 'Key:         ' . $it->key() . "\n\n";
    }

    $it->next();
}

if (!file_put_contents($distRoot . 'php-budgetmailer-api.php', $dist)) {
    die('didnt write it.');
}
