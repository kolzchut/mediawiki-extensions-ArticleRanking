# Article Ranking

## Purpose

The purpose of this extension is to let users rank certain articles.

It can be used with or without a captcha; the captcha used is [hCaptcha's
Invisible Recaptcha](https://docs.hcaptcha.com/invisible),
which requires a site key and a secret key provided.
Please note that if you use the captcha, it is recommended for legal and privacy reasons to include
a note and a link to hCaptcha's privacy policy. See details here:
https://docs.hcaptcha.com/faq#do-i-need-to-display-anything-on-the-page-when-using-hcaptcha-in-invisible-mode

One (smaller) part of this extension is used to launch an external change proposal form.
This is dependent on extension:WRShareBar.

## Upgrading from v1 to v2
You __must__ run `update.php` immediately. It will create a new table, migrate data from the old one and then remove it.

## Configuration

| Main Key                 | sub-key                | default                 | description                                    |
|--------------------------|------------------------|-------------------------|------------------------------------------------|
| $wgArticleRankingConfig  | `trackClicks`          | true                    | whether to use Google Analytics to track votes |
| $wgArticleRankingConfig  | `changerequest['url']` | "/forms/ChangeRequest/" | the location of the change request form        |
| $wgArticleRankingCaptcha | `siteKey`              | empty                   | Captcha site key                               |
| $wgArticleRankingCaptcha | `secret`               | empty                   | Captcha secret key                             |

Leaving either of the $wgArticleRankingCaptcha keys empty will disable
the use of the captcha, falling back to only using a MediaWiki token
to verify (basically a CSRF protection and nothing more).

## Report
`Special:ArticleRanking` shows the current results, including some filtering.
In the future it will also probably allow drilling down into individual votes for a page.

If [extension:ArticleContentArea](https://github.com/kolzchut/mediawiki-extensions-ArticleContentArea)
is installed, it will allow filtering by the content area in the report. 


## API modules

### Updating a vote count for a specific page
To update a vote count, make a POST request to
`https://example.com/api.php?action=rank-vote&pageid={page_id}&vote={vote_type}&token={token}&format=json`

| Parameter    | Type    | Description                                                   |
|--------------|---------|---------------------------------------------------------------|
| vote         | Integer | 1 for a positive vote, -1 for a negative vote                 |
| pageid       | Integer | The page id of the page being ranked                          |
| captchaToken | String  | The captcha token to be verified; Retrieved from the frontend |

### Getting vote count for a specific page
To get a page vote count, make a GET request to `http://example.com/api.php?action=rank-votes&pageid={page_id}&format=json`

| Parameter  | Type    | Description                           |
|------------|---------|---------------------------------------|
| pageid     | Integer | The page id of the page being queried |


## TODO
- Allow getting the vote count by page title
- Add a user right for getting the vote count through the API
- Maybe: add a user right for voting
