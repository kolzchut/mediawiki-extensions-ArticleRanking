# Article Ranking

## Purpose

The purpose of this extension is to let users rank certain articles. 

[Invisible Recaptcha](https://developers.google.com/recaptcha/docs/invisible)
is also provided and requires a site key and a secret key provided by Google.

One (smaller) part of this extension is used to launch an external change
proposal form. This is dependant on extension:WRShareBar.

## Configuration

ArticleRankingConfig - Currently contains various properties to be considered
when a user clicks the 'propose changes' button

ArticleRankingCaptcha - Contains the site key and secret key pair for the captcha

## How to use

### Updating a vote count for a specific page
To update a vote count, make a POST request to
`http://example.com/api.php?action=rank-vote&id={page_id}&vote={vote_type}&token={token}&format=json`

| Parameter | Type | Description |
|-----------|-------------|------|
| vote_type | Integer     | 1 for a positive vote, 0 for a negative vote |
| page_id   | Integer     | The page id of the page being ranked |
| token     | String      | The captcha token to be verified; Retrieved from the frontend |

### Getting vote count for a specific page
To get a page vote count, make a GET request to `http://example.com/api.php?action=rank-votes&id={page_id}&format=json`

| Parameter | Type | Description |
|-----------|-------------|------|
| page_id     | Integer      | The page id of the page being queried |
