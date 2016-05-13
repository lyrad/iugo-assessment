<?php
namespace App\Model\Exception;

class TransactionException extends \Exception
{
	const CODE_WRONG_VERIFIER = 1001;
	const MESSAGE_WRONG_VERIFIER = "Verifier error. Please check your API key.";

	const CODE_TRANSACTION_ALREADY_EXISTS = 1002;
	const MESSAGE_TRANSACTION_ALREADY_EXISTS = 'Transaction (id: %d) was already recorded.';

	const CODE_USER_NOT_FOUND = 1003;
	const MESSAGE_USER_NOT_FOUND = "Transaction's user (id: %d) cannot be found.";

	const CODE_USER_NO_TRANSACTION = 1004;
	const MESSAGE_USER_NO_TRANSACTION = "No transaction was found for the user (id: %d).";
}

