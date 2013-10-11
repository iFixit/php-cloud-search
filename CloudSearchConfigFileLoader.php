<?php

use Guzzle\Service\AbstractConfigLoader;

/**
 * A minimal concrete implementation of Guzzle's AbstractConfigLoader class 
 * that simply passes the parsed configuration array through.
 */
class CloudSearchConfigFileLoader extends AbstractConfigLoader {
   protected static $loader;

   public static function loadConf($path) {
      if (is_null(self::$loader)) {
         self::$loader = new CloudSearchConfigFileLoader();
      }
      return self::$loader->load($path);
   }

   protected function build($config, array $options) {
      return $config;
   }
}
