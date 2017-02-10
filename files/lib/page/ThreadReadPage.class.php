<?php
namespace wbb\page;
use wcf\page\AbstractPage;
use wbb\data\post\Post;
use wbb\data\post\ThreadPostList;
use wbb\data\thread\ThreadAction;
use wbb\data\thread\ThreadEditor;
use wbb\data\thread\ViewableThread;
use wbb\system\WBBCore;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\language\LanguageFactory;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\StringUtil;
use wcf\util\DateUtil;

class ThreadReadPage extends AbstractPage {
	/**
	 * @see	\wcf\page\AbstractPage::$activeMenuItem
	 */
	public $activeMenuItem = 'wbb.header.menu.board';
	
	/**
	 * @see	\wcf\page\AbstractPage::$enableTracking
	 */
	public $enableTracking = true;

	public $threadID = 0;
	public $thread = null;
	public $postIDs = array();
	public $posts = null;
	public $whoReadThread = array();

	/**
	 * @see	\wcf\page\IPage::readParameters()
	 */
	public function readParameters() {
		if (isset($_REQUEST['id'])) $this->threadID = intval($_REQUEST['id']);
		
		$this->thread = ViewableThread::getThread($this->threadID);
		if ($this->thread === null || $this->thread->movedThreadID) {
			throw new IllegalLinkException();
		}
		if (!$this->thread->canRead()) {
			throw new PermissionDeniedException();
		}
		if (!WBB_THREADREAD_ACTIVE) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @see	\wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		$this->getWhoReadThread();
		$this->getPosts();
		
		usort($this->whoReadThread, array($this, 'sortEntries'));
		usort($this->posts, array($this, 'sortEntries'));
		
		$history = array();
		
		$today = DateUtil::format( DateUtil::getDateTimeByTimestamp(TIME_NOW), 'wcf.date.dateFormat');
		$topvisits = $this->getUsersBetweenPostTimes(9999999999, $this->posts[0]['unixtime']);
		
		$history[$today] = array( array(
			'type' => 'visits', 
			'users' => $topvisits
		));
		
		$time_it = TIME_NOW;
		foreach($this->posts as $key=>$post) {
			if(isset($this->posts[$key+1])) {
				$visitsBetweenThisPostAndNextPost = $this->getUsersBetweenPostTimes($post['unixtime'], $this->posts[$key+1]['unixtime']);
				
				$history[$post['postdate']][] = $post;
				$history[$post['postdate']][] = array(
					'type' => 'visits',
					'users' => $visitsBetweenThisPostAndNextPost
				);
			} else {
				$history[$post['postdate']][] = $post;
			}
		}
		
		/*echo "<pre>";
		var_dump($history);
		echo "</pre>";
		die();*/
		
		if (!empty($this->whoReadThread)) {
			WCF::getTPL()->assign(array(
				'thread' => $this->thread,
				'threadID' => $this->threadID,
				'history' => $history,
				'today' => $today
			));
		}
	}
	
	protected function getUsersBetweenPostTimes($high, $low) {
		$users = array();
		
		foreach($this->whoReadThread as $key=>$user) {
			if( ($user['lastvisitunixtime'] < $high && $user['lastvisitunixtime'] >= $low) ||
			    ($user['firstvisitunixtime'] < $high && $user['firstvisitunixtime'] >= $low)) {
			    $users[] = $user;
			}
		}
		
		return $users;
	}
	
	protected function sortEntries($a, $b) {
		if ($a['unixtime']==$b['unixtime']) return 0;
		return ($a['unixtime']>$b['unixtime'])?-1:1;
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
	
	public function getWhoReadThread() {
		$sql = "SELECT * 
			FROM wbb".WCF_N."_thread_read 
			WHERE threadID = ?
			ORDER BY lastvisit DESC";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$this->threadID
		));
		while ($row = $statement->fetchArray()) {
			$lastvisitunixtime = $row['lastvisit'];
			$firstvisitunixtime = $row['firstvisit'];
		
			$lastvisitdate = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['lastvisit']), 'wcf.date.dateFormat');
			$lastvisittime = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['lastvisit']), 'wcf.date.timeFormat');
			$row['lastvisit'] = $lastvisitdate . ", " . $lastvisittime;
			
			$firstvisitdate = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['firstvisit']), 'wcf.date.dateFormat');
			$firstvisittime = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['firstvisit']), 'wcf.date.timeFormat');
			$row['firstvisit'] = $firstvisitdate . ", " . $firstvisittime;
			
			$row['lastvisitunixtime'] = $lastvisitunixtime;
			$row['firstvisitunixtime'] = $firstvisitunixtime;
			$row['firstvisitdate'] = $firstvisitdate;
			$row['lastvisitdate'] = $lastvisitdate;
			$row['unixtime'] = $lastvisitunixtime;
			
			$row['type'] = 'user';
			
			$this->whoReadThread[] = $row;
		}
	}
	
	public function getPosts() {
		$sql = "SELECT * 
			FROM wbb".WCF_N."_post 
			WHERE threadID = ?
			ORDER BY time DESC, postID DESC";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$this->threadID
		));
		while ($row = $statement->fetchArray()) {
			$postunixtime = $row['time'];
		
			$postdate = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['time']), 'wcf.date.dateFormat');
			$posttime = DateUtil::format( DateUtil::getDateTimeByTimestamp($row['time']), 'wcf.date.timeFormat');
			$row['time'] = $postdate . ", " . $posttime;
			
			if (strlen($row['message']) > 50) {
				$message = substr($row['message'], 0, 50) . " ...";
			} else {
				$message = $row['message'];
			}
		
			$this->posts[] = array(
				'type' => 'post',
				'postID' => $row['postID'],
				'time' => $row['time'],
				'postdate' => $postdate,
				//'postdate_arrayformat' => $postdate_arrayformat,
				'unixtime' => $postunixtime,
				'userID' => $row['userID'],
				'username' => $row['username'],
				'message' => $message
			);
		}
	}
}