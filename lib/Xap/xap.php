<?php
/**
 * Xap - MySQL Rapid Development Engine for PHP 5.5+
 *
 * @package Xap
 * @version 0.0.6
 * @copyright 2014 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @link <https://github.com/shayanderson/xap>
 */

/**
 * Xap Engine helper function
 *
 * Commands:
 *		add			(also insert, insert new record)
 *		call		(call stored procedure or function)
 *		columns		(show table columns)
 *		commit		(commit transaction)
 *		count		(count table records)
 *		debug		(get debug info for connections)
 *		del			(also delete, delete record(s))
 *		error		(check if error has occurred)
 *		error_last	(get last error, when error has occurred)
 *		exists		(check if record exists)
 *		id			(get last insert ID)
 *		key			(get/set table primary key column name, default 'id')
 *		log			(get debug log, debugging must be turned on)
 *		mod			(also update, update record(s))
 *		pagination	(get/set pagination params)
 *		query		(execute manual query)
 *		replace		(replace record)
 *		rollback	(rollback transaction)
 *		tables		(show database tables)
 *		transaction	(start transaction)
 *
 * @author Shay Anderson 07.14 <http://www.shayanderson.com/contact>
 *
 * @param string $cmd
 * @param mixed $_ (optional values)
 * @return mixed
 * @throws \Exception
 *
 */
function xap($cmd, $_ = null)
{
	return \Xap\Engine::exec(func_get_args());
}