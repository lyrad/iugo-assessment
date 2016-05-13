<?php
namespace App\Model\Entity;

class Transaction
{
	private $tra_id;

	private $_usr_id;

	private $tra_currencyamount;

	private $tra_verifier;

	public function __construct($tra_id, $_usr_id, $tra_currencyamount, $tra_verifier)
	{
		$this->tra_id = (int)$tra_id;
		$this->_usr_id = (int)$_usr_id;
		$this->tra_currencyamount = (int)$tra_currencyamount;
		$this->tra_verifier = $tra_verifier;
	}
	
	public function __toString()
	{
		return $this->tra_id . $this->_usr_id . $this->tra_currencyamount;
	}

	public function __get($name)
	{
		return $this->$name;
	} 
}
