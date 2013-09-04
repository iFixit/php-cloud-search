<?php

$awsSdkDir = getenv('AWS_SDK');

if ($awsSdkDir) {
   require_once "{$awsSdkDir}/aws-autoloader.php";
} else {
   fprintf(STDERR, "Missing AWS SDK location. Set the AWS_SDK environment\n" .
      "variable to the base directory of the AWS PHP SDK (this directory\n" .
      "should contain the file 'aws-autoloader.php'). For example:\n\n" .
      "$ export AWS_SDK=/path/to/aws-sdk\n" .
      "$ phpunit tests\n");
   exit(1);
}
