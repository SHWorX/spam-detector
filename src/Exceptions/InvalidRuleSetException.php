<?php
/*
 * Project:     SPAM Detector
 * File:        InvalidRuleSetException.php
 * Date:        2026-08-29
 * Author:      Steffen Haase <shworx.development@gmail.com
 * Copyright:   2026 SHWorX (Steffen Haase)
 */

declare(strict_types=1);

namespace SHWorX\SpamDetector\Exceptions;

use RuntimeException;

final class InvalidRuleSetException extends RuntimeException
{ }