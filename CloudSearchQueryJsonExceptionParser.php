<?php

namespace Aws\CloudSearch;

use Aws\Common\Exception\Parser\AbstractJsonExceptionParser;
use Guzzle\Http\Message\Response;

class CloudSearchQueryJsonExceptionParser extends AbstractJsonExceptionParser {

   /**
    * The fields expected by consumers of the error data:
    *
    * - code:       Exception code
    * - type:       Exception type
    * - message:    Exception message
    * - request_id: Request ID
    * - parsed:     The parsed representation of the data (array)
    *
    * This implementation is specific to handling errors encountered while
    * querying the index. The parent class parses the JSON body and initializes
    * the required $data fields to reasonable defaults. The `type` is usually
    * going to be something like "client", and the code and message are
    * specific to application-level errors (e.g., `CS-RankExpressionParseError`
    * and "Syntax error in match set expression: open parenthesis should be
    * followed by and, or, optional, not, field, token, phrase, or filter").
    * These are likely to be logic rather than data errors.
    */
   protected function doParse(array $data, Response $response) {
      if ($json = $data['parsed']) {
         if (!isset($json['messages'][0])) {
            $ex = new \DebugException("Malformed CloudSearch Error Response");
            $ex->appendKeyedDebugInfo('data', $data);
            $ex->appendKeyedDebugInfo('response', $response);
            throw $ex;
         }
         $message = $json['messages'][0];
         $data['code'] = $message['code'];
         $data['message'] = $message['message'];
      }
      return $data;
   }
}
