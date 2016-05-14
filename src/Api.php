<?php
namespace App;

use PDO;
use PDOException;

use App\Request;
use App\Response;
use App\ApiException;
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
		
		try {
			// If JSON index exists in request, erase the whole $_POST array with its content, json decoded
			If(true === isset($this->_request->request['JSON'])) {
				$this->_request->request = json_decode($this->_request->request['JSON'], true);
				// If JSON input parameter not well formatted
				if( JSON_ERROR_NONE != json_last_error() ) {
					// Throw JSON ApiException
					throw new ApiException(ApiException::MESSAGE_INPUT_JSON, ApiException::CODE_INPUT_JSON);
				}
			}
		} catch(ApiException $e) {
				// Return 500 HTTP status	
			return new Response(json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionCode' => $e->getCode(), 'ExceptionTrace' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR);
		
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
		
		try {
			// If endpoint required not declared in configuration or if HTTP request type does not matche with configuration
			if(false === isset($this->_expected_inputs[$endpoint]) || false === isset($this->_expected_inputs[$endpoint]["type"]) || $this->_expected_inputs[$endpoint]["type"] !== $this->_request->method  ) {
				// throw endpoint not found ApiException
				throw new ApiException(sprintf(ApiException::MESSAGE_ENDPOINT_NOT_EXISTS, $endpoint), ApiException::CODE_ENDPOINT_NOT_EXISTS );
			// if endpoint not implemented
			} elseif(false === method_exists($this, $endpoint)) {
				// Throw ENDPOINT NOT IMPLEMENTED ApiException
				throw new ApiException(sprintf(ApiException::MESSAGE_ENDPOINT_NOT_IMPLEMENTED, $endpoint), ApiException::CODE_ENDPOINT_NOT_IMPLEMENTED); 
			} else {
				// Check if configuration matches with request params => filter inputs
				if(true === isset($this->_expected_inputs[$endpoint]["data"]["JSON"])) {
					// If endpoint expects parameters
					// For each expected parameter
					foreach($this->_expected_inputs[$endpoint]["data"]["JSON"] as $param_name => $param_validation) {
						if(false === isset($this->_request->request[$param_name])) {
							// Param name configured is missing 
							// Throw input missing ApiException
							throw new ApiException(ApiException::MESSAGE_INPUT_MISSING, ApiException::CODE_INPUT_MISSING);
						} elseif(false === $param_validation($this->_request->request[$param_name])) {
							// If parameter does not valid validation function
							// Throw input wrong type ApiException
							throw new ApiException(ApiException::MESSAGE_INPUT_TYPE, ApiException::CODE_INPUT_TYPE);
						}
					}	
				}

				// Execute endpoint method and return result (HTTP response obj)
				$response = $this->$endpoint();
				return $response;
			}
		} catch( ApiException $e ) {
			// If API Exception
			// Depending on code
			switch($e->getCode()){
				case ApiException::CODE_ENDPOINT_NOT_IMPLEMENTED:
					// If endpoint not implemented
					// Return NOT IMPLEMENTED HTTP STATUS
					return new Response(json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionCode' => $e->getCode(), 'ExceptionTrace' => $e->getTraceAsString())), Response::HTTP_NOT_IMPLEMENTED);
					break;
				default:
					// DEFAULT
					// RETURN 500 HTTP STATUS
					return new Response(json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionCode' => $e->getCode(), 'ExceptionTrace' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR);
			}
		} catch( PDOException $e) {
			// If PDO Exception
			// return HTTP 500 status
			return new Response(json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionCode' => $e->getCode(), 'ExceptionTrace' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR);
		} catch(Exception $e) {
			// If other uncaught Exception
			// return HTTP 500 status
			return new Response(json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionCode' => $e->getCode(), 'ExceptionTrace' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	public function timestamp()
	{
		$date = new \DateTime();
		return new Response( json_encode(array('Timestamp' => $date->getTimestamp())));	
	}
	/**
	 *
	 *
	 *
	 */
	public function transaction()
	{
		$post = $this->_request->request;

		try {
			// Get transaction's user
			$user = $this->userRepository->getUserById($post['UserId']);
			
			// Create new Transcation
			$transaction = new Transaction($post['TransactionId'], $user, $post['CurrencyAmount'], $post['Verifier']);	
			// Check transaction's verifier
			try {
				$transaction->checkVerifier($this->_api_key);
				// Add transaction (joined object User is nor added/updated)
				$this->transactionRepository->addTransaction($transaction);
				return new Response( json_encode(array('Success' => true)), Response::HTTP_OK);
			} catch( TransactionException $e ) {
				// Verifier is not correct or Transaction can't be added
				return new Response( json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionDetails' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR );	
			} 
		} catch (UserException $e) {
			// Transaction's User can't be found
			return new Response( json_encode(array('Error' => true, 'ErrorMessage' => $e->getMessage(), 'ExceptionDetails' => $e->getTraceAsString())), Response::HTTP_INTERNAL_SERVER_ERROR );	
		}
	}

	public function transactionStats()
	{
		$post = $this->_request->request;
		
		try {
			// Get User (user's transactions are loaded into "transactions" user attribute)
			$user = $this->userRepository->getUserById($post['UserId']);
			
			return new Response( json_encode( array( 'TransactionCount' => $user->getTransactionsCount(), 'CurrencySum' => $user->getTransactionsCurrencyamountSum())  ), Response::HTTP_OK );
		} catch(UserRepository $e) {
			// No User was found

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

