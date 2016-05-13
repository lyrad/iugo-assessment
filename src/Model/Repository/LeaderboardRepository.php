<?php
namespace App\Model\Repository;

use PDO;
use PDOException;
use App\Model\Entity\Leaderboard;
use App\Model\Exception\LeaderboardException;

class LeaderboardRepository
{
        private $_db;

        public function __construct(PDO $db)
        {
                $this->_db = $db;
        }
	
	public function getMaxUserScore($lea_id)
	{
		$sql = "SELECT MAX(score) AS max_score
			FROM lea_usr SCO
			WHERE SCO._lea_id = :b_lea_id";

		$stmt = $this->_db->prepare($sql);
		$stmt->bindValue(':b_lea_id', $lea_id, PDO::PARAM_INT);
                $stmt->execute();
		$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if(count($res) === 0) {
			throw new LeaderboardException(LeaderboardException::MESSAGE_USER_NOT_FOUND, LeaderboardException::CODE_USER_NOT_FOUND);
		} else {
			return $res[0]['max_score'];	
		}
	}

	public function addUserScore($lea_id, $usr_id, $score)
        {
                $sql = "INSERT INTO lea_usr (`_lea_id`, `_usr_id`, `score`) VALUES (:b_lea_id, :b_usr_id, :b_score)";

		$stmt = $this->_db->prepare($sql);
		$stmt->bindValue(':b_lea_id', $lea_id, PDO::PARAM_INT);
		$stmt->bindValue(':b_usr_id', $usr_id, PDO::PARAM_INT);
		$stmt->bindValue(':b_score', $score, PDO::PARAM_INT);
		
		try {
			return $stmt->execute();
		} catch(PDOException $e) {
			switch($e->errorInfo[1]) {
				case 1452:
					// @TODO two constraints can fail here. Exception code (SQLSTATE) and Driver error code can't make the difference.
					// Using Exception error message, waiting a proper solution
					
					// If 'REFERENCES `leaderboard` (`lea_id`)' is in Exception error message
					if(false === strpos($e->getMessage(), 'REFERENCES `leaderboard` (`lea_id`)')) {
						// Leaderboard cannot be found
						throw new LeaderboardException(sprintf(LeaderboardException::MESSAGE_NOT_FOUND, $lea_id), LeaderboardException::CODE_NOT_FOUND, $e);
					} else {
						// User cannot be found
						throw new UserException(sprintf(UserException::MESSAGE_NOT_FOUND, $lea_id), UserException::CODE_NOT_FOUND, $e);
					}	
					break;
				default:
					throw $e;	
			}
		}
        }

	public function getLeaderboardById($lea_id)
	{
		$sql = "SELECT *
		FROM leaderboard LEA
		LEFT JOIN lea_usr SCO ON SCO._lea_id = LEA.lea_id
		WHERE LEA.lea_id = :b_lea_id
		ORDER BY SCO.score DESC";
		
		$stmt = $this->_db->prepare($sql);
		$stmt->bindValue(':b_lea_id', $lea_id, PDO::PARAM_INT);
		$stmt->execute();
		$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if(count($res) === 0){
			throw new LeaderboardException(sprintf(LeaderboardException::MESSAGE_NOT_FOUND, $lea_id), LeaderboardException::CODE_NOT_FOUND);
		} else {
			$leaderboard = new Leaderboard($lea_id);
			foreach($res as $score) {
				if($score['_usr_id'] != null) {
					$leaderboard->addEntrie($score['_usr_id'], $score['score']);
				}
			}
			
			return $leaderboard;
		}
	}

	public function removeScoreAll()
	{
		$sql = "DELETE FROM lea_usr";
		$stmt = $this->_db->prepare($sql);
		return $stmt->execute();		
	}

	public function removeAll()
        {
                $sql = "DELETE FROM leaderboard";
                $stmt = $this->_db->prepare($sql);
                return $stmt->execute();
        }

}
