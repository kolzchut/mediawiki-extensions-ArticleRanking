-- Enforce one live vote per (page, identity) so a repeat vote replaces the
-- prior value (last-wins) instead of accumulating (ArticleRanking#8).
-- Historical rows have ranking_voter_key = NULL and are exempt: NULLs do not
-- collide in a UNIQUE index, so this can be applied without collapsing old data.
CREATE UNIQUE INDEX /*i*/ranking_voter_dedup
	ON /*_*/article_rankings2 (ranking_page_id, ranking_voter_key);
