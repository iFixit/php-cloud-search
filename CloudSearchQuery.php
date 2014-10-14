<?php

namespace Aws\CloudSearchQuery;

/**
 * A fairly thin convenience layer for building queries to be passed to the
 * CloudSearchQueryClient query method.
 *
 * Example:
 *
 *    $q = new CloudSearchQuery();
 *    $q->and_(
 *       $q->field('title', $userQuery),
 *       $q->filter('public', true),
 *       $q->or_(
 *          $q->filter('year', [1990, NULL]),
 *          $q->filter('timeless', true))));
 *    $q->facet('genre');
 *    $results = $client->query($q);
 */
class CloudSearchQuery {
   const DESC = 1;
   const ASC = 0;

   const JSON = 'json';
   const XML = 'xml';

   protected $idGenerator = 1;
   protected $expIndex = [];
   protected $facets = [];
   protected $facetConstraints = [];
   protected $facetSorts = [];
   protected $facetTopN = [];
   protected $q;
   protected $rankExpressions = [];
   protected $ranks = [];
   protected $resultsType;
   protected $returnFields = [];
   protected $size;
   protected $start;
   protected $thresholds = [];

   public static function uint($n) {
      if (is_a($n, '\Aws\CloudSearch\Query\CloudSearchQueryUint'))
         return $n;
      else
         return new CloudSearchQueryUint($n);
   }

   public static function str($s) {
      if (is_a($s, '\Aws\CloudSearch\Query\CloudSearchQueryString')) {
         return $s;
      } else
         return new CloudSearchQueryString($s);
   }

   public function build() {
      $query = [];

      if (empty($this->expIndex) && empty($this->q)) {
         throw new EmptyQueryException;
      }

      if (!empty($this->expIndex))
         $query['bq'] = $this->buildBooleanQuery();

      if (!empty($this->facets))
         $query['facet'] = implode(',', array_keys($this->facets));

      if (!empty($this->facetConstraints))
         foreach ($this->facetConstraints as $facet => $constraints)
            $query["facet-{$facet}-constraints"] = implode(',', $constraints);

      if (!empty($this->facetSorts))
         foreach ($this->facetSorts as $facet => $sortBy)
            $query["facet-{$facet}-sort"] = $sortBy->build();

      if (!empty($this->facetTopN))
         foreach($this->facetTopN as $facet => $n)
            $query["facet-{$facet}-top-n"] = $n;

      if (!empty($this->q)) {
         $query['q'] = $this->q;
      }

      if (!empty($this->rankExpressions))
         foreach ($this->rankExpressions as $name => $rankExp)
            $query["rank-{$name}"] = $rankExp;

      if (!empty($this->ranks))
         $query['rank'] = implode(',', $this->ranks);

      if (!is_null($this->resultsType))
         $query['results-type'] = $this->resultsType;

      if (!empty($this->returnFields))
         $query['return-fields'] = implode(',',
          array_keys($this->returnFields));

      if (!is_null($this->size))
         $query['size'] = $this->size;

      if (!is_null($this->start))
         $query['start'] = $this->start;

      if (!empty($this->thresholds))
         foreach ($this->thresholds as $rankName => $range)
            $query["t-{$rankName}"] = $range;

      return $query;
   }

   public function defaultField($value) {
      $value = self::str($value);
      return $this->addExp($value->build());
   }

   public function field($field, $value) {
      $value = self::str($value);
      return $this->addExp(['field', $field, $value->build()]);
   }

   public function filter($field, $value) {
      $value = self::uint($value);
      return $this->addExp(['filter', $field, $value->build()]);
   }

   public function and_(/* $id1, $id2, ... */) {
      $args = array_flatten(func_get_args());
      $exps = [];
      foreach ($args as $id) {
         $exps[] = $this->deleteExp($id);
      }

      return $this->addExp(array_merge(['and'], $exps));
   }

   public function or_(/* $id1, $id2, ... */) {
      $args = array_flatten(func_get_args());
      $exps = [];
      foreach ($args as $id) {
         $exps[] = $this->deleteExp($id);
      }

      return $this->addExp(array_merge(['or'], $exps));
   }

   public function not_($id) {
      $exp = $this->deleteExp($id);
      return $this->addExp(['not', $exp]);
   }

   public function facet(/* $facet1, $facet2, ... */) {
      $facetNames = array_flatten(func_get_args());
      foreach ($facetNames as $facetName)
         $this->facets[$facetName] = $facetName;
   }

   public function deleteFacets(/* $facet1, $facet2, ... */) {
      $facetNames = array_flatten(func_get_args());

      if (empty($facetNames)) {
         $this->facets = [];
         $this->facetConstraints = [];
         $this->facetSorts = [];
         $this->facetTopN = [];
         return;
      }

      foreach ($facetNames as $facet) {
         unset($this->facets[$facetName]);
         unset($this->facetConstraints[$facetName]);
         unset($this->facetSorts[$facetName]);
         unset($this->facetTopN[$facetName]);
      }
   }

