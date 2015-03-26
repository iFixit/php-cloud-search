<?php

require_once __DIR__ . '/../CloudSearchQuery.php';

use Aws\CloudSearchQuery\CloudSearchQuery;

class CloudSearchQueryTest extends PHPUnit_Framework_TestCase {

   /**
    * @expectedException \Aws\CloudSearchQuery\EmptyQueryException
    */
   public function testEmptyQueryException() {
      $q = new CloudSearchQuery();

      // Do we appropriately complain when neither 'q' nor 'bq' are set?
      $query = $q->build();
   }

   public function testUintType() {
      $q = new CloudSearchQuery();

      // Do we correctly support the N, N.., N..M, and ..M string constructors?
      $this->assertSame('12', $q::uint('12')->build());
      $this->assertSame('12..', $q::uint('12..')->build());
      $this->assertSame('12..20', $q::uint('12..20')->build());
      $this->assertSame('..20', $q::uint('..20')->build());

      // And the same, but with numbers?
      $this->assertSame('12', $q::uint(12)->build());
      $this->assertSame('12..', $q::uint([12, NULL])->build());
      $this->assertSame('12..20', $q::uint([12, 20])->build());
      $this->assertSame('..20', $q::uint([NULL, 20])->build());

      // Does passing in an existing CloudSearchQueryUint work?
      $uint = new \Aws\CloudSearchQuery\CloudSearchQueryUint([12, NULL]);
      $this->assertSame('12..', $q::uint($uint)->build());
   }

   public function testStrType() {
      $q = new CloudSearchQuery();

      // Do we add single quotes by default? Do we strip whitespace and escape
      // backslashes and single quotes?
      $this->assertSame("'q\\\\uer\\'y'", $q::str(" q\\uer'y ")->build());

      // Do we convert non-strings?
      $this->assertSame("'42'", $q::str(42)->build());

      // Do we respect the NO_QUOTE setting?
      $this->assertSame("query", $q::str('query')->build(
       \Aws\CloudSearchQuery\CloudSearchQueryString::NO_QUOTE));

      // Do we add a wildcard?
      $query = $q::str('query')->addWildcard();
      $this->assertSame("'query*'", $query->build());

      // Do we NOT add it twice?
      $this->assertSame("'query*'", $query->addWildcard()->build());
   }

   public function testBooleanQueryOneExp() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');
      $query = $q->build();

