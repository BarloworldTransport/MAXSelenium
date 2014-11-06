<?php
/** Class::FileParser
  * @author Feighen Oosterbroek
  * @author feighen@manlinegroup.com
  * @copyright 2010 Manline Group (Pty) Ltd
  * @example First Example Start
  * require_once(BASE.'basefunctions/baseapis/FileParser.php');
  * $fileParser = new FileParser(BASE.'tmp/budget.csv');
  * if ($fileParser->getErrors()) {
  * 	print("<pre style='font-family:verdana;font-size:13'>");
  * 	print_r($fileParser->getErrors());
  * 	print("</pre>");
  * 	return false;
  * }
  * $data = $fileParser->parseFile();
  * print("<pre style='font-family:verdana;font-size:13'>fileData");
  * print_r($data);
  * print("</pre>");
  * @example First Example End
  * @example Second Example Start
  * $fileParser = new FileParser($url);
  * $fileParser->setCurlFile("pooky.csv");
  * if ($fileParser->getErrors()) {
  * 	print("<pre style='font-family:verdana;font-size:13'>");
  * 	print_r($fileParser->getErrors());
  * 	print("</pre>");
  * 	return false;
  * }
  * $data = $fileParser->parseFile();
  * if ($data === false) {
  * 	print("<pre style='font-family:verdana;font-size:13'>");
  * 	print_r($fileParser->getErrors());
  * 	print("</pre>");
  * 	return;
  * }
  * @example End
  * @example with destroy()
  $fileParser = new FileParser(BASE.'basefunctions'.DS.'baseapis'.DS.'cache'.DS.'tmp'.DS.'TableManager.csv');
  if ($fileParser->getErrors()) {
    print("<pre style='font-family:verdana;font-size:13'>");
    print_r($fileParser->getErrors());
    print("</pre>");
    return false;
  }
  $data = $fileParser->parseFile();
  print("<pre style='font-family:verdana;font-size:13'>fileData");
  print_r($data);
  print("</pre>");
  $fileParser->destroy();
  if ($fileParser->getErrors()) {
    print("<pre style='font-family:verdana;font-size:13'>");
    print_r($fileParser->getErrors());
    print("</pre>");
    return false;
  }
  * @example End
*/
class FileParser
{
        //: Constants
        const ARRAY_COMB = "The file %s has rows of different lengths. Row1 is %q in length. Row2 is %w in length";
        const COULDNT_PARSE_FILE = "The file %s could not be parsed at this time";
        const DISALLOWED_EXTENSION = "The file %s has an extension that we cannot, as yet, parse.";
        const FILE_DOESNT_EXIST = "The file %s could not be found";
        const FILE_COULDNT_BE_DELETED = "The file %s could not be deleted from the file system.";
        const FILE_NOT_READABLE = "The file %s could not be read. Probable causes: Incorrect permission or in a path that PHP cannot read";
        const FOPEN_COULD_NOT_OPEN = "The file %s could not be opened";
        const FOPEN_NOT_ALLOWED_TO_OPEN = "The file %s cannot be opened, as fopen cannot open urls";
        const DS = DIRECTORY_SEPARATOR;
        
        //: Variables
        protected $_allowedExtensions = array(
                'csv'=>'CSV files',
                'xml'=>'XML files'
        );
        protected $_cURLFile;
        protected $_cURLTimeout = 1800;
        protected $_dir;
        protected $_errors = array();
        protected $_extension;
        protected $_file;
        protected $_fileName;
        protected $_variableTypes = array(
                0=>'array',
                1=>'bool',
                2=>'float',
                3=>'int',
                4=>'null',
                5=>'numeric',
                6=>'object',
                7=>'resource',
                8=>'scalar',
                9=>'string'
        );
        protected $_urlFopen = false;
        
        //: Public functions
        public function checkFileType()
        {
                $substr = (array)array(
                        0=>substr($this->getFile(), 0, 3),
                        1=>substr($this->getFile(), 0, 4),
                        2=>substr($this->getFile(), 0, 5)
                );
                $phpAllowedFopenFormats = (array)array(
                        0=>'ftp',
                        1=>'ftps',
                        2=>'http',
                        3=>'https'
                );
                return array_intersect($substr, $phpAllowedFopenFormats);
        }
        
        public function checkFopen($fileString = null)
        {
                $file = $fileString ? $fileString : $this->getFile();
                if ((substr($file, 0, 4) === 'http') && (ini_get('allow_url_fopen') === false)) {
                        $this->_errors[] = preg_replace('/%s/', $this->getFile(), self::FOPEN_NOT_ALLOWED_TO_OPEN);
                        return false;
                }
                return true;
        }
        
        public function checkFileIsReadable($fileString = null)
        {
                $file = $fileString ? $fileString : $this->getFile();
                if ((substr($file, 0, 4) != 'http') && (is_readable($file) === false)) {
                        $this->_errors[] = preg_replace('/%s/', $file, self::FILE_NOT_READABLE);
                        return false;
                }
                return true;
        }
        
