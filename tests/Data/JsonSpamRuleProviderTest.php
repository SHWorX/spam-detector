<?php
/*
 * Project:     SPAM Detector
 * File:        JsonSpamRuleProviderTest.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\Tests\Data;

use JsonException;
use PHPUnit\Framework\TestCase;
use SHWorX\SpamDetector\Data\JsonSpamRuleProvider;
use SHWorX\SpamDetector\Exceptions\InvalidRuleSetException;

class JsonSpamRuleProviderTest extends TestCase
{
    private string $rulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $path = tempnam(sys_get_temp_dir(), 'spam-rules-');
        if ($path === false) {
            self::fail('Unable to create temporary rule file.');
        }

        $this->rulesPath = $path;
    }

    protected function tearDown(): void
    {
        if (isset($this->rulesPath) && is_file($this->rulesPath)) {
            unlink($this->rulesPath);
        }

        parent::tearDown();
    }

    /**
     * @throws JsonException
     */
    public function testLoadsAValidRuleSet(): void
    {
        $this->writeRules([
            'version' => 1,
            'thresholds' => [
                'minimumMatches' => 2,
                'minimumScore' => 4,
            ],
            'indicators' => [
                'names' => ['John Doe'],
                'emails' => ['spam@example.com'],
                'keywords' => [
                    'winner' => 3,
                    'prize' => 2,
                ],
            ],
        ]);

        $ruleSet = new JsonSpamRuleProvider($this->rulesPath);
        $result = $ruleSet->getRuleSet();

        self::assertSame(1, $result->version);
        self::assertSame(2, $result->threshold->minimumMatches);
        self::assertSame(4, $result->threshold->minimumScore);
        self::assertSame(['John Doe'], $result->names);
        self::assertSame(['spam@example.com'], $result->emails);
        self::assertSame(['winner' => 3, 'prize' => 2], $result->keywords);
    }

    public function testRejectsMissingFile(): void
    {
        $provider = new JsonSpamRuleProvider($this->rulesPath . '-missing');
        $this->expectException(InvalidRuleSetException::class);
        $provider->getRuleSet();
    }

    public function testRejectsInvalidJson(): void
    {
        file_put_contents($this->rulesPath, '{invalid');

        $provider = new JsonSpamRuleProvider($this->rulesPath);
        $this->expectException(InvalidRuleSetException::class);
        $provider->getRuleSet();
    }

    /**
     * @throws JsonException
     */
    public function testRejectsMissingVersion(): void
    {
        $this->writeRules([
            'thresholds' => [
                'minimumMatches' => 2,
                'minimumScore' => 4,
            ],
            'indicators' => [
                'names' => [],
                'emails' => [],
                'keywords' => [],
            ],
        ]);

        $this->expectException(InvalidRuleSetException::class);
        new JsonSpamRuleProvider($this->rulesPath)->getRuleSet();
    }

    /**
     * @throws JsonException
     */
    public function testRejectsMissingThresholds(): void
    {
        $this->writeRules([
            'version' => 1,
            'indicators' => [
                'names' => [],
                'emails' => [],
                'keywords' => [],
            ],
        ]);

        $this->expectException(InvalidRuleSetException::class);
        new JsonSpamRuleProvider($this->rulesPath)->getRuleSet();
    }

    /**
     * @throws JsonException
     */
    public function testRejectsInvalidThresholdType(): void
    {
        $this->writeRules([
            'version' => 1,
            'thresholds' => [
                'minimumMatches' => '2',
                'minimumScore' => 4,
            ],
            'indicators' => [
                'names' => [],
                'emails' => [],
                'keywords' => [],
            ],
        ]);

        $this->expectException(InvalidRuleSetException::class);
        new JsonSpamRuleProvider($this->rulesPath)->getRuleSet();
    }

    /**
     * @throws JsonException
     */
    public function testRejectsInvalidIndicators(): void
    {
        $this->writeRules([
            'version' => 1,
            'thresholds' => [
                'minimumMatches' => 2,
                'minimumScore' => 4,
            ],
            'indicators' => [
                'names' => 'John Doe',
                'emails' => [],
                'keywords' => [],
            ],
        ]);

        $this->expectException(InvalidRuleSetException::class);
        new JsonSpamRuleProvider($this->rulesPath)->getRuleSet();
    }

    /**
     * @throws JsonException
     */
    public function testRejectsInvalidKeywordWeight(): void
    {
        $this->writeRules([
            'version' => 1,
            'thresholds' => [
                'minimumMatches' => 2,
                'minimumScore' => 4,
            ],
            'indicators' => [
                'names' => [],
                'emails' => [],
                'keywords' => [
                    'winner' => '3',
                ],
            ],
        ]);

        $this->expectException(InvalidRuleSetException::class);
        new JsonSpamRuleProvider($this->rulesPath)->getRuleSet();
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
