CREATE TABLE IF NOT EXISTS /*_*/article_rankings (
  ranking_id int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  page_id int(10) UNSIGNED NOT NULL,
  positive_votes int(10) DEFAULT 0,
  total_votes int(10) DEFAULT 0,
  UNIQUE KEY /*_*/article_rankings_page_id_UNIQUE (page_id),
  CONSTRAINT /*_*/article_rankings_page_id_fk FOREIGN KEY (page_id) REFERENCES /*_*/page(page_id) ON DELETE CASCADE ON UPDATE CASCADE
) /*$wgDBTableOptions*/;

DROP TRIGGER IF EXISTS /*_*/article_rankings_BEFORE_UPDATE;

DELIMITER |
CREATE TRIGGER /*_*/article_rankings_BEFORE_UPDATE BEFORE UPDATE ON /*_*/article_rankings FOR EACH ROW
BEGIN
	IF NEW.positive_votes != OLD.positive_votes THEN
		SET NEW.total_votes = OLD.total_votes + 1;
	END IF;
END
|
DELIMITER ;
