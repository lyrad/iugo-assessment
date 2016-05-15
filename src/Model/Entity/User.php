<?php
namespace App\Model\Entity;

use App\Model\Exception\UserException;
use App\Model\Entity\Transaction;
use App\Model\Exception\TransactionException;
use App\Model\Entity\Userdata;
use App\Model\Exception\UserdataException;

class User 
{
	private	$usr_id;

	private $userdata;
	
	private $transactions;

	public function __construct($usr_id) 
	{
		$this->usr_id = $usr_id;
		$this->userdata = array();
		$this->transactions = array();
	}

	public function addTransaction(Transaction $transaction) {
		// @TODO Should check if user transition Object is same id than this?
		
		// If transition doesn't already exist
		if(false === isset($this->transactions[$transaction->tra_id])) {
			$this->transactions[$transaction->tra_id] = $transaction;
		} else {
			throw new TransactionException(sprintf(TransactionException::MESSAGE_EXISTS, $transaction->tra_id),TransactionException::CODE_EXISTS );
		}
	}
	
	public function getTransactionsCount()
	{
		return count($this->transactions);
	}

	public function getTransactionsCurrencyamountSum()
	{
		$sum = 0;
		foreach($this->transactions as $transaction){
			$sum += $transaction->tra_currencyamount;
		}
		return $sum;
	}

	public function addUserdata(Userdata $userdata) {
		// @TODO Should check if user userdata Object is same id than this?

		// If userdata already exists
		if(true === isset($this->userdata[$userdata->usrd_id])) {
			// Throw UserException userdata can't be added
			throw UserException(sprintf(UserException::MESSAGE_DATA_EXISTS, $userdata->usrd_id . $this->usr_id, $this->usr_id), UserException::CODE_DATA_EXISTS);	
		} else {
			// Update/Create userdata
			$this->userdata[$userdata->usrd_id] = $userdata;
		}
	}

	public function updateUserdata(Userdata $userdata) {
		// If userdata doesn't exist
		if(false === isset($this->userdata[$userdata->usrd_id])) {
			// Throw UserException data does not exists
			throw new UserdataException(sprintf(UserException::MESSAGE_DATA_NOT_EXISTS, $userdata->usrd_id, $this->usr_id), UserException::CODE_DATA_NOT_EXISTS);
		} else {
			$this->userdata[$userdata->usrd_id] = $userdata;
		}	
	}

	public function __get($attribute) 
	{
		return $this->$attribute;
	}
	
	public function __set($attribute, $value)
	{
		$this->$attribute = $value;
	}
}
