<?php

require_once( "config.parent.php" );

class Config_Log extends Config
{
	/* This function contains all the configuration settings that you can change.  --Kris */
	private function config_settings()
	{
		// See docs/constants.txt for available constants.  --Kris
		$this->error_reporting = LOG_E_ALL;
		$this->system_reporting = LOG_SYS_ALL;
		$this->session_reporting = LOG_SESS_ALL;
		$this->cookie_reporting = LOG_COOKIE_ALL;
		$this->server_reporting = LOG_SRV_ALL;
		$this->php_reporting = LOG_PHP_ALL;
		$this->output_formats = LOG_OUT_SQL;
		
		/* Enable SQL log caching?  VERY recommended to avoid prohibitive SQL lag.  Applies only to LOG_OUT_SQL.  --Kris */
		$this->log_cache_sql = TRUE;
		
		/* If log_cache_sql is enabled, the LOCAL path to the PHP script that sends the GET request to the SQL insertion script.  --Kris */
		$this->log_cache_sql_passthru = "log_cache_sql_passthru.php";
		
		/* If log_cache_sql is enabled, the path (local or remote) to the script that will query the SQL server.  --Kris */
		$this->log_cache_sql_query = "https://TODO/log_cache_sql_query.php";
		
		// TODO - The above is not supported at this time.  Add SSL paths and setup on MySQL server w/ Apache/PHP.  --Kris
		
		/* Enable caching of less essential logs into the session?  Independent of log_cache but recommended to work in tandem with it.  --Kris */
		$this->log_cache_session = TRUE;
		
		/* If log_cache_session is enabled, the maximum number of log entries allowed in the session before it must be stored.  --Kris */
		$this->log_cache_session_size = 30;
		
		/* Settings pertaining to how the log data is displayed when viewed.  --Kris */
		// TODO
		
		/*
		 * Defines all "custom" logging columns that are specific to your app.
		 * All of these columns must exist in the database logs table between microtime and details.
		 * 
		 * Usage:  array( 
		 * 		(string) $column_name => array( "type" => (string) $type, "limit" => (int) $limit, 
		 			"null" => (bool) $null, "default" => (mixed) $default )
		 * 		....
		 * 		)
		 * 
		 * --Kris
		 */
		$this->custom_cols = array( 
						"target_userid" => array( "type" => "int", "limit" => 11, "null" => FALSE, "default" => 0 ), 
						"notes" => array( "type" => "text", "limit" => 0, "null" => TRUE, "default" => NULL ), 
						"charset" => array( "type" => "varchar", "limit" => 255, "null" => TRUE, "default" => NULL ), 
						"due_date" => array( "type" => "date", "limit" => 0, "null" => TRUE, "default" => NULL ), 
					);
	}
	
	public function __construct()
	{
		/*
		 * ----------------------------
		 * DO NOT EDIT BELOW THIS LINE!
		 * ----------------------------
		 */
		
		parent::__construct( FALSE );
	
		$this->load_constants();
		$this->config_settings();
		$this->setup();
		$this->qa();
	}
	
	private function load_constants()
	{
		require_once( "constants.class.php" );
		
		/* Logging constants may already be defined.  This is just to make sure for maximum portability.  --Kris */
		$constants = new Constants();
		
		$constants->load_constants( "log_error_levels" );
		$constants->load_constants( "log_system_state" );
		$constants->load_constants( "log_session_data" );
		$constants->load_constants( "log_server_data" );
		$constants->load_constants( "log_php_data" );
		$constants->load_constants( "log_cookie_data" );
		$constants->load_constants( "log_output_formats" );
	}
}
