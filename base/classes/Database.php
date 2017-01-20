<?php

/**
 * Database class.
 */
class Database {
	private $host = DB_HOST;
	private $user = DB_USER;
	private $pass = DB_PASS;
	private $dbname = DB_NAME;

	private $dbh;
	private $error;

	private $stmt;


	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// Set DSN
		$dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname.';charset=utf8';
        
		// Set options
		$options = array(
		    PDO::ATTR_PERSISTENT => true,
		    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		);

		try {
			$this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
		}
		// Catch any errors
		catch (PDOException $e) {
			$this->error = $e->getMessage();
		}
	}


	/**
	 * query function.
	 *
	 * @access public
	 * @param mixed $query
	 * @return void
	 */
	public function query($query) {
		$this->stmt = $this->dbh->prepare($query);
	}


	/**
	 * bind function.
	 *
	 * @access public
	 * @param mixed $param
	 * @param mixed $value
	 * @param mixed $type (default: null)
	 * @return void
	 */
	public function bind($param, $value, $type = null) {
		if(is_null($type)) {
			switch(true) {
				case is_int($value):
					$type = PDO::PARAM_INT;
				break;
				case is_bool($value):
					$type = PDO::PARAM_BOOL;
				break;
				case is_null($value):
					$type = PDO::PARAM_NULL;
				break;
				default:
					$type = PDO::PARAM_STR;
			}
		}

		$this->stmt->bindValue($param, $value, $type);
	}


	/**
	 * execute function.
	 *
	 * @access public
	 * @return void
	 */
	public function execute() {
		return $this->stmt->execute();
	}


	/**
	 * resultset function.
	 *
	 * @access public
	 * @return void
	 */
	public function resultset() {
		$this->execute();
		return $this->stmt->fetchAll(PDO::FETCH_OBJ);
	}


	/**
	 * single function.
	 *
	 * @access public
	 * @return void
	 */
	public function single() {
	    $this->execute();
	    return $this->stmt->fetch(PDO::FETCH_OBJ);
	}


	/**
	 * rowCount function.
	 *
	 * @access public
	 * @return void
	 */
	public function rowCount() {
		return $this->stmt->rowCount();
	}


	/**
	 * lastInsertId function.
	 *
	 * @access public
	 * @return void
	 */
	public function lastInsertId() {
		return $this->dbh->lastInsertId();
	}


	/**
	 * beginTransaction function.
	 *
	 * @access public
	 * @return void
	 */
	public function beginTransaction() {
	    return $this->dbh->beginTransaction();
	}


	/**
	 * endTransaction function.
	 *
	 * @access public
	 * @return void
	 */
	public function endTransaction() {
	    return $this->dbh->commit();
	}


	/**
	 * cancelTransaction function.
	 *
	 * @access public
	 * @return void
	 */
	public function cancelTransaction() {
	    return $this->dbh->rollBack();
	}


	/**
	 * debugDumpParams function.
	 *
	 * @access public
	 * @return void
	 */
	public function debugDumpParams() {
	    return $this->stmt->debugDumpParams();
	}


	/**
	 * get_user_options function.
	 *
	 * Return an empty array if no options are available
	 *
	 * @access public
	 * @param mixed $user_id
	 * @return void
	 */
	public function get_user_options($user_id) {
		$this->query("SELECT * FROM options WHERE `user_id` = :user_id");
		$this->bind(":user_id", $user_id);
		$this->execute();

		return $this->resultset();

	}

}
