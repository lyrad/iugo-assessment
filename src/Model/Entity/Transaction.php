<?php
namespace App\Model\Entity;

use App\Model\Entity\User;
use App\Model\Entity\UserException;
use App\Model\Exception\TransactionException;

class Transaction
{
	private $tra_id;

	private $user;

	private $tra_currencyamount;

	private $tra_verifier;

	public function __construct($tra_id, User $user, $tra_currencyamount, $tra_verifier)
	{
		$this->tra_id = (int)$tra_id;
		$this->user = $user;
		$this->tra_currencyamount = (int)$tra_currencyamount;
		$this->tra_verifier = $tra_verifier;
	}

	public function checkVerifier($api_key)
	{
		if( sha1($api_key . $this->tra_id . $this->user->usr_id . $this->tra_currencyamount) != $this->tra_verifier ) {
                	throw new TransactionException(TransactionException::MESSAGE_WRONG_VERIFIER, TransactionException::CODE_WRONG_VERIFIER);
                } else {
			return true;
		}
	}
	
	public function __get($name)
	{
		return $this->$name;
	} 
}
