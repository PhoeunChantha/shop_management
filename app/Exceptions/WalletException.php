<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A wallet operation problem (e.g. insufficient balance).
 */
class WalletException extends RuntimeException {}
