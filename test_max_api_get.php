<?php
/**
 * test_max_api_get.php
 *
 * @package test_max_api_get.php
 * @author Clinton Wright <cwright@bwtrans.co.za>
 * @copyright 2016 onwards Barloworld Transport (Pty) Ltd
 * @license GNU GPL
 * @link http://www.gnu.org/licenses/gpl.html
 *       * This program is free software: you can redistribute it and/or modify
 *       it under the terms of the GNU General Public License as published by
 *       the Free Software Foundation, either version 3 of the License, or
 *       (at your option) any later version.
 *
 *       This program is distributed in the hope that it will be useful,
 *       but WITHOUT ANY WARRANTY; without even the implied warranty of
 *       MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *       GNU General Public License for more details.
 *
 *       You should have received a copy of the GNU General Public License
 *       along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

# Convert this to a PHPUnit test MAX_API_Get.php
# Currently used to test the functionality of MAX_API_Get.php library

require dirname(__FILE__) . DIRECTORY_SEPARATOR .  'MAX_API_Get.php';

$_curl_lib = new MAX_API_Get('live');
print(PHP_EOL . "Dump errors: " . PHP_EOL);
var_dump($_curl_lib->getErrors());

print(PHP_EOL . "Dump logs: " . PHP_EOL);
var_dump($_curl_lib->getLogs());

print(PHP_EOL . "Dump MAX URL: " . PHP_EOL);
var_dump($_curl_lib->getMaxUrl());

print(PHP_EOL . "Dump Debug Data: " . PHP_EOL);
var_dump($_curl_lib->getDebugData());

print(PHP_EOL . "Dump Object Registry Objects JSON data file: " . PHP_EOL);
var_dump($_curl_lib->getObjRegFilePath());
unset($_curl_lib);