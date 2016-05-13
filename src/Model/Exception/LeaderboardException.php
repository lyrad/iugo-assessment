<?php
namespace App\Model\Exception;

class LeaderboardException extends \Exception
{
	const CODE_NOT_FOUND = 3001;
	const MESSAGE_NOT_FOUND = 'Leaderborad (id: %d) cannot be found.';

	// User can't be found in a given leaderboard
	const CODE_USER_NOT_FOUND = 3002;
	const MESSAGE_USER_NOT_FOUND = "User (id: %d) cannot be found for the leaderboard (id: %d).";
}
