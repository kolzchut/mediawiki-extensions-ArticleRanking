# Select positive and negative scores from the old table, and insert them into the new one
# Set the timestamp on the migrated votes to the start of today.
# Only grab values > 0
INSERT INTO /*_*/article_rankings2 (ranking_page_id, ranking_value, ranking_timestamp)
	(
		SELECT page_id, positive_votes as `value`, DATE_FORMAT(NOW(), '%Y%m%d000000') `timestamp`
		FROM /*_*/article_rankings
		WHERE (positive_votes > 0)
		UNION
		SELECT page_id,
			   -(total_votes - positive_votes)    `value`,
			   DATE_FORMAT(NOW(), '%Y%m%d000000') `timestamp`
		FROM /*_*/article_rankings
		WHERE (total_votes - positive_votes > 0)
	);

DROP TABLE /*_*/article_rankings;
