<?php
namespace wbb\data\thread;
use wbb\data\board\Board;
use wbb\data\board\BoardCache;
use wbb\data\board\BoardEditor;
use wbb\data\board\ModeratorBoardNodeList;
use wbb\data\modification\log\BoardModificationLogList;
use wbb\data\post\Post;
use wbb\data\post\PostAction;
use wbb\data\post\PostEditor;
use wbb\data\post\PostList;
use wbb\data\post\SimplifiedViewablePostList;
use wbb\data\post\ThreadPostList;
use wbb\data\post\ViewablePostList;
use wbb\system\label\object\ThreadLabelObjectHandler;
use wbb\system\log\modification\ThreadModificationLogHandler;
use wbb\system\thread\ThreadHandler;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\user\object\watch\UserObjectWatchList;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\IClipboardAction;
use wcf\data\IVisitableObjectAction;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\label\LabelHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\request\LinkHandler;
use wcf\system\tagging\TagEngine;
use wcf\system\user\activity\event\UserActivityEventHandler;
use wcf\system\user\activity\point\UserActivityPointHandler;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\object\watch\UserObjectWatchHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\StringUtil;

 /**
 * Provides additional functions for thread-related actions.
 * 
 * Written for the purpose of capturing Thread Read events that come 
 * from Taptalk plugin. 
 */
class ThreadReadAction extends ThreadAction {
	/**
	 * @see	\wbb\thread\ThreadAction::markAsRead()
	 * Called from mobiquo/mbqClass/lib/write/MbqWrEtForumTopic.php::markForumTopicRead($oMbqEtForumTopic)
	 * That file has been edited to point at `ThreadReadEditor` instead of native WBB `ThreadEditor`.
	 */
	public function markAsRead() {
		parent::markAsRead();
		
		$threadIDs = array();
		foreach ($this->objects as $thread) {
			/* 
			 * If Thread Read plugin is active, do the extra queries
			 */
			if (WBB_THREADREAD_ACTIVE) {
				$this->getWhoReadUsers($thread->threadID);
			}
		}
	}
	
	/**
	 * @see	\wcf\data\IVisitableObjectAction::validateMarkAsRead()
	 */
	public function validateMarkAsRead() {
		parent::checkPermissions();
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
				intval($threadID), 
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
						intval($threadID),
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
						intval($threadID)
					));
				}
			}
		}
        }
}
