<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A request that breaks a business rule from the PRD. The message is safe to show to users.
 */
class BusinessRuleException extends RuntimeException {}
