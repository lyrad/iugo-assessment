<?php
namespace App\Model\Exception;

class UserException extends \Exception
{
	const CODE_NOT_FOUND = 2001;
	const MESSAGE_NOT_FOUND = 'User (id: %d) cannot be found.';

	const CODE_EXISTS = 2002;
	const MESSAGE_EXISTS = 'User (id: %d) already exists.';

	const CODE_DATA_EXISTS = 2003;
	const MESSAGE_DATA_EXISTS = "Data (id: %s) already exists for user (id: %s).";

	const CODE_DATA_NOT_EXISTS = 2004;
	const MESSAGE_DATA_NOT_EXISTS = "Data (id: %s) does not exist for user (id: %s).";
}
