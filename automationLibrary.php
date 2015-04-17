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
    const SQL_QUERY_OFFLOAD_BU_LINK = "select ID from udo_offloadingcustomersbusinessunit_link where offloadingCustomers_id=%o and businessUnit_id=%b;";
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
    const ERR_NO_DATE_RANGE_VALUE = "ERROR: Could not find DateRangeValue for Record: %s";
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

    /**
     * automationLibrary::CONSOLE_OUTPUT($_heading, $_description, $_type, $_query, $_data)
     * Output debug information onto screen
     * Heading: What debug information we are displaying title
     * Description: A short description about the debug information
     * Type: Only 'sql' type available at the moment for displaying SQL Debug Data
     * Query: SQL query ran
     * Data: SQL results returned from the above query
     *
     * @param string: $_heading
     * @param string: $_description
     * @param string: $_type
     * @param string: $_query
     * @param array: $_data            
     */
    public static function CONSOLE_OUTPUT($_heading, $_description, $_type, $_query, $_data) {
        switch ($_type) {
            case "sql" :
            default : {
		printf("INFO: %s. Query run: %s" . PHP_EOL, $_heading, $_query);
		printf("DEBUG: %s" . PHP_EOL, $_description); 
                var_dump($_data);
            }
        }
    }


    /**
     * automationLibrary::stringHypenFix($_value)
     * Replace long hyphens in string to short hyphens as part of a problem
     * created when importing data from spreadsheets
     *
     * @param string: $_value            
     * @param string: $_result            
     */
    public static function stringHypenFix($_value)
    {
        $_result = preg_replace("/–/", "-", $_value);
        return $_result;
    }

    /**
     * automationLibrary::addErrorRecord(&$_errArr, &$_session, $_scrDir, $_errmsg, $_record, $_process)
     * Add error record to error array
     *
     * @param array: $_erArrr
     * @param object: $_session
     * @param string: $_scrDir
     * @param string: $_errmsg
     * @param string: $_record
     * @param string: $_process
     */
    public static function addErrorRecord(&$_errArr, $_session, $_scrDir, $_errmsg, $_record, $_process)
    {
        $_erCount = count($_errArr);
        $_errArr[$_erCount + 1]["error"] = $_errmsg;
        $_errArr[$_erCount + 1]["record"] = $_record;
        $_errArr[$_erCount + 1]["type"] = $_process;
        self::takeScreenshot($_session, $_scrDir);
    }
    
    /**
     * automationLibrary::getSelectedOptionValue($_using, $_value, &$_session)
     * This is a function description for a selenium test function
     *
     * @param string: $_using
     * @param string: $_value
     * @param object: $_session
     */
    public static function getSelectedOptionValue($_using, $_value, &$_session) {
        try {
            $_result = FALSE;
            $_cnt = count ( $_session->elements ( $_using, $_value ) );
            for($x = 1; $x <= $_cnt; $x ++) {
                $_selected = $_session->element ( $_using, $_value . "[$x]" )->attribute ( "selected" );
                if ($_selected) {
                    $_result = $_session->element ( $_using, $_value . "[$x]" )->attribute ( "value" );
                    break;
                }
            }
        } catch ( Exception $e ) {
            $_result = FALSE;
        }
        return ($_result);
    }
    
    /**
     * automationLibrary::assertElementPresent($_using, $_value, &$_session, &$_phpunit_fw_obj)
     * This is a function description for a selenium test function
     *
     * @param string: $_using
     * @param string: $_value
     * @param object: $_session
     * @param object: $_phpunit_fw_obj
     */
    public static function assertElementPresent($_using, $_value, &$_session, &$_phpunit_fw_obj) {
        $e = $_session->element ( $_using, $_value );
        try {
            $_phpunit_fw_obj->assertEquals ( count ( $e ), 1 );
        } catch (Exception $e) {
            return FALSE;
        }
        return TRUE;
    }


    /**
     * automationLibrary::writeExcelFile($excelFile, $excelData)
     * Create, Write and Save Excel Spreadsheet from collected data obtained from the variance report
     *
     * @param $excelFile, $excelData            
     */
    public static function writeExcelFile($excelFile, $excelData, $columns, $author, $title, $subject)
    {
        try {
            // Check data validility
            if (count($excelData) != 0) {
                
                // : Create new PHPExcel object
                print("<pre>");
                print(date('H:i:s') . " Create new PHPExcel object" . PHP_EOL);
                $objPHPExcel = new PHPExcel();
                // : End
                
                // : Set properties
                print(date('H:i:s') . " Set properties" . PHP_EOL);
                $objPHPExcel->getProperties()->setCreator($author);
                $objPHPExcel->getProperties()->setLastModifiedBy($author);
                $objPHPExcel->getProperties()->setTitle($title);
                $objPHPExcel->getProperties()->setSubject($subject);
                // : End
                
                // : Setup Workbook Preferences
                print(date('H:i:s') . " Setup workbook preferences" . PHP_EOL);
                $objPHPExcel->getDefaultStyle()
                    ->getFont()
                    ->setName('Arial');
                $objPHPExcel->getDefaultStyle()
                    ->getFont()
                    ->setSize(8);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setFitToWidth(1);
                $objPHPExcel->getActiveSheet()
                    ->getPageSetup()
                    ->setFitToHeight(0);
                // : End
                
                // : Set Column Headers
                $alphaVar = range('A', 'Z');
                print(date('H:i:s') . " Setup column headers" . PHP_EOL);
                
                $i = 0;
                foreach ($columns as $key) {
                    $objPHPExcel->getActiveSheet()->setCellValue($alphaVar[$i] . "1", $key);
                    $objPHPExcel->getActiveSheet()
                        ->getStyle($alphaVar[$i] . '1')
                        ->getFont()
                        ->setBold(true);
                    $i ++;
                }
                
                // : End
                
                // : Add data from $excelData array
                print(date('H:i:s') . " Add data from error array" . PHP_EOL);
                $rowCount = (int) 2;
                $objPHPExcel->setActiveSheetIndex(0);
                foreach ($excelData as $values) {
                    $i = 0;
                    foreach ($values as $key => $value) {
                        $objPHPExcel->getActiveSheet()
                            ->getCell($alphaVar[$i] . strval($rowCount))
                            ->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                        $i ++;
                    }
                    $rowCount ++;
                }
                // : End
                
                // : Setup Column Widths
                for ($i = 0; $i <= count($columns); $i ++) {
                    $objPHPExcel->getActiveSheet()
                        ->getColumnDimension($alphaVar[$i])
                        ->setAutoSize(true);
                }
                // : End
                
                // : Rename sheet
                print(date('H:i:s') . " Rename sheet" . PHP_EOL);
                $objPHPExcel->getActiveSheet()->setTitle($title);
                // : End
                
                // : Save spreadsheet to Excel 2007 file format
                print(date('H:i:s') . " Write to Excel2007 format" . PHP_EOL);
                print("</pre>" . PHP_EOL);
                $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
                $objWriter->save($excelFile);
                $objPHPExcel->disconnectWorksheets();
                unset($objPHPExcel);
                unset($objWriter);
                // : End
            } else {
                print("<pre>");
                print_r("ERROR: The function was passed an empty array");
                print("</pre>");
            }
        } catch (Exception $e) {
            echo "Caught exception: ", $e->getMessage(), "\n";
        }
    }
    // : End

    // : Magic Methods
    // : End
    
    // : Private Methods

    /**
     * automationLibrary::takeScreenshot()
     * This is a function description for a selenium test function
     *
     * @param object: $_session            
     */
    private static function takeScreenshot($_session, $_scrDir)
    {
        $_params = func_get_args();
        $_img = $_session->screenshot();
        $_data = base64_decode($_img);
        $_pathname_extra = (string) "";
        
        if ($_params && is_array($_params)) {
            if (array_key_exists(2, $_params)) {
                $_pathname_extra = $_params[2];
            }
        }
        // Suport for variable length arguments (only 1 extra argument supported
        if ($_pathname_extra) {
            $_file = $_scrDir . DIRECTORY_SEPARATOR . date("Y-m-d_His") . "_${_pathname_extra}_WebDriver.png";
        } else {
            $_file = $_scrDir . DIRECTORY_SEPARATOR . date("Y-m-d_His") . "_WebDriver.png";
        }
        $_success = file_put_contents($_file, $_data);
        if ($_success) {
            return $_file;
        } else {
            return FALSE;
        }
    }
    // : End
}
