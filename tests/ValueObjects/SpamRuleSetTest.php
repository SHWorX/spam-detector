<?php
/*
 * Project:     SPAM Detector
 * File:        SpamRuleSetTest.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\Tests\ValueObjects;

use PHPUnit\Framework\TestCase;
use SHWorX\SpamDetector\Exceptions\InvalidRuleSetException;
use SHWorX\SpamDetector\ValueObjects\DetectionThreshold;
use SHWorX\SpamDetector\ValueObjects\SpamRuleSet;

class SpamRuleSetTest extends TestCase
{
    /**
     * Creates a new spam rule set
     *
     * @param int $version
     * @param array $names
     * @param array $emails
     * @param array $keywords
     *
     * @return SpamRuleSet
     */
    private function createRuleSet(
        int $version = 1,
        array $names = [],
        array $emails = [],
        array $keywords = [],
    ): SpamRuleSet
    {
        return new SpamRuleSet(
            version: $version,
            threshold: new DetectionThreshold(2, 4),
            names: $names,
            emails: $emails,
            keywords: $keywords,
        );
    }

    public function testCreatesAValidRuleSet(): void
    {
        $ruleSet = $this->createRuleSet(
            names: ['John Doe'],
            emails: ['spam@example.com'],
            keywords: ['winner' => 3],
        );

        self::assertSame(1, $ruleSet->version);
        self::assertSame(['John Doe'], $ruleSet->names);
        self::assertSame(['spam@example.com'], $ruleSet->emails);
        self::assertSame(['winner' => 3], $ruleSet->keywords);
    }

    public function testRejectsUnsupportedVersion(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        $this->createRuleSet(version: 2);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        $this->createRuleSet(names: ['']);
    }

    public function testRejectsDuplicateNamesIgnoringCase(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        $this->createRuleSet(names: ['John Doe', 'john doe']);
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        $this->createRuleSet(emails: ['not-an-email']);
    }

    public function testRejectsDuplicateEmailsIgnoringCase(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        $this->createRuleSet(emails: ['spam@example.com', 'SPAM@example.com']);
    }

    public function testRejectsEmptyKeyword(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        $this->createRuleSet(keywords: ['' => 2]);
    }

    public function testRejectsZeroKeywordWeight(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        $this->createRuleSet(keywords: ['winner' => 0]);
    }

    public function testRejectsNegativeKeywordWeight(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        $this->createRuleSet(keywords: ['winner' => -1]);
    }

    public function testRejectsDuplicateKeywordsIgnoringCase(): void
    {
        $this->expectException(InvalidRuleSetException::class);
        $this->createRuleSet(keywords: ['winner' => 2, 'WINNER' => 3]);
    }
}
