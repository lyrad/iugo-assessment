<?php
namespace App\Model\Entity;

class Leaderboard 
{
	private $lea_id;
	
	private $entries;

	public function __construct($lea_id)
	{
		$this->lea_id = $lea_id;
		$this->entries = array();
	}	

	public function addEntrie($usr_id, $score)
	{
		$this->entries[] = array( 'UserId' => $usr_id, 'Score' => $score );
	}	

	public function getEntries()
	{
		// Sort entries by score desc before returning 
		usort($this->entries, function($a, $b) {
			return $b['Score'] - $a['Score'];
		});
		
		// Add rank to entries (according to spec, rank begin at 1 so index + 1)
		array_walk( $this->entries, function(&$val, $key) {
			$val['Rank'] = $key + 1;
		});
		return $this->entries;
	}
	
	public function getUserEntries($usr_id, $offset = 0, $limit = null)
	{
		return array_slice(array_filter(array_map(function($val) use ($usr_id){
			if($val['UserId'] == $usr_id){
				return $val;
			}
		}, $this->getEntries())), $offset, $limit);
	}
}
