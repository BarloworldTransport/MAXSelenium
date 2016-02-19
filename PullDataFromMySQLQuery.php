<?php
// Error reporting
error_reporting ( E_ALL );

// : Includes
// : End

/**
 * Object::PullDataFromMySQLQuery
 *
 * @author Clinton Wright
 * @author cwright@bwtsgroup.com
 * @copyright 2011 onwards Manline Group (Pty) Ltd
 * @license GNU GPL
 * @see http://www.gnu.org/copyleft/gpl.html
 */
class PullDataFromMySQLQuery {
	// : Constants
	const DS = DIRECTORY_SEPARATOR;
	
	// : Variables
	protected $_db;
	protected $_dbdsn;
	protected $_dbuser;
	protected $_dbpwd;
	protected $_dboptions = array (
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true 
	);
	protected static $_config_db = array(
		'maxdb' => array(
			'dbdsn' => '',
			'dbhost' => '',
			'dbuser' => '',
			'dbpwd' => ''
		)
	);
	protected $_errors = array ();
	
	// : Public functions
	// : Accessors
	
	/**
	 * PullDataFromMySQLQuery::getErrors()
	 * Return error messages if any errors occured while attempting to run MySQL query file
	 * 
	 * @param array: $this->_data        	
	 */
	public function getErrors() {
		return $this->_errors;
	}
	
	public static function getDefaultConfigOptions()
	{
		return PullDataFromMySQLQuery::$_config_db;
	}
	
	/**
	 * PullDataFromMySQLQuery::dbOpen()
	 * Return error messages if any errors occured while attempting to run MySQL query file
	 * 
	 * @param array: $this->_openDB
	 *        	( dbdsn, dbuser, dbpwd, dboptions )
	 */
	public function dbOpen() {
		if ($this->openDB ( $this->_dbdsn, $this->_dbuser, $this->_dbpwd, $this->_dboptions ) != FALSE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * PullFandVContractData::dbClose()
	 * Close connection to Database
	 * 
	 * @param object: $this->_db        	
	 */
	public function dbClose() {
		$this->_db = null;
	}
	
	// : End
	
	// : Public Functions
	
	/**
	 * getDataFromQuery::getDataFromQuery()
	 * Return the data from the MySQL Query
	 * 
	 * @param array: $this->_data        	
	 */
	public function getDataFromQuery($sqlquery) {
		try {
			$_errors = ( array ) array ();
			$_data = ( array ) array ();
			$_data = $this->queryDB ( $sqlquery );
			if ((count ( $_data ) == 0)) {
				$_errors [] = "No rows where returned by MySQL.";
			}
		} catch ( Exception $e ) {
			$_errors [] = $e->getMessage ();
		}
		if (count ( $_errors ) != 0) {
			foreach ( $_errors as $value ) {
				$this->_errors [] = $value;
			}
			return FALSE;
		} else {
			return $_data;
		}
		unset ( $_errors, $_data );
	}
	
	/**
	 * PullDataFromMySQLQuery::getDataFromSQLFile()
	 * Return the data from the MySQL Query
	 *
	 * @param array: $this->_data        	
	 */
	public function getDataFromSQLFile($sqlfile, $replace, $pattern, $replacement) {
		try {
			$_errors = ( array ) array ();
			$_data = ( array ) array ();
			$_file = dirname ( __FILE__ ) . self::DS . $sqlfile;
			if (file_exists ( $_file )) {
				$sqlquery = file_get_contents ( $_file );
				if ($replace != FALSE) {
					$sqlquery = preg_replace ( $pattern, $replacement, $sqlquery );
				}
				$_data = $this->queryDB ( $sqlquery );
			} else {
				$_errors [] = "File not found: " . $_file;
			}
			if ((count ( $_data ) == 0)) {
				$_errors [] = "No rows where returned by MySQL.";
			}
		} catch ( Exception $e ) {
			$_errors [] = $e->getMessage ();
		}
		if (count ( $_errors ) != 0) {
			foreach ( $_errors as $value ) {
				$this->_errors [] = $value;
			}
			return FALSE;
		} else {
			return $_data;
		}
		unset ( $_errors, $data );
	}
	
	// : End
	
	// : Magic
	/**
	 * PullDataFromMySQLQuery::__construct()
	 * Class constructor
	 */
	public function __construct($_tenant, $_config_data) {
		try {
			
			if (isset($_config_data['maxdb']['dbdsn']) && isset($_config_data['maxdb']['dbhost']) && isset($_config_data['maxdb']['dbuser']) && isset($_config_data['maxdb']['dbpwd']))
			{
				$_dsn = preg_replace ( "/%s/", $_tenant, $_config_data['maxdb']['dbdsn'] );
				$_dsn = preg_replace ( "/%h/", $_config_data['maxdb']['dbhost'], $_dsn );
				$this->_dbdsn = $_dsn;
				$this->_dbuser = $_config_data['maxdb']['dbuser'];
				$this->_dbpwd = $_config_data['maxdb']['dbpwd'];
				$this->dbOpen ();
			} else
			{
				throw new Exception ( "Correct fields where not found in the supplied config array." );
			}
			
		} catch ( Exception $e ) {
			$this->_errors [] = $e->getMessage ();
		}
		if (count ( $this->_errors ) != 0) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	/**
	 * PullDataFromMySQLQuery::__destruct()
	 * Class destructor
	 * Allow for garbage collection
	 */
	public function __destruct() {
		unset ( $this );
	}
	// : End
	
	// : Private Functions
	/**
	 * PullFandVContractData::openDB($dsn, $username, $password, $options)
	 * Open connection to Database
	 *
	 * @param string: $dsn        	
	 * @param string: $username        	
	 * @param string: $password        	
	 * @param array: $options        	
	 */
	private function openDB($dsn, $username, $password, $options) {
		try {
			$this->_db = new PDO ( $dsn, $username, $password, $options );
		} catch ( PDOException $ex ) {
			return FALSE;
		}
	}
	
	/**
	 * PullFandVContractData::queryDB($sqlquery)
	 * Pass MySQL Query to database and return output
	 *
	 * @param string: $sqlquery        	
	 * @param array: $result        	
	 */
	private function queryDB($sqlquery) {
		try {
			
			$result = $this->_db->query ( $sqlquery );
			return $result->fetchAll ( PDO::FETCH_ASSOC );
			
		} catch ( PDOException $ex ) {
			
			return FALSE;
			
		}
	}
	// : End
}
