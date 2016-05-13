<?php
namespace App\Model\Repository;

use PDO;
use PDOException;
use App\Model\Entity\User;
use App\Model\Exception\UserException;

class UserRepository
{
	private $_db;

	public function __construct(PDO $db)
	{
		$this->_db = $db;
	}

	public function getUserById($usr_id)
        {
                $sql = "SELECT *
                FROM user USR
                LEFT JOIN userdata DAT ON DAT._usr_id = USR.usr_id
                WHERE USR.usr_id = :b_usr_id";

                $stmt = $this->_db->prepare($sql);
                $stmt->bindValue(':b_usr_id', $usr_id, PDO::PARAM_INT);
                $stmt->execute();
                $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// If user not found
                if(count($res) === 0){
			// throw UserException
                        throw new UserException(sprintf(UserException::MESSAGE_NOT_FOUND, $usr_id), UserException::CODE_NOT_FOUND);
                } else {
			// Create new user object
                        $user = new User($usr_id);
			// add userdata to user object
                        foreach($res as $data) {
                                if($data['usrd_id'] != null) {
					try {
                                        	$user->addData($data['usrd_id'], unserialize($data['data']));
					} catch(UserException $e) {
						// Userdata can't be added to user object
						// Can't happen here (duplicated userdata can't be persisted in database)
						// Do nothing
					}
                                }
                        }
                        return $user;
                }
        }

	public function addUser(User $user)
	{
		// INSERT USER
		$sql = 'INSERT INTO user (`usr_id`) VALUES (:b_usr_id)';
		
		try {
			$stmt = $this->_db->prepare($sql);	
			$stmt->bindValue(':b_usr_id', $user->usr_id, PDO::PARAM_INT);
			$stmt->execute();
		} catch(PDOException $e) {
			// Depending on driver error code
                        // TODO driver error codes in constants somewhere?
                        switch($e->errorInfo[1]) {
				case 1062:
                                        // Primary key constraint failed
                                        // Throw "already exist user" Exception
                                        throw new UserException(UserException::MESSAGE_EXISTS, UserException::CODE_EXISTS, $e);
                                        break;
				default:
					// Other database error
					// Rethrow Exception
					throw $e;
			}
		}

		// INSERT USERDATA
		foreach($user->data as $data_key => $data) {
			try {
				$this->addUserdata($usr_id, $data_key, $data);
			} catch(UserException $e) {
				// Depending on Exception code
				switch($e->getCode) {
					case UserException::CODE_NOT_FOUND:
						// User not found (not suppose to happen, just added...)
						// Rethrow UserException
						throw $e;
						break;
					case UserException::CODE_DATA_EXISTS:
						// Userdata already exists for this user
						// Update user data
						try {
							$this->updateUserdata($usr_id, $data_key, $data);
						} catch (UserException $e) {
							// Userdata can't be found or user do not exists
							// Not supose to happend (user just added, data exists) 
							// Do nothing	
						}
						break;
					default:
						// Other UserException
						// Rethrow UserException
						throw $e;
				}
			}
		}
	}
	
	public function updateUser(User $user)
	{
		// In this project user has only one attribute : his primary key which cannot change.
		// So, no need to update user itself...	

		// Update each userdata
		foreach($user->data as $data_key => $data) {
			try {
				$this->updateUserdata($user->usr_id, $data_key, $data);
			} catch(UserException $e) {
				// Depending on the exception code
				switch($e->getCode()) {
					case UserException::CODE_DATA_EXISTS:
						// Userdata has not changed
						// Do nothing
						break;
					case UserException::CODE_DATA_NOT_EXISTS:
						// Userdata not exists
						// Add Userdata
						try {
							$this->addUserdata($user->usr_id, $data_key, $data);
						} catch(UserdataException $e) {
							// Depending on UserException code
							switch($e->getCode()) {
								case UserException::CODE_NOT_FOUND:
								case UserException::CODE_DATA_EXISTS:
								default:
									// User must exist, userdata cannot not exist...
									// rethrow UserException
									throw $e;
							}
						}
						break;
					default:
						throw $e;
				
				}	
			}	
		}

		//return		
	}

	public function addUserdata($usr_id, $data_key, $data)
	{
		$sql = 'INSERT INTO userdata (`usrd_id`, `_usr_id`, `data`) VALUES (:b_usrd_id, :b_usr_id, :b_data)';
		
		try {
			$stmt = $this->_db->prepare($sql);
			$stmt->bindValue(':b_usrd_id', $data_key);
			$stmt->bindValue(':b_usr_id', $usr_id, PDO::PARAM_INT);
			$stmt->bindValue(':b_data', serialize($data));
			return $stmt->execute();
		} catch(PDOException $e) {
			// Depending on driver error code ($e->getCode() => SQLSTATE not accurate enought : 23000 => a constraint failed...)
			// TODO driver error codes in constants somewhere?
			switch($e->errorInfo[1]) {
				case 1062:
					// Primary key constraint failed
					// Throw "already exist userdata" Exception
					throw new UserException(sprintf(UserException::MESSAGE_DATA_EXISTS, $data_key, $usr_id), UserException::CODE_DATA_EXISTS, $e);
					break;
				case 1452:
					// Foreign key constraint failed
					// Throw "not exists user" Exception
					throw new UserException(sprintf(UserException::MESSAGE_NOT_FOUND, $usr_id), UserException::CODE_NOT_FOUND, $e);
					break;
				default:
					// Other database Exception
					// Rethrow Exception
					throw $e;
			}
		}
	}

	public function updateUserdata($usr_id, $data_key, $data)
	{
		$sql = 'UPDATE userdata USRD SET `data` = :b_data WHERE USRD.usrd_id = :b_usrd_id AND USRD._usr_id = :b_usr_id';
		
		$stmt = $this->_db->prepare($sql);
		$stmt->bindValue(':b_usrd_id', $data_key);
                $stmt->bindValue(':b_usr_id', $usr_id, PDO::PARAM_INT);
                $stmt->bindValue(':b_data', serialize($data));
		$stmt->execute();
		// If userdata was updated
		if($stmt->rowCount() === 1) {
			return $stmt->rowCount();
		} else {
			// Update failed : data was the same or userdata cannot be found
			// TODO How to know the difference? Find an other way to new select request...
			$sql = 'SELECT COUNT(*) FROM userdata USRD WHERE USRD.usrd_id = :b_usrd_id AND USRD._usr_id = :b_usr_id';
			$stmt = $this->_db->prepare($sql);
	                $stmt->bindValue(':b_usrd_id', $data_key);
        	        $stmt->bindValue(':b_usr_id', $usr_id, PDO::PARAM_INT);
        	        $stmt->execute();
			$res = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
			
			if($res[0] > 0) {
				// if userdata exists => userdata wasnot updated because the same
				throw new UserException(sprintf(UserException::CODE_DATA_EXISTS, $data_key, $usr_id), UserException::CODE_DATA_EXISTS);	
			} else {
				// if userdata do not exist => userdata wasnot updated because not exeists
				throw new UserException(sprintf(UserException::MESSAGE_DATA_NOT_EXISTS, $data_key, $usr_id), UserException::CODE_DATA_NOT_EXISTS);	
			}		
		}
	}


	public function removeUserdataAll()
        {
                $sql = "DELETE FROM userdata";
                $stmt = $this->_db->prepare($sql);
                $stmt->execute();
		return $stmt->rowCount();
        }

	public function removeAll()
        {
                $sql = "DELETE FROM user";
                $stmt = $this->_db->prepare($sql);
                $stmt->execute();
		return $stmt->rowCount();
        }
}
