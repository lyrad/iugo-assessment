<?php
namespace App\Model\Repository;

use PDO;
use PDOException;
use App\Model\Entity\User;
use App\Model\Exception\UserException;
use App\Model\Entity\Userdata;
use App\Model\Exception\UserdataException;
use App\Model\Repository\UserdataRepository;
use App\Model\Entity\Transaction;
use App\Model\Repository\TransactionRepository;
use App\Model\Exception\TransactionException;

class UserRepository
{
	private $_db;

	private $transactionRepository;
	
	private $userdataRepository;

	public function __construct(PDO $db)
	{
		$this->_db = $db;
		
		$this->transactionRepository = new TransactionRepository($db);
		$this->userdataRepository = new UserdataRepository($db);
	}

	public function getUserById($usr_id)
        {
                $sql = "SELECT *
                FROM user USR
                WHERE USR.usr_id = :b_usr_id";

                $stmt = $this->_db->prepare($sql);
                $stmt->bindValue(':b_usr_id', $usr_id, PDO::PARAM_INT);
                $stmt->execute();
                $res_user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// If user not found
                if(count($res_user) === 0){
			// throw UserException
                        throw new UserException(sprintf(UserException::MESSAGE_NOT_FOUND, $usr_id), UserException::CODE_NOT_FOUND);
                } else {
			// Create new user object
                        $user = new User($usr_id);
		
			// Get User's Userdata
			$res_userdata = $this->userdataRepository->getUserdataByUserId($usr_id);
			// Add userdatas to user object
			foreach($res_userdata as $userdata) {
				try {
                                	$user->addUserdata($userdata);
				} catch(UserException $e) {
					// Userdata can't be added to user object
					// Can't happen here (duplicated userdata can't be persisted in database)
					// Do nothing (don't add userdata to User object)
                                }
                        }

			// Get User's Transitions 
			$res_transaction = $this->transactionRepository->getTransactionByUserId($usr_id);
			// Add transactions to user
			foreach($res_transaction as $transaction) {
				try {
					$user->addTransaction($transaction);
				} catch (TransactionException $e) {
					// Transaction can't be added to user object
                                        // Can't happen here (duplicated transaction can't be persisted in database)
                                        // Do nothing (don't add transaction to UserObject)
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
		foreach($user->userdata as $userdata) {
			try {
				$this->addUserdata($userdata);
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
							$this->updateUserdata($userdata);
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
		foreach($user->userdata as $userdata) {
			try {
				$this->userdataRepository->updateUserdata($userdata);
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
							$this->userdataRepository->addUserdata($userdata);
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

		return true;		
	}

	public function removeAll()
        {
                $sql = "DELETE FROM user";
                $stmt = $this->_db->prepare($sql);
                $stmt->execute();
		return $stmt->rowCount();
        }
}
