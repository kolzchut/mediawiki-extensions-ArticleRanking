# Article Ranking

## Purpose

The purpose of this extension is to let users rank certain articles.

It can be used with or without a captcha; the captcha used is Google's
[Invisible Recaptcha](https://developers.google.com/recaptcha/docs/invisible),
which requires a site key and a secret key provided by Google.

One (smaller) part of this extension is used to launch an external
change proposal form. This is dependent on extension:WRShareBar.

## Configuration

| Main Key                 | sub-key                | default                 | description                                    |
|--------------------------|------------------------|-------------------------|------------------------------------------------|
| $wgArticleRankingConfig  | `trackClicks`          | true                    | whether to use Google Analytics to track votes |
| $wgArticleRankingConfig  | `changerequest['url']` | "/forms/ChangeRequest/" | the location of the change request form        |
| $wgArticleRankingCaptcha | `siteKey`              | empty                   | Google's captcha site key                      |
| $wgArticleRankingCaptcha | `secret`               | empty                   | Google's captcha secret key                    |

Leaving either of the $wgArticleRankingCaptcha keys empty will disable
the use of the captcha, falling back to only using a MediaWiki token
to verify (basically a CSRF protection and nothing more).

## API modules

### Updating a vote count for a specific page
To update a vote count, make a POST request to
`http://example.com/api.php?action=rank-vote&id={page_id}&vote={vote_type}&token={token}&format=json`

| Parameter    | Type    | Description                                                   |
|--------------|---------|---------------------------------------------------------------|
| vote_type    | Integer | 1 for a positive vote, -1 for a negative vote                 |
| page_id      | Integer | The page id of the page being ranked                          |
| captchaToken | String  | The captcha token to be verified; Retrieved from the frontend |

### Getting vote count for a specific page
To get a page vote count, make a GET request to `http://example.com/api.php?action=rank-votes&id={page_id}&format=json`

| Parameter | Type    | Description                           |
|-----------|---------|---------------------------------------|
| page_id   | Integer | The page id of the page being queried |
