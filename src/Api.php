<?php
namespace App;

use PDO;
use PDOException;

use App\Request;
use App\Response;
use App\Model\Entity\Transaction;
use App\Model\Repository\TransactionRepository;
use App\Model\Exception\TransactionException;
use App\Model\Entity\User;
use App\Model\Repository\UserRepository;
use App\Model\Exception\UserException;
use App\Model\Entity\Leaderboard;
use App\Model\Repository\LeaderboardRepository;
use App\Model\Exception\LeaderboardException;

class Api
{
	private $_request;
	
	private $_api_key;
	
	private $_expected_inputs;

	private $transactionRepository;

	private $userRepository;

	private $leaderboardRepository;

	public function __construct(Request $request, $api_key, $expected_inputs, $db)
	{
		// Save the HTTP request & API key in class attribute
		$this->_request = $request;

		// If JSON index exists in request, erase the whole $_POST array with its content, json decoded
		If(true === isset($this->_request->request['JSON'])) {
			$this->_request->request = json_decode($this->_request->request['JSON'], true);
		}
		$this->_api_key = $api_key;
		$this->_expected_inputs = $expected_inputs;

		// Initialize Repositories
		$this->transactionRepository = new TransactionRepository($db);
		$this->userRepository = new UserRepository($db);
		$this->leaderboardRepository = new LeaderboardRepository($db);
	}

