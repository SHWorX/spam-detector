<?php
/*
 * Project:     SPAM Detector
 * File:        SpamRuleSet.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\ValueObjects;

use SHWorX\SpamDetector\Exceptions\InvalidRuleSetException;

final readonly class SpamRuleSet
{
    public const int SUPPORTED_VERSION = 1;

    /**
     * @param int $version
     * @param DetectionThreshold $threshold
     * @param array<int, string> $names
     * @param array<int, string> $emails
     * @param array<string, int> $keywords
     */
    public function __construct(
        public int $version,
        public DetectionThreshold $threshold,
        public array $names,
        public array $emails,
        public array $keywords,
    ) {
        $this->validateVersion();
        $this->validateNames();
        $this->validateEmails();
        $this->validateKeywords();
    }

    /**
     * Validates the version of the JSON rule set schema
     *
     * @return void
     */
    private function validateVersion(): void
    {
        if ($this->version !== self::SUPPORTED_VERSION) {
            throw new InvalidRuleSetException(sprintf('Unsupported rule set version: %d.', $this->version));
        }
    }

    /**
     * Validates the name indicators
     *
     * @return void
     */
    private function validateNames(): void
    {
        $normalized = [];
        foreach ($this->names as $name) {
            if (!is_string($name) || trim($name) === '') {
                throw new InvalidRuleSetException('Every name indicator must be a non-empty string.');
            }

            $key = mb_strtolower(trim($name), 'UTF-8');
            if (isset($normalized[$key])) {
                throw new InvalidRuleSetException(sprintf('Duplicate name indicator: "%s".', $name));
            }

            $normalized[$key] = true;
        }
    }

    /**
     * Validates the email indicators
     *
     * @return void
     */
    private function validateEmails(): void
    {
        $normalized = [];
        foreach ($this->emails as $email) {
            if (!is_string($email) || trim($email) === '') {
                throw new InvalidRuleSetException('Every email indicator must be a non-empty string.');
            }

            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidRuleSetException(sprintf('Invalid email indicator: "%s".', $email));
            }

            $key = mb_strtolower($email, 'UTF-8');
            if (isset($normalized[$key])) {
                throw new InvalidRuleSetException(sprintf('Duplicate email indicator: "%s".', $email));
            }

            $normalized[$key] = true;
        }
    }

    /**
     * Validates the keyword indicators
     *
     * @return void
     */
    private function validateKeywords(): void
    {
        $normalized = [];
        foreach ($this->keywords as $keyword => $weight) {
            if (!is_string($keyword) || trim($keyword) === '') {
                throw new InvalidRuleSetException('Every keyword indicator must have a non-empty string key.');
            }

            if (!is_int($weight) || $weight < 1) {
                throw new InvalidRuleSetException(sprintf('Keyword "%s" must have a positive integer weight.', $keyword));
            }

            $key = mb_strtolower(trim($keyword), 'UTF-8');
            if (isset($normalized[$key])) {
                throw new InvalidRuleSetException(sprintf('Duplicate keyword indicator: "%s".', $keyword));
            }

            $normalized[$key] = true;
        }
    }
}
