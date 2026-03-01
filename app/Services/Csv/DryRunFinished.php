<?php

namespace App\Services\Csv;

use RuntimeException;

/**
 * Thrown at the end of a dry run to roll the import transaction back.
 */
final class DryRunFinished extends RuntimeException {}
