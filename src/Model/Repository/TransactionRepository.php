<?php
namespace App\Model\Repository;

use App\Model\Entity\Transaction;
use App\Model\Exception\TransactionException;
use App\Model\Entity\User;
use App\Model\Exception\UserException;
use PDO;
use PDOException;

class TransactionRepository
{
	private $_db;

	public function __construct(PDO $db)
	{
		$this->_db = $db;	
	}

	public function getTransactionByUserId($usr_id)
	{
		$sql = "SELECT * FROM transaction TRA 
		INNER JOIN user USR ON USR.usr_id = TRA._usr_id
		WHERE TRA._usr_id = :b_usr_id";
		$stmt = $this->_db->prepare($sql);
		$stmt->bindValue(':b_usr_id', $usr_id, PDO::PARAM_INT);
		$stmt->execute();
		$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$return = array();
		foreach($res as $transaction_array) {
			$return[$transaction_array['tra_id']] = new Transaction($transaction_array['tra_id'], new User($usr_id),$transaction_array['tra_currencyamount'], $transaction_array['tra_verifier']);
		}
		return $return;		
	}

	public function addTransaction(Transaction $transaction)
	{
		$sql = "INSERT INTO transaction (`tra_id`, `_usr_id`, `tra_currencyamount`, `tra_verifier`) VALUES (:b_tra_id, :b_usr_id, :b_tra_currencyamount, :b_tra_verifier)";
		
		$stmt = $this->_db->prepare($sql);
		$stmt->bindValue(':b_tra_id', $transaction->tra_id, PDO::PARAM_INT);
		$stmt->bindValue(':b_usr_id', $transaction->user->usr_id, PDO::PARAM_INT);
		$stmt->bindValue(':b_tra_currencyamount', $transaction->tra_currencyamount, PDO::PARAM_INT);
		$stmt->bindValue(':b_tra_verifier', $transaction->tra_verifier);

		try {
			return $stmt->execute();		
		} catch(PDOException $e) {
			// Depending on driver error code ($e->getCode() => SQLSTATE not accurate enought : 23000 => a constraint failed...)
                        // TODO driver error codes in constants somewhere?
			switch($e->errorInfo[1]) {
				case 1062:
					// Primary key constraint failed
                                        // Throw "already exist transaction" Exception
					throw new TransactionException(sprintf(TransactionException::MESSAGE_EXISTS, $transaction->tra_id), TransactionException::CODE_EXISTS, $e);
					break;
				case 1452:
					// Foreign key constraint failed
                                        // Throw "not exists user" Exception
					throw new TransactionException(sprintf(TransactionException::MESSAGE_USER_NOT_FOUND, $transaction->_usr_id), TransactionException::CODE_USER_NOT_FOUND, $e);
					break;
				default:
					// Other database Exception
                                        // Rethrow Exception
					throw $e;
			}
		}
	}

	public function removeAll()
        {
                $sql = "DELETE FROM transaction";
                $stmt = $this->_db->prepare($sql);
                $stmt->execute();
		return $stmt->rowCount();
        }
}
