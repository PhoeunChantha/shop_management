<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A product could not be published to the Facebook Page (not configured,
 * no image, or the Graph API rejected the request).
 */
class FacebookPublishException extends RuntimeException {}
