<?php
/*
 * Project:     SPAM Detector
 * File:        SpamDetector.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector;

use SHWorX\SpamDetector\Contracts\SpamRuleProviderInterface;
use SHWorX\SpamDetector\Data\JsonSpamRuleProvider;
use SHWorX\SpamDetector\ValueObjects\SpamDetectionResult;
use SHWorX\SpamDetector\ValueObjects\SpamRuleSet;

final class SpamDetector
{
    private const int NAME_SCORE = 2;
    private const int EMAIL_SCORE = 3;

    private readonly SpamRuleProviderInterface $ruleProvider;
    private readonly SpamRuleSet $ruleSet;

    /**
     * @param string $rulesPath The path to the JSON file with the rule sets
     */
    public function __construct(string $rulesPath)
    {
        $this->ruleProvider = new JsonSpamRuleProvider($rulesPath);
        $this->ruleSet = $this->ruleProvider->getRuleSet();
    }

    /**
     * This method runs the detection scans if an email is a SPAM mail or not
     *
     * @param string $senderName
     * @param string $senderEmail
     * @param string $subject
     * @param string $body
     *
     * @return SpamDetectionResult
     */
    public function detect(
        string $senderName,
        string $senderEmail,
        string $subject,
        string $body,
    ): SpamDetectionResult
    {
        $matchedIndicators = [];

        $this->matchName($senderName, $matchedIndicators);
        $this->matchEmail($senderEmail, $matchedIndicators);
        $this->matchKeywords($subject, $body, $matchedIndicators);
        $matches = count($matchedIndicators);
        $score = array_sum(array_column($matchedIndicators, 'score'));
        $isSpam = $matches >= $this->ruleSet->threshold->minimumMatches
            && $score >= $this->ruleSet->threshold->minimumScore;

        return new SpamDetectionResult(
            isSpam: $isSpam,
            matches: $matches,
            score: $score,
            matchedIndicators: $matchedIndicators,
        );
    }

    /**
     * Matches the name
     *
     * @param string $senderName
     * @param array<int, array{
     *     type: string,
     *     value: string,
     *     score: int
     * }> $matchedIndicators
     *
     * @return void
     */
    private function matchName(string $senderName, array &$matchedIndicators): void
    {
        $normalizedName = mb_strtolower(trim($senderName), 'UTF-8');
        if ($normalizedName === '') {
            return;
        }

        foreach ($this->ruleSet->names as $name) {
            if ($normalizedName === mb_strtolower(trim($name), 'UTF-8')) {
                $matchedIndicators[] = ['type' => 'name', 'value' => $name, 'score' => self::NAME_SCORE];
                return;
            }
        }
    }

    /**
     * Matches the email address
     *
     * @param string $senderEmail
     * @param array<int, array{
     *     type: string,
     *     value: string,
     *     score: int
     * }> $matchedIndicators
     *
     * @return void
     */
    private function matchEmail(string $senderEmail, array &$matchedIndicators): void
    {
        $normalizedEmail = mb_strtolower(trim($senderEmail), 'UTF-8');
        if ($normalizedEmail === '') {
            return;
        }

        foreach ($this->ruleSet->emails as $email) {
            if ($normalizedEmail === mb_strtolower(trim($email), 'UTF-8')) {
                $matchedIndicators[] = ['type' => 'email', 'value' => $email, 'score' => self::EMAIL_SCORE];
                return;
            }
        }
    }

    /**
     * Matches keywords
     *
     * @param string $subject
     * @param string $body
     * @param array<int, array{
     *     type: string,
     *     value: string,
     *     score: int
     * }> $matchedIndicators
     *
     * @return void
     */
    private function matchKeywords(
        string $subject,
        string $body,
        array &$matchedIndicators,
    ): void
    {
        $content = mb_strtolower($subject . "\n" . $body, 'UTF-8');

        foreach ($this->ruleSet->keywords as $keyword => $weight) {
            $normalizedKeyword = mb_strtolower(trim($keyword), 'UTF-8');

            if (mb_strpos($content, $normalizedKeyword) === false) {
                continue;
            }

            $matchedIndicators[] = [
                'type' => 'keyword',
                'value' => $keyword,
                'score' => $weight,
            ];
        }
    }
}
