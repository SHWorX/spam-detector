# Spam Detector

A lightweight, configurable spam detection library for PHP.

Spam Detector analyzes email metadata and content against a configurable JSON rule set. It uses weighted indicators and configurable detection thresholds to determine whether an email should be classified as spam.

## Requirements

- PHP 8.5 or higher
- `ext-mbstring`

## Installation

Install the package via Composer:

```bash
composer require shworx/spam-detector
```

## Quick Start

Spam rules are application-owned and are not included in the package.

Create a JSON rule file in your application:

```json
{
    "version": 1,
    "thresholds": {
        "minimumMatches": 2,
        "minimumScore": 4
    },
    "indicators": {
        "names": [],
        "emails": [],
        "keywords": {}
    }
}
```

Initialize the detector by passing the path to the rule file:

```php
use SHWorX\SpamDetector\SpamDetector;

$detector = new SpamDetector(
    __DIR__ . '/config/spam-rules.json'
);

$result = $detector->detect(
    senderName: 'John Doe',
    senderEmail: 'john@example.com',
    subject: 'You have won!',
    body: 'Claim your prize now.',
);

if ($result->isSpam()) {
    // Handle spam.
}
```

## Rule Configuration

Spam Detector uses a versioned JSON configuration file.

```json
{
    "version": 1,
    "thresholds": {
        "minimumMatches": 2,
        "minimumScore": 4
    },
    "indicators": {
        "names": [
            "Known Spam Sender"
        ],
        "emails": [
            "spam@example.com"
        ],
        "keywords": {
            "winner": 3,
            "claim your prize": 4,
            "urgent": 1
        }
    }
}
```

### Version

The `version` field defines the rule-set format version.

Currently supported:

```json
{
    "version": 1
}
```

Unsupported versions will cause an `InvalidRuleSetException`.

### Thresholds

The `thresholds` section defines when an email is classified as spam.

```json
{
    "thresholds": {
        "minimumMatches": 2,
        "minimumScore": 4
    }
}
```

Both conditions must be satisfied:

```text
matches >= minimumMatches
AND
score >= minimumScore
```

For example:

| Matches | Score | Result |
| --- | --- | --- |
| 1 | 10 | Not spam |
| 3 | 3 | Not spam |
| 2 | 4 | Spam |
| 3 | 8 | Spam |

### Name Indicators

Names are compared against the sender name.

```json
{
    "names": [
        "Known Spam Sender",
        "Another Sender"
    ]
}
```

Name matching is case-insensitive.

Each matching name contributes:

- 1 match
- 2 score points

A name indicator is counted only once.

### Email Indicators

Email addresses are compared against the sender email address.

```json
{
    "emails": [
        "spam@example.com",
        "another-spammer@example.com"
    ]
}
```

Email matching is case-insensitive.

Each matching email address contributes:

- 1 match
- 3 score points

An email indicator is counted only once.

All configured email indicators must contain valid email addresses.

### Keyword Indicators

Keywords are searched in both the email subject and message body.

```json
{
    "keywords": {
        "winner": 3,
        "claim your prize": 4,
        "urgent": 1
    }
}
```

The JSON key is the keyword or phrase, while the value defines its score.

```text
"keyword": score
```

Keyword matching is case-insensitive.

Each configured keyword contributes at most once, even when it occurs multiple times in the email.

For example, given:

```json
{
    "keywords": {
        "winner": 3
    }
}
```

The following message:

```text
Winner! Winner! Winner! You are a winner!
```

produces:

```text
Matches: 1
Score:   3
```

## Detection

The `detect()` method accepts four values:

```php
$result = $detector->detect(
    senderName: string,
    senderEmail: string,
    subject: string,
    body: string,
);
```

Example:

```php
$result = $detector->detect(
    senderName: 'Known Spam Sender',
    senderEmail: 'spam@example.com',
    subject: 'URGENT: You are a winner',
    body: 'Claim your prize immediately.',
);
```

The method returns a `SpamDetectionResult`.

## Detection Result

`SpamDetectionResult` provides information about the detection process.

