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

	public function addTransaction(Transaction $transaction)
	{
		$sql = "INSERT INTO transaction (`tra_id`, `_usr_id`, `tra_currencyamount`, `tra_verifier`) VALUES (:b_tra_id, :b_usr_id, :b_tra_currencyamount, :b_tra_verifier)";
		
		$stmt = $this->_db->prepare($sql);
		$stmt->bindValue(':b_tra_id', $transaction->tra_id, PDO::PARAM_INT);
		$stmt->bindValue(':b_usr_id', $transaction->_usr_id, PDO::PARAM_INT);
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
					throw new TransactionException(sprintf(TransactionException::MESSAGE_TRANSACTION_ALREADY_EXISTS, $transaction->tra_id), TransactionException::CODE_TRANSACTION_ALREADY_EXISTS, $e);
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

	public function getTransactionStatsByUserId($_usr_id)
        {
		$sql = "SELECT 
			_usr_id AS UserId, 
			COUNT(tra_id) AS TransactionCount, 
			SUM(tra_currencyamount) AS CurrencySum
			FROM transaction TRA
			WHERE TRA._usr_id = :b_usr_id
			GROUP BY TRA._usr_id";

                $stmt = $this->_db->prepare($sql);
		$stmt->bindValue(':b_usr_id', $_usr_id, PDO::PARAM_INT);
		$stmt->execute();
		$res = $stmt->fetchAll( PDO::FETCH_ASSOC );
		if(count($res) > 0) {
			// If user has at least one transaction
			// PDO stringify integers !*$^m!
			// TODO Find a way to natively force PDO to NOT stringify intergers
			$res[0]['TransactionCount'] = (int)$res[0]['TransactionCount'];
			$res[0]['CurrencySum'] = (int)$res[0]['CurrencySum'];

			return $res[0];
		} else {
			// If user has not trasaction
			// Throw TransactionException
			throw new TransactionException(sprintf(TransactionException::MESSAGE_USER_NO_TRANSACTION, $_usr_id), TransactionException::CODE_USER_NO_TRANSACTION);		
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
