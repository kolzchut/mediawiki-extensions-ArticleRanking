<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Pager
 */

/**
 * @ingroup Pager
 */
namespace MediaWiki\Extension\ArticleRanking;

use MediaWiki\MediaWikiServices;
use SpecialPage;
use TablePager;
use TitleValue;


class FeedbackPager extends TablePager {

	protected $conds;
	protected $page;

	/**
	 * @param SpecialPage $page
	 * @param array $conds
	 */
	function __construct( $page, $conds ) {
		$this->page = $page;
		$this->conds = $conds;
		$this->mDefaultDirection = TablePager::DIR_DESCENDING;
		parent::__construct( $page->getContext() );
	}

	function getFieldNames() {
		static $headers = null;

		if ( $headers === null ) {
			$headers = [
				'page_title' => 'article-ranking-feedbacklist-title',
				'text' => 'article-ranking-feedbacklist-text',
				'vote' =>  'article-ranking-feedbacklist-vote',
				'timestamp' => 'article-ranking-feedbacklist-timestamp',
			];
			foreach ( $headers as $key => $val ) {
				$headers[$key] = $this->msg( $val )->text();
			}
		}

		return $headers;
	}

	function formatValue( $name, $value ) {
		$language = $this->getLanguage();
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

		$formatted = '';

		switch ( $name ) {
			case 'timestamp':
				$formatted = htmlspecialchars( $language->userTimeAndDate( $value, $this->getUser() ) );
				break;

			case 'page_title':
				$row = $this->getCurrentRow();
				$title = new TitleValue( (int)$row->page_namespace, $row->page_title );
				$formatted = $linkRenderer->makeKnownLink( $title );
				break;

			case 'text':
				$formatted = htmlspecialchars( $value );
				break;

			case 'vote':
				$formatted = $value;
				break;

			default:
				$formatted = "Unable to format $name";
				break;
		}

		return $formatted;
	}

	function getQueryInfo() {
		$query = [
			'tables' => [ 'article_rankings_votes_messages', 'page' ],
			'fields' => [
				'page_title',
				'page_namespace',
				'page_id',
				'text' => 'votes_messages',
				'vote' => 'positive_or_negative',
				'timestamp' => 'votes_timestamp'
			],
			'conds' => [
			],
			'join_conds' => [ 'page' => [ 'LEFT JOIN', 'votes_messages_page_id = page_id' ] ]
		];
		if ( isset( $this->conds['page'] ) ) {
			$dbr = wfGetDB( DB_REPLICA );
			$query['conds'][] = 'page_title ' . $dbr->buildLike( $this->conds['page'], $dbr->anyString() );
		}

		return $query;
	}

	function getDefaultSort() {
		return 'timestamp';
	}

	function isFieldSortable( $name ) {
		if ( in_array( $name, [ 'page_title', 'vote', 'timestamp' ] ) ) {
			return true;
		}
		return false;
	}

}
