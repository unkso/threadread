-- Create the whoread table
DROP TABLE IF EXISTS wbb1_thread_read;
CREATE TABLE wbb1_thread_read (
	id 				INT(10) AUTO_INCREMENT PRIMARY KEY,
    threadID 		INT(10) NOT NULL,
	userID			INT(10) NOT NULL,
	username		VARCHAR(255) NOT NULL,
	lastvisit		INT(10) NOT NULL,
	firstvisit		INT(10) NOT NULL
);
ALTER TABLE wbb1_thread_read ADD FOREIGN KEY (threadID) REFERENCES wbb1_thread (threadID) ON DELETE CASCADE;
ALTER TABLE wbb1_thread_read ADD FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE CASCADE;