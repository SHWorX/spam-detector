<?php
/*
 * Project:     SPAM Detector
 * File:        SpamDetectionResultTest.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\Tests\ValueObjects;

use PHPUnit\Framework\TestCase;
use SHWorX\SpamDetector\ValueObjects\SpamDetectionResult;

final class SpamDetectionResultTest extends TestCase
{
    public function testStoresDetectionResult(): void
    {
        $indicators = [
            [
                'type' => 'email',
                'value' => 'spam@example.com',
                'score' => 3,
            ],
        ];

        $result = new SpamDetectionResult(true, 1, 3, $indicators);
        self::assertTrue($result->isSpam());
        self::assertSame(1, $result->matches());
        self::assertSame(3, $result->score());
        self::assertSame($indicators, $result->matchedIndicators());
    }

    public function testDefaultsToAnEmptyIndicatorList(): void
    {
        $result = new SpamDetectionResult(false, 0, 0);
        self::assertSame([], $result->matchedIndicators());
    }
}