        /** FileParser::destroy()
          * @return true on success false otherwise
        */
        public function destroy()
        {
                if ($this->checkFileIsReadable($file) === false) {return false;}
                ## all that needs to happen here is for the file to be unlinked
                if ((unlink($this->getFile())) === false) {
                        $this->_errors[] = preg_replace('/%s/', $this->getFile(), self::FILE_COULDNT_BE_DELETED);
                        return false;
                }
                return true;
        }
        
        //: Getters
        public function getAllowedExtensions()
        {
                return $this->_allowedExtensions;
        }
        
        public function getCurlFile()
        {
                return $this->_cURLFile;
        }
        
        public function getCurlTimeout()
        {
                return $this->_cURLTimeout;
        }
        
        public function getDirectory()
        {
                return $this->_dir;
        }
        
        public function getErrors()
        {
                return $this->_errors;
        }
        
        public function getExtension()
        {
                return $this->_extension;
        }
        
        public function getFile()
        {
                return $this->_file;
        }
        
        public function getFileName()
        {
                return $this->_fileName;
        }
        
        public function getUrlFopen()
        {
                return $this->_urlFopen;
        }
        //: End
        
        //: Magic Functions
        /** FileParser::__construct($file)
          * Class Constructor
          * @param string $file full file path to file X
        */
        public function __construct($file)
        {
                if (!$file) {
                        $this->_errors[] = preg_replace('/%s/', $this->getFile(), self::FILE_DOESNT_EXIST);
                        return false;
                }
                $this->setFile($file);
                if (!$this->getFile()) {
                        $this->_errors[] = preg_replace('/%s/', $this->getFile(), self::FILE_DOESNT_EXIST);
                        return false;
                }
                $url = $this->checkFileType();
                $this->setDirectory($this->_parseDirectory($this->getFile()));
                $this->setFileName($this->_parseFileName($this->getFile()));
                if ($url) {
                        if ($this->getCurlFile() === null) {$this->setCurlFile(date('Ym').'.csv');}
                        $this->setUrlFopen(true);
                } else {
                        $this->setExtension($this->_parseExtension($this->getFile()));
                        if ((substr($this->getFile(), 0, 4) !== 'http') && !in_array($this->_extension, array_keys($this->_allowedExtensions))) {
                                $this->_errors[] = preg_replace('/%s/', $this->getFile(), self::DISALLOWED_EXTENSION);
                                return false;
                        }
                }
                return true;
        }
        
        /** FileParser::__destruct()
          * Class destructor
          * removes from memory this class
        */
        public function __destruct()
        {
                unset($this);
        }
        //: End
        
        function parseFile() {
                if ($this->_errors) {return $this->getErrors();}
                if ($this->_urlFopen) {
                        return $this->_parseUrl();
                } else {
                        $name = (string)'_parse'.ucwords($this->_extension).'File';
                        return $this->$name();
                }
        }
        
        //: Setters
        public function setAllowedExtensions(array $data)
        {
                $this->_allowedExtensions = $data;
        }
        
        public function setCurlFile($file)
        {
                $this->_cURLFile = $file;
        }
        
        public function setCurlTimeout($timeout)
        {
                $this->_cURLTimeout = $timeout;
        }
        
        public function setDirectory($dir)
        {
                $this->_dir = $dir;
        }
        
        public function setExtension($extension)
        {
                $this->_extension = $extension;
        }
        
        public function setFile($file)
        {
                $this->_file = $file;
        }
        
        public function setFileName($fileName)
        {
                $this->_fileName = $fileName;
        }
        
        public function setUrlFopen($url)
        {
                $this->_urlFopen = $url;
        }
        //: End
        
        //: Private functions
        /** FileParser::_parseCsvFile()
          * @return array file data
        */
        private function _parseCsvFile($fileString = null)
        {
                $file = $fileString ? $fileString : $this->getFile();
                if ($this->checkFopen($file) === false) {return false;}
                if ($this->checkFileIsReadable($file) === false) {return false;}
                $fp = fopen($file, 'rb');
                if ($fp === null) {
                        $this->_errors[] = preg_replace('/%s/', $file,  SELF::FOPEN_COULD_NOT_OPEN);
                        return false;
                }
                $data = (array)array();
                
                while (($row = fgetcsv($fp, (substr($file, 0, 4) === 'http' ? 25*64*1000 : (filesize($file) ? filesize($file) : 25*64*1000)))) !== false) {
                        $data[] = $row;
                }
                fclose($fp);
                /* Not sure if there is a nice way of testing for column headers 
                I had used a test for column values being the same type 
                Yes I know that if you have a string column heading and string data it will fail */
                $headers = (bool)false;
                foreach ($this->_variableTypes as $key=>$val) {
                        $phpFunc = (string)'is_'.$val;
                        if (($phpFunc($data[1][0]) === true) && ($phpFunc($data[0][0]) === false)) {
                                $headers = true;
                                break;
                        }
                }
                if ($headers) {
                        $headerRow = array_shift($data);
                        foreach ($data as $key=>$val) {
                                $data[$key] = $this->_combineDataRow($headerRow, $val);
                        }
                }
                return $data;
        }
        
