CREATE TABLE IF NOT EXISTS /*_*/article_rankings2 (
	ranking_id int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
	ranking_page_id  int unsigned NOT NULL,
	ranking_actor bigint unsigned NOT NULL default 0,
	-- Recorded IP address the ranking was made from
	ranking_ip varbinary(40),
	-- ranking_value is smallint for BC - we shove the older total values from the previous table
	-- in a single row, and those number can be huge
	ranking_value smallint NOT NULL,
	ranking_timestamp binary(14) NOT NULL default ''
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/page_id_index ON /*_*/article_rankings2 (ranking_page_id);
CREATE INDEX /*i*/timestamp_index ON /*_*/article_rankings2 (ranking_timestamp);

