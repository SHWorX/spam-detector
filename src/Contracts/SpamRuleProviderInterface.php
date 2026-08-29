<?php
/*
 * Project:     SPAM Detector
 * File:        SpamRuleProviderInterface.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\Contracts;

use SHWorX\SpamDetector\ValueObjects\SpamRuleSet;

interface SpamRuleProviderInterface
{
    public function getRuleSet(): SpamRuleSet;
}
