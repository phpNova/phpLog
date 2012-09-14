<?php

try
{
	/* If integrated with the phpNova framework, load the main configuration class.  --Kris */
	include( "../../config.class.php" );
}
catch
{
	/* Otherwise, load the placeholder so that the configuration inheritance doesn't break.  --Kris */
	require( "config.class.standalone.php" );
}
