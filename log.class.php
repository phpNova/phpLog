<?php

require_once( "config_log.class.php" );

class Log extends Config_Log
{
	/* These are reset whenever hook() is called.  --Kris */
	public $errors = array();
	public $warnings = array();
	public $notices = array();
	
	public function __construct()
	{
		if ( !isset( $this->output_formats ) )
		{
			parent::__construct();
		}
	}
	
	/* This is the entry point.  All arguments are optional.  Stick this hook anywhere you want an event to be logged.  --Kris */
	public function hook( $action = NULL, $method = NULL, $details = NULL, $nocache = TRUE, $custom_data = array(), $results = NULL, $type = 0x00, $status = 0x00 )
	{
		/* Log environment info based on configuration directives.  --Kris */
		$system = $this->system( $status );
		$server = $this->server( $status );
		$php = $this->php( $status );
		$session = $this->session( $status );
		$cookie = $this->cookie( $status );
		$get = $this->get( $status );
		$post = $this->post( $status );
		$error = $this->error( $status );  // Currently unused.  --Kris
		
		/* The following values are ALWAYS logged.  --Kris */
		$ip = $_SERVER["REMOTE_ADDR"];
		$num_errors = count( $this->errors );
		$num_warnings = count( $this->warnings );
		$num_notices = count( $this->notices );
		$timestamp = microtime( TRUE );
		
		/* Cache to the session, if appropriate.  Note that a cached entry may not make it to the database if the session is terminated!  --Kris */
		if ( $this->log_cache_session == TRUE && $nocache == FALSE 
			&& ( !isset( $_SESSION["log_cache"] ) || !is_array( $_SESSION["log_cache"] ) || empty( $_SESSION["log_cache"] ) 
				|| count( $_SESSION["log_cache"] ) < $this->log_cache_session_size ) )
		{
			$this->session_cache( 	$action, $method, $details, $custom_data, $results, $type, $status, $system, $server, $php, 
						$session, $cookie, $get, $post, $error, $ip, $num_errors, $num_warnings, $num_notices, $timestamp );
		}
		else
		{
			/* If there are items in the cache, save them first.  --Kris */
			if ( isset( $_SESSION["log_cache"] ) && is_array( $_SESSION["log_cache"] ) && !empty( $_SESSION["log_cache"] ) )
			{
				$this->session_cache( 	$action, $method, $details, $custom_data, $results, $type, $status, $system, $server, $php, 
							$session, $cookie, $get, $post, $error, $ip, $num_errors, $num_warnings, $num_notices, $timestamp );
				
				foreach ( $_SESSION["log_cache"] as $cache )
				{
					/* So I can just do a lazy copy/paste.  --Kris */
					foreach ( $cache as $var => $val )
					{
						$$var = $val;
					}
					
					$this->out( 	$action, $method, $details, $custom_data, $results, $type, $status, $system, $server, $php, 
							$session, $cookie, $get, $post, $error, $ip, $num_errors, $num_warnings, $num_notices, $timestamp );
				}
				
				$_SESSION["log_cache"] = array();
			}
			else
			{
				$this->out( 	$action, $method, $details, $custom_data, $results, $type, $status, $system, $server, $php, 
						$session, $cookie, $get, $post, $error, $ip, $num_errors, $num_warnings, $num_notices, $timestamp );
			}
		}
	}
	
	/* Add an entry to the session cache.  --Kris */
	private function session_cache( $action, $method, $details, $custom_data, $results, $type, $status, $system, $server, $php, $session, $cookie, $get, $post, $error, $ip, $num_errors, $num_warnings, $num_notices, $timestamp )
	{
		if ( !isset( $_SESSION["log_cache"] ) || !is_array( $_SESSION["log_cache"] ) )
		{
			$_SESSION["log_cache"] = array();
		}
		
		$_SESSION["log_cache"][] = array( 
						"action" => $action, 
						"method" => $method, 
						"details" => $details, 
						"custom_data" => $custom_data, 
						"results" => $results, 
						"type" => $type, 
						"status" => $status, 
						"system" => $system, 
						"server" => $server, 
						"php" => $php, 
						"session" => $session, 
						"cookie" => $cookie, 
						"get" => $get, 
						"post" => $post, 
						"error" => $error, 
						"ip" => $ip, 
						"num_errors" => $num_errors, 
						"num_warnings" => $num_warnings, 
						"num_notices" => $num_notices, 
						"timestamp" => $timestamp 
						);
	}
	
	/* Output a log entry to the appropriate medium(s).  --Kris */
	private function out( $action, $method, $details, $custom_data, $results, $type, $status, $system, $server, $php, $session, $cookie, $get, $post, $error, $ip, $num_errors, $num_warnings, $num_notices, $timestamp )
	{
		/* Output to SQL.  --Kris */
		if ( $this->output_formats & LOG_OUT_SQL )
		{
			$this->out_sql( $action, $method, $details, $custom_data, $results, $type, $status, 
					$system, $server, $php, $session, $cookie, $get, $post, $error, 
					$ip, $num_errors, $num_warnings, $num_notices, $timestamp );
		}
		
		// TODO - Other logging output methods (text, csv, etc).  --Kris
	}
	
