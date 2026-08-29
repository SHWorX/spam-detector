<?php
/*
 * Project:     SPAM Detector
 * File:        DetectionThreshold.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\ValueObjects;

use SHWorX\SpamDetector\Exceptions\InvalidRuleSetException;

final readonly class DetectionThreshold
{
    /**
     * @param int $minimumMatches
     * @param int $minimumScore
     *
     * @throws InvalidRuleSetException
     */
    public function __construct(public int $minimumMatches, public int $minimumScore)
    {
        if ($minimumMatches < 1) {
            throw new InvalidRuleSetException('The minimum match threshold must be at least 1.');
        }

        if ($minimumScore < 1) {
            throw new InvalidRuleSetException('The minimum score threshold must be at least 1.');
        }
    }
}
