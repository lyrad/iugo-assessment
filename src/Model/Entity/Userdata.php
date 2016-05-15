<?php
namespace App\Model\Entity;

use App\Model\Entity\User;
use App\Model\Exception\UserdataException;

class Userdata implements \JsonSerializable 
{
	private $ursd_id;

	private $user;

	private $data;

	public function __construct($usrd_id, User $user, array $data)
	{
		$this->usrd_id = $usrd_id;
		$this->user = $user;
		$this->data = $data;
	}

	public function jsonSerialize() 
	{
        	return (object) $this->data;
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
