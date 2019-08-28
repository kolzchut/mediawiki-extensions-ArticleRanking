CREATE TABLE IF NOT EXISTS /*_*/article_rankings_votes_messages (
  votes_messages_id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  votes_messages_page_id int unsigned NOT NULL,
  votes_messages blob NOT NULL,
  positive_or_negative tinyint DEFAULT NULL,
  votes_timestamp varbinary(14) NOT NULL
) /*$wgDBTableOptions*/;