### Check Spam Status

```php
$result->isSpam();
```

Returns:

```php
bool
```

### Get Number of Matches

```php
$result->matches();
```

Returns the number of unique indicators matched.

### Get Score

```php
$result->score();
```

Returns the total score of all matched indicators.

### Get Matched Indicators

```php
$result->matchedIndicators();
```

Returns an array containing information about every matched indicator.

Example:

```php
[
    [
        'type' => 'name',
        'value' => 'Known Spam Sender',
        'score' => 2,
    ],
    [
        'type' => 'email',
        'value' => 'spam@example.com',
        'score' => 3,
    ],
    [
        'type' => 'keyword',
        'value' => 'winner',
        'score' => 3,
    ],
]
```

## Scoring

The current scoring model is:

| Indicator | Matches | Score |
| --- | ---: | ---: |
| Name | 1 | 2 |
| Email | 1 | 3 |
| Keyword | 1 | Configured weight |

The total number of matches and score are calculated independently.

An email is classified as spam only when both configured thresholds are met.

## Example

Rule configuration:

```json
{
    "version": 1,
    "thresholds": {
        "minimumMatches": 2,
        "minimumScore": 4
    },
    "indicators": {
        "names": [
            "Spam Sender"
        ],
        "emails": [
            "spam@example.com"
        ],
        "keywords": {
            "winner": 3,
            "urgent": 1
        }
    }
}
```

Detection:

```php
use SHWorX\SpamDetector\SpamDetector;

$detector = new SpamDetector(
    __DIR__ . '/config/spam-rules.json'
);

$result = $detector->detect(
    senderName: 'Spam Sender',
    senderEmail: 'unknown@example.com',
    subject: 'URGENT: You are a winner',
    body: 'Congratulations!',
);
```

Result:

```php
$result->isSpam();
// true

$result->matches();
// 3

$result->score();
// 6
```

The detection consists of:

| Indicator | Match | Score |
| --- | --- | ---: |
| Name | Spam Sender | 2 |
| Keyword | winner | 3 |
| Keyword | urgent | 1 |
| **Total** | **3 matches** | **6** |

Since both thresholds are satisfied, the email is classified as spam.

## Rule Validation

Rule files are validated when the `SpamDetector` is initialized.

The following conditions are validated:

- The rule file exists.
- The rule file is readable.
- The file contains valid JSON.
- The root JSON value is an object.
- The rule-set version is supported.
- `thresholds` is present and valid.
- `minimumMatches` is a positive integer.
- `minimumScore` is a positive integer.
- `indicators` is present and valid.
- `names` is an array of non-empty strings.
- `emails` is an array of valid email addresses.
- `keywords` is an object with non-empty string keys.
- Keyword weights are positive integers.
- Duplicate names are rejected case-insensitively.
- Duplicate email addresses are rejected case-insensitively.
- Duplicate keywords are rejected case-insensitively.

Invalid rule sets throw:

```php
SHWorX\SpamDetector\Exceptions\InvalidRuleSetException
```

Example:

```php
use SHWorX\SpamDetector\Exceptions\InvalidRuleSetException;
use SHWorX\SpamDetector\SpamDetector;

try {
    $detector = new SpamDetector(
        __DIR__ . '/config/spam-rules.json'
    );
} catch (InvalidRuleSetException $exception) {
    // Handle invalid rule configuration.
}
```

## Architecture

The package separates spam detection from rule loading.

```text
Application
    |
    | JSON rule file path
    v
SpamDetector
    |
    v
JsonSpamRuleProvider
    |
    v
SpamRuleSet
    |
    v
DetectionThreshold
```

The JSON provider is used internally by `SpamDetector`.

The public API remains simple:

```php
$detector = new SpamDetector('/path/to/spam-rules.json');
```

The actual spam rules remain outside the Composer package and are owned by the consuming application.

## Testing

Run the test suite:

```bash
composer test
```

Run the test suite with coverage output:

```bash
composer test-coverage
```

## License

Spam Detector is open-sourced software licensed under the [MIT license](LICENSE).