# Xap
#### MySQL Rapid Development Engine for PHP 5.5.0+
Xap requirements:

1. PHP 5.5.0+
2. PHP [PDO database extension](http://www.php.net/manual/en/book.pdo.php)
3. Database table names cannot include characters `.`, `/`, `:` or ` ` (whitespace) and cannot start with `[`

Here is a list of Xap commands:

- [`add`](https://github.com/shayanderson/xap#insert) - insert record (can also use `insert`)
- [`call`](https://github.com/shayanderson/xap#call-stored-procedurefunction-routines) - call stored procedure or function
- [`columns`](https://github.com/shayanderson/xap#show-table-columns) - show table columns
- [`commit`](https://github.com/shayanderson/xap#transactions) - commit transaction
- [`count`](https://github.com/shayanderson/xap#count-query) - count table records
- [`debug`](https://github.com/shayanderson/xap#debugging) - get debug info for connections
- [`del`](https://github.com/shayanderson/xap#delete) - delete record(s) (can also use `delete`)
- [`error`](https://github.com/shayanderson/xap#error-checking) - check if error has occurred
- [`error_last`](https://github.com/shayanderson/xap#get-last-error) - get last error, when error has occurred
- [`id`](https://github.com/shayanderson/xap#insert-with-insert-id) - get last insert ID
- [`key`](https://github.com/shayanderson/xap#custom-table-primary-key-column-name) - get/set table primary key column name (default 'id')
- [`log`](https://github.com/shayanderson/xap#debug-log) - get debug log (debugging must be turned on)
- [`mod`](https://github.com/shayanderson/xap#update) - update record(s) (can also use `update`)
- [`pagination`](https://github.com/shayanderson/xap#pagination) - get/set pagination params
- [`query`](https://github.com/shayanderson/xap#execute-query) - execute manual query
- [`replace`](https://github.com/shayanderson/xap#insert) - replace record
- [`rollback`](https://github.com/shayanderson/xap#transactions) - rollback transaction
- [`tables`](https://github.com/shayanderson/xap#show-tables) - show database tables
- [`transaction`](https://github.com/shayanderson/xap#transactions) - start transaction

Xap also supports:

- [Custom primary key name](https://github.com/shayanderson/xap#custom-table-primary-key-column-name)
- [Custom log handler](https://github.com/shayanderson/xap#custom-log-handler)
- [Custom error handler](https://github.com/shayanderson/xap#custom-error-handler)
- [Query options](https://github.com/shayanderson/xap#query-options)
- [Multiple database connections](https://github.com/shayanderson/xap#multiple-database-connections)
- [Pagination](https://github.com/shayanderson/xap#pagination)
- [Data Modeling (ORM)](https://github.com/shayanderson/xap#data-modeling)

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
	'errors' => true, // true: throw Exceptions, false: no Exceptions, use error methods
	'debug' => true // turn logging on/off
```

Next, include the bootstrap file in your project file:
```php
require_once './xap.bootstrap.php';
```

Now execute SELECT query:
```php
try
{
	$user = xap('users.14'); // same as "SELECT * FROM users WHERE id = '14'"
	if($user) echo $user->fullname; // print record field value
}
catch(\Exception $ex)
{
	// warn here
}
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
// SELECT fullname, email FROM users WHERE is_active = '1' AND fullname = 'Shay Anderson'
$r = xap('users(fullname, email) WHERE is_active = :active AND fullname = :name LIMIT 2',
	['active' => 1, 'name' => 'Shay Anderson']);
```
Select query with question mark parameters:
```php
// SELECT fullname, email FROM users WHERE is_active = 1 AND fullname = 'Shay Anderson' LIMIT 2
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
$r = xap('users(fullname, is_active).2 WHERE fullname = ? LIMIT 1', ['Name']);
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
>The default primary key column name is `id`, for using different primary key column name see [custom table primary key column name](https://github.com/shayanderson/xap#custom-table-primary-key-column-name)

#### Select Distinct
Select distinct example query:
```php
$r = xap('users(fullname)/distinct'); // SELECT DISTINCT fullname FROM users
```

#### Insert
Simple insert example:
```php
// INSERT INTO users (fullname, is_active, created) VALUES('Name Here', '1', NOW())
$affected_rows = xap('users:add', ['fullname' => 'Name Here', 'is_active' => 1,
	'created' => ['NOW()']]);

// can also use action ':insert'
// xap('users:insert', ...);
```
The `replace` command can also be used, for example:
```php
// REPLACE INTO users (id, fullname, is_active, created) VALUES(5, 'Name Here', '1', NOW())
$affected_rows = xap('users:replace', ['id' => 5 'fullname' => 'Name Here',
	'is_active' => 1, 'created' => ['NOW()']]);
```

#### Insert with Insert ID
Insert query and get insert ID:
```php
// INSERT INTO users (fullname, is_active, created) VALUES('Name Here', '1', NOW())
xap('users:add', ['fullname' => 'Name Here', 'is_active' => 1, 'created' => ['NOW()']]);

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
$affected_rows = xap('users:mod WHERE user_id = :user_id', ['fullname' => 'Shay Anderson'],
	['user_id' => 2]);

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

#### Call Stored Procedure/Function (Routines)
Call SP/SF example:
```php
xap(':call sp_name'); // CALL sp_name()

// Call SP/SF with params:
// CALL sp_addUser('Name Here', '1', NOW())
xap(':call sp_addUser', 'Name Here', 1, ['NOW()']);

// Call SP/SF with params and out param
xap(':query SET @out = "";'); // set out param
// CALL sp_addUser('Name Here', '1', NOW(), @out)
xap(':call sp_addUserGetId', 'Name Here', 1, ['NOW()'], ['@out']);
// get out param value
$r = xap(':query SELECT @out;');
```

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
Check if error has occurred example:
```php
if(xap(':error'))
{
	// do something
}
```
> For error checking errors must be disabled, otherwise exception is thrown

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

##### Query Option
The `query` option can be used to return the query string only, without executing the query (for debugging), for example:
```php
$r = xap('users(fullname)/distinct/query'); // returns string 'SELECT DISTINCT fullname FROM users'
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
> Other options not mentioned here are: [`/pagination`](https://github.com/shayanderson/xap#pagination) and [`/model`](https://github.com/shayanderson/xap#data-modeling)

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
// $r['pagination'] contains pagination values: rpp, page, next, prev, offset
// $r['rows'] contains selected rows
```
> Pagination only works on select commands like `users(id, fullname)/pagination` and will *not* work on other commands like `:query/pagination SELECT id, fullname FROM users`

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
**Note**: if column name are *not* defined they are automatically set by the model object, meaning that an update following a load (select) will update all the automatically loaded columns instead of defined columns (which is recommended)

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
$user = xap('users)/model'); // set model object
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
$user = xap('users)/model'); // set model object
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
$user = xap('users)/model WHERE is_active = 1'); // set model object with where clause
$user->id = 14; // set primary key column value (required)
// check if model record exists 'WHERE id = 14 AND is_active = 1'
if($user->exists())
{
	// active user with ID 14 exists
}
```
Also query params (*named* query params) can be used:
```php
$user = xap('users)/model WHERE is_active = :active', ['active' => 1]);
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