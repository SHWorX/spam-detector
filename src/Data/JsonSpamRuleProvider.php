<?php
/*
 * Project:     SPAM Detector
 * File:        JsonSpamRuleProvider.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\Data;

use JsonException;
use SHWorX\SpamDetector\Contracts\SpamRuleProviderInterface;
use SHWorX\SpamDetector\Exceptions\InvalidRuleSetException;
use SHWorX\SpamDetector\ValueObjects\DetectionThreshold;
use SHWorX\SpamDetector\ValueObjects\SpamRuleSet;

final class JsonSpamRuleProvider implements SpamRuleProviderInterface
{
    /**
     * @param string $path
     */
    public function __construct(private readonly string $path) {
        if (trim($this->path) === '') {
            throw new InvalidRuleSetException('The spam rule file path must not be empty.');
        }
    }

    /**
     * Returns the rule set
     *
     * @return SpamRuleSet
     */
    public function getRuleSet(): SpamRuleSet
    {
        $contents = $this->readFile();

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidRuleSetException('The spam rule file contains invalid JSON.', previous: $exception);
        }

        if (!is_array($data)) {
            throw new InvalidRuleSetException('The spam rule file must contain a JSON object.');
        }

        return $this->createRuleSet($data);
    }

    /**
     * Read file
     *
     * @return string
     */
    private function readFile(): string
    {
        if (!is_file($this->path)) {
            throw new InvalidRuleSetException(sprintf('The spam rule file does not exist: "%s".', $this->path));
        }

        if (!is_readable($this->path)) {
            throw new InvalidRuleSetException(sprintf('The spam rule file is not readable: "%s".', $this->path));
        }

        $contents = file_get_contents($this->path);
        if ($contents === false) {
            throw new InvalidRuleSetException(sprintf('Unable to read the spam rule file: "%s".', $this->path));
        }

        return $contents;
    }

    /**
     * Create a rule set
     *
     * @param array<string, mixed> $data
     *
     * @return SpamRuleSet
     */
    private function createRuleSet(array $data): SpamRuleSet
    {
        $version = $this->requireInteger($data, 'version');
        $thresholds = $this->requireArray($data, 'thresholds');
        $indicators = $this->requireArray($data, 'indicators');

        $minimumMatches = $this->requireInteger($thresholds, 'minimumMatches');
        $minimumScore = $this->requireInteger($thresholds, 'minimumScore');

        $names = $this->requireList($indicators, 'names');
        $emails = $this->requireList($indicators, 'emails');
        $keywords = $this->requireKeywords($indicators, 'keywords');

        return new SpamRuleSet(
            version: $version,
            threshold: new DetectionThreshold(minimumMatches: $minimumMatches, minimumScore: $minimumScore),
            names: $names,
            emails: $emails,
            keywords: $keywords,
        );
    }

    /**
     * Requires the value of $data[$key] to be an integer.
     *
     * @param array<string, mixed> $data
     */
    private function requireInteger(array $data, string $key): int
    {
        if (!array_key_exists($key, $data) || !is_int($data[$key])) {
            throw new InvalidRuleSetException(sprintf('The "%s" value must be an integer.', $key));
        }

        return $data[$key];
    }

    /**
     * Requires the value of $data[$key] to be an array
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function requireArray(array $data, string $key): array
    {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            throw new InvalidRuleSetException(sprintf('The "%s" value must be an object.', $key));
        }

        return $data[$key];
    }

    /**
     * Requires the value of $data[$key] to be a list
     *
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function requireList(array $data, string $key): array
    {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            throw new InvalidRuleSetException(sprintf('The "%s" indicators must be an array.', $key));
        }

        if (!array_is_list($data[$key])) {
            throw new InvalidRuleSetException(sprintf('The "%s" indicators must be a list.', $key));
        }

        foreach ($data[$key] as $value) {
            if (!is_string($value)) {
                throw new InvalidRuleSetException(sprintf('Every "%s" indicator must be a string.', $key));
            }
        }

        return $data[$key];
    }

    /**
     * Requires the keywords
     *
     * @param array<string, mixed> $data
     * @return array<string, int>
     */
    private function requireKeywords(array $data, string $key): array
    {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            throw new InvalidRuleSetException('The "keywords" indicators must be an object.');
        }

        if (array_is_list($data[$key])) {
            throw new InvalidRuleSetException('The "keywords" indicators must be an object.');
        }

        foreach ($data[$key] as $keyword => $weight) {
            if (!is_string($keyword) || !is_int($weight)) {
                throw new InvalidRuleSetException('Every keyword must map to an integer weight.');
            }
        }

        return $data[$key];
    }
}
