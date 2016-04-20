<?php
require_once "../../Classes/PHPExcel.php";

/**
 * bwt_php_excel.php
 *
 * @package bwt_php_excel
 * @author Clinton Wright <cwright@bwtrans.co.za>
 * @copyright 2013 onwards Barloworld Transport (Pty) Ltd
 * @license GNU GPL
 * @link http://www.gnu.org/licenses/gpl.html
 *       This program is free software: you can redistribute it and/or modify
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
class bwt_php_excel
{
    
    // : Variables
    protected $_defaultReader = "Excel5";

    protected $_objPHPExcel = NULL;

    protected $_errors = array();

    protected $_readerTypes = array(
        0 => "Excel5",
        1 => "Excel2007",
        2 => "Excel2013XML",
        3 => "OOCalc",
        4 => "SYLK",
        5 => "Gnumeric",
        6 => "CSV"
    );
    
    // : Getters
    /**
     * bwt_php_excel::getReaderTypes()
     * Excel reader object types getter method
     */
    public function getReaderTypes()
    {
        return $this->_readerTypes;
    }

    /**
     * bwt_php_excel::getDefaultReader()
     * Excel reader object set default type getter method
     */
    public function getDefaultReader()
    {
        return $this->_defaultReader;
    }
    
    // : Setters
    /**
     * bwt_php_excel::setReaderType()
     * Set the default reader object type setter method
     */
    public function setReaderType($_readerType)
    {
        $_error = "";
        // Check that given argument is a string an contains only alphanumeric values
        if (ctype_alnum($_readerType) && is_string($_readerType)) {
            // Check that given argument value is found in defined list of reader objects
            if (preg_match("/$_readerType/", $this->_readerTypes)) {
                $this->_defaultReader = $_readerType;
            } else {
                $_error = "The supplied value for the PHP Excel Object name does not exist. User getReaderTypes to get a list of reader types available.";
            }
        } else {
            $_error = "Please supply a string containing the name of the reader object and make the value of the string only contains alphanumeric characters.";
        }
        
        // : If any errors occured then add error to class error array property else return TRUE
        if ($_error) {
            $this->_errors[] = __FUNCTION__ . $_error;
        } else {
            return TRUE;
        }
        // : End
    }
    
    // : Public Methods
    
    /**
     * bwt_php_excel::validateFileInput($_file)
     * Validate filename string
     */
    public function validateFileInput($_file)
    {
        if (preg_match("/[^a-zA-Z0-9\\_\\.\\-\\,\\(\\)]/", $_file)) {
            $this->_errors[] = __FUNCTION__ . "Invalid filename string. Please check that your filename only contains alphanumeric characters and no special characters.";
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * bwt_php_excel::getFileExtension($_file)
     * Get filename file extension
     */
    public function getFileExtension($_file)
    {
        $_fileSplit = preg_split("/\\./", $_file);
        if (count($_fileSplit) >= 2) {
            return $_fileSplit[count($_fileSplit) - 1];
        } else {
            $this->_errors[] = __FUNCTION__ . ": Not a valid filename string";
        }
    }
    
    // : Magic
    
    /**
     * bwt_php_excel::__construct($_file, $_readerType)
     * Constructor method
     */
    public function __construct($_file, $_mode, $_readerType = NULL, $_readDataOnly = TRUE, array $_sheets = NULL)
    {
        
        /*
         * Parameter config:
         *
         * $_mode
         * r = read
         * w = write
         *
         * $_readerType = Accepted values
         * Excel5
         * Excel2007
         * Excel2013XML
         * OOCalc
         * SYLK
         * Gnumeric
         * CSV
         *
         * $_readDataOnly
         * TRUE = Exclude cell formatting and include cell data
         * FALSE = Include cell formatting and data
         *
         * $_sheets
         * Filter the name of sheets to load into memory. Provide in comma seperated string or array (indice per sheet)
         */
        try {
            if ($this->validateFileInput($_file)) {
                
                // : If mode is read then prepare given input from arguments
                if ($_mode === 'r') {
                    
                    // : Check if user wants to filter sheets that will be loaded into memory else all sheets will be loaded
                    if ($_sheets || is_string($_sheets) || is_array($_sheets)) {
                        $_sheeterr = (array) array();
                        if (is_string($_sheets)) {
                            $_sheets = explode(',', $_sheets);
                        }
                        
                        $_sheeterr[] = "Sheets variable must be an array or a comma seperated string with the names of the sheets.";
                        if ($_sheeterr) {
                            $_sheets = NULL;
                        }
                    } else {
                        $_sheets = NULL;
                    }
                    // : End
                    
                    // : If readerType is not given then automatically detect filetype and load file else load specified reader and load file
                    switch ($_readerType) {
                        case ! NULL:
                            {
                                $this->setReaderType($_readerType);
                                $objReader = PHPExcel_IOFactory::createReader($_readerType);
                                break;
                            }
                        case NULL:
                        default:
                            {
                                $_filetype = PHPExcel_IOFactory::identify($_file);
                                $objReader = PHPExcel_IOFactory::createReader($_filetype);
                                break;
                            }
                    }
                    if (! $this->_errors && isset($objReader)) {
                        try {
                            // : If sheets variable is not null then load specified sheets only else load all worksheets
                            if ($_sheets !== NULL) {
                                $objReader->setLoadSheetsOnly($_sheets);
                            } else {
                                $objReader->setLoadAllSheets();
                            }
                            // : End
                            $this->_objPHPExcel = $objReader->load($_file);
                        } catch (Exception $e) {
                            $this->_errors[] = $e->getMessage();
                        }
                    }
                    // : End
                } else 
                    if ($_mode !== 'w' or $_mode !== 'r') {
                        throw new Exception("Invalid mode given. Please provide one of the following mode settings: mode = 'r, w'");
                    }
                // : End
            } else {
                return FALSE;
            }
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
            return FALSE;
        }
        return TRUE;
    }

    /**
     * bwt_php_excel::verifySheetName($_sheets)
     * Validate characters in a given single dimension array to be valid for a sheetname and return result
     */
    private function verifySheetName($_sheets)
    {
        $_tmp = (array) array();
        if (is_array($_sheets)) {
            foreach ($_sheets as $_key => $_value) {
                if (! preg_match("#[^a-zA-Z0-9\\-\\_/]#", $_value) && ! is_array($_value)) {
                    $_tmp[$_key] = $_value;
                }
            }
            if ($_tmp) {
                if (! array_diff($_sheets, $_tmp)) {
                    return TRUE;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * bwt_php_excel::readExcelFile($excelFile, $sheetName)
     * Read a spreadsheet into memory and return array with data of spreadsheet
     *
     * @param $excelFile, $sheetName            
     */
    public function parse_excel_file()
    {
        try {
            // Type cast necessary variables
            $_data = (array) array();
            // Create PHPExcel Reader Object
            $inputFileType = PHPExcel_IOFactory::identify($excelFile);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($excelFile); // Load worksheet into memory
            $worksheet = $objPHPExcel->getSheetByName($sheetname);
            
            // Read spreadsheet data and store into array
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(true);
                foreach ($cellIterator as $cell) {
                    if ($cell->getRow() != 1) {
                        $data[$objPHPExcel->getActiveSheet()
                            ->getCell($cell->getColumn() . "1")
                            ->getValue()][$cell->getRow() - 1] = $cell->getValue();
                    }
                }
            }
            return $data;
        } catch (Exception $e) {
            echo "Caught exception: ", $e->getMessage(), "\n";
            return false;
        }
    }

    /**
     * bwt_php_excel::writeExcelFile($excelFile, $excelData)
     * Create, Write and Save Excel Spreadsheet from collected data obtained from the variance report
     *
     * @param $excelFile, $excelData            
     */
    public function write_excel_file($_data, $_columns)
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
                $objPHPExcel->getProperties()->setCreator(self::XLS_CREATOR);
                $objPHPExcel->getProperties()->setLastModifiedBy(self::XLS_CREATOR);
                $objPHPExcel->getProperties()->setTitle(self::XLS_TITLE);
                $objPHPExcel->getProperties()->setSubject(self::XLS_SUBJECT);
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
                $objPHPExcel->getActiveSheet()->setTitle(self::XLS_TITLE);
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
                exit();
            }
        } catch (Exception $e) {
            echo "Caught exception: ", $e->getMessage(), "\n";
            exit();
        }
    }
}