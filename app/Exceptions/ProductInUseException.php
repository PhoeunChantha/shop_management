<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A product (or one of its variants) can't be deleted because it's still
 * referenced by history that must not be destroyed (e.g. a purchase order).
 */
class ProductInUseException extends RuntimeException {}
