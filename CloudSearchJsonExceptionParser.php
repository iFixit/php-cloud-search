<?php

namespace Aws\CloudSearch;

use Aws\Common\Exception\Parser\JsonQueryExceptionParser;
use Guzzle\Http\Message\Response;

class CloudSearchJsonExceptionParser extends JsonQueryExceptionParser {

   /**
    * The fields expected by consumers of the error data:
    *
    * - code:       Exception code
    * - type:       Exception type
    * - message:    Exception message
    * - request_id: Request ID
    * - parsed:     The parsed representation of the data (array)
    *
    * The code is used to look for related exception classes, so we want it to
    * be relatively generic (i.e., not the exact error). For CloudSearch
    * errors, the only value for 'error' I've seen is 'info'. The code
    * associated with a particular error message is very specific, and doesn't
    * seem suitable to associating with an exception class; instead we set it
    * as the exception type.
    */
   protected function doParse(array $data, Response $response) {

      if ($json = $data['parsed']) {
         if (!isset($json['error']) || !isset($json['rid']) {
            $ex = new \DebugException("Malform CloudSearch Error Response");
            $ex->appendDebugInfo($response);
            throw $ex;
         }
         $data['code'] = $json['error'];
         $data['request_id'] = $json['rid'];
         if (isset($json['messages'][0])) {
            $message = $json['messages'][0];
            $data['type'] = $message['code'];
            $data['message'] = $message['message'];
         } else {
            $data['type'] = $data['message'] = null;
         }
      }

      return $data;
   }
}
