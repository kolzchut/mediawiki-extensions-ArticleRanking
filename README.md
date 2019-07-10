# Article Ranking

## Purpose

The purpose of this extension is to let users rank certain articles.

It can be used with or without a captcha; the captcha used is Google's
[Invisible Recaptcha](https://developers.google.com/recaptcha/docs/invisible),
which requires a site key and a secret key provided by Google.

One (smaller) part of this extension is used to launch an external
change proposal form. This is dependant on extension:WRShareBar.

## Configuration

| Main Key                 | sub-key                | default                 | description
|--------------------------|------------------------|-------------------------|------------
| $wgArticleRankingConfig  | `trackClicks`          | true                    | whether to use Google Analytics to track votes
| $wgArticleRankingConfig  | `changerequest['url']` | "/forms/ChangeRequest/" | the location of the change request form
| $wgArticleRankingCaptcha | `siteKey`              | empty                   | Google's captcha site key
| $wgArticleRankingTemplatePath |                | empty                   | Path of directory includes template file.
| $wgArticleRankingTemplateFileName |                | voting                   | mustache file name
| $wgArticleRankingAddChangeRequest |                | true                   | If to add change request part

Leaving either of the $wgArticleRankingCaptcha keys empty will disable
the use of the captcha, falling back to only using a MediaWiki token
to verify (basically a CSRF protection and nothing more).  
When omitting  $wgArticleRankingTemplatePath `ArticleRanking/templates` used.

## Templating  
Best way is copy from `ArticleRanking/templates/voting.mustache` and modifing it.
The dynamic of JS is applyed by classes adding and removing.
When button clicked it would get `selected` class. When call is firing - `on-call`. When finishing - both removed and `after-success-call` is set (if succeed).

**Into the button:**  
`.ranking-on-request` is visible just when button have `.on-call` and hidden otherwise. All others are the opposite.  
`.ranking-just-before` is visible just before `.after-success-call`. `.ranking-just-after` - the opposite.  
`.ranking-always` is visible on both cases (but not on `.ranking-on-request`)


`.voting-messages` holds messages. It's hidden by default, and getting `show` class after call returns (both success or failure).  
When the call returns, it also get `.voting-messages-wrp-success` or `.voting-messages-wrp-failure` respectively which hide or show `.voting-messages-failure` and `.voting-messages-success`.

## Hooks
`ArticleRankingTemplateParams` allows you to modify the parmams passed into the mustache template. You can pass to the hook additional parameters to use, when calling `ArticleRanking::createRankingSection`.  
For example, pass pageId to use the title.

```
$html .= ArticleRanking::createRankingSection(['pageId' => $pageId]);
```
Then:  
```
public static function onArticleRankingTemplateParams( &$params, $additionalParams ) {
		$title = Title::newFromID( $additionalParams['pageId']);
		$params['section1title'] = 'Do you like ' . $title->getFullText() . '?';
}
```


## API modules

### Updating a vote count for a specific page
To update a vote count, make a POST request to
`http://example.com/api.php?action=rank-vote&id={page_id}&vote={vote_type}&token={token}&format=json`

| Parameter    | Type    | Description
|--------------|---------|------------
| vote_type    | Integer | 1 for a positive vote, 0 for a negative vote
| page_id      | Integer | The page id of the page being ranked
| captchaToken | String  | The captcha token to be verified; Retrieved from the frontend

### Getting vote count for a specific page
To get a page vote count, make a GET request to `http://example.com/api.php?action=rank-votes&id={page_id}&format=json`

| Parameter | Type    | Description
|-----------|---------|------------
| page_id   | Integer | The page id of the page being queried
