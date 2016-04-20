<?php
// Error reporting
error_reporting(E_ALL);

// : Includes
require_once dirname(__FILE__) . '/get_users_without_bu_groups.php';
// : End

/**
 * get_users_bu_from_list.php
 *
 * @package get_users_bu_from_list
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
class get_users_bu_from_list
{

    const DS = DIRECTORY_SEPARATOR;

    const DELIMITER = ',';

    const ENCLOSURE = '"';

    const CSV_LIMIT = 0;
    
    // : Variables
    protected $_fileName;

    protected $_errors;

    protected $_data = array();

    protected $_nomatch = array();

    protected $_bu = array();

    protected $_bugroups = array(
        "bwts_all" => array(
            0,
            1,
            2,
            3,
            4,
            5,
            6
        ),
        "bwts" => array(
            2,
            5,
            6
        ),
        "manline" => array(
            0,
            1,
            3,
            6
        ),
        "mega" => array(
            3
        ),
        "freight" => array(
            0
        ),
        "dedicated" => array(
            2
        ),
        "energy" => array(
            1
        ),
        "specialised" => array(
            6
        ),
        "t24" => array(
            4
        )
    );
    
    // : Public functions
    
    /**
     * get_users_bu_from_list::exportData()
     *
     * @param array: $this->_data            
     */
    public function exportData($_file)
    {
        // : Customly build the data array into an array that can be exported to a csv file
        try {
            
            $_data = (array) array();
            foreach ($this->_data as $key => $value) {
                $_count = count($_data);
                foreach ($value as $key2 => $value2) {
                    if ($key2 != "bugroup") {
                        $_data[$value["firstnames"] . " " . $value["surname"]][$key2] = $value2;
                    }
                }
            }
            if ($_data) {
                if ($this->exportToCSV($_file, $_data) != FALSE) {
                    return TRUE;
                } else {
                    return FALSE;
                }
            }
        } catch (Exception $e) {
            return FALSE;
        }
        // : End
    }

    /**
     * get_users_bu_from_list::exportNoMatch()
     *
     * @param array: $this->_data            
     */
    public function exportNoMatch($_file)
    {
        try {
            if ($this->exportToCSV($_file, $this->_nomatch) != FALSE) {
                return TRUE;
            } else {
                return FALSE;
            }
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * get_users_bu_from_list::stringHypenFix($_value)
     * Replace long hyphens in string to short hyphens as part of a problem
     * created when importing data from spreadsheets
     *
     * @param string: $_value            
     * @param string: $_result            
     */
    public function stringHypenFix($_value)
    {
        $_result = preg_replace("/–/", "-", $_value);
        return $_result;
    }
    
    // : Accessors
    
    /**
     * get_users_bu_from_list::getError()
     *
     * @param string: $this->_errors;            
     */
    public function getError()
    {
        if (! empty($this->_errors)) {
            return $this->_errors;
        } else {
            return FALSE;
        }
    }

    /**
     * get_users_bu_from_list::getBUList()
     *
     * @param array: $this->_bu            
     */
    public function getBUList()
    {
        return $this->_bu;
    }

    /**
     * get_users_bu_from_list::getBUGroupsList()
     *
     * @param array: $this->_bugroups            
     */
    public function getBUGroupsList()
    {
        return $this->_bugroups;
    }

    /**
     * get_users_bu_from_list::getNoMatch()
     *
     * @param array: $this->_nomatch            
     */
    public function getNoMatch()
    {
        return $this->_nomatch;
    }

    /**
     * get_users_bu_from_list::getData()
     *
     * @param array: $this->_data            
     */
    public function getData()
    {
        return $this->_data;
    }
    // : End
    
    // : Setters
    // : End
    
    // : Magic
    /**
     * get_users_bu_from_list::__construct()
     * Class constructor
     */
    public function __construct($_file1, $_file2)
    {
        if (file_exists($_file1) && file_exists($_file2)) {
            try {
                // Run query and get all users that do not belong to business unit groups and export results to a CSV file
                $_maxusers = new get_users_without_bu_groups($_file1);
                
                // Get Business Units that were pulled from the MAX Live DB
                $_tmpGroups = $_maxusers->getBUGroups();
                foreach ($_tmpGroups as $key => $value) {
                    if ($value) {
                        $this->_bu[] = $value["name"];
                    }
                }
                
                // Check if the above exported CSV file exists and import the data
                $_csvdata1 = $this->ImportFromCSV($_file1);
                
                // Import data from CSV file containing list of employees at BWT
                $_csvdata2 = $this->ImportFromCSV($_file2);
                
                // Destroy object_maxusers
                unset($_maxusers);
                
                $_budata = (array) array();
                $_namesdata = (array) array(
                    "firstName1" => "",
                    "firstName2" => "",
                    "surname1",
                    "surname2"
                );
                $_cond1 = (bool) FALSE;
                $_cond2 = (bool) FALSE;
                
                // : If both csv files imported and contain data then process the data
                if ((isset($_csvdata1)) && (! empty($_csvdata1)) && (! empty($_csvdata2)) && (isset($_csvdata2))) {
                    foreach ($_csvdata1 as $key1 => $value1) {
                        
                        // Reset condition 2
                        $_cond2 = FALSE;
                        
                        foreach ($_csvdata2 as $key2 => $value2) {
                            // : Store all data into variables that need to be processed
                            
                            // Reset condition 1
                            $_cond1 = FALSE;
                            
                            $_namesdata["firstName1"] = strtolower($value1["firstnames"]);
                            $_namesdata["firstName2"] = strtolower($value2["full names"]);
                            $_namesdata["firstName3"] = strtolower($value2["known as name"]);
                            $_namesdata["surname1"] = strtolower($value1["surname"]);
                            $_namesdata["surname2"] = strtolower($value2["surname"]);
                            $_budata[0] = strtolower($value2["vip description"]);
                            $_budata[1] = strtolower($value2["cost centre description"]);
                            $_budata[2] = strtolower($value2["business unit description"]);
                            // : End
                            
                            // : Clean up variables
                            $_tempArr = preg_split("/\s/", $_namesdata["firstName2"]);
                            if (! $_tempArr) {
                                if (! $_tempArr[0]) {
                                    $_namesdata["firstName2"] = $_tempArr[0];
                                }
                            }
                            
                            $_tempArr = preg_split("/\s/", $_namesdata["firstName3"]);
                            if (! $_tempArr) {
                                if (! $_tempArr[0]) {
                                    $_namesdata["firstName3"] = $_tempArr[0];
                                }
                            }
                            
                            $_tempArr = preg_split("/\s/", $_namesdata["surname2"]);
                            if (! $_tempArr) {
                                if (! $_tempArr[0] && count($_tempArr > 1)) {
                                    $_namesdata["surname2"] = $_tempArr[1];
                                } else 
                                    if ($_tempArr[0] && count($_tempArr < 2) && count($_tempArr != 0)) {
                                        $_namesdata["surname2"] = $_tempArr[0];
                                    }
                            }
                            // : End
                            
                            // : Check if name matches
                            preg_match("/^" . $_namesdata["firstName1"] . ".*$/", $_namesdata["firstName2"], $_matches);
                            preg_match("/^" . $_namesdata["firstName1"] . ".*$/", $_namesdata["firstName3"], $_matches2);
                            preg_match("/^" . $_namesdata["surname1"] . ".*$/", $_namesdata["surname2"], $_matches3);
                            
                            if ((((! $_matches && $_matches2) || ($_matches && ! $_matches2) || ($_matches && $_matches2)) && $_matches3)) {
                                $_cond1 = TRUE;
                                $_cond2 = TRUE;
                                $this->_data[$value1["personalgroupid"]] = $value1;
                            }
                            // : End
                            
                            // : If name match is found then determine which Business Units need to be added for the user
                            if ($_cond1) {
                                if ($_budata) {
                                    if ($_budata[0] && $_budata[1]) {
                                        preg_match("/bwts|dedicated|energy|t24|mega|kumkani|freight/", $_budata[0], $_matches);
                                        if ($_matches) {
                                            switch ($_matches[0]) {
                                                case "bwts":
                                                    preg_match("/functional|management|cranes|specialised/", $_budata[0], $_submatches);
                                                    if ($_submatches) {
                                                        switch ($_submatches) {
                                                            case "functional":
                                                                preg_match("/bwts|dedicated|energy|group|t24|mega/", $_budata[1], $_subsubmatches);
                                                                if ($_subsubmatches) {
                                                                    switch ($_subsubmatches) {
                                                                        case "bwts":
                                                                        case "group":
                                                                            $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["bwts_all"];
                                                                            break;
                                                                        case "freight":
                                                                            $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["freight"];
                                                                            break;
                                                                        case "energy":
                                                                            $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["energy"];
                                                                            break;
                                                                        case "dedicated":
                                                                            $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["dedicated"];
                                                                            break;
                                                                        case "mega":
                                                                            $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["mega"];
                                                                            break;
                                                                        case "t24":
                                                                            $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["t24"];
                                                                            break;
                                                                        default:
                                                                            $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["bwts_all"];
                                                                            break;
                                                                    }
                                                                }
                                                                break;
                                                            case "management":
                                                                $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["bwts_all"];
                                                                break;
                                                            case "cranes":
                                                            case "specialised":
                                                                $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["specialised"];
                                                                break;
                                                            default:
                                                                $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["bwts_all"];
                                                                break;
                                                        }
                                                    }
                                                    break;
                                                case "dedicated":
                                                    $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["dedicated"];
                                                    break;
                                                case "energy":
                                                    $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["energy"];
                                                    break;
                                                case "t24":
                                                    $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["t24"];
                                                    break;
                                                case "mega":
                                                    $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["mega"];
                                                    break;
                                                case "kumkani":
                                                    $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["specialised"];
                                                    break;
                                                case "freight":
                                                    $this->_data[$value1["personalgroupid"]]["bugroups"] = $this->_bugroups["freight"];
                                                    break;
                                                default:
                                                    $this->_data[$value1["personalgroupid"]]["bugroups"] = 0;
                                                    break;
                                            }
                                        }
                                    }
                                }
                            }
                            // : End
                        }
                        if (! $_cond2) {
                            $this->_nomatch[$value1["personalgroupid"]] = $value1;
                        }
                    }
                }
                
                unset($_db);
                // : End
            } catch (Exception $e) {
                $this->_errors[] = $e->getMessage();
                unset($_mysqlQueryMAX);
                unset($_db);
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * get_users_bu_from_list::__destruct()
     * Class destructor
     * Allow for garbage collection
     */
    public function __destruct()
    {
        unset($this);
    }
    // : End
    
    // : Private Functions
    
    /**
     * get_users_bu_from_list::ImportFromCSV($csvFile)
     * From supplied csv file save data into multidimensional array
     *
     * @param string: $csvFile            
     * @param array: $_result            
     */
    private function ImportFromCSV($csvFile)
    {
        try {
            $_data = (array) array();
            $_header = NULL;
            if (file_exists($csvFile)) {
                if (($_handle = fopen($csvFile, 'r')) !== FALSE) {
                    while (($_row = fgetcsv($_handle, self::CSV_LIMIT, self::DELIMITER, self::ENCLOSURE)) !== FALSE) {
                        if (! $_header) {
                            foreach ($_row as $_value) {
                                $_header[] = strtolower($_value);
                            }
                        } else {
                            $_data[] = array_combine($_header, $_row);
                        }
                    }
                    fclose($_handle);
                    
                    if (count($_data) != 0) {
                        
                        foreach ($_data as $_key => $_value) {
                            foreach ($_value as $_keyA => $_valueA) {
                                $_data[$_key][$_keyA] = $this->stringHypenFix($_valueA);
                            }
                        }
                        
                        return $_data;
                    } else {
                        $_msg = preg_replace("@%s@", $csvFile, self::FILE_EMPTY);
                        throw new Exception($_msg);
                    }
                } else {
                    $_msg = preg_replace("@%s@", $csvFile, self::COULD_NOT_OPEN_FILE);
                    throw new Exception($_msg);
                }
            } else {
                $_msg = preg_replace("@%s@", $csvFile, self::FILE_NOT_FOUND);
                throw new Exception($_msg);
            }
        } catch (Exception $e) {
            $this->_functionError = $e->getMessage();
            return FALSE;
        }
    }

    /**
     * get_users_bu_from_list::exportToCSV($csvFile, $arr)
     * From supplied csv file save data into multidimensional array
     *
     * @param string: $csvFile            
     * @param array: $_arr            
     */
    private function exportToCSV($csvFile, $_arr)
    {
        try {
            $_data = (array) array();
            if (file_exists(dirname($csvFile))) {
                $_handle = fopen($csvFile, 'w');
                foreach ($_arr as $key => $value) {
                    fputcsv($_handle, $value);
                }
                fclose($_handle);
            } else {
                $_msg = preg_replace("@%s@", $csvFile, self::DIR_NOT_FOUND);
                throw new Exception($_msg);
            }
        } catch (Exception $e) {
            return FALSE;
        }
    }
    // : End
}