   public function facetConstraintsUint($facet, $constraints) {
      $this->facetConstraints[$facet] = [];
      foreach ($constraints as $constraint) {
         $constraint = self::uint($constraint);
         $this->facetConstraints[$facet][] = $constraint->build();
      }
   }

   public function facetConstraintsStr($facet, $constraints) {
      $this->facetConstraints[$facet] = [];
      foreach ($constraints as $constraint) {
         $constraint = self::str($constraint);
         $this->facetConstraints[$facet][] = $constraint->build(
          CloudSearchQueryString::QUOTE,
          CloudSearchQueryString::ESCAPE_COMMA);
      }
   }

   public function facetSort($facet) {
      $sort = new CloudSearchQueryFacetSort($facet);
      $this->facetSorts[$facet] = $sort;
      return $sort;
   }

   public function facetTopN($facet, $value) {
      $this->facetTopN[$facet] = max(1, intval($value));
   }

   public function q($query) {
      $query = self::str($query);
      $this->q = $query->build(CloudSearchQueryString::NO_QUOTE);
   }

   /**
    * Note that `rank` requires that all ranking fields be specified in one
    * go, and it replaces any prior ranking.
    */
   public function rank(/* $field1, $order1, $field2, $order2, ... */) {
      $args = array_flatten(func_get_args());
      $numArgs = count($args);
      $this->ranks = [];

      for ($i = 0; $i < $numArgs; $i++) {
         if ($i + 1 < $numArgs && !is_string($args[$i + 1])) {
            $name = $args[$i];
            $desc = (bool)$args[$i + 1];
            $i++;
         } else {
            $name = $args[$i];
            $desc = false;
         }

         if ($desc)
            $name = '-' . $name;

         $this->ranks[] = $name;
      }

      if (count($this->ranks) > 10) {
         throw new Exception(
          'A maximum of 10 fields and rank expressions can be specified.');
      }
   }

   public function defineRank($name, $rankExp) {
      $this->rankExpressions[$name] = $rankExp;
   }

   public function resultsType($type) {
      $this->resultsType = $type;
   }

   public function returnFields(/* $field1, $field2, ... */) {
      $returnFields = array_flatten(func_get_args());
      $this->returnFields = array_combine($returnFields, $returnFields);
   }

   public function size($size) {
      $this->size = max(0, intval($size));
   }

   public function start($start) {
      $this->start = max(0, intval($start));
   }

   public function limit($start, $size) {
      $this->start($start);
      $this->size($size);
   }

   public function threshold($rankName, $range) {
      $range = self::uint($range);
      $this->thresholds[$rankName] = $range->build();
   }

   public function deleteExp($id) {
      $exp = $this->expIndex[$id];
      unset($this->expIndex[$id]);
      return $exp;
   }

   protected function buildBooleanQuery() {
      $exps = [];

      foreach ($this->expIndex as $exp) {
         $exps[] = $this->buildBooleanQuerySubexp($exp);
      }

      $numExps = count($exps);
      if ($numExps == 1)
         return $exps[0];
      else
         return '(and ' . implode(' ', $exps) . ')';
   }

   protected function buildBooleanQuerySubexp($exp) {
      $subexps = [];
      foreach ($exp as $subexp) {
         if (is_array($subexp))
            $subexp = $this->buildBooleanQuerySubexp($subexp);
         $subexps[] = $subexp;
      }
      return '(' . implode(' ', $subexps) . ')';
   }

   protected function addExp($exp) {
      $id = $this->idGenerator++;
      $this->expIndex[$id] = $exp;
      return $id;
   }
}


/**
 * A representation of strings to be passed as arguments to the text query and
 * boolean query functions, and to be used as facet constraints. Backslashes
 * and single quotes are automatically escaped, and the default is to surround
 * the string with single quotes. You can override this behavior when building
 * the final string. Example:
 *
 *    use \Aws\CloudSearch\Query\CloudSearchQueryClient;
 *    use \Aws\CloudSearch\Query\CloudSearchQueryString;
 *
 *    $client = CloudSearchQueryClient::factory();
 *    $q = $client->newQuery();
 *    $stringObj = $q::str('query');
 *    $quotedStr = $stringObj->build();    // 'query'
 *    $unquotedStr = $stringObj->build(
 *     CloudSearchQueryString::NO_QUOTES); // query
 *
 * Note the use of the `str` function as a factory for CloudSearchQueryStrings.
 */
class CloudSearchQueryString {
   const QUOTE = 1;
   const NO_QUOTE = 0;
   const ESCAPE_COMMA = 1;
   const NO_ESCAPE_COMMA = 0;

   protected $str;

   public function __construct($s) {
      $s = trim(strval($s));
      $this->str = $s;
   }