        /** FileParser::_parseDirectory($file)
         * @param string $file file name with full path
         * @return string directory path on success false otherwise
        */
        private function _parseDirectory($file)
        {
                $split = preg_split("/\\".self::DS."/", $file);
                unset($split[count($split)-1]);
                return implode(self::DS, $split).self::DS;
        }
        
        /** FileParser::_parseExtension($file)
          * @param string $file file name with full path
          * @return string file extension on success false otherwise
        */
        private function _parseExtension($file)
        {
                return substr($file, strrpos($file, '.')+1);
        }
        
        /** FileParser::_parseFileName($file)
          * @param string $file file name with full path
          * @return string file name on success false otherwise
        */
        private function _parseFileName($file)
        {
                $ds = DIRECTORY_SEPARATOR;
                $split = preg_split("/\\".$ds."/", $file);
                return $split[count($split)-1];
        }
        
        private function _parseUrl()
        {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->getFile());
                curl_setopt($ch, CURLOPT_USERPWD, 'api@manlinegroup.com:Tp9$6zqTS5');
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the page
                
                if ($this->getCurlTimeout() && is_int($this->getCurlTimeout())) {
                        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getCurlTimeout());
                }
                $output = (string)realpath(dirname(__FILE__)).self::DS.'tmp'.self::DS.$this->getCurlFile();
                
                $fp = fopen($output, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT']) {
                	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
                }
                $data = curl_exec($ch);
                
                $info = curl_getinfo($ch);
                
                curl_close($ch);
                fclose($fp);

                $fp = fopen($output, 'rb');
                if ($fp === null) {
                        $this->_errors[] = preg_replace('/%s/', $this->getFile(),  SELF::FOPEN_COULD_NOT_OPEN);
                        return false;
                }
                $data = (array)array();
                
                while (($row = fgetcsv($fp, (substr($this->getFile(), 0, 4) === 'http' ? 25*64*1000 : (filesize($this->getFile()) ? filesize($this->getFile()) : 25*64*1000)))) !== false) {
                  $data[] = $row;
                }
                fclose($fp);
                
                $headerRow = isset($data[0]) ? $data[0] : NULL;
                if (isset($data[0])) {unset($data[0]);}
                foreach ($data as $key=>$val) {
                        $data[$key] = $this->_combineDataRow($headerRow, $val);
                }
                return $data;
        }
        
        /** FileParser::_parseXmlFile($fileString = null)
          * @param string $fileString which file do we need to open?
          * @return array file data
        */
        private function _parseXmlFile($fileString = null)
        {
                $file = $fileString ? $fileString : $this->getFile();
                if ($this->checkFopen($file) === false) {return false;}
                if ($this->checkFileIsReadable($file) === false) {return false;}
                $fp = fopen($file, 'rb');
                if ($fp === null) {
                        $this->_errors[] = preg_replace('/%s/', $file,  SELF::FOPEN_COULD_NOT_OPEN);
                        return false;
                }
                $data = fread($fp, (substr($file, 0, 4) === 'http' ? 25*64*1000 : filesize($file)));
                fclose($fp);
                $values = (array)array();
                $parser = xml_parser_create('');
                xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
                xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
                xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
                xml_parse_into_struct($parser, trim($data), $values);
                xml_parser_free($parser);
                $xml_array = (array)array();
                $int = (int)0;
                foreach ($values as $data) {
                        switch ($data['type']) {
                        case 'close':
                                $int++;
                                break;
                        case 'complete':
                                $xml_array[$int][$data['tag']] = trim($data['value']);
                                break;
                        case 'open':
                                $xml_array[$int] = (array)array();
                                break;
                        }
                }
                return $xml_array;
        }
        
        /** FileParser::_combineDataRow(array $arrh, array $arrv)
          * @param array $arrh to match against
          * @param array $arrv to be matched against parameter 1
          * @return array updated data row on success
        */
        private function _combineDataRow(array $arrh, array $arrv)
        {
                $diff = (int)0;
                $cntH = count($arrh);
                $cntV = count($arrv);
                if ($cntH > $cntV) {
                        $diff = $cntH-$cntV;
                        for ($i=1; $i<=$diff; $i++) {
                                $arrv[] = "unknown".$i;
                        }
                } elseif ($cntV > $cntH) {
                        $diff = $cntV-$cntH;
                        for ($i=1; $i<=$diff; $i++) {
                                $arrh[] = "unknown".$i;
                        }
                }
                return array_combine($arrh, $arrv);
        }
}
