<?php
class pwd_tools {
	// : Constants
	const DEFAULT_ALGO = "CRYPT_BLOWFISH";
	
	// : Properties
	private $_algo;
	
	// : Getters
	public function get_algo($algo) {
	}
	
	// : Setters
	public function set_algo($algo) {
	}
	
	// : Public Methods
	
	// : Magic
	public function __construct($algo = NULL) {
		if (defined ( $algo ) && ($algo)) {
			$this->_algo = $algo;
		} else {
			$this->_algo = self::DEFAULT_ALGO;
		}
	}
	public function create_pwd($_pwd, $_round = 7) {
		try {
			if (defined ( $this->_algo ) == 1) {
				$_result = crypt ( $_pwd, $this->salt_gen ( $_round ) );
			}
		} catch ( Exception $e ) {
			return FALSE;
		}
		if (isset ( $_result )) {
			return $_result;
		} else {
			return FALSE;
		}
	}
	public function verify_pwd($_pwd, $_hashed_pwd) {
		// : Use time attack safe method -> hash_equals
		return hash_equals ( $_pwd, $_hashed_pwd );
	}
	// : End
	
	// : Private Methods
	private function salt_gen($_round) {
		if (defined ( $this->_algo )) {
			$_salt = ( string ) "";
			$_format = ( string ) "";
			
			if ($this->_algo === "CRYPT_BLOWFISH") {
				$_format = "$2y$%02d$";
				$_salt_rnd_arr = array_merge ( range ( "A", "Z" ), range ( "a", "z" ), range ( "0", "9" ) );
				for($i = 0; $i < 22; $i ++) {
					$_salt .= array_rand ( $_salt_rnd_arr );
				}
			}
			
			if ($_salt && $_format) {
				return sprintf ( $_format, $_round ) . $_salt;
			}
		}
	}
	// : End
}