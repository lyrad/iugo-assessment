<?php
namespace App\Model\Repository;

use App\Model\Entity\Userdata;
use App\Model\Exception\UserdataException;
use App\Model\Entity\User;
use App\Model\Exception\UserException;
use PDO;
use PDOException;

class UserdataRepository
{
        private $_db;

        public function __construct(PDO $db)
        {
                $this->_db = $db;
        }
	
	public function getUserdataByUserId($usr_id)
        {
                $sql = "SELECT * FROM userdata USRD
                INNER JOIN user USR ON USR.usr_id = USRD._usr_id
                WHERE USRD._usr_id = :b_usr_id";
                $stmt = $this->_db->prepare($sql);
                $stmt->bindValue(':b_usr_id', $usr_id, PDO::PARAM_INT);
                $stmt->execute();
                $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $return = array();
                foreach($res as $userdata_array) {
                        $return[$userdata_array['usrd_id'] . $userdata_array['_usr_id']] = new Userdata($userdata_array['usrd_id'], new User($usr_id), unserialize($userdata_array['data']));
                }
                return $return;
        }

	public function addUserdata(Userdata $userdata)
        {
                $sql = 'INSERT INTO userdata (`usrd_id`, `_usr_id`, `data`) VALUES (:b_usrd_id, :b_usr_id, :b_data)';

                try {
                        $stmt = $this->_db->prepare($sql);
                        $stmt->bindValue(':b_usrd_id', $userdata->usrd_id);
                        $stmt->bindValue(':b_usr_id', $userdata->user->usr_id, PDO::PARAM_INT);
                        $stmt->bindValue(':b_data', serialize($userdata->data));
                        return $stmt->execute();
                } catch(PDOException $e) {
                        // Depending on driver error code ($e->getCode() => SQLSTATE not accurate enought : 23000 => a constraint failed...)
                        // TODO driver error codes in constants somewhere?
                        switch($e->errorInfo[1]) {
                                case 1062:
                                        // Primary key constraint failed
                                        // Throw "already exist userdata" Exception
                                        throw new UserException(sprintf(UserException::MESSAGE_DATA_EXISTS, $userdata->usrd_id, $userdata->user->usr_id), UserException::CODE_DATA_EXISTS, $e);
                                        break;
                                case 1452:
                                        // Foreign key constraint failed
                                        // Throw "not exists user" Exception
                                        throw new UserException(sprintf(UserException::MESSAGE_NOT_FOUND, $userdata->user->usr_id), UserException::CODE_NOT_FOUND, $e);
                                        break;
                                default:
                                        // Other database Exception
                                        // Rethrow Exception
                                        throw $e;
                        }
                }
        }

	public function updateUserdata(Userdata $userdata)
        {
                $sql = 'UPDATE userdata USRD SET `data` = :b_data WHERE USRD.usrd_id = :b_usrd_id AND USRD._usr_id = :b_usr_id';

                $stmt = $this->_db->prepare($sql);
                $stmt->bindValue(':b_usrd_id', $userdata->usrd_id);
                $stmt->bindValue(':b_usr_id', $userdata->user->usr_id, PDO::PARAM_INT);
                $stmt->bindValue(':b_data', serialize($userdata->data));
                $stmt->execute();
                // If userdata was updated
                if($stmt->rowCount() === 1) {
                        return $stmt->rowCount();
                } else {
                        // Update failed : data was the same or userdata cannot be found
                        // TODO How to know the difference? Find an other way to new select request...
                        $sql = 'SELECT COUNT(*) FROM userdata USRD WHERE USRD.usrd_id = :b_usrd_id AND USRD._usr_id = :b_usr_id';
                        $stmt = $this->_db->prepare($sql);
                        $stmt->bindValue(':b_usrd_id', $userdata->usrd_id);
                        $stmt->bindValue(':b_usr_id', $userdata->user->usr_id, PDO::PARAM_INT);
                        $stmt->execute();
                        $res = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

                        if($res[0] > 0) {
                                // if userdata exists => userdata wasnot updated because the same
                                throw new UserException(sprintf(UserException::CODE_DATA_EXISTS, $userdata->usrd_id, $userdata->user->usr_id), UserException::CODE_DATA_EXISTS);
                        } else {
                                // if userdata do not exist => userdata wasnot updated because not exeists
                                throw new UserException(sprintf(UserException::MESSAGE_DATA_NOT_EXISTS, $userdata->usrd_id, $userdata->user->usr_id), UserException::CODE_DATA_NOT_EXISTS);
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


}
