<?php

trait MAX_Selenium_Library {

    /**
     * MAX_Selenium_Library::takeScreenshot($_session)
     * This is a function description for a selenium test function
     *
     * @param object: $_session            
     */
    private function takeScreenshot($_session, $_filename)
    {
        $_img = $_session->screenshot();
        $_data = base64_decode($_img);
        $_file = dirname(__FILE__) . $this->_scrdir . self::DS . date("Y-m-d_His") . $_filename . "png";
        $_success = file_put_contents($_file, $_data);
        if ($_success) {
            return $_file;
        } else {
            return FALSE;
        }
    }

    /**
     * MAX_Selenium_Library::assertElementPresent($_using, $_value)
     * This is a function description for a selenium test function
     *
     * @param string: $_using            
     * @param string: $_value            
     */
    private function assertElementPresent($_using, $_value)
    {
        $e = $this->_session->element($_using, $_value);
        $this->assertEquals(count($e), 1);
    }

    /**
     * MAX_Selenium_Library::getSelectedOptionValue($_using, $_value)
     * This is a function description for a selenium test function
     *
     * @param string: $_using            
     * @param string: $_value            
     */
    private function getSelectedOptionValue($_using, $_value)
    {
        try {
            $_result = FALSE;
            $_cnt = count($this->_session->elements($_using, $_value));
            for ($x = 1; $x <= $_cnt; $x ++) {
                $_selected = $this->_session->element($_using, $_value . "[$x]")->attribute("selected");
                if ($_selected) {
                    $_result = $this->_session->element($_using, $_value . "[$x]")->attribute("value");
                    break;
                }
            }
        } catch (Exception $e) {
            $_result = FALSE;
        }
        return ($_result);
    }
}