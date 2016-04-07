<?php
// Set error reporting level for this script
error_reporting(E_ALL);

// : Includes
// : End

/**
 * MAX_FandV.php
 *
 * @package MAX_FandV
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
class maxRoutesRates
{
    
    // : Constants
    const DS = DIRECTORY_SEPARATOR;

    const LIVE_URL = "https://login.max.bwtsgroup.com";

    const TEST_URL = "http://max.mobilize.biz";

    const NOT_CORRECT_ACTION = "Could not verify the action was correct when updating the action update for: %s";
    // : End - Constants
    
    // : Variables
    public $_tmp;

    protected $_maxurl;

    protected $_autoLibObj;

    protected $_errors = array();
    // : End
    
    // : Magic Functions
    /**
     * MAX_FandV::__construct
     * Class constructor
     */
    public function __construct(&$_autoObj, $_maxurl)
    {
        if (is_object($_autoObj) && is_string($_maxurl)) {
            
            // Save referenced automation library object containing session and phpunit objects
            $this->_autoLibObj = $_autoObj;
            $this->_maxurl = $_maxurl;
        }
    }

    /**
     * MAX_FandV::__destruct
     * Class destructor
     */
    public function __destruct()
    {
        unset($this);
    }
    
    // : End - Magic Functions
    
    // : Public Functions
    /**
     * MAX_FandV::maxLogin
     * Log into MAX
     */
    public function getLastError()
    {
        $_result = "";
        end($this->_errors);
        $_result = $this->_errors[key($this->_errors)];
        reset($this->_errors);
        return $_result;
    }
    
    /**
     * MAX_FandV::getRouteName
     * Concatenate location from and location to city names to build
     * a MAX formatted version of the route name
     */
    public function getRouteName($_locationFrom, $_locationTo) {
		
        // Concatenate string for route name
        $_routeName = $_locationFrom . " TO " . $_locationTo;
        return $_routeName;
    }

    /**
     * MAX_FandV::maxRouteCreate
     * Log into MAX
     */
    public function maxRouteCreate($_bu, $_locationFrom, $_locationTo, $_expectedKms, $_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
        try {
            // : Log into MAX
            
            switch (intval($_version)) {
                case 2:
                    {
                        try {
                            $_routeName = getRouteName($_locationFrom, $_locationTo);
                            
                            $session = $this->_autoLibObj->_sessionObj;
                            $this->_autoLibObj->_sessionObj->open($this->_maxurl . automationLibrary::URL_LOCATION_ROUTE);
                            
                            // Wait for element text Route Ward
                            $e = $this->_autoLibObj->_wObj->until(function ($session)
                            {
                                return $session->element("xpath", "//*[contains(text(),'Route Ward')]");
                            });
                            
                            $this->_autoLibObj->assertElementPresent("css selector", "div.toolbar-cell-create");
                            $this->_autoLibObj->_sessionObj->element("css selector", "div.toolbar-cell-create")->click();
                            
                            $e = $this->_autoLibObj->_wObj->until(function ($session)
                            {
                                return $session->element("xpath", "//*[contains(text(),'Capture the Business Unit')]");
                            });
                            
                            $this->_autoLibObj->assertElementPresent("xpath", "//*[@id='udo_Rates[0][businessUnit_id]']");
                            $this->_autoLibObj->assertElementPresent("css selector", "input[name=save][type=submit]");
                            
                            try {
                                $this->_autoLibObj->_sessionObj->element("xpath", "//*[@id='udo_Rates[0][businessUnit_id]']/option[text()='$_bu']")->click();
                            } catch (PHPWebDriver_NoSuchElementWebDriverError $e) {
                                throw new Exception("ERROR: Could not find the location from on the create route page" . PHP_EOL . $e->getMessage());
                            }
                            
                            $this->_autoLibObj->_sessionObj->element("css selector", "input[name=save][type=submit]")->click();
                            
                            // Wait for element Page Heading
                            $e = $this->_autoLibObj->_wObj->until(function ($session)
                            {
                                return $session->element("xpath", "//*[@name='udo_Route[0][locationFrom_id]']");
                            });
                            
                            switch ($_bu) {
                                case "Timber 24":
                                    {
                                        // : Assert all elements on page
                                        $this->assertElementPresent("xpath", "//*[@name='udo_Route[0][locationTo_id]']");
                                        $this->assertElementPresent("xpath", "//*[@name='udo_Route[0][expectedKms]']");
                                        $this->assertElementPresent("css selector", "input[type=submit][name=save]");
                                        // : End
                                        
                                        try {
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][locationFrom_id]']/option[text()='" . $_locationFrom . "']")->click();
                                        } catch (PHPWebDriver_NoSuchElementWebDriverError $e) {
                                            throw new Exception("ERROR: Could not find the location from on the create route page" . PHP_EOL . $e->getMessage());
                                        }
                                        
                                        $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][locationTo_id]']/option[text()='" . $_locationTo . "']")->click();
                                        if ($_dataset["expected kms"]["value"] != FALSE) {
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][expectedKms]']")->sendKeys($_expectedKms);
                                        }
                                        break;
                                    }
                                
                                default:
                                    {
                                        // : Assert all elements on page
                                        $this->_autoLibObj->assertElementPresent("xpath", "//*[@name='udo_Route[0][locationTo_id]']");
                                        $this->_autoLibObj->assertElementPresent("xpath", "//*[@name='udo_Route[0][expectedKms]']");
                                        $this->_autoLibObj->assertElementPresent("xpath", "//*[@name='udo_Route[0][duration]']");
                                        $this->_autoLibObj->assertElementPresent("css selector", "input[type=submit][name=save]");
                                        // : End
                                        
                                        try {
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][locationFrom_id]']/option[text()='$_locationFrom']")->click();
                                        } catch (PHPWebDriver_NoSuchElementWebDriverError $e) {
                                            throw new Exception("ERROR: Could not find the location from on the create route page" . PHP_EOL . $e->getMessage());
                                        }
                                        
                                        $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][locationTo_id]']/option[text()='$_locationTo']")->click();
                                        if ($_expectedKms != FALSE) {
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][expectedKms]']")->sendKeys($_expectedKms);
                                            // Calculate duration from kms value at 60K/H
                                            $duration = strval(number_format((floatval($_expectedKms) / 80) * 60, 0, "", ""));
                                            $this->_autoLibObj->_sessionObj->element("xpath", "//*[@name='udo_Route[0][duration]']")->sendKeys($duration);
                                        }
                                        break;
                                    }
                            }
                            
                            $this->_session->element("css selector", "input[type=submit][name=save]")->click();
                            
                            // Wait for element text Route Ward
                            $e = $this->_autoLibObj->_wObj->until(function ($session)
                            {
                                return $session->element("xpath", "//*[contains(text(),'Route Ward')]");
                            });
                        } catch (Exception $e) {
                            
                            $this->_autoLib->addErrorRecord($this->_error, $this->_scrDir, $e->getMessage(), $this->lastRecord . ". Object data that failed: Route", "Create Route");
                        }
                        
                        break;
                    }
                case 3:
                    {
                        break;
                    }
            }
        } catch (Exception $e) {
            // Store error message into static array
            $this->_errors[] = $e->getMessage();
            return FALSE;
        }
        return TRUE;
    }
    
    
    /**
     * MAX_FandV::maxCreateRateContribData
     * Add non F&V rate contribution data
     */
    public function maxCreateRateContribData($_customer, $_bu, $_locationFrom, $_locationTo, $_trucktype, $_contrib_data, $_version = automationLibrary::DEFAULT_MAX_VERSION)
    {
		try
		{
			$_status_report = (array) array();
			$_report_template = (array) array(
				"contrib_type" => false,
				"rate_id" => false,
				"process_pass" => false,
				"check_in_db" => false,
				"error" => false,
				"errormsg" => false,
				"record_exists" => false
			);
			if ($this->_autoLibObj->get_db_status() != false)
			{
				if (is_array($_contrib_data) && $_contrib_data)
				{
					
					// : Fetch ids and store into variables
					
					$_objRegId = $this->_autoLibObj->fetchObjectRegistryId(automationLibrary::OBJREG_CUSTOMER);
					$_rateObjRegId = $this->_autoLibObj->fetchObjectRegistryId(automationLibrary::OBJREG_RATE);
					
					$_customerId = $this->_autoLibObj->fetchCustomerId($_customer);
					$_buId = $this->_autoLibObj->fetchBusinessUnitId($_bu);
					$_trucktypeId = $this->_autoLibObj->fetchTruckDescriptionId($_trucktype);
					$_locationFromId = $this->_autoLibObj->fetchLocationId($_locationFrom, automationLibrary::UDO_LOCATION_TYPE_CITY);
					$_locationToId = $this->_autoLibObj->fetchLocationId($_locationTo, automationLibrary::UDO_LOCATION_TYPE_CITY);

					// Default value to define the variable
					$_routeId = 0;
					$_rateId = 0;
				
					if ($_locationFromId && $_locationToId)
					{
						$_routeId = $this->_autoLibObj->fetchRouteId($_locationFromId, $_locationToId);
					}
					// : End
					
					if ($_routeId)
					{
						$_rateId = $this->_autoLibObj->fetchRateId($_customerId, $_routeId, $_buId, $_trucktypeId, $_objRegId);
					}
					
					if ($_rateId)
					{
						switch($_version)
						{
							case 3:
							{
								// Add code here for v3
								break;
							}
							case 2:
							default:
							{
								$x = 0;
								
								foreach ($_contrib_data as $key => $value)
								{
									
									try
									{
										if ($value['value'])
										{
										
										$_status_report[$x]['rate_id'] = $value['value'];
										
										$_utcBeginDate = false;
										$_utcEndDate = false;
										
										$_status_report[$x] = $_report_template;
										
										// Convert each word's first letter to an uppercase char
										$_contribType = ucwords($key);
					
										// Remove whitespaces
										$_contribType = preg_replace("@\s@", '', $_contribType);
										
										$_status_report[$x]['contrib_type'] = $_contribType;
										
										// : Set UTC versions of the times
										if (isset($value['beginDate']))
										{
											if ($value['beginDate'] && is_string($value['beginDate']))
											{
												$_utcBeginDate = automationLibrary::dateTimeAmend($value['beginDate'], 'Y-m-d H:i:s', "-2 Hours");
											}
										}
										
										if (isset($value['endDate']))
										{
											if ($value['endDate'] && is_string($value['endDate']))
											{
												$_utcEndDate = automationLibrary::dateTimeAmend($value['endDate'], 'Y-m-d H:i:s', "-2 Hours");
											}
										}
										// : End
										
										// : Setup extra options to pass to SQL query
										$_extra_options = array();
										
										if ($value['endDate'] && $_utcEndDate)
										{
											$_extra_options['endDate'] = $_utcEndDate;
										}
										
										if ($value['beginDate'] && $_utcBeginDate)
										{
											$_extra_options['beginDate'] = $_utcBeginDate;
										}
										// : End
										
										$_fetchDrvId = $this->_autoLibObj->fetchDateRangeValueId($_rateObjRegId, $_rateId, $_contribType, $_extra_options);
										
										if (intval($_fetchDrvId) == 0)
										{
											$session = $this->_autoLibObj->_sessionObj;
											$_rateurl = preg_replace("@%s@", $_rateId, automationLibrary::URL_RATEVAL);
											$_route_name = $this->getRouteName($_locationFrom, $_locationTo);
											$_page_title = sprintf("Customer %s %s", $_customer, $_route_name);
											$this->_autoLibObj->_sessionObj->open($this->_maxurl . $_rateurl);
                            
											$this->_tmp = $_page_title;
                            
											// Wait for element text Customer [Customer Name] [Route]
											$e = $this->_autoLibObj->_wObj->until(function ($session)
											{
												return $session->element("xpath", "//div[@class='detail-title' and contains(text(), '$this->_tmp')]");
											});
                            
											$this->_autoLibObj->assertElementPresent("xpath", "//*[@id='subtabselector']/select/option[contains(text(),'$_contribType Values')]");
											$this->_autoLibObj->_sessionObj->element("xpath", "//*[@id='subtabselector']/select/option[contains(text(),'$_contribType Values')]")->click();
								
											$this->_autoLibObj->assertElementPresent("css selector", "div#button-create");
											$this->_autoLibObj->_sessionObj->element("css selector", "div#button-create")->click();
								
											// Wait for element text to contain Create Date Range Values on the page
											$e = $this->_autoLibObj->_wObj->until(function ($session)
											{
												return $session->element("xpath", "//*[contains(text(),'Create Date Range Values')]");
											});
								
											$this->_autoLibObj->assertElementPresent("xpath", "//*[contains(text(),'Create Date Range Values')]");
											$this->_autoLibObj->assertElementPresent("id", "DateRangeValue-2_0_0_beginDate-2");
											$this->_autoLibObj->assertElementPresent("id", "DateRangeValue-4_0_0_endDate-4");
											$this->_autoLibObj->assertElementPresent("id", "DateRangeValue-20_0_0_value-20");
											$this->_autoLibObj->assertElementPresent("css selector", "input[name=save][type=submit]");
							
											$this->_autoLibObj->_sessionObj->element("id", "DateRangeValue-2_0_0_beginDate-2")->clear();
											$this->_autoLibObj->_sessionObj->element("id", "DateRangeValue-4_0_0_endDate-4")->clear();
											$this->_autoLibObj->_sessionObj->element("id", "DateRangeValue-20_0_0_value-20")->clear();
								
											$this->_autoLibObj->_sessionObj->element("id", "DateRangeValue-2_0_0_beginDate-2")->sendKeys($value['beginDate']);
											$this->_autoLibObj->_sessionObj->element("id", "DateRangeValue-4_0_0_endDate-4")->sendKeys($value['endDate']);
											$this->_autoLibObj->_sessionObj->element("id", "DateRangeValue-20_0_0_value-20")->sendKeys($value['value']);
								
											$this->_autoLibObj->_sessionObj->element("css selector", "input[name=save][type=submit]")->click();
								
											// Wait for element text Customer [Customer Name] [Route]
											$e = $this->_autoLibObj->_wObj->until(function ($session)
											{
												return $session->element("css selector", "div.detail-title");
											});

											// Check record exists in the DB
																				
											$_fetchDrvId = $this->_autoLibObj->fetchDateRangeValueId($_rateObjRegId, $_rateId, $_contribType, $_extra_options);
										
											if (intval($_fetchDrvId))
											{
												$_status_report[$x]['check_in_db'] = true;
											}
										} else
										{
											$_status_report[$x]['record_exists'] = true;
											$_status_report[$x]['check_in_db'] = true;
										}
										
										// Process pass point
										$_status_report[$x]['process_pass'] = true;
										
									}
									} catch (Exception $e)
									{
										$_status_report[$x]['error'] = true;
										$_status_report[$x]['errormsg'] = $e->getMessage();
									}
									
									$x++;
								}
							}
						}		
					}
				}
			}
			
		} catch (Exception $e)
		{
			$this->_autoLibObj->setReports($_status_report);
			return false;
		}
		
		$this->_autoLibObj->setReports($_status_report);
		return true;
	}
	
    // : End - Public Functions
    
    // : Private Functions
    // : End - Private Functions
}

