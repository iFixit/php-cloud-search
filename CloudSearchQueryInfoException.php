<?php

namespace Aws\CloudSearchQuery\Exception;

use Aws\Common\Exception\ServiceResponseException;

/**
 * An exception class specific to CloudSearch query endpoint errors. The error
 * response from the server is parsed, and the code is matched against class
 * names in the Exception namespace. We just need to define an exception class
 * with the right name and extend the appropriate base class in order to get an
 * error specific to the CloudSearch service instead of a generic HTTP errror.
 */
class InfoException extends ServiceResponseException {
}
