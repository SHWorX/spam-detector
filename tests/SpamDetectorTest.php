<?php
/*
 * Project:     SPAM Detector
 * File:        SpamDetectorTest.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;
use SHWorX\SpamDetector\Exceptions\InvalidRuleSetException;
use SHWorX\SpamDetector\SpamDetector;

class SpamDetectorTest extends TestCase
{
    private string $rulesPath;

    /**
     * @throws JsonException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $path = tempnam(sys_get_temp_dir(), 'spam-rules-');
        if ($path === false) {
            self::fail('Unable to create temporary rule file.');
        }
        $this->rulesPath = $path;

        $this->writeRules([
            'version' => 1,
            'thresholds' => [
                'minimumMatches' => 2,
                'minimumScore' => 4,
            ],
            'indicators' => [
                'names' => [
                    'Danielscere',
                ],
                'emails' => [
                    'spam@example.com',
                ],
                'keywords' => [
                    'winner' => 3,
                    'prize' => 2,
                    'urgent' => 1,
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->rulesPath) && is_file($this->rulesPath)) {
            unlink($this->rulesPath);
        }

        parent::tearDown();
    }

    public function testItDetectsSpamUsingSenderEmailAndKeyword(): void
    {
        $detector = new SpamDetector($this->rulesPath);
        $result = $detector->detect(
            'Unknown Sender',
            'spam@example.com',
            'You are a winner',
            'Claim your prize now.',
        );

        self::assertTrue($result->isSpam());
        self::assertSame(3, $result->matches());
        self::assertSame(8, $result->score());
        self::assertCount(3, $result->matchedIndicators());
    }

    public function testItDetectsSpamUsingSenderNameAndKeyword(): void
    {
        $detector = new SpamDetector($this->rulesPath);
        $result = $detector->detect(
            'Danielscere',
            'normal@example.com',
            'Winner notification',
            'Please review this message.',
        );

        self::assertTrue($result->isSpam());
        self::assertSame(2, $result->matches());
        self::assertSame(5, $result->score());
    }

    public function testMatchingIsCaseInsensitive(): void
    {
        $detector = new SpamDetector($this->rulesPath);
        $result = $detector->detect(
            'DANIELSCERE',
            'SPAM@EXAMPLE.COM',
            'WINNER',
            '',
        );

        self::assertTrue($result->isSpam());
        self::assertSame(3, $result->matches());
        self::assertSame(8, $result->score());
    }

    public function testRepeatedKeywordCountsOnlyOnce(): void
    {
        $detector = new SpamDetector($this->rulesPath);
        $result = $detector->detect(
            'Unknown Sender',
            'normal@example.com',
            'Winner winner winner',
            'winner winner winner',
        );

        self::assertFalse($result->isSpam());
        self::assertSame(1, $result->matches());
        self::assertSame(3, $result->score());
    }

    public function testSubjectAndBodyAreBothSearched(): void
    {
        $detector = new SpamDetector($this->rulesPath);
        $result = $detector->detect(
            'Unknown Sender',
            'normal@example.com',
            'Normal subject',
            'This message contains a winner.',
        );

        self::assertSame(1, $result->matches());
        self::assertSame(3, $result->score());
    }

    /**
     * @throws JsonException
     */
    public function testItRequiresBothThresholds(): void
    {
        $this->writeRules([
            'version' => 1,
            'thresholds' => [
                'minimumMatches' => 3,
                'minimumScore' => 4,
            ],
            'indicators' => [
                'names' => [
                    'Danielscere',
                ],
                'emails' => [
                    'spam@example.com',
                ],
                'keywords' => [
                    'winner' => 3,
                ],
            ],
        ]);

        $detector = new SpamDetector($this->rulesPath);
        $result = $detector->detect(
            'Danielscere',
            'normal@example.com',
            'Winner',
            '',
        );

        self::assertFalse($result->isSpam());
        self::assertSame(2, $result->matches());
        self::assertSame(5, $result->score());
    }

    public function testItRejectsMessagesThatReachMatchThresholdButNotScore(): void
    {
        $detector = new SpamDetector($this->rulesPath);
        $result = $detector->detect(
            'Danielscere',
            'normal@example.com',
            'Urgent',
            '',
        );

        self::assertFalse($result->isSpam());
        self::assertSame(2, $result->matches());
        self::assertSame(3, $result->score());
    }

    public function testItReturnsNoMatchesForCleanMessage(): void
    {
        $detector = new SpamDetector($this->rulesPath);
        $result = $detector->detect(
            'John Smith',
            'john@example.com',
            'Meeting tomorrow',
            'Let us meet at 10am.',
        );

        self::assertFalse($result->isSpam());
        self::assertSame(0, $result->matches());
        self::assertSame(0, $result->score());
        self::assertSame([], $result->matchedIndicators());
    }

    public function testItRejectsAnInvalidRulesFileDuringInitialization(): void
    {
        file_put_contents($this->rulesPath, '{invalid');
        $this->expectException(InvalidRuleSetException::class);
        new SpamDetector($this->rulesPath);
    }

    /**
     * @param array<string, mixed> $rules
     *
     * @throws JsonException
     */
    private function writeRules(array $rules): void
    {
        $json = json_encode($rules, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        file_put_contents($this->rulesPath, $json);
    }
}
