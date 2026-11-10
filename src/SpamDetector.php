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
     * Keywords prepared once in the constructor (normalized for matching,
     * original kept for reporting).
     *
     * @var list<array{original: string, normalized: string, weight: int}>
     */
    private readonly array $preparedKeywords;

    /**
     * @param string $rulesPath The path to the JSON file with the rule sets
     */
    public function __construct(string $rulesPath)
    {
        $this->ruleProvider = new JsonSpamRuleProvider($rulesPath);
        $this->ruleSet = $this->ruleProvider->getRuleSet();
        $this->preparedKeywords = $this->prepareKeywords();
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
        $normalizedName = $this->normalizeText($senderName);
        if ($normalizedName === '') {
            return;
        }

        foreach ($this->ruleSet->names as $name) {
            if ($normalizedName === $this->normalizeText($name)) {
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
        $normalizedEmail = $this->normalizeText($senderEmail);
        if ($normalizedEmail === '') {
            return;
        }

        foreach ($this->ruleSet->emails as $email) {
            if ($normalizedEmail === $this->normalizeText($email)) {
                $matchedIndicators[] = ['type' => 'email', 'value' => $email, 'score' => self::EMAIL_SCORE];
                return;
            }
        }
    }

    /**
     * Matches keywords. Subject and body are normalized separately and joined
     * with a newline, so a keyword can never match across the subject/body
     * boundary. Runs of whitespace inside the text are collapsed to a single
     * space, so "wrote about     price" matches the keyword "wrote about price".
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
        $content = $this->normalizeText($subject) . "\n" . $this->normalizeText($body);

        foreach ($this->preparedKeywords as $keyword) {
            if (mb_strpos($content, $keyword['normalized']) === false) {
                continue;
            }

            $matchedIndicators[] = [
                'type' => 'keyword',
                'value' => $keyword['original'],
                'score' => $keyword['weight'],
            ];
        }
    }

    /**
     * Normalizes all keywords of the rule set once.
     *
     * @return list<array{original: string, normalized: string, weight: int}>
     */
    private function prepareKeywords(): array
    {
        $prepared = [];

        foreach ($this->ruleSet->keywords as $keyword => $weight) {
            $original = (string) $keyword;
            $normalized = $this->normalizeText($original);

            if ($normalized === '') {
                continue;
            }

            $prepared[] = [
                'original' => $original,
                'normalized' => $normalized,
                'weight' => $weight,
            ];
        }

        return $prepared;
    }

    /**
     * Lowercases the text, removes invisible zero-width characters, collapses
     * every run of whitespace (spaces, tabs, newlines, non-breaking spaces and
     * other Unicode separators) into a single space and trims the result.
     *
     * @param string $text
     *
     * @return string
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[\x{200B}-\x{200D}\x{2060}\x{FEFF}]+/u', '', $text) ?? $text;
        $text = preg_replace('/[\s\p{Z}]+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}