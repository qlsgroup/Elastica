<?php

namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Exception\ResponseException;
use Elastica\Query\QueryString;
use Elastica\Test\Base as BaseTest;

class QueryStringTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testSearchMultipleFields()
    {
        $str = md5(rand());
        $query = new QueryString($str);

        $expected = [
            'query' => $str,
        ];

        $this->assertEquals(['query_string' => $expected], $query->toArray());

        $fields = [];
        $max = rand() % 10 + 1;
        for ($i = 0; $i < $max; ++$i) {
            $fields[] = md5(rand());
        }

        $query->setFields($fields);
        $expected['fields'] = $fields;
        $this->assertEquals(['query_string' => $expected], $query->toArray());

        foreach ([false, true] as $val) {
            $query->setUseDisMax($val);
            $expected['use_dis_max'] = $val;

            $this->assertEquals(['query_string' => $expected], $query->toArray());
        }
    }

    /**
     * @group functional
     */
    public function testSearch()
    {
        $index = $this->_createIndex();
        $index->getSettings()->setNumberOfReplicas(0);
        $type = $index->getType('_doc');

        $doc = new Document(1, ['email' => 'test@test.com', 'username' => 'hanswurst', 'test' => ['2', '3', '5']]);
        $type->addDocument($doc);
        $index->refresh();

        $queryString = new QueryString('test*');
        $resultSet = $type->search($queryString);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * Tests if search in multiple fields is possible.
     *
     * @group functional
     */
    public function testSearchFields()
    {
        $index = $this->_createIndex();
        $type = $index->getType('_doc');

        $doc = new Document(1, ['title' => 'hello world', 'firstname' => 'nicolas', 'lastname' => 'ruflin', 'price' => '102', 'year' => '2012']);
        $type->addDocument($doc);
        $index->refresh();

        $query = new QueryString();
        $query = $query->setQuery('ruf*');
        $query = $query->setFields(['title', 'firstname', 'lastname', 'price', 'year']);

        $resultSet = $type->search($query);
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * Tests if search in multiple fields is possible.
     *
     * @group functional
     */
    public function testSearchFieldsValidationException()
    {
        $index = $this->_createIndex();
        $type = $index->getType('_doc');

        $doc = new Document(1, ['title' => 'hello world', 'firstname' => 'nicolas', 'lastname' => 'ruflin', 'price' => '102', 'year' => '2012']);
        $type->addDocument($doc);
        $index->refresh();

        $query = new QueryString();
        $query = $query->setQuery('ruf*');
        $query = $query->setDefaultField('title');
        $query = $query->setFields(['title', 'firstname', 'lastname', 'price', 'year']);

        try {
            $resultSet = $type->search($query);
        } catch (ResponseException $ex) {
            $error = $ex->getResponse()->getFullError();

            $this->assertSame('query_shard_exception', $error['root_cause'][0]['type']);
            $this->assertStringContainsString('failed to create query', $error['root_cause'][0]['reason']);

            $this->assertContains('query_validation_exception', $error);
            $this->assertStringContainsString('[fields] parameter in conjunction with [default_field]', $error['failed_shards'][0]['reason']['caused_by']['reason']);

            $this->assertEquals(400, $ex->getResponse()->getStatus());
        }
    }

    /**
     * @group unit
     */
    public function testSetDefaultOperator()
    {
        $operator = 'AND';
        $query = new QueryString('test');
        $query->setDefaultOperator($operator);

        $data = $query->toArray();

        $this->assertEquals($data['query_string']['default_operator'], $operator);
    }

    /**
     * @group unit
     */
    public function testSetDefaultField()
    {
        $default = 'field1';
        $query = new QueryString('test');
        $query->setDefaultField($default);

        $data = $query->toArray();

        $this->assertEquals($data['query_string']['default_field'], $default);
    }

    /**
     * @group unit
     */
    public function testSetAnalyzer()
    {
        $value = 'test';
        $query = new QueryString('test');
        $query->setAnalyzer($value);

        $this->assertEquals($value, $query->toArray()['query_string']['analyzer']);
    }

    /**
     * @group unit
     */
    public function testSetAllowLeadingWildcard()
    {
        $value = true;
        $query = new QueryString('test');
        $query->setAllowLeadingWildcard($value);

        $this->assertEquals($value, $query->toArray()['query_string']['allow_leading_wildcard']);
    }

    /**
     * @group unit
     */
    public function testSetEnablePositionIncrements()
    {
        $value = true;
        $query = new QueryString('test');
        $query->setEnablePositionIncrements($value);

        $this->assertEquals($value, $query->toArray()['query_string']['enable_position_increments']);
    }

    /**
     * @group unit
     */
    public function testSetFuzzyPrefixLength()
    {
        $value = 1;
        $query = new QueryString('test');
        $query->setFuzzyPrefixLength($value);

        $this->assertEquals($value, $query->toArray()['query_string']['fuzzy_prefix_length']);
    }

    /**
     * @group unit
     */
    public function testSetFuzzyMinSim()
    {
        $value = 0.1;
        $query = new QueryString('test');
        $query->setFuzzyMinSim($value);

        $this->assertEquals($value, $query->toArray()['query_string']['fuzzy_min_sim']);
    }

    /**
     * @group unit
     */
    public function testSetAnalyzeWildcard()
    {
        $value = true;
        $query = new QueryString('test');
        $query->setAnalyzeWildcard($value);

        $this->assertEquals($value, $query->toArray()['query_string']['analyze_wildcard']);
    }

    /**
     * @group unit
     */
    public function testSetAutoGeneratePhraseQueries()
    {
        $value = true;
        $query = new QueryString('test');
        $query->setAutoGeneratePhraseQueries($value);

        $this->assertEquals($value, $query->toArray()['query_string']['auto_generate_phrase_queries']);
    }

    /**
     * @group unit
     */
    public function testSetTieBreaker()
    {
        $value = 0.2;
        $query = new QueryString('test');
        $query->setTieBreaker($value);

        $this->assertEquals($value, $query->toArray()['query_string']['tie_breaker']);
    }

    /**
     * @group unit
     */
    public function testSetRewrite()
    {
        $rewrite = 'scoring_boolean';
        $query = new QueryString('test');
        $query->setRewrite($rewrite);

        $data = $query->toArray();

        $this->assertEquals($data['query_string']['rewrite'], $rewrite);
    }

    /**
     * @group unit
     */
    public function testSetQueryInvalid()
    {
        $this->expectException(\Elastica\Exception\InvalidException::class);

        $query = new QueryString();
        $query->setQuery([]);
    }

    /**
     * @group unit
     */
    public function testSetTimezone()
    {
        $timezone = 'Europe/Paris';
        $text = 'date:[2012 TO 2014]';

        $query = new QueryString($text);
        $query->setTimezone($timezone);

        $expected = [
            'query_string' => [
                'query' => $text,
                'time_zone' => $timezone,
            ],
        ];

        $this->assertEquals($expected, $query->toArray());
        $this->assertInstanceOf(QueryString::class, $query->setTimezone($timezone));
    }

    /**
     * @group unit
     */
    public function testSetPhraseSlop()
    {
        $phraseSlop = 9;

        $query = new QueryString('test');
        $query->setPhraseSlop($phraseSlop);

        $data = $query->toArray();
        $this->assertEquals($phraseSlop, $data['query_string']['phrase_slop']);
    }

    /**
     * @group functional
     */
    public function testSetBoost()
    {
        $index = $this->_createIndex();
        $query = new QueryString('test');
        $query->setBoost(9.3);

        $doc = new Document('', ['name' => 'test']);
        $index->getType('_doc')->addDocument($doc);
        $index->refresh();

        $resultSet = $index->search($query);

        $this->assertEquals(1, $resultSet->count());
    }
}
