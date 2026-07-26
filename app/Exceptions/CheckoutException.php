<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A checkout problem whose message is safe to show the customer
 * (e.g. an item ran out of stock, or the cart is empty).
 */
class CheckoutException extends RuntimeException {}
