<?php
namespace App\Model\Entity;

use App\Model\Exception\UserException;
use App\Model\Entity\Transaction;
use App\Model\Exception\TransactionException;

class User 
{
	private	$usr_id;

	private $data;
	
	private $transactions;

	public function __construct($usr_id) 
	{
		$this->usr_id = $usr_id;
		$this->data = array();
		$this->transactions = array();
	}

	public function addTransaction(Transaction $transaction) {
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

	public function addData($data_key, $data) {
		// If data already exists
		if(true === isset($this->data[$data_key])) {
			// Throw UserException data can't be added
			throw UserException(sprintf(UserException::MESSAGE_DATA_EXISTS, $data_key, $this->usr_id), UserException::CODE_DATA_EXISTS);	
		} else {
			// Update/Create data
			$this->data[$data_key] = $data;
		}
	}

	public function updateData($data_key, $data) {
		// If data doesn't exist
		if(false === isset($this->data[$data_key])) {
			// Throw UserException data does not exists
			throw new UserException(sprintf(UserException::MESSAGE_DATA_NOT_EXISTS, $data_key, $this->usr_id), UserException::CODE_DATA_NOT_EXISTS);
		} else {
			$this->data[$data_key] = $data;
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
