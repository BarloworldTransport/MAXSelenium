<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes

// : End

/**
 * MAX_API_Get.php
 *
 * @package MAX_API_Get
 * @author Clinton Wright <cwright@bwtrans.com>
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
class MAX_API_Get
{
    
    // : Constants
    const INI_FILE = "api_data.ini";
    const LIVE_URL = "https://login.max.bwtsgroup.com";
    const TEST_URL = "http://max.mobilize.biz";
    const API_URL = "/api_request/Data/get?objectRegistry=";
    const DS = DIRECTORY_SEPARATOR;
    // : End - Constants
    
    // : Variables
    
    // Define array containing all valid objectRegistry entries that can be used to get data using the MAX Get API
    protected $_data = array();
    protected $_sqlQueryString;
    protected $_xmlResponseString;
    protected $_htmlDataString;
    protected $_apiObject;
    protected $_apiFilter;
    protected $_maxurl;
    protected $_apiuserpwd;
    protected $_errors;
    protected $_maxObjects = array(
        "ObjectRegistry",
        "Condition",
        "Person",
        "Corporate",
        "Group",
        "PermissionUser",
        "PermissionRole",
        "Group_Role_Link",
        "MFile",
        "GroupPermissionTemplate",
        "ObjectRegistryField",
        "Message",
        "MessagePermissionUser",
        "DataView",
        "ItemListDataView",
        "Currency",
        "DataViewField",
        "Report",
        "Tab",
        "TabDataView",
        "Setting",
        "StyleSetting",
        "ObjectAttachment",
        "Calendar",
        "CalendarEvent",
        "Process",
        "DocumentCategory",
        "DocumentTemplate",
        "ProcessStep",
        "Layout",
        "GraphType",
        "GraphDataView",
        "EventDataView",
        "udo_Process_Graph",
        "AlertDataView",
        "udo_Model",
        "udo_Make",
        "udo_Location",
        "udo_Continent",
        "udo_Point_Type",
        "udo_Country",
        "udo_Province",
        "udo_Suburb",
        "udo_City",
        "udo_Truck",
        "udo_Refuel",
        "udo_Point_Point_Type",
        "udo_Point",
        "udo_Driver",
        "ObjectNote",
        "udo_ProductCategory",
        "udo_PaymentTerms",
        "udo_PalletType",
        "udo_ContactType",
        "udo_AccountManager",
        "udo_Customer",
        "udo_Proposal",
        "udo_RateType",
        "udo_Quote",
        "udo_QuoteRate",
        "udo_Trip",
        "udo_ChepDepot",
        "udo_ManlineDepot",
        "udo_ProductType",
        "udo_ProductDetail",
        "udo_Cargo",
        "udo_Assistant",
        "udo_Trailer",
        "udo_TrailerMake",
        "udo_TrailerModel",
        "udo_TripLegCargo",
        "udo_TripLeg",
        "udo_CustomerType",
        "udo_CustomerAdminDetails",
        "udo_CustomerContacts",
        "udo_IndustryClass",
        "udo_AccountStatus",
        "udo_PaymentMethod",
        "udo_CustomerCustomerType",
        "udo_CustomerProcedures",
        "udo_OffloadingCustomers",
        "udo_CustomerLocations",
        "udo_Rates",
        "udo_Timeframe",
        "udo_DebriefGrid",
        "udo_DebriefGridRow",
        "udo_TripException",
        "udo_Debrief",
        "udo_DebriefTripExceptionLink",
        "udo_FuelException",
        "udo_TimeframeFuelExceptionLink",
        "udo_LocalOrders",
        "udo_LocalAllocation",
        "udo_FleetType",
        "udo_Fleet",
        "udo_FleetTruckLink",
        "udo_DriverActivity",
        "udo_Route",
        "udo_Envelope",
        "udo_RefuelOrderNumber",
        "ImageGroup",
        "udo_Position",
        "ObjectRegistryDescriptiveField",
        "udo_ManualPosition",
        "ReportGroup",
        "udo_RouteCache",
        "udo_RouteCacheSummary",
        "ObjectLog",
        "ObjectInstanceLog",
        "VersionControlItem",
        "udo_LocsTruck",
        "ObjectCrudAction",
        "ObjectCrudActionProcess",
        "ObjectCrudActionAlert",
        "ObjectCrudActionError",
        "MDateTime",
        "udo_RateAdjustment",
        "udo_FuelRoute",
        "ObjectDataReference",
        "DataViewMathColumn",
        "ObjectCrudActionAlarm",
        "Collection",
        "CollectionMembers",
        "udo_PositionDestination",
        "udo_LocalCharges",
        "UniqueConstraint",
        "UniqueConstraintField",
        "udo_Notes",
        "udo_FuelPrice",
        "PreferenceDescription",
        "Preference",
        "ObjectActionLog",
        "ExpressionEvaluator",
        "StartupProcess",
        "StartupProcessUser",
        "udo_PreferenceDescription",
        "ReleaseNote",
        "Sequence",
        "DetailDataView",
        "DetailDataViewField",
        "udo_Plantation",
        "udo_Depot",
        "udo_Mill",
        "DateRangeValue",
        "udo_TollClass",
        "udo_TruckDescription",
        "udo_Loader",
        "udo_LoaderRate",
        "udo_LoadingCharges",
        "udo_RatesAdjustment",
        "RateDateRangeValue",
        "udo_LoaderRateAdjustment",
        "LoaderRateDateRangeValue",
        "udo_TollGate",
        "udo_RouteTollGate_link",
        "udo_Toll",
        "udo_EpodImageDefinitionsExclude",
        "udo_FandVContract",
        "udo_FandVContractTruck_link",
        "DataViewParameter",
        "DataViewParameterDate",
        "DataViewParameterBoolean",
        "DataViewParameterSelect",
        "udo_HollowAdhoc",
        "udo_TruckBudget",
        "udo_FleetBudgetTotal",
        "udo_FleetBudgetFactor",
        "ObjectFieldEnum",
        "Queue",
        "QueueEntry",
        "QueueEntryClia",
        "QueueEntryProcess",
        "QueueEntryEmail",
        "QueueEntrySMS",
        "QueueEntrySearch",
        "QueueEntryOcr",
        "ImageItemOcr",
        "DataViewParameterDateTime",
        "CalendarEventRecurring",
        "ResultSet",
        "udo_DebriefIncompleteDocumentationReason",
        "udo_DieselPrice",
        "PaymentLog",
        "Esign",
        "EsignEntry",
        "SupportIssue",
        "DataTableDataView",
        "udo_CurrencyExchangeRate",
        "ObjectFieldNumber",
        "ObjectFieldDate",
        "ObjectFieldDateTime",
        "ObjectFieldForeignKey",
        "ObjectFieldForeignKeyFile",
        "ObjectFieldMonetary",
        "ObjectFieldSet",
        "ObjectFieldText",
        "Order",
        "udo_Zone",
        "udo_ZoneCity_link",
        "udo_DummyOrder",
        "Correspondence",
        "Batch",
        "Image",
        "OcrImageNote",
        "udo_CargoBatchUpdate",
        "udo_BusinessUnit",
        "udo_CustomerBusinessUnit_link",
        "ProcessTracker",
        "AbstractInstance",
        "udo_OffloadingCustomersBusinessUnit_link",
        "udo_CustomerLocationsBusinessUnit_link",
        "DataViewParameterMultipleSelect",
        "udo_FandVContractRoute_link",
        "ObjectFieldLargeText",
        "ObjectFieldCalculated",
        "ObjectFieldBoolean",
        "udo_SubcontractorType",
        "udo_Subcontractor",
        "udo_SubcontractorFleet_link",
        "udo_SubcontractorContact",
        "QueueEntrySyspro",
        "VersionControl",
        "ImageDefinition",
        "ItemListMore",
        "udo_ImportedTrip",
        "QueueEntryImportedTrip",
        "udo_UnassignedCargo",
        "QueueEntryOfflineTrip",
        "Process_role_link",
        "ReleaseLog",
        "MenuItem",
        "ProcessSubscription",
        "PersistedResultSet",
        "EventFrequency",
        "udo_DeletedTrip",
        "udo_TripProject"
    );
    // : End
    
    // : Getters
    /**
     * MAX_API_Get::getData()
     * Get data returned from runApiQuery
     */
    public function getData()
    {
        if ($this->_data) {
            if (is_array($this->_data)) {
                return $this->_data;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
    
    /**
     * MAX_API_Get::getLastStatus()
     * Get status reponse given in XML file returned in result
     */
    public function getLastStatus()
    {
        // : Some code goes here
    }

    /**
     * MAX_API_Get::getObjects()
     * Get list of available objectRegistry objects that can be used to get data
     */
    public function getObjects() {
        try {
            $_result = implode("," . PHP_EOL ,$this->_maxObjects);
        } catch (Exception $e) {
            return FALSE;
        }
        return $_result;
    }
    
    // : Setters
    /**
     * MAX_API_Get::clearResults()
     * Reset data and query
     */
    public function clearResults() {
        $this->_data = array();
        $this->_sqlQueryString = "";
        $this->_xmlResponseString = "";
        $this->_htmlDataString = "";
        $this->_apiObject = "";
        $this->_apiFilter = "";
    }
    
    /**
     * MAX_API_Get::setFilter()
     * Set filter string for the API query
     */
    public function setFilter($_filterStr) {
        $this->_apiFilter = $_filterStr;
    }
    
    /**
     * MAX_API_Get::setObject()
     * Set the objectRegistry object for the API query
     */
    public function setObject($_objectStr) {
        $_findMatch = preg_grep("/^$_objectStr$/i", $this->_maxObjects);
        if ($_findMatch) {
            $this->_apiObject = $_objectStr;
        } else {
            return FALSE;
        }
    }
    
    // : Public Functions
    /**
     * MAX_API_Get::runApiQuery()
     * Run the API query
     */
    public function runApiQuery() {
        if ($this->_apiObject && $this->_apiFilter) {
            
            $_result = $this->splitResultIntoDataArray($this->maxApiGetData());

            if ($_result) {
                $this->_sqlQueryString = $_result['sql'];
                $this->_xmlResponseString = $_result['xml'];
                $this->_htmlDataString = $_result['html'];
                
                $this->_data = $this->extractDataFromHTML($this->_htmlDataString);
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
    
    // : End - Public Functions
    
    // : Magic
    
    /**
     * MAX_API_Get::__construct()
     * Class constructor
     *         
     */
    public function __construct($_mode)
    {
        try {
            
            $ini = dirname ( realpath ( __FILE__ ) ) . self::DS . "ini" . self::DS . self::INI_FILE;
            
            if (is_file ( $ini ) === FALSE) {
                echo "No " . self::INI_FILE . " file found. Please create it and populate it with the following data: username=x@y.com, password=`your password`, your name shown on MAX the welcome page welcome=`Joe Soap` and mode=`test` or `live`" . PHP_EOL;
                return FALSE;
            }
            $data = parse_ini_file ( $ini );
            if ((array_key_exists ( "apiuserpwd", $data ) && $data ["apiuserpwd"])) {
                $this->_apiuserpwd = $data ["apiuserpwd"];

            } else {
                echo "The correct data is not present in " . self::INI_FILE . ". Please confirm. Field is apiuserpwd" . PHP_EOL;
                return FALSE;
            }
            
            switch ($_mode) {
                case "live" :
                    $this->_maxurl = self::LIVE_URL;
                    break;
                default :
                    $this->_maxurl = self::TEST_URL;
            }
            
        } catch (Exception $e) {
            return FALSE;
        }
        // If reaches here then code passed
        return TRUE;
    }
    
    // : End
    
    // : Private Functions
    /**
     * MAX_API_Get::maxApiGetData($_url)
     * Get data from MAX using using MAX API HTTP GET request
     */
    public function maxApiGetData()
    {
        $_result = array();

        if ($this->_apiFilter && $this->_apiObject && $this->_maxurl && $this->_apiuserpwd) {
        try {
            // Build url string to use to run the API request
            $_url = ($this->_maxurl . self::API_URL . urlencode($this->_apiObject) . "&filter=" . urlencode($this->_apiFilter));
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $_url);
            curl_setopt($ch, CURLOPT_USERPWD, $this->_apiuserpwd);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $output = curl_exec($ch);
            
            curl_close($ch);
        } catch (Exception $e) {
            return FALSE;
        }
            return $output;
        } else {
            return FALSE;
        }
    }

    /**
     * MAX_API_Get::splitResultIntoDataArray()
     * Clean HTML string and extract data into an array
     */
    private function splitResultIntoDataArray($_htmlResponse)
    {
        
        // : Prepare variables
        $_httpUrlData = explode("\n", $_htmlResponse);
        $xmlStartLine = (int) 0;
        $xmlEndLine = (int) 0;
        $sqlQueryStart = (int) 0;
        $sqlSelect = (int) 0;
        $sqlWhere = (int) 0;
        $sqlLimit = (int) 0;
        $xmlDef = (int) 0;
        $_xmlData = (array) array();
        $_htmlData = (array) array();
        $_sqlData = (array) array();
        $_result = (array) array();
        // : End
        
        // : Detect line numbers where each section of data is situated
        foreach ($_httpUrlData as $_key => $_value) {
            if (strpos($_value, '<response>') !== FALSE) {
                $xmlStartLine = $_key;
            } else 
                if (strpos($_value, '</response>') !== FALSE) {
                    $xmlEndLine = $_key;
                } else 
                    if (strpos($_value, 'SELECT') !== FALSE) {
                        $sqlSelect = $_key;
                    } else 
                        if (strpos($_value, 'WHERE') !== FALSE) {
                            $sqlWhere = $_key;
                        } else 
                            if (strpos($_value, 'LIMIT') !== FALSE) {
                                $sqlLimit = $_key;
                            } else 
                                if (strpos($_value, '<?xml version="1.0" encoding="UTF-8"?>') !== FALSE) {
                                    $xmlDef = $_key;
                                }
        }
        // : End
        
        // : Construct the SQL Query into an array
        if ($sqlLimit === 0 && $sqlWhere && $sqlLimit) {
            
            for ($x = $sqlLimit; $x <= $sqlLimit; $x ++) {
                $_sqlData[] = $_httpUrlData[$x];
            }
        }
        // : End
        
        // : Construct the HTML Data into an array
        if ($xmlDef && $sqlWhere && $sqlLimit) {
            
            $_startX = $sqlLimit + 2;
            $_endX = $xmlDef - 2;
            for ($x = $_startX; $x <= $_endX; $x ++) {
                
                $_htmlData[] = $_httpUrlData[$x];
            }
        }
        // : End
        
        // : Construct the XML Data into an array
        if ($xmlEndLine != FALSE && $xmlStartLine != FALSE) {
            for ($x = $xmlStartLine; $x <= $xmlEndLine; $x ++) {
                $_xmlData[] = $_httpUrlData[$x];
            }
        }
        // : End
        
        $_result["sql"] = $_sqlData;
        $_result["html"] = $_htmlData;
        $_result["xml"] = $_xmlData;
        
        return $_result;
    }

    /**
     * MAX_API_Get::getDataFromHTML()
     * Clean HTML string and extract data into an array
     */
    private function extractDataFromHTML($_html)
    {
        $_result = (array) array();
        
        foreach ($_html as $_index => $_value) {
            
            // Set variable string values to empty for each loop
            $_htmlKey = "";
            $_htmlValue = "";
            
            // Seperate key from value
            
            // Split is working properly
            $_split = preg_split("/:\s/", $_html[$_index]);
            
            if (count($_split) > 1) {
                
                // Clean up spaces in the value
                preg_match("/([A-Za-z0-9].*$)/", $_split[1], $_cleanStr);
                
                if (count($_cleanStr) > 1) {
                    // Successful find which cleans out the spaces from the value
                    $_htmlValue = $_cleanStr[1];
                } else 
                    if (count($_cleanStr)) {
                        // No spaces found return value
                        $_htmlValue = $_cleanStr[0];
                    }
                
                preg_match("/\s([a-zA-Z0-9].*$)/", $_split[0], $_cleanStr);
                
                if (count($_cleanStr) > 1) {
                    // Spaces found and spaceless values returned
                    $_htmlKey = $_cleanStr[1];
                }
            }
            
            if (($_htmlKey && $_htmlValue) || ($_htmlKey && ! $_htmlValue)) {
                $_result[$_htmlKey] = $_htmlValue;
            }
        }
        
        if ($_result) {
            return $_result;
        } else {
            return FALSE;
        }
    }
    // : End - Private Functions
}

