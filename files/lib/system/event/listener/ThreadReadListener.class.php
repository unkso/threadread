<?php
namespace wbb\system\event\listener;
use wcf\system\event\IEventListener;
use wcf\util\DateUtil;
use wcf\system\WCF;

class ThreadReadListener implements IEventListener {
	protected $eventObj = null;
	
	public $whoReadUsers = array();
	public $usercount = 0;
	public $threadID = 0;
	public $userCounter = 0;
    
        public function execute($eventObj, $className, $eventName) {
        	if (WBB_THREADREAD_ACTIVE) {
			$this->eventObj = $eventObj;
			$this->threadID = $eventObj->threadID;   
			$this->getWhoReadUsers($this->threadID);
		
			if (!empty($this->whoReadUsers)) {
				WCF::getTPL()->assign(array(
					'whoReadUsers' => $this->whoReadUsers, 
					'usercount' => $this->countUser()
				));
			}
		}
        }
    
	protected function countUser() {
		$sql = "SELECT COUNT(*) AS userID
			FROM wbb".WCF_N."_thread_read
			WHERE threadID = ? 
			LIMIT 1";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(intval($this->threadID)));
		$row = $statement->fetchArray();
		return $row['userID'];
	}
    
        public function getWhoReadUsers($threadID) {
		if (WCF::getUser()->userID > 0) {
			$sql = "SELECT * 
				FROM wbb".WCF_N."_thread_read 
				WHERE threadID = ?
				AND userID = ?
				LIMIT 1";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array(
				intval($this->threadID), 
				WCF::getUser()->userID
			));
			$row = $statement->fetchArray();
			
			$sql = "SELECT COUNT(userID)
				FROM wcf".WCF_N."_user_to_group
				WHERE userID = ?
				AND groupID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array(
				WCF::getUser()->userID,
				WBB_THREADREAD_GROUPID
			));
			$isMember = $statement->fetchArray();
			$isMember = $isMember["COUNT(userID)"];
			
			// Is the current viewer a member of the correct usergroup?
			// Do not insert or update if not
			if($isMember) {
				$this->userCounter = $row['userID'];
				
				if ($this->userCounter == 0) {
					$sql = "INSERT INTO wbb".WCF_N."_thread_read
						SET userID = ?, username = ?, threadID = ?, firstvisit = ?, lastvisit = ?";
					$statement = WCF::getDB()->prepareStatement($sql);
					$statement->execute(array(
						WCF::getUser()->userID,
						WCF::getUser()->username,
						intval($this->threadID),
						TIME_NOW,
						TIME_NOW
					));
				}
					
				if ($this->userCounter > 0) {
					$sql = "UPDATE wbb".WCF_N."_thread_read
						SET lastvisit = ? 
						WHERE userID = ? 
						AND threadID = ?";
					$statement = WCF::getDB()->prepareStatement($sql);
					$statement->execute(array(
						TIME_NOW,
						WCF::getUser()->userID,
						intval($this->threadID)
					));
				}
			}
		}
		
		$sql = "SELECT * 
			FROM wbb".WCF_N."_thread_read 
			WHERE threadID = ?
			ORDER BY lastvisit DESC";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$this->threadID
		));
		while ($row = $statement->fetchArray()) {
			$lastvisitdate = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['lastvisit']), 'wcf.date.dateFormat');
			$lastvisittime = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['lastvisit']), 'wcf.date.timeFormat');
			$row['lastvisit'] = $lastvisitdate . ", " . $lastvisittime;
			
			$firstvisitdate = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['firstvisit']), 'wcf.date.dateFormat');
			$firstvisittime = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['firstvisit']), 'wcf.date.timeFormat');
			$row['firstvisit'] = $firstvisitdate . ", " . $firstvisittime;
			
			$this->whoReadUsers[] = $row;
		}
        }
}