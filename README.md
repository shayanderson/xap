# Xap
#### MySQL Rapid Development Engine for PHP 5.5+
Xap requirements:

1. PHP 5.5+
2. PHP [PDO database extension](http://www.php.net/manual/en/book.pdo.php)
3. Database table names cannot include characters `.`, `/`, `:` or ` ` (whitespace) and cannot start with `[`

Install Xap options:

- Git clone: `git clone https://github.com/shayanderson/xap.git`
- Subversion checkout URL: `https://github.com/shayanderson/xap/trunk`
  - Subversion checkout library files only: `https://github.com/shayanderson/xap/trunk/lib/Xap`
- Download [ZIP file](https://github.com/shayanderson/xap/archive/master.zip)

Here is a list of Xap commands:

- [`add`](https://github.com/shayanderson/xap#insert) - insert record (can also use `insert`)
- [`call`](https://github.com/shayanderson/xap#call-stored-procedurefunction-routines) - call stored procedure or function (and [`call_affected`](https://github.com/shayanderson/xap#call-stored-procedurefunction-routines) and [`call_rows`](https://github.com/shayanderson/xap#call-stored-procedurefunction-routines))
- [`cache`](https://github.com/shayanderson/xap#caching) - set single cache expire time
- [`columns`](https://github.com/shayanderson/xap#show-table-columns) - show table columns
- [`commit`](https://github.com/shayanderson/xap#transactions) - commit transaction
- [`count`](https://github.com/shayanderson/xap#count-query) - count table records
- [`debug`](https://github.com/shayanderson/xap#debugging) - get debug info for connections
- [`del`](https://github.com/shayanderson/xap#delete) - delete record(s) (can also use `delete`)
- [`error`](https://github.com/shayanderson/xap#error-checking) - check if error has occurred
- [`error_last`](https://github.com/shayanderson/xap#get-last-error) - get last error, when error has occurred
- [`exists`](https://github.com/shayanderson/xap#records-exist) - check if record exists
- [`id`](https://github.com/shayanderson/xap#insert-with-insert-id) - get last insert ID
- [`key`](https://github.com/shayanderson/xap#custom-table-primary-key-column-name) - get/set table primary key column name (default 'id')
- [`log`](https://github.com/shayanderson/xap#debug-log) - get debug log (debugging must be turned on)
- [`log_handler`](https://github.com/shayanderson/xap#custom-log-handler) - add log message to database log (debugging must be turned on)
- [`mod`](https://github.com/shayanderson/xap#update) - update record(s) (can also use `update`)
- [`pagination`](https://github.com/shayanderson/xap#pagination) - get/set pagination params
- [`query`](https://github.com/shayanderson/xap#execute-query) - execute manual query
- [`replace`](https://github.com/shayanderson/xap#insert) - replace record
- [`rollback`](https://github.com/shayanderson/xap#transactions) - rollback transaction
- [`tables`](https://github.com/shayanderson/xap#show-tables) - show database tables
- [`transaction`](https://github.com/shayanderson/xap#transactions) - start transaction
- [`truncate`](https://github.com/shayanderson/xap#truncate-table) - truncate table

Xap supports:

- [Custom primary key name](https://github.com/shayanderson/xap#custom-table-primary-key-column-name)
- [Custom log handler](https://github.com/shayanderson/xap#custom-log-handler)
- [Custom error handler](https://github.com/shayanderson/xap#custom-error-handler)
- [Query options](https://github.com/shayanderson/xap#query-options)
- [Multiple database connections](https://github.com/shayanderson/xap#multiple-database-connections)
- [Pagination](https://github.com/shayanderson/xap#pagination)
- [Data Modeling (ORM)](https://github.com/shayanderson/xap#data-modeling)
- [Data Decorators](https://github.com/shayanderson/xap#data-decorators)
- [Caching](https://github.com/shayanderson/xap#caching)

## Quick Start
Edit the `xap.bootstrap.php` file and add your database connection params:
```php
// register database connection
xap([
	// database connection params
	'host' => 'localhost',
	'database' => 'test',
	'user' => 'myuser',
	'password' => 'mypass',
	'errors' => true, // true: Exceptions, false: no Exceptions, use error methods
	'debug' => true // turn logging on/off
]);
```

Next, include the bootstrap file in your project file:
```php
require_once './xap.bootstrap.php';
```

Now execute SELECT query:
```php
$user = xap('users.14'); // same as "SELECT * FROM users WHERE id = '14'"
if($user) echo $user->fullname; // print record column value
```

## Commands

#### Select
Simple select queries examples:
```php
$r = xap('users'); // SELECT * FROM users
$r = xap('users(fullname, email)'); // SELECT fullname, email FROM users
$r = xap('users LIMIT 1'); // SELECT * FROM users LIMIT 1
$r = xap('users WHERE is_active = 1'); // SELECT * FROM users WHERE is_active = 1
```

#### Select Where
Select query with named parameters:
```php
// SELECT fullname, email FROM users WHERE is_active = '1'
//	AND fullname = 'Shay Anderson'
$r = xap('users(fullname, email) WHERE is_active = :active AND fullname = :name'
	. ' LIMIT 2', ['active' => 1, 'name' => 'Shay Anderson']);
```
Select query with question mark parameters:
```php
// SELECT fullname, email FROM users WHERE is_active = 1
//	AND fullname = 'Shay Anderson' LIMIT 2
$r = xap('users(fullname, email) WHERE is_active = ? AND fullname = ? LIMIT 2',
	[1, 'Shay Anderson']);
```

#### Select with Key
Select queries with primary key value:
```php
$r = xap('users.2'); // SELECT * FROM users WHERE id = '2'
// test if record exists + display value for column 'fullname'
if($r) echo $r->fullname;

// using plain SQL in query example
// SELECT fullname, is_active FROM users WHERE id = '2' AND fullname = 'Shay'
$r = xap('users(fullname, is_active).2 WHERE fullname = ?', ['Name']);
```
> Query options can be used when selecting with key like:
```php
$q = xap('users.14/query');
// or with columns
$q = xap('users.14(fullname, is_active)/query');
```

When selecting with key use integer values only, for example:
```php
$r = xap('users.' . (int)$id);
```
> The default primary key column name is `id`, for using different primary key column name see [custom table primary key column name](https://github.com/shayanderson/xap#custom-table-primary-key-column-name)

<blockquote>Select with key command <i>cannot</i> use commands like <code>:command</code></blockquote>

#### Select Distinct
Select distinct example query:
```php
$r = xap('users(fullname)/distinct'); // SELECT DISTINCT fullname FROM users
```

#### Insert
Simple insert example:
```php
// INSERT INTO users (fullname, is_active, created)
//	VALUES('Name Here', '1', NOW())
$affected_rows = xap('users:add', ['fullname' => 'Name Here', 'is_active' => 1,
	'created' => ['NOW()']]);

// can also use action ':insert'
// xap('users:insert', ...);
```
The `replace` command can also be used, for example:
```php
// REPLACE INTO users (id, fullname, is_active, created)
//	VALUES(5, 'Name Here', '1', NOW())
$affected_rows = xap('users:replace', ['id' => 5 'fullname' => 'Name Here',
	'is_active' => 1, 'created' => ['NOW()']]);
```

#### Insert with Insert ID
Insert query and get insert ID:
```php
// INSERT INTO users (fullname, is_active, created) VALUES('Name Here', '1', NOW())
xap('users:add', ['fullname' => 'Name Here', 'is_active' => 1,
	'created' => ['NOW()']]);

// get insert ID
$insert_id = xap(':id');
```

#### Insert Ignore
Insert ignore query example:
```php
// INSERT IGNORE INTO users (user_id, fullname) VALUES('3', 'Name Here')
xap('users:add/ignore', ['user_id' => 3, 'fullname' => 'Name Here']);
```

#### Inserting Objects
Insert into table using object instead of array:
```php
// note: all class public properties must be table column names
class User
{
	public $user_id = 70;
	public $fullname = 'Name';
	public $created = ['NOW()'];
}

$affected_rows = xap('users:add', new User);
```

#### Update
Simple update query example:
```php
// UPDATE users SET fullname = 'Shay Anderson' WHERE user_id = '2'
$affected_rows = xap('users:mod WHERE user_id = :user_id',
	['fullname' => 'Shay Anderson'], ['user_id' => 2]);

// can also use action ':update'
// xap('users:update', ...);
```
> When using the `mod` (or `update`) command all params must be *named* params like `:my_param` and *not* question mark parameters

#### Update Ignore
Update ignore query example:
```php
// UPDATE IGNORE users SET user_id = '3' WHERE user_id = 6
$affected_rows = xap('users:mod/ignore WHERE user_id = 6', ['user_id' => 3]);
```

#### Delete
Delete query examples:
```php
// delete all
$affected_rows = xap('users:del'); // DELETE FROM users

// can also use action ':delete'
// xap('users:delete', ...);

// DELETE FROM users WHERE is_active = 1
$affected_rows = xap('users:del WHERE is_active = 1');

// DELETE FROM users WHERE user_id = '29'
$affected_rows = xap('users:del WHERE user_id = ?', [29]);
```

#### Delete Ignore
Delete ignore query example:
```php
// DELETE IGNORE FROM users WHERE user_id = 60
$affected_rows = xap('users:del/ignore WHERE user_id = 60');
```

#### Execute Query
Execute manual query example:
```php
// execute any query using the 'query' command
$r = xap(':query SELECT * FROM users LIMIT 2');

// use params with manual query:
$r = xap(':query SELECT * FROM users WHERE user_id = ?', [2]);
```
> The query command can use these query options: [/query](https://github.com/shayanderson/xap#query-option), [/first](https://github.com/shayanderson/xap#first-option), [/pagination](https://github.com/shayanderson/xap#pagination), [/cache](https://github.com/shayanderson/xap#caching). For example:
```php
$query_string = $r = xap(':query/query SELECT * FROM users LIMIT 2');
```

#### Count Query
Get back a count (integer) query example:
```php
// returns int of all records
$count = xap('users:count'); // SELECT COUNT(1) FROM users

// SELECT COUNT(1) FROM users WHERE is_active = 1
$count = xap('users:count WHERE is_active = 1');

// SELECT COUNT(1) FROM users WHERE user_id > '2' AND is_active = '1'
$count = xap('users:count WHERE user_id > ? AND is_active = ?', [2, 1]);
```

#### Record(s) Exist
Check if record(s) exists:
```php
$has_records = xap('users:exists'); // check if records exists
if($has_records) // do something

// use query params example:
$is_record = xap('users:exists WHERE user_id = ? AND is_active = 1', [2])
if($is_record) // do something
```

#### Truncate Table
A table can be truncated using:
```php
xap('table_name:truncate');
```

#### Call Stored Procedure/Function (Routines)
Call SP/SF example:
```php
xap(':call sp_name'); // CALL sp_name()

// Call SP/SF with params:
// CALL sp_addUser('Name Here', '1', NOW())
xap(':call sp_addUser', ['Name Here', 1, ['NOW()']]);

// Call SP/SF with params and out param
xap(':query SET @out = "";'); // set out param
// CALL sp_addUser('Name Here', '1', NOW(), @out)
xap(':call sp_addUserGetId', ['Name Here', 1, ['NOW()'], ['@out']]);
// get out param value
$r = xap(':query SELECT @out;');
```
The `call` command will return a `boolean` value. If a recordset `array` or affected rows `integer` is required instead use:
```php
// get recordset:
$rows = xap(':call_rows sp_getActiveUsers'); // array
// or get affected rows count:
$affected = xap(':call_affected sp_updateUser'); // integer
```
> Query options can be used with the `call` command like: `xap(':call/query sp_name');`

#### Transactions
Transactions are easy, for example:
```php
xap(':transaction'); // start transaction (autocommit off)
xap('users:add', ['fullname' => 'Name 1']);
xap('users:add', ['fullname' => 'Name 2']);

if(!xap(':error')) // no error
{
	if(xap(':commit')) ... // no problem, commit + continue with logic
}
else // error
{
	xap(':rollback'); // problem(s), rollback
	// warn client
}
```
> When [errors are on](https://github.com/shayanderson/xap#quick-start), use *try/catch* block like:
```php
try
{
	xap(':transaction'); // start transaction (autocommit off)
	xap('users:add', ['fullname' => 'Name 1']);
	xap('users:add', ['fullname' => 'Name 2']);
	if(xap(':commit')) ... // no problem, commit + continue with logic
}
catch(\Exception $ex)
{
	xap(':rollback'); // problem(s), rollback
	// warn client
}
```

#### Show Tables
Show database tables query example:
```php
$tables = xap(':tables'); // returns array of tables
```

#### Show Table Columns
Show table columns query example:
```php
$columns = xap('users:columns'); // returns array of table column names
```

#### Debug Log
Get debug log array example:
```php
$log = xap(':log'); // returns array of debug log messages
```
> Debug mode must be enabled for this example

#### Error Checking
Check if error has occurred when errors are *on* (throws exceptions) example:
```php
try
{
    $user = xap('users.14'); // same as "SELECT * FROM users WHERE id = '14'"
    if($user) echo $user->fullname; // print record field value
}
catch(\Exception $ex)
{
	// warn here
	echo 'Database error: ' . $ex->getMessage();
}
```
Or, if errors are *off* use error commands:
```php
$user = xap('users.14'); // same as "SELECT * FROM users WHERE id = '14'"
if(!xap(':error'))
{
    if($user) echo $user->fullname; // print record field value
}
else
{
    echo xap(':error_last'); // print error
}
```

#### Get Last Error
Get last error string example:
```php
if(xap(':error'))
{
	echo xap(':error_last');
}
```
> For getting last error message errors must be disabled, otherwise exception is thrown

#### Debugging
To display all registered connections, mapped keys, debug log and errors use:
```php
print_r( xap(':debug') ); // returns array with debug info
```

## Advanced
### Custom Table Primary Key Column Name
By default the primary key column named used when selecting with key is 'id'.
 This can be changed using the 'key' or 'keys' command:
```php
// register 'user_id' as primary key column name for table 'users'
xap('users:key user_id');

// now 'WHERE id = 2' becomes 'WHERE user_id = 2'
$r = xap('users.2'); // SELECT * FROM users WHERE user_id = '2'

// also register multiple key column names:
xap(':key', [
	'users' => 'user_id',
	'orders' => 'order_id'
]);
```

### Custom Log Handler
A custom log handler can be used when setting a database connection, for example:
```php
// register database connection
xap([
	// database connection params
	'host' => 'localhost',
	...
	'debug' => true, // debugging must be enabled for log handler
	// register custom log handler (must be callable)
	'log_handler' => function($msg) { echo '<b>Message:</b> ' . $msg . '<br />'; }
```
Now all Xap log messages will be sent to the custom callable log handler.
> If a custom log handler is used and the Xap log handler should be disabled the custom log handler callable must return `true`, for example:
```php
	...
	'log_handler' => function($msg)
	{
		echo '<b>Message:</b> ' . $msg . '<br />';
		return true; // flag as handled, do not pass message to Xap log handler
	}
	...
```
Now the default Xap log handler has been disabled.

The `log_handler` command can be used to insert log message into the database (without logging the actual log message being sent to the database), for example:
```php
	...
	// send all log messages to the `event_log` table columne `message`
	'log_handler' => function($msg) { xap('event_log:log_handler', ['message' => $msg]); }
	...
```

### Custom Error Handler
A custom error handler can be used when setting a database connection, for example:
```php
// register database connection
xap([
	// database connection params
	'host' => 'localhost',
	...
	'errors' => true, // errors must be enabled for error handler
	// register custom error handler (must be callable)
	'error_handler' => function($err) { echo '<b>Error:</b> ' . $err . '<br />'; }
```
Now all Xap error messages will be sent to the custom callable error handler.

### Query Options
Query options are used like: `table:command/[option]` and can be used with `SELECT` commands and other commands.

Example of option use:
```php
$r = xap('users(fullname)/distinct'); // DISTINCT option
```

Options can be chained together to complete valid MySQL statements:
```php
// UPDATE LOW_PRIORITY IGNORE users SET fullname = 'Shay Anderson' WHERE user_id = '2'
$affected_rows = xap('users:mod/low_priority/ignore WHERE user_id = :user_id',
	['fullname' => 'Shay Anderson'], ['user_id' => 2]);
```

##### Array Option
The `array` option can be used to force return type of arrays instead of objects when using objects in configuration settings:
```php
$r = xap('users(fullname)/distinct/array LIMIT 5');
```
Now the `$r` variable is an array with arrays instead of an array with objects.
> The `array` option is helpful when using objects for connection in configuration settings but array return types are desired for a single or several select commands. This option is used with `SELECT` commands only.

##### Query Option
The `query` option can be used to return the query string only, without executing the query (for debugging), for example:
```php
// returns string 'SELECT DISTINCT fullname FROM users'
$r = xap('users(fullname)/distinct/query');
```
> The following commands can use the `query` option: `add`, `call`, `columns`, `count`, `del`, `mod`, `query`, `replace`, and `tables`

##### First Option
The `first` option can be used to return the first record only, for example:
```php
$user = xap('users/first WHERE is_active = 1');
if($user) echo $user->fullname;
```
This can simplify using the first record only instead of having to use:
```php
if(isset($user[0])) echo $user[0]->fullname;
```
> Other options not mentioned here are: [`/cache`](https://github.com/shayanderson/xap#caching), [`/pagination`](https://github.com/shayanderson/xap#pagination) and [`/model`](https://github.com/shayanderson/xap#data-modeling)

### Multiple Database Connections
Using multiple database connections is easy, register database connections in bootstrap:
```php
// connection 1 (default connection)
xap(['host' => 'host1.server.com',
	// more here
]);

// connection 2
xap(['host' => 'host2.server.com',
	// more here
]);

// or manually set connection ID
xap(['host' => 'host5.server.com',
	// more here
	'id' => 5 // manually set ID (int only)
]);
```
> **Note:** manually set ID must be integer

Now to use different connections:
```php
// select from connection 1 / default connection
$r = xap('users.2'); // SELECT * FROM users WHERE id = '2'

// select from connection 2, where '[n]' is connection ID
$r2 = xap('[2]users.2'); // SELECT * FROM users WHERE id = '2'
```

### Pagination
Pagination is easy to use for large select queries, here is an example:
```php
// set current page number, for this example use GET parameter 'pg'
$pg = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;

// next set 10 Records Per Page (rpp) and current page number
xap(':pagination', ['rpp' => 10, 'page' => $pg]);

// execute SELECT query with pagination (SELECT query cannot contain LIMIT clause)
// SELECT DISTINCT id, fullname FROM users WHERE LENGTH(fullname) > '0' LIMIT x, y
$r = xap('users(id, fullname)/distinct/pagination WHERE LENGTH(fullname) > ?', [0]);
// $r['pagination'] contains pagination values:
//		rpp, page, next, prev, offset, next_string, prev_string
// $r['rows'] contains selected rows
```
Displaying the previous page numbers can done using:
```php
if($r['pagination']->prev > 0)
{
	// set last 5 pages viewed, so if on page 12 the pages would be: [7,8,9,10,11]
	$pages = array_slice(range(1, $r['pagination']->prev), -5);
}
```
**Note:** Pagination can also use [decorators](https://github.com/shayanderson/xap#decorators-with-pagination).
> Pagination only works on select commands like `users(id, fullname)/pagination` and select queries like `:query/pagination SELECT id, fullname FROM users`

### Data Modeling
Data Modeling (or ORM) can be used in Xap. First, ensure the `\Xap\Model` class is included in the `xap.bootstrap.php` file:
```php
require_once './lib/Xap/Model.php';
```
Next, set the data model object using the `/model` option and load (select) the model record data:
```php
$user = xap('users/model'); // \Xap\Model object
$user->id = 14; // set primary key column value
if($user->load()) // load record data
{
	echo $user->fullname;
}
```
> If the table primary key column name is not `id` use [custom table primary key column name](https://github.com/shayanderson/xap#custom-table-primary-key-column-name)

This can also be done using:
```php
$user = xap('users/model'); // \Xap\Model object
if($user->load(14)) // load record data with primary key column value
{
	echo $user->fullname;
}
```
> Column names can also be defined to optimize the load query:
```php
$user = xap('users(fullname)/model'); // only load 'fullname' column
```
**Note**: if column names are not defined they are automatically set by the model object, meaning that an update following a load (select) will update all the automatically loaded columns instead of defined columns (which is recommended)

##### Add Model Record
Adding (inserting) a model record is simple:
```php
// set model object and define model columns (required for insert)
$user = xap('users(fullname,is_active,created)/model');
// set model column values
$user->fullname = 'Shay Anderson';
$user->is_active = 1;
$user->created = ['NOW()']; // plain SQL as array value
// add model record
if($user->add())
{
	// now the insert ID is ready:
	$insert_id = $user->id;
}
else
{
	// warn failed to add user record
}
```

##### Modify Model Record
Modifying (updating) a model record is simple:
```php
// set model object and define model columns (required for update)
$user = xap('users(fullname,is_active)/model');
// set model record primary key value (required for update)
$user->id = 14; // update record with ID = 14
// set new model column values
$user->fullname = 'New Name';
$user->is_active = 1;
// update model record
if($user->save())
{
	// do something
}
else
{
	// warn failed to save user record
}
```

##### Delete Model Record
Deleting a model record is simple:
```php
$user = xap('users/model'); // set model object
// set model record primary key value (required for delete)
$user->id = 14; // delete record with ID = 14
// delete model record
if($user->delete())
{
	// do something
}
else
{
	// warn failed to delete user record
}
```

##### Model Record Exists
Checking if a model record exists is simple:
```php
$user = xap('users/model'); // set model object
// set model record primary key value (required for exists)
$user->id = 14; // does record with ID = 14 exist
// check if model record exists
if($user->exists())
{
	// do something
}
else
{
	// warn user record does not exist
}
```

##### Adding Query SQL
Query SQL can be added when setting the model object, for example:
```php
$user = xap('users/model WHERE is_active = 1'); // set model object with where clause
$user->id = 14; // set primary key column value (required)
// check if model record exists 'WHERE id = 14 AND is_active = 1'
if($user->exists())
{
	// active user with ID 14 exists
}
```
Also query params (*named* query params) can be used:
```php
$user = xap('users/model WHERE is_active = :active', ['active' => 1]);
```

> Query SQL rules:
- The `LIMIT` clause *cannot* be used with the model object
- Do *not* include the model primary key column name as a named parameter (in the query paramters) because it is automatically set by the model object
- Select with key like `users.14/model` will *not* work with the model object

##### Other Model Object Methods
Other useful `\Xap\Model` methods are:

- `getColumns()` - get model column names in array
- `getData()` - get the loaded model record data
- `getKey()` - get the model primary key column name
- `getTable()` - get the table name
- `isColumn()` - check if a column exists
- `isLoaded()` - check if the model record is loaded
- `setData()` - set the model record data

### Data Decorators
Data decorators can be used in Xap. First, ensure the `\Xap\Decorate` class is included in the `xap.bootstrap.php` file:
```php
require_once './lib/Xap/Decorate.php';
```
Here is a simple example of a data decorator using a select query:
```php
// SELECT * FROM users LIMIT 3
$decorated = xap('users LIMIT 3',
	'<tr><td>{$id}</td><td>{$fullname}</td><td>{$is_active}</td></tr>');
// $decorated is string
```
Now the decorated data can be printed as string:
```php
if(!empty($decorated))
{
	echo '<table>' . $decorated . '</table>';
}
else
{
	echo 'No records found';
}
```
Which will output something like:
```html
<table><tr><td>1</td><td>Shay Anderson</td><td>1</td></tr>
<tr><td>2</td><td>Mike Smith</td><td>1</td></tr>
<tr><td>3</td><td>John Smith</td><td>0</td></tr></table>
```
The above decorator can be improved using a *value switch* decorator which uses the logic `x?:y` where `x` is used for a *positive* value (`> 0` when numeric or `length > 0` when string) and `y` is used for a *negative* value, for example change the decorator:
```php
echo xap('users LIMIT 3', '{$id} - {$fullname} - {$is_active:Yes?:No}<br />');
```
Notice the `{$is_active:Yes?:No}` switch decorator. Now the output will be:
```html
1 - Shay Anderson - Yes<br />2 - Mike Smith - Yes<br />3 - John Smith - No<br />
```
> Decorators work for all commands except: `columns`, `commit`, `debug`, `key`, `log`, `pagination` (but will work with the [pagination select query](https://github.com/shayanderson/xap#decorators-with-pagination)), `rollback`, `tables`, `transaction`

##### Decorator Filters
Callable decorator filters can be used with decorators, for example:
```php
echo xap('users LIMIT 3', '{$id} - {$fullname:upper} - {$is_active:Yes?:No}<br />',
	['upper' => function($name) { return strtoupper($name); }]);
```
Notice the `{$fullname:upper}` where `upper` is the filter name, and in the array of filters the key `upper` is used to denote the filter by name. Now the output will be:
```html
1 - SHAY ANDERSON - Yes<br />2 - MIKE SMITH - Yes<br />3 - JOHN SMITH - No<br />
```
Or a callable filter can be used with the entire array (or row), for example the example above could be rewritten as:
```php
echo xap('users LIMIT 3', '{$id} - {$:upper} - {$is_active:Yes?:No}<br />',
	['upper' => function($row) { return strtoupper($row['fullname']); }]);
```
> When using decorate filters the array of callable filters must be passed to the Xap directly *after* the decorator string

When using decorate filter(s) with string values, like error decorators, the value passed to the callable filter is the value and not an array, for example:
```php
echo xap(':error_last', '<b>{$error:upper}</b>',
	['upper' => function($error) { return strtoupper($error); }]);
```

##### Test Decorators
*Test* decorators are used for commands like `add`, `count`, `del` `exists` and `mod` when the command returns a `boolean` or `integer` value and use the logic `x?:y` (where `x` is used when value is `true` or `integer > 0`, otherwise `y`), for example:
```php
echo xap('users:del WHERE user_id = ?', [122],
	'User has been deleted ?: Failed to delete user');
```
The value `User has been deleted` is displayed if the user exists and has been deleted, otherwise the value `Failed to delete user` is displayed.

##### Error Decorators
Error decorators can be used when error exceptions are turned off, for example:
```php
echo xap(':error', 'Error has occurred ?: No error occurred');
```
Or when display last error:
```php
if(xap(':error')) echo xap(':error_last', '<div class="error">{$error}</div>');
```

##### Decorators with Pagination
Decorators can be used with pagination, for example:
```php
$decorated = xap('users/pagination WHERE is_active = 1',
	'{$id} - {$fullname} - {$is_active:Yes?:No}<br />');

// display the decorated (and paginated) data:
echo $decorated['rows'];
```
> Pagination values are still available in `$decorated['pagination']`

Decorators can also be used for the pagination values `next_string` and `prev_string`, for example:
```php
// set decorators for 'next_string' and 'prev_string'
xap(':pagination', ['next_string' => '<a href="?pg={$next}">Next</a>',
	'prev_string' => '<a href="?pg={$prev}">Prev</a>'])

$data = xap('users/pagination');
```
Now when the value `$data['pagination']->next_string` is called and there is a next page it will output HTML like:
```html
<a href="?pg=2">Next</a>
```
And likewise for the value `$data['pagination']->prev_string`. If there is no *next* page then no value will be set, and same for the *previous* page value.

##### Decorators with Data Modeling
Decorators can be used with data modeling, for example:
```php
$user = xap('users/model', '{$id} - {$fullname} - {$is_active:Yes?:No}');
$user->load(14); // load (select) user record
echo $user; // display decorated data
```
This example would display something like:
```html
14 - Mike Smith - Yes
```

### Caching
Caching can be used to reduce database calls. First, ensure the `\Xap\Cache` class is included in the `xap.bootstrap.php` file and the cache settings are set:
```php
require_once './lib/Xap/Cache.php';
...
// set global cache expire time to 10 seconds (default is 30 seconds)
\Xap\Cache::setExpireGlobal('10 seconds');
// set global cache directory path for cache writes
\Xap\Cache::setPath('/var/www/app/cache');
```
> **Security Warning:** Do *not* use caching to store private data that should be stored in a secure database. Also, for Web applications the global cache directory path should be protected from public (external) requests using Web server configuration files.

Here is a simple example of caching using a select query and the `/cache` option:
```php
// SELECT title, sku, is_active FROM items LIMIT 10
$items = xap('items(title, sku, is_active)/cache LIMIT 10');
```
Now the recordset has been cached and will expire in 10 seconds (the global expire time). When the cache expires it will be rewritten with current data.

> Caching can be used for all different types of select commands and queries, but cannot be used with the `/model` option

To use a custom expire time for a single query (and *not* the global cache expire time) use:
```php
xap(':cache 1 hour'); // refresh cache every hour
$items = xap('items(title, sku, is_active)/cache LIMIT 10');
```
Now the recordset will expire every 1 hour.
> Cache expire times can be strings like: `1 hour` or `30 minutes` or `15 seconds`, or an integer for seconds like `25` would be converted to `25 seconds`. Also the cache expire time `never` can be used to allow a never expire cache.

A custom expire time is only used for a *single query*, for example:
```php
xap(':cache 1 hour'); // refresh cache every hour
$items = xap('items(title, sku, is_active)/cache LIMIT 10');
// $items_inactive cache expires in the global expire time and not in 1 hour
$items_inactive = xap('items(title, sku)/cache WHERE is_active = 0 LIMIT 10');
```
A custom cache key prefix can be used. This can be helpful when managing cache files with scripts. Here is an example:
```php
// set cache key prefix to 'item', cache key will be 'item-[cache key]'
\Xap\Cache::setCacheKeyPrefix('item');
// only this cache will use the 'item' cache key prefix
// it must be set again for any other caches
$items = xap('items(title, sku, is_active)/cache LIMIT 10');
```
> Custom cache key prefixes should only include word characters (`\w`), all other characters will be removed

Likewise, a custom cache key can be used, for example:
```php
// set cache key to 'my-custom-cache'
\Xap\Cache::setCacheKey('my-custom-cache');
```
> Custom cache keys can only include word characters (`\w`) and hyphens `-`

All cache files can be removed by using the command:
```php
\Xap\Cache::flush(); // flush all cache files
```
> By default Xap uses compression for cache files (requires PHP <a href="http://php.net/manual/en/book.zlib.php" target="_blank">Zlib functions</a>). To globally disable compression use:
```php
\Xap\Cache::$use_compression = false;
```