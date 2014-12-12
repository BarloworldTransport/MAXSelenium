<?php

require_once "../../Classes/PHPExcel.php";

class bwt_php_excel {
    
    // : Variables
    protected $_defaultReader = "Excel5";
    protected $_errors = array();
    protected $_readerTypes = array(
        0 => "Excel5",
        1 => "Excel2007",
        2 => "Excel2013XML",
        3 => "OOCalc",
        4 => "SYLK",
        5 => "Gnumeric",
        6 => "CSV",
    );
    
    // : Getters
    /**
     * bwt_php_excel_lib::getReaderTypes()
     * Excel reader object types getter method
     *
     */
    public function getReaderTypes() {
        return $this->_readerTypes;
    }

    /**
     * bwt_php_excel_lib::getDefaultReader()
     * Excel reader object set default type getter method
     *
     */
    public function getDefaultReader() {
        return $this->_defaultReader;
    }    
    
    // : Setters
    /**
     * bwt_php_excel_lib::setReaderType()
     * Set the default reader object type setter method
     *
     */
    public function setReaderType($_readerType) {
        $_error = "";
        // Check that given argument is a string an contains only alphanumeric values
        if (ctype_alnum($_readerType) && is_string($_readerType)) {
            // Check that given argument value is found in defined list of reader objects
            if(preg_match("/$_readerType/", $this->_readerTypes )) {
                $this->_defaultReader = $_readerType;
            } else {
                $_error = "The supplied value for the PHP Excel Object name does not exist. User getReaderTypes to get a list of reader types available.";
            }
        } else {
            $_error = "Please supply a string containing the name of the reader object and make the value of the string only contains alphanumeric characters.";
        }
        
        // : If any errors occured then add error to class error array property else return TRUE
        if ($_error) {
            $this->_errors[] = $_error;
        } else {
            return TRUE;
        }
        // : End
    }    
    
    
    // : Public Methods

    /**
     * bwt_php_excel_lib::validateFileInput($_file)
     * Get filename file extension
     *
     */
    public function validateFileInput($_file) {
    
        preg_split("/\./");
    }
    
    /**
     * bwt_php_excel_lib::getFileExtension($_file)
     * Get filename file extension
     *
     */
    public function getFileExtension($_file) {
        
            preg_split("/\./");
    }
    
    // : Magic

    /**
     * bwt_php_excel_lib::__construct($_file, $_mode = 0, $_readerType)
     * Constructor method
     *
     */
    public function __construct($_file, $_mode = 0, $_readerType = NULL) {
        
        /*
         * $_mode
         * 0 - auto detect filetype and load appropriate reader object
         * 1 - Do not auto detect filetype but specify reader object type
         * 
         * $_readerType = Accepted values
         * Excel5
         * Excel2007
         * Excel2013XML
         * OOCalc
         * SYLK
         * Gnumeric
         * CSV
         */
        
        
        
    }
    
    /**
     * bwt_php_excel_lib::readExcelFile($excelFile, $sheetName)
     * Read a spreadsheet into memory containing F and V Contract data
     * and arrange into a multidimensional array
     *
     * @param $excelFile, $sheetName
     */
    public function parse_excel_file($excelFile, $sheetname) {
        try {
            // Type cast necessary variables
            $_data = ( array ) array ();
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
                        $data[$objPHPExcel->getActiveSheet()->getCell($cell->getColumn() . "1")->getValue()] [$cell->getRow() - 1] = $cell->getValue();
                    }
                }
            }
            return $data;
        } catch ( Exception $e ) {
            echo "Caught exception: ", $e->getMessage (), "\n";
            return false;
        }
    }
    
    /**
     * bwt_php_excel_lib::writeExcelFile($excelFile, $excelData)
     * Create, Write and Save Excel Spreadsheet from collected data obtained from the variance report
     *
     * @param $excelFile, $excelData            
     */
    public function write_excel_file($excelFile, $excelData, $columns)
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