	public function handle()
	{
		// Get endpoint required from request object
		$endpoint = $this->_request->attributes['filename'];

		// If endpoint required not declared in configuration or if HTTP request type does not matche with configuration
		if(false === isset($this->_expected_inputs[$endpoint]) || false === isset($this->_expected_inputs[$endpoint]["type"]) || $this->_expected_inputs[$endpoint]["type"] !== $this->_request->method  ) {
			// return a 404 HTTP status
			return new Response(json_encode(array('Error' => true, 'ErrorMessage' => 'Endpoint not found')), Response::HTTP_NOT_FOUND);
		// if endpoint not implemented
		} elseif(false === method_exists($this, $endpoint)) {
			// return a 501 HTTP status
			return new Response(json_encode(array('Error' => true, 'ErrorMessage' => 'Endpoint not implemented')), Response::HTTP_NOT_IMPLEMENTED);
		} else {
			// Check if configuration matches with request params => filter inputs
			if(true === isset($this->_expected_inputs[$endpoint]["data"]["JSON"])) {
				// If endpoint is expects parameters
				foreach($this->_expected_inputs[$endpoint]["data"]["JSON"] as $param_name => $param_validation) {
					if(false === isset($this->_request->request[$param_name]) || false === $param_validation($this->_request->request[$param_name])) {
						// Param name configured is missing or param value is not valid
						// Return 500 HTTP status
						return new Response(json_encode(array('Error' => true, 'ErrorMessage' => 'A parameter is missing or has a wrong type. Please check your request.')), Response::HTTP_INTERNAL_SERVER_ERROR);
					}
				}	
			}

			try {
				// Execute endpoint method and return result (HTTP response obj)
				$response = $this->$endpoint();
				return $response;
			} catch (PDOException $e) {
				// Uncaught PDOException
				// Return a 500 HTTP status
				return new Response( json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionDetails' => $e->getCode() . $e->getMessage() . $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR);
			}
		}
	}

	public function timestamp()
	{
		$date = new \DateTime();
		return new Response( json_encode(array('Timestamp' => $date->getTimestamp())));	
	}

	public function transaction()
	{
		$post = $this->_request->request;
		
		$transaction = new Transaction($post['TransactionId'], $post['UserId'], $post['CurrencyAmount'], $post['Verifier']);	

		try {
			//Check verifier
			if( sha1($this->_api_key . $transaction) != $transaction->tra_verifier ) { 
				throw new TransactionException(TransactionException::MESSAGE_WRONG_VERIFIER, TransactionException::CODE_WRONG_VERIFIER);
			}
			$this->transactionRepository->addTransaction($transaction);
			return new Response( json_encode(array('Success' => true)), Response::HTTP_OK);
		} catch( TransactionException $e) {
			return new Response( json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionDetails' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR );	
		}
	}

	public function transactionStats()
	{
		$post = $this->_request->request;
		
		try {
			$stats_array = $this->transactionRepository->getTransactionStatsByUserId($post['UserId']);
			return new Response( json_encode($stats_array), Response::HTTP_OK );
		} catch(TransactionException $e) {
			// No transaction was found for this specified user.
			$stats_array = array('UserId' => $post['UserId'], 'TransactionCount' => 0, 'CurrencySum' => 0);
			return new Response( json_encode($stats_array), Response::HTTP_OK );
		}
	}

	public function scorePost()
	{
		$post = $this->_request->request;

		try {
			$leaderboard = $this->leaderboardRepository->getLeaderboardById($post['LeaderboardId']);			
			$usr_best_entrie = $leaderboard->getUserEntries($post['UserId'],0,1);
			
			if(isset($usr_best_entrie[0]['Score']) === false) {
				// If user has no entrie for this leaderboard
				// Force his max score to 0 so new score will be added
                        	$maxscore = 0;
			} else {
				$maxscore = $usr_best_entrie[0]['Score'];
			}

			// Adding a new entrie if newScore > best user score
			if( $maxscore < $post['Score'] ) {
                                $this->leaderboardRepository->addUserScore($post['LeaderboardId'], $post['UserId'], $post['Score']);
				$leaderboard->addEntrie($post['UserId'], $post['Score']);
				$usr_best_entrie = $leaderboard->getUserEntries($post['UserId'],0,1);
                        }
			return new Response( json_encode( array('UserId' => $post['UserId'], 'LeaderboardId' => $post['LeaderboardId'], 'Score' => $usr_best_entrie[0]['Score'], 'Rank' => $usr_best_entrie[0]['Rank']) ), Response::HTTP_OK );
		} catch(LeaderboardException $e) {
			// Leaderboard can't be found
                        return new Response( json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionDetails' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR );
		} catch(UserException $e) {
			// User can't be found
                        return new Response( json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionDetails' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR);
                }
	}

	public function leaderboardGet()
	{
		$post = $this->_request->request;
	
		if(false === isset($post['Offset'])){
			$post['Offset'] = 0;
		}

		if(false === isset($post['Limit'])){
                        $post['Limit'] = null;
                }

		try {
                        $leaderboard = $this->leaderboardRepository->getLeaderboardById($post['LeaderboardId']);
			$usr_entries = $leaderboard->getUserEntries($post['UserId'], $post['Offset'], $post['Limit']);
			
			$usr_best_entrie = $leaderboard->getUserEntries($post['UserId'], 0, 1);
				
			if(false === isset($usr_best_entrie[0]['Score'])) {
				// User has not entrie for this leaderboard
				return new Response( json_encode( array( "Error" => true, "ErrorMessage" => sprintf("User (id: %d) has not entrie for the leaderboard (id: %d).", $post['UserId'], $post['LeaderboardId'] ) ) ), Response::HTTP_INTERNAL_SERVER_ERROR );
			} else {
				return new Response( json_encode( array('UserId' => $post['UserId'], 'LeaderboardId' => $post['LeaderboardId'], 'Score' => $usr_best_entrie[0]['Score'], 'Rank' => $usr_best_entrie[0]['Rank'], 'Entries' => $usr_entries) ), Response::HTTP_OK) ;
			}
		} catch(LeaderboardException $e) {
                        // Leaderboard can't be found
                        return new Response( json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionDetails' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR );	
		}
	}

	public function userSave()
	{
		$post = $this->_request->request;
		try {
			// Get User
			$user = $this->userRepository->getUserById($post['UserId']);
			// Update/Add user data to user
			foreach($post['Data'] as $data_key => $data) {
				try {
					$user->updateData($data_key, $data);
				} catch (UserException $e) {
					// Data does not exist for the user
					// Try to add data
					try {
						$user->addData($data_key, $data);
					} catch (UserException $e) {
						// Data already exists for the user
						// Can't happen, do nothing
					}
				}
			}
			try {
				// Update user into database
				$this->userRepository->updateUser($user);
				return new Response( json_encode( array('Success' => true) ), Response::HTTP_OK );			
			} catch(UserException $e) {
				// User cannot be updated
                                // return HTTP 500 error
                                return new Response( json_encode( array( 'Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionCode' => $e->getCode(), 'ExceptionTrace' => $e->getTraceAsString() )), Response::HTTP_INTERNAL_SERVER_ERROR);			
			}
		} catch(UserException $e) {
			// User does not exist
			// Create user object
			$user = new User($post['UserId']);

			// Add Userdata to User object
			foreach($post['Data'] as $data_key => $data) {
				try {
					$user->addData($data_key, $data);		
				} catch (UserException $e) {
					// Data already exists Means here duplication in data array coming from request
					// Do nothing (ignore duplication)
				}
			}
			
			try {
				// Persist User in database
				$this->userRepository->addUser($user);
				return new Response( json_encode( array('Success' => true) ), Response::HTTP_CREATED );
			} catch(UserException $e) {
				// User cannot be created
				// return HTTP 500 error
				return new Response( json_encode( array( 'Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionCode' => $e->getCode(), 'ExceptionTrace' => $e->getTraceAsString() )), Response::HTTP_INTERNAL_SERVER_ERROR);
			}
		}
	}

	public function userLoad()
	{
		$post = $this->_request->request;
		
		try {
			$user = $this->userRepository->getUserById($post['UserId']);
			return new Response( json_encode( $user->data, Response::HTTP_OK) );
		} catch(UserException $e) {
			// User does not exist
			// Returning empty json object according to spec
			return new Response( json_encode( array(), Response::HTTP_OK) );
		}		
	}

	public function reset()
	{
		$res = array(
			'UserData deleted' => $this->userRepository->removeUserDataAll(),
			'Score deleted' => $this->leaderboardRepository->removeScoreAll(),
			// No leaderboard add endpoint... So keep leaderboard in datatbase
			// 'Leaderboard deleted' => $this->leaderboardRepository->removeAll(),
			'Transaction deleted' => $this->transactionRepository->removeAll(),
			'User deleted' => $this->userRepository->removeAll(),
		);

		return new Response( json_encode( array('Success' => true, 'details' => $res ) ) , Response::HTTP_OK );
	}
}

