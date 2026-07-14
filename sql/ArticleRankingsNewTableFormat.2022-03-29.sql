CREATE TABLE IF NOT EXISTS /*_*/article_rankings2 (
	ranking_id int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
	ranking_page_id  int unsigned NOT NULL,
	-- Legacy columns, no longer written (kept for historical rows). Voter
	-- identity now lives in ranking_voter_key; see ArticleRanking#8.
	ranking_actor bigint unsigned NOT NULL default 0,
	ranking_ip varbinary(40),
	-- Pseudonymous per-voter dedup key: 'u:<user id>' or 'ip:<HMAC of IP>'.
	-- NULL for historical (pre-dedup) rows.
	ranking_voter_key varbinary(255) DEFAULT NULL,
	-- ranking_value is smallint for BC - we shove the older total values from the previous table
	-- in a single row, and those number can be huge
	ranking_value smallint NOT NULL,
	ranking_timestamp binary(14) NOT NULL default ''
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/page_id_index ON /*_*/article_rankings2 (ranking_page_id);
CREATE INDEX /*i*/timestamp_index ON /*_*/article_rankings2 (ranking_timestamp);
-- One live vote per (page, identity); historical NULL-key rows are exempt.
CREATE UNIQUE INDEX /*i*/ranking_voter_dedup ON /*_*/article_rankings2 (ranking_page_id, ranking_voter_key);