   public function addWildcard($dropTrailingS = true) {
      if ($this->str[strlen($this->str) - 1] == '*')
         return;
      $this->str .= '*';
      return $this;
   }

   public function getValue() {
      return $this->str;
   }

   public function build($quote = self::QUOTE,
    $escapeComma = self::NO_ESCAPE_COMMA) {
      if ($quote == self::NO_QUOTE) {
         return $this->str;
      } else {
         $s = preg_replace('/([\'\\\\])/', '\\\\$1', $this->str);
         if ($escapeComma == self::ESCAPE_COMMA)
            $s = str_replace(',', '\\,', $s);
         return "'{$s}'";
      }
   }
}


/**
 * A representation of unsigned integers to be used in CloudSearch query
 * requests. Within queries, a uint may always be either an exact number or a
 * range, open on either end. Uints may be (usefully) constructed from
 * booleans, any kind of numeric, strings, or two-element arrays specifying a
 * range. In the case of strings, two dots may be used to indicate a range
 * (e.g., '..26', '27..28', '29..'); in the case of arrays, NULL is used to
 * indicate a range open on one end (e.g., [NULL, 26], [27, 28], [29, NULL]).
 * No matter what the input, the result of the `build` method is always a
 * string. For example:
 *
 *    use \Aws\CloudSearch\Query\CloudSearchQueryClient;
 *    use \Aws\CloudSearch\Query\CloudSearchQueryString;
 *
 *    $client = CloudSearchQueryClient::factory();
 *    $q = $client->newQuery();
 *    $uintObj = $q::uint([28 29]);
 *    $str = $uintObj->build()  // '28..29'
 *    $uintObj = $q::uint(29);
 *    $str = $uintObj->build()  // '29'
 */
class CloudSearchQueryUint {
   protected $from;
   protected $to;
   protected $exact;

   public function __construct($n) {
      if (is_array($n)) {
         list($this->from, $this->to) = $n;
         if (!is_null($this->from))
            $this->from = max(0, intval($this->from));
         if (!is_null($this->to))
            $this->to = max(0, intval($this->to));
      } else if (is_string($n)) {
         if (preg_match('/^(\d+)$/', $n)) {
            $this->exact = max(0, intval($n));
         } else if (preg_match('/^(\d+)\.\.(\d+)?$/', $n, $matches)) {
            $this->from = max(0, intval($matches[1]));
            $this->to = !empty($matches[2]) ?
             max(0, intval($matches[2])) : NULL;
         } else if (preg_match('/^(\d+)?\.\.(\d+)$/', $n, $matches)) {
            $this->from = !empty($matches[1]) ?
             max(0, intval($matches[1])) : NULL;
            $this->to = max(0, intval($matches[2]));
         }
      } else if (is_numeric($n)) {
         $this->exact = max(0, intval($n));
      }
   }

   public function getValue() {
      if (!is_null($this->exact))
         return $this->exact;
      return [$this->from, $this->to];
   }

   public function build() {
      if (!is_null($this->exact)) {
         return strval($this->exact);
      } else if (is_null($this->from)) {
         return '..' . $this->to;
      } else if (is_null($this->to)) {
         return $this->from . '..';
      } else {
         return $this->from . '..' . $this->to;
      }
   }
}


/**
 * A helper class to provide a bit of syntactic sugar when specifying the sort
 * method for a facet. An instance of this class is returned by the
 * CloudSearchQuery::facetSort method, and it's then possible to specify a sort
 * method by calling one of the methods of that instance. If none of the
 * methods is called, the default 'count' method will be used when the actual
 * sort specification is built. Example:
 *
 *    $client = CloudSearchQueryClient::factory();
 *    $q = $client->newQuery();
 *    $q->facetSort('field1')->max('field2', $q::DESC);
 *
 * The main reason for using this interface is to encode and check the various
 * formats for the different sort methods.
 */
class CloudSearchQueryFacetSort {
   protected $facet;
   protected $string;

   function __construct($facet) {
      $this->facet = $facet;
   }

   function alpha() {
      $this->string = 'alpha';
   }

   function count() {
      $this->string = 'count';
   }

   function max($byField, $desc = false) {
      $this->string = ($desc ? '-' : '') . "max({$byField})";
   }

   function sum($byField, $desc = false) {
      $this->string = ($desc ? '-' : '') . "sum({$byField})";
   }

   function build() {
      if (is_null($this->string))
         $this->count();
      return $this->string;
   }
}


/**
 * An exception raised when neither the 'q' nor the 'bq' field of a query is
 * set. The API requires at least one (both are okay).
 */
class EmptyQueryException extends \Exception {}


/**
 * It's hard to believe PHP still doesn't include an array_flatten function in
 * the standard library.
 */
function array_flatten($array) {
   $flattened = [];
   foreach ($array as $v) {
      if (is_array($v))
         $flattened = array_merge($flattened, array_flatten($v));
      else
         $flattened[] = $v;
   }
   return $flattened;
}
