CREATE TABLE IF NOT EXISTS /*_*/article_rankings_votes_messages (
  votes_messages_id int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  votes_messages_page_id int(10) UNSIGNED NOT NULL,
  votes_messages varchar(255) DEFAULT 0,
  positive_or_negative int(1) DEFAULT 0,
  votes_timestamp int(12) DEFAULT 0
) /*$wgDBTableOptions*/;