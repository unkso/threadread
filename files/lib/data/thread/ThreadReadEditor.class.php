<?php
namespace wbb\data\thread;
use wbb\data\post\Post;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\DatabaseObjectEditor;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Provides additional functions to edit threads.
 * 
 * Written for the purpose of capturing Thread Read events that come 
 * from Taptalk plugin. 
 */
class ThreadReadEditor extends ThreadEditor {
	/**
	 * @see	\wcf\data\DatabaseObjectEditor::updateCounters()
	 * Called from mobiquo/mbqClass/lib/write/MbqWrEtForumTopic.php::addForumTopicViewNum($oMbqEtForumTopic)
	 * That file has been edited to point at `ThreadReadEditor` instead of native WBB `ThreadEditor`.
	 */
	public function updateCounters(array $counters = array()) {
		parent::updateCounters($counters);
		
		/* 
		 * If Thread Read plugin is active, do the extra queries
		 */
		if (WBB_THREADREAD_ACTIVE) {
			$this->getWhoReadUsers($this->getObjectID());
		}
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
        }
}
