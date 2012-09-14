<?php

/*
 * This file is used ONLY if you are running the module independent of the phpNova framework.
 * 
 * --Kris
 */
class Config
{
	/*
	 * Paths (files and/or directories) that are required for the application to function.
	 * 
	 * Usage:
	 * 
	 * array( [(string) Unique name] => array( 
	 * 			"path" => (string) Absolute or relative filesystem path, 
	 * 			"perms" => array( 
	 * 					[] => (char) Required permission ('R', 'W', or 'X'), 
	 * 					...
	 * 			), 
	 * 			"type" => (string) "file" or "dir", 
	 * 			"create" => (bool) If TRUE, file/dir will be created if it doesn't already exist 
	 * 		)
	 * 
	 * --Kris
	 */
	public $paths = array();
	
	public function __construct( $setup = TRUE )
	{
		/* PHP error reporting.  Comment out to use server defaults.  --Kris */
		//error_reporting( E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING );
		
		/*
		 * ----------------------------
		 * DO NOT EDIT BELOW THIS LINE!
		 * ----------------------------
		 */
		
		if ( $setup == TRUE )
		{
			$this->setup();
			$this->qa();
		}
	}
	
	public function __toString()
	{
		return "(PHP Object)";
	}
	
	/* If there are any configuration errors, they'll be stored here.  --Kris */
	public $errors = array();
	
	/* Perform any automated configuration tweaks that are not intended to be modified by the developer.  --Kris */
	public function setup()
	{
		$this->setup_paths();
	}
	
	/* Prepare and sanitize the paths.  --Kris */
	public function setup_paths()
	{
		foreach ( $this->paths as $name => &$attribs )
		{
			$attribs = $this->sanitize_path( $attribs );
			
			/* If path doesn't exist and we're allowed to create it, attempt to do so.  --Kris */
			if ( !file_exists( $attribs["path"] ) && $attribs["create"] == TRUE )
			{
				if ( strcmp( $attribs["type"], "dir" ) == 0 )
				{
					if ( mkdir( $attribs["path"], 0777, TRUE ) === FALSE )
					{
						$this->errors[] = "Unable to create directory '" . $attribs["path"] . "'!";
					}
				}
				else
				{
					if ( touch( $attribs["path"] ) === FALSE )
					{
						$this->errors[] = "Unable to create file '" . $attribs["path"] . "'!";
					}
				}
			}
		}
	}
	
	/* Sanitize a path string.  May be called redundantly without significant performance drain.  --Kris */
	public function sanitize_path( $attribs )
	{
		/* Make absolute and clean-up any other anomalies in the path string.  --Kris */
		$attribs["path"] = realpath( $attribs["path"] );
		
		/* Add a trailing slash for directories if not already there.  --Kris */
		$attribs["path"] .= ( strcmp( substr( $attribs["path"], strlen( $attribs["path"] ) - 1, 1 ), '/' ) 
			&& strcmp( $attribs["type"], "dir" ) == 0 ? '/' : NULL );
		
		return $attribs;
	}
	
	/* Perform basic tests on configuration values and add report any errors.  --Kris */
	public function qa()
	{
		$this->qa_paths();
	}
	
	/* Validate all the paths.  --Kris */
	public function qa_paths()
	{
		foreach ( $this->paths as $name => $attribs )
		{
			$this->qa_path( $attribs );
		}
	}
	
	/* Validate a given path.  Delayed recursion to mitigate temporary FSO access conflicts.  --Kris */
	public function qa_path( $attribs, $retry = 5 )
	{
		$errors = array();
		
		if ( !file_exists( $attribs["path"] ) )
		{
			$errors[] = "FSO '" . $attribs["path"] . "' may not exist!";
		}
		
		if ( strcmp( $attribs["type"], "dir" ) == 0 && is_file( $attribs["path"] ) )
		{
			$errors[] =  "FSO '" . $attribs["path"] . "' is a file; directory expected!";
		}
		else if ( strcmp( $attribs["type"], "file" ) == 0 && is_dir( $attribs["path"] ) )
		{
			$errors[] = "FSO '" . $attribs["path"] . "' is a directory; file expected!";
		}
		
		if ( in_array( "R", $attribs["perms"] ) && !is_readable( $attribs["path"] ) )
		{
			$errors[] = "FSO '" . $attribs["path"] . "' is not readable!";
		}
		
		if ( in_array( "W", $attribs["perms"] ) && !is_writable( $attribs["path"] ) )
		{
			$errors[] = "FSO '" . $attribs["path"] . "' is not writable!";
		}
		
		if ( in_array( "X", $attribs["perms"] ) && !is_executable( $attribs["path"] ) )
		{
			$errors[] = "FSO '" . $attribs["path"] . "' is not executable!";
		}
		
		if ( !empty( $errors ) )
		{
			if ( $retry > 0 )
			{
				sleep( 1 );
				
				$retry--;
				$this->qa_path( $attribs, $retry );
			}
			else
			{
				$this->errors = array_merge( $this->errors, $errors );
			}
		}
	}
}
