<?php
/**
 * Xap bootstrap
 */

// import Xap engine
require_once './lib/Xap/Engine.php';

// import Xap Model class (if using '/model' query option)
// require_once './lib/Xap/Model.php';

// import Xap Decorator class (if using decorators)
// require_once './lib/Xap/Decorator.php';

// import xap() function
require_once './lib/Xap/xap.php';

// register database connection
xap([
	// database connection params
	'host' => 'localhost',
	'database' => 'test',
	'user' => 'myuser',
	'password' => 'mypass',
	// 'id' => 1, // manually set connection ID (default 1)

	// 'errors' => false, // display errors (default true)
	// 'debug' => false, // debug messages and errors to log (default true)
	// 'objects' => false, // return objects instead of arrays (default true)
	// 'error_handler' => null, // optional error handler (callable)
	// 'log_handler' => null // optional log handler (callable)
]);

// set global pagination records per page (default 10)
// xap(':pagination', ['rpp' => 10]);