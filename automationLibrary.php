<?php
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriver.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverWait.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverBy.php';
include_once 'PHPUnit/Extensions/php-webdriver/PHPWebDriver/WebDriverProxy.php';
include_once dirname ( __FILE__ ) . '/ReadExcelFile.php';
include_once 'PHPUnit/Extensions/PHPExcel/Classes/PHPExcel.php';

/**
 * automationLibrary.php
 *
 * @package automationLibrary
 * @author Clinton Wright <cwright@bwtsgroup.com>
 * @copyright 2013 onwards Barloworld Transport (Pty) Ltd
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

class automationLibrary {
    // : Constants
    # Constants - SQL Queries
    const SQL_QUERY_OBJREG = "select ID from objectregistry where handle = '%s';";
    const SQL_QUERY_ZONE = "select ID from udo_zone where name='%s';";
    const SQL_QUERY_ROUTE = "select ID from udo_route where locationFrom_id=%f and locationTo_id=%t;";
    const SQL_QUERY_RATE = "select ID from udo_rates where route_id=%ro and objectregistry_id=%g and objectInstanceId=%c and truckDescription_id=%d and enabled=1 and model='%m' and businessUnit_id=%b and rateType_id=%r;";
    const SQL_QUERY_CUSTOMER = "select ID from udo_customer where tradingName='%t';";
    const SQL_QUERY_OFFLOADING_CUSTOMER = "select ID from udo_offloadingcustomers where offloadingCustomer_id IN (select ID from udo_customer where tradingName='%o') and customer_id=%c;";
    const SQL_QUERY_CUSTOMER_LOCATION_LINK = "select ID from udo_customerlocations where location_id=%l and customer_id=%c;";
    const SQL_QUERY_LOCATION = "select ID from udo_location where name = '%n' and _type='%t';";
    const SQL_QUERY_TRUCK_TYPE = "select ID from udo_truckdescription where description='%d';";
    const SQL_QUERY_RATE_TYPE = "select ID from udo_ratetype where name='%s';";
    const SQL_QUERY_BUNIT = "select ID from udo_businessunit where name='%s';";
    const SQL_QUERY_CUSTOMER_LOCATION_BU_LINK = "select ID from udo_customerlocationsbusinessunit_link where customerLocations_id=%l and businessUnit_id=%b;";
    # Constants - Location Types
    const _TYPE_CITY = "udo_City";
    const _TYPE_CONTINENT = "udo_Continent";
    const _TYPE_DEPOT = "udo_Depot";
    const _TYPE_MILL = "udo_Mill";
    const _TYPE_PLANTATION = "udo_Plantation";
    const _TYPE_POINT = "udo_Point";
    const _TYPE_PROVINCE = "udo_Province";
    const _TYPE_SUBURB = "udo_Suburb";
    const _TYPE_TOLLGATE = "udo_TollGate";
    # Constants - Object Registry Objects
    const OBJREG_CUSTOMER = "udo_Customer";
    # Constants - Error Messages
    const ERR_COULD_NOT_FIND_ELEMENT = "ERROR: Could not find the expected element on page: %s";
    const ERR_NO_CUSTOMER_DATA = "FATAL: Could not find customer data when attempting to access the imported data from array.";
    const ERR_SQL_QUERY_NO_RESULTS_REQ_DATA = "FATAL: Required data searched from the database was not found using the following SQL query: %s";
    const ERR_COULD_NOT_FIND_RECORD_USING_URL = "ERROR: Could not find %d after creating it using the following URL: %u";
    const ERR_PROCESS_FAILED_UNEXPECTEDLY = "ERROR: Caught error while busy with process %s with error message: %e";
    # Constants - URL addresses
    const URL_CUSTOMER = "/DataBrowser?browsePrimaryObject=461&browsePrimaryInstance=";
    const URL_PB = "/Planningboard";
    const URL_POINT = "/Country_Tab/points?&tab_id=52";
    const URL_CITY = "/Country_Tab/cities?&tab_id=50";
    const URL_CUST_LOCATION_BU = "/DataBrowser?browsePrimaryObject=495&browsePrimaryInstance=";
    const URL_OFFLOAD_CUST_BU = "/DataBrowser?browsePrimaryObject=494&browsePrimaryInstance=";
    const URL_RATEVAL = "/DataBrowser?&browsePrimaryObject=udo_Rates&browsePrimaryInstance=%s&browseSecondaryObject=DateRangeValue&relationshipType=Rate"; 
    const URL_LIVE = "https://login.max.bwtsgroup.com"; 
    const URL_TEST = "http://max.mobilize.biz";
    // : End
    
    // : Properties
    // : End
    
    // : Public Methods
    public static function CONSOLE_OUTPUT($_heading, $_description, $_type, $_query, $_data) {
        switch ($_type) {
            case "sql" :
            default : {
                echo "INFO: " . $_heading . ". Query run: " . $_query . PHP_EOL;
                echo "DEBUG: " . $_description . PHP_EOL;
                var_dump($_data);
            }
        }
    }
    // : End

    // : Magic Methods
    // : End
    
    // : Private Methods
    // : End
}