	/* Add the database entry.  --Kris */
	private function out_sql( $action, $method, $details, $custom_data, $results, $type, $status, $system, $server, $php, $session, $cookie, $get, $post, $error, $ip, $num_errors, $num_warnings, $num_notices, $timestamp )
	{
		$custom_cols = NULL;
		$custom_insert = array();
		foreach ( $custom_data as $col => $arr )
		{
			$custom_cols .= ", $col";
			$custom_insert[] = $custom_data[$col];  // TODO - Validate for type/length/etc.  --Kris
		}
		
		$query = "insert into logs ( action, microtime";
		$query .= $custom_cols;
		$query .= ", details, results, errors, warnings, notices, success, denied, num_errors, num_warnings, num_notices";
		$query .= ", server_array, session_array, cookie_array, get_array, post_array, ip, php_array, php_method, system_array";
		$query .= ", type, status";
		$query .= " ) values ( ";
		$query .= "?, ?";
		foreach ( $custom_data as $dummy )
		{
			$query .= ", ?";
		}
		$query .= ", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
		$query .= ", ?, ?, ?, ?, ?, ?, ?, ?";
		$query .= ", ?, ?, ?";
		$query .= " )";
		
		$params = array();
		$params[] = $action;
		$params[] = $timestamp;
		$params = array_merge( $params, $custom_insert );
		$params[] = $details;
		$params[] = $results;
		$params[] = json_encode( $this->errors );
		$params[] = json_encode( $this->warnings );
		$params[] = json_encode( $this->notices );
		$params[] = ( !empty( $this->errors ) ? 1 : 0 );
		$params[] = 0;  // TODO - Denied.  Not sure what to make this yet lol.  --Kris
		$params[] = $num_errors;
		$params[] = $num_warnings;
		$params[] = $num_notices;
		$params[] = $server;
		$params[] = $session;
		$params[] = $cookie;
		$params[] = $get;
		$params[] = $post;
		$params[] = $ip;
		$params[] = $php;
		$params[] = $method;
		$params[] = $system;
		$params[] = hexdec( $type );
		$params[] = hexdec( $status );
		
		$this->sql->query( $query, $params, SQL_RETURN_AFFECTEDROWS );
	}
	
	/* Report on system information at the moment of the event.  --Kris */
	private function system( $status )
	{
		$out = array();
		
		// TODO - Allow $status to act as override exceptions to config logging rules.  --Kris
		
		// CPU
		if ( $this->system_reporting & LOG_SYS_CPU )
		{
			// TODO
			// 
			// Looks like nothing natively can do this; getrusage() was a red herring.
			// The phpSysInfo package looks promising but the demo doesn't show CPU load, either.
			// May have to be looked at on Internals.  In the meantime, I guess I could just hack 
			// together an exec() call.
			// 
			// --Kris
		}
		
		// RAM Available / Total
		if ( $this->system_reporting & LOG_SYS_RAM )
		{
			// TODO
		}
		
		// HDD Info
		if ( $this->system_reporting & LOG_SYS_HDD )
		{
			// TODO
		}
		
		return json_encode( $out );
	}
	
	/* Report on server information at the moment of the event.  --Kris */
	private function server( $status )
	{
		$out = array();
		
		// TODO - ""
		
		// TODO - Go through the $_SERVER array and pick it apart.
		
		return json_encode( $out );
	}
	
	/* Report on PHP environment information at the moment of the event.  --Kris */
	private function php( $status )
	{
		$out = array();
		
		// TODO - ""
		
		// PHP Version
		if ( $this->php_reporting & LOG_PHP_VER )
		{
			// TODO
		}
		
		// PHP INI Settings
		if ( $this->php_reporting & LOG_PHP_INI )
		{
			// TODO
		}
		
		// PHP Extensions Loaded
		if ( $this->php_reporting & LOG_PHP_EXT )
		{
			// TODO
		}
		
		// PHP Constants
		if ( $this->php_reporting & LOG_PHP_CONST )
		{
			// TODO
		}
		
		// PHP $_GET Array Contents
		if ( $this->php_reporting & LOG_PHP_GET )
		{
			// TODO
		}
		
		// PHP $_POST Array Contents
		if ( $this->php_reporting & LOG_PHP_POST )
		{
			// TODO
		}
		
		// PHP RAM Usage
		if ( $this->php_reporting & LOG_PHP_RAM )
		{
			$out["RAM"] = array( "real=TRUE" => memory_get_usage( TRUE ), "real=FALSE" => memory_get_usage( FALSE ) );
			$out["RAM_PEAK"] = array( "real=TRUE" => memory_get_peak_usage( TRUE ), "real=FALSE" => memory_get_peak_usage( FALSE ) );
		}
		
		return json_encode( $out );
	}
	
	private function get( $status )
	{
		$out = array();
		
		// TODO - ""
		
		// TODO - Break down GET data.
		
		return json_encode( $out );
	}
	
	private function post( $status )
	{
		$out = array();
		
		// TODO - ""
		
		// TODO - Break down POST data.
		
		return json_encode( $out );
	}
	
	private function session( $status )
	{
		$out = array();
		
		// TODO - ""
		
		// TODO - Break down session data.
		
		return json_encode( $out );
	}
	
	private function cookie( $status )
	{
		$out = array();
		
		// TODO - ""
		
		// TODO - Break down cookie data.
		
		return json_encode( $out );
	}
	
	private function error( $status )
	{
		$out = array();
		
		// TODO - ""
		
		// TODO - PHP error handling is problematic since not everything can be caught.  Will implement later.
		
		return json_encode( $out );
	}
}
