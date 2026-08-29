<?php
/*
 * Project:     SPAM Detector
 * File:        DetectionThresholdTest.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\Tests\ValueObjects;

use PHPUnit\Framework\TestCase;
use SHWorX\SpamDetector\Exceptions\InvalidRuleSetException;
use SHWorX\SpamDetector\ValueObjects\DetectionThreshold;

final class DetectionThresholdTest extends TestCase
{
    public function testCreatesAValidThreshold(): void
    {
        $threshold = new DetectionThreshold(2, 4);
        self::assertSame(2, $threshold->minimumMatches);
        self::assertSame(4, $threshold->minimumScore);
    }

    public function testRejectsZeroMinimumMatches(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        new DetectionThreshold(0, 4);
    }

    public function testRejectsNegativeMinimumMatches(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        new DetectionThreshold(-1, 4);
    }

    public function testRejectsZeroMinimumScore(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        new DetectionThreshold(2, 0);
    }

    public function testRejectsNegativeMinimumScore(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        new DetectionThreshold(2, -1);
    }
}
