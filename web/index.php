<?php
use App\Request;
use App\Api;

// Autoloader
// Load project files
include_once __DIR__ . '/../src/Request.php';
include_once __DIR__ . '/../src/Response.php';
include_once __DIR__ . '/../src/Api.php';

include_once __DIR__ . '/../src/Model/Entity/Transaction.php';
include_once __DIR__ . '/../src/Model/Repository/TransactionRepository.php';
include_once __DIR__ . '/../src/Model/Exception/TransactionException.php';
include_once __DIR__ . '/../src/Model/Entity/User.php';
include_once __DIR__ . '/../src/Model/Repository/UserRepository.php';
include_once __DIR__ . '/../src/Model/Exception/UserException.php';
include_once __DIR__ . '/../src/Model/Entity/Leaderboard.php';
include_once __DIR__ . '/../src/Model/Repository/LeaderboardRepository.php';
include_once __DIR__ . '/../src/Model/Exception/LeaderboardException.php';

// Bootstrap
// Set application configuration variables

//API key
$api_key = 'NwvprhfBkGuPJnjJp77UPJWJUpgC7mLz';

// Database PDO obect
$db = new PDO('mysql:host=127.0.0.1;dbname=iugo-assessment', 'root', 'root');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
$db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

// Aobjec
// Include endpoint name, type of HTTP request and attributes.
// For each attribute is provided a validation function for filtering.
$expected_inputs = [
	'timestamp' => array(
        	"type" => 'GET',
                "data" => null
        ),
        'transaction' =>  array(
                 "type" => 'POST',
                 "data" => array(
                 	"JSON" => array(
        	        	"TransactionId" => "is_int",
                                        "UserId" => "is_int",
                                        "CurrencyAmount" => "is_int",
                                        "Verifier" => "is_string"
                                )
                        )
                ),
                'transactionstats' => array(
                        "type" => 'POST',
                        "data" => array(
                                "JSON" => array(
                                        "UserId" => "is_int",
                                )
                        )
                ),
                'scorepost' => array(
                        "type" => 'POST',
                        "data" => array(
                                "JSON" => array(
                                        "UserId" => "is_int",
                                        "LeaderboardId" => "is_int",
                                        "Score" => "is_int"
                                )
                        )
                ),
                'leaderboardget' => array(
                        "type" => 'POST',
                        "data" => array(
                                "JSON" => array(
                                        "UserId" => "is_int",
                                        "LeaderboardId" => "is_int",
                                        "Offset" => "is_int",
                                        "Limit" => "is_int"
                               )
                        )
                ),
                'usersave' => array(
                        "type" => 'POST',
                        "data" => array(
                                "JSON" => array(
                                        "UserId" => "is_int",
                                        "Data" => "is_array"
                                )
                        )
                ),
                'userload' => array(
                        "type" => 'POST',
                        "data" => array(
                                "JSON" => array(
                                        "UserId" => "is_int"
                                )
                        )
                ),
		'reset' => array(
			"type" => 'GET',
			"data" => null
		),
		'notimplementedendpoint' => array(
			"type" => 'GET',
                        "data" => null
                )
        ];


// Request Object
// Creation from $_SERVER globals
$request = Request::createFromGlobals();

// Create API Object
$app = new Api($request, $api_key, $expected_inputs, $db);

// Handle Request and get Response Object
$response = $app->handle();

// Send Response to the client
$response->send();