      // Do we leave out the implicit 'and' expression when there's just a
      // single simple subexpression? Do we also appropriately place the value
      // for the 'field' expression in single quotes?
      $this->assertSame("(field field1 'query')", $query['bq']);
   }

   public function testBooleanQueryTwoExps() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');
      $q->field('field2', 'query');
      $query = $q->build();

      // Do we appropriately build the implicit 'and' expression around
      // multiple subexpressions?
      $this->assertSame("(and (field field1 'query') " .
       "(field field2 'query'))", $query['bq']);
   }

   public function testbooleanQueryFilterExps() {
      $q = new CloudSearchQuery();
      $q->filter('field1', [NULL, 9]);
      $q->filter('field2', 10);
      $q->filter('field3', [11, 20]);
      $q->filter('field4', [21, NULL]);
      $query = $q->build();

      // Do we appropriately build filter expressions for exact values,
      // open-ended ranges, and closed ranges?
      $this->assertSame("(and (filter field1 ..9) " .
       "(filter field2 10) (filter field3 11..20) (filter field4 21..))",
       $query['bq']);
   }

   public function testBooleanQueryOr() {
      $q = new CloudSearchQuery();
      $q->or_(
         $q->field('field1', 'query'),
         $q->field('field2', 'query'));
      $query = $q->build();

      // Do we properly build an 'or' expression out of multiple
      // subexpressions?
      $this->assertSame("(or (field field1 'query') " .
       "(field field2 'query'))", $query['bq']);
   }

   public function testBooleanQueryOrArray() {
      $q = new CloudSearchQuery();
      $q->or_([
         $q->field('field1', 'query'),
         $q->field('field2', 'query')]);
      $query = $q->build();

      // Does everything work the same when we pass an array of expressions to
      // an 'or' expression instead of passing multiple arguments?
      $this->assertSame("(or (field field1 'query') " .
       "(field field2 'query'))", $query['bq']);
   }

   public function testFacet() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');
      $q->facet('field1', 'field2');
      $query = $q->build();

      // Do we remember facets correctly?
      $this->assertSame('field1,field2', $query['facet']);

      // Each call to `facet` should append facets.
      $q->facet('field3');
      $query = $q->build();
      $this->assertSame('field1,field2,field3', $query['facet']);

      // We can delete facets.
      $q->deleteFacets('field2');
      $query = $q->build();
      $this->assertSame('field1,field3', $query['facet']);
      $q->deleteFacets();
      $query = $q->build();
      $this->assertArrayNotHasKey('facet', $query);
   }

   public function testFacetConstraints() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');
      $q->facetConstraintsUint('field1', [[NULL, 10], 42]);
      $q->facetConstraintsStr('field2', ['red', 'blue', 'white']);
      $query = $q->build();

      // Do uint constraints work properly?
      $this->assertSame("..10,42", $query['facet-field1-constraints']);

      // Do string constraints work properly?
      $this->assertSame("'red','blue','white'",
       $query['facet-field2-constraints']);

      // Are we adding only the specified fields?
      $keys = array_keys($query);
      sort($keys);
      $this->assertSame(['bq', 'facet-field1-constraints',
       'facet-field2-constraints'], $keys);
   }

   public function testFacetSort() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');
      $q->facetSort('field1')->alpha();
      $q->facetSort('field2')->count();
      $q->facetSort('field3')->max('field1', $q::DESC);
      $q->facetSort('field4')->sum('field1');
      $q->facetSort('field5');
      $query = $q->build();

      // Do we end up with the appropriate methods for each field?
      $this->assertSame('alpha', $query['facet-field1-sort']);
      $this->assertSame('count', $query['facet-field2-sort']);
      $this->assertSame('-max(field1)', $query['facet-field3-sort']);
      $this->assertSame('sum(field1)', $query['facet-field4-sort']);

      // Do we default to 'count' when no method is specified?
      $this->assertSame('count', $query['facet-field5-sort']);
   }

   public function testQuery() {
      $q = new CloudSearchQuery();

      // Do we correctly set the default text query, which shouldn't have
      // quotes around it?
      $q->q('query');
      $query = $q->build();
      $this->assertSame('query', $query['q']);
   }

   public function testRank() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');
      $q->defineRank('popularity', '(0.4*view_score)+(0.6*vote_score)');
      $q->defineRank('myrank', '(0.5*text_relevance)+(0.5*popularity)');
      $q->rank(
         'myrank', $q::DESC,
         'modified_date', $q::DESC,
         'field1',
         'title', $q::ASC,
         'field2');
      $query = $q->build();

      // Do we assign the rank definitions correctly?
      $this->assertSame('(0.4*view_score)+(0.6*vote_score)',
       $query['rank-popularity']);
      $this->assertSame('(0.5*text_relevance)+(0.5*popularity)',
       $query['rank-myrank']);

      // Do we set the ranking in the appropriate order, respecting descending
      // versus ascending order? Do we appropriately assign a default ascending
      // order when a field has no associated order? Do we handle the edge case
      // where the last field has no associated order?
      $this->assertSame('-myrank,-modified_date,field1,title,field2',
       $query['rank']);
   }

   public function testRankConstraints() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');

      // Do we set thresholds using the standard range syntax?
      $q->threshold('field1', [10, NULL]);
      $q->threshold('field2', 12);
      $q->threshold('field3', '11..20');
      $query = $q->build();
      $this->assertSame('10..', $query['t-field1']);
      $this->assertSame('12', $query['t-field2']);
      $this->assertSame('11..20', $query['t-field3']);
   }

   public function testResultType() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');
      $q->resultsType($q::JSON);

      // Do we handle JSON correctly?
      $query = $q->build();
      $this->assertSame('json', $query['results-type']);

      // And XML?
      $q->resultsType($q::XML);
      $query = $q->build();
      $this->assertSame('xml', $query['results-type']);
   }

   public function testReturnFields() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');

      // Do we set the return fields correctly when they're specified as an
      // array?
      $q->returnFields(['field1', 'field2']);
      $query = $q->build();
      $this->assertSame('field1,field2', $query['return-fields']);

      // Do we get the same result when using varargs?
      $q->returnFields('field1', 'field2', 'field3');
      $query = $q->build();
      $this->assertSame('field1,field2,field3', $query['return-fields']);

      // Adding the same field incrementally does nothing.
      $q->addReturnField('field3');
      $query = $q->build();
      $this->assertSame('field1,field2,field3', $query['return-fields']);

      // Adding a new return field incrementally appends it.
      $q->addReturnField('last');
      $query = $q->build();
      $this->assertSame('field1,field2,field3,last', $query['return-fields']);
   }

   public function testSizeStartLimit() {
      $q = new CloudSearchQuery();
      $q->field('field1', 'query');

      // Do we respect the set size?
      $q->size(100);
      $query = $q->build();
      $this->assertSame(100, $query['size']);

      // Do we respect the set start point?
      $q->start(100);
      $query = $q->build();
      $this->assertSame(100, $query['start']);

      // Does limit appropriately overwrite the size and start parameters, and
      // set them according to the SQL LIMIT <start>, <size> order?
      $q->limit(50, 10);
      $query = $q->build();
      $this->assertSame(50, $query['start']);
      $this->assertSame(10, $query['size']);
   }
}
