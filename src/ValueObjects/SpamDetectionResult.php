<?php
/*
 * Project:     SPAM Detector
 * File:        SpamDetectionResult.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\ValueObjects;

final readonly class SpamDetectionResult
{
    /**
     * @param bool $isSpam
     * @param int $matches
     * @param int $score
     * @param array<int, array{
     *     type: string,
     *     value: string,
     *     score: int
     * }> $matchedIndicators
     */
    public function __construct(
        public bool $isSpam,
        public int $matches,
        public int $score,
        public array $matchedIndicators = [],
    ) { }

    /**
     * @return bool
     */
    public function isSpam(): bool
    {
        return $this->isSpam;
    }

    /**
     * @return int
     */
    public function matches(): int
    {
        return $this->matches;
    }

    /**
     * @return int
     */
    public function score(): int
    {
        return $this->score;
    }

    /**
     * @return array<int, array{
     *     type: string,
     *     value: string,
     *     score: int
     * }>
     */
    public function matchedIndicators(): array
    {
        return $this->matchedIndicators;
    }
}
