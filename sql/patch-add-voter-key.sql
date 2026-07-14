-- One-vote-per-identity dedup key (ArticleRanking#8).
-- Nullable on purpose: existing (pre-dedup) rows keep NULL and, because NULLs
-- never collide in a UNIQUE index, are unaffected by the dedup constraint added
-- in patch-add-voter-dedup-index.sql. Only votes cast after this migration are
-- deduplicated.
ALTER TABLE /*_*/article_rankings2
	ADD COLUMN ranking_voter_key varbinary(255) DEFAULT NULL;
