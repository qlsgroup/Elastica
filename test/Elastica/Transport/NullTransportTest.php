<?php

namespace Elastica\Test\Transport;

use Elastica\Connection;
use Elastica\Query;
use Elastica\Request;
use Elastica\Response;
use Elastica\Test\Base as BaseTest;
use Elastica\Transport\NullTransport;

/**
 * Elastica Null Transport Test.
 *
 * @author James Boehmer <james.boehmer@jamesboehmer.com>
 * @author Jan Domanski <jandom@gmail.com>
 */
class NullTransportTest extends BaseTest
{
    /** @var NullTransport NullTransport */
    protected $transport;

    public function set_up()
    {
        parent::set_up();
        $this->transport = new NullTransport();
    }

    /**
     * @group functional
     */
    public function testEmptyResult()
    {
        // Creates a client with any destination, and verify it returns a response object when executed
        $client = $this->_getClient();
        $connection = new Connection(['transport' => 'NullTransport']);
        $client->setConnections([$connection]);

        $index = $client->getIndex('elasticaNullTransportTest1');

        $resultSet = $index->search(new Query());
        $this->assertNotNull($resultSet);

        $response = $resultSet->getResponse();
        $this->assertNotNull($response);

        // Validate most of the expected fields in the response data.  Consumers of the response
        // object have a reasonable expectation of finding "hits", "took", etc
        $responseData = $response->getData();
        $this->assertArrayHasKey('took', $responseData);
        $this->assertEquals(0, $responseData['took']);
        $this->assertArrayHasKey('_shards', $responseData);
        $this->assertArrayHasKey('hits', $responseData);
        $this->assertArrayHasKey('total', $responseData['hits']);
        $this->assertEquals(0, $responseData['hits']['total']);
        $this->assertArrayHasKey('params', $responseData);

        $took = $response->getEngineTime();
        $this->assertEquals(0, $took);

        $errorString = $response->getError();
        $this->assertEmpty($errorString);

        $shards = $response->getShardsStatistics();
        $this->assertArrayHasKey('total', $shards);
        $this->assertEquals(0, $shards['total']);
        $this->assertArrayHasKey('successful', $shards);
        $this->assertEquals(0, $shards['successful']);
        $this->assertArrayHasKey('failed', $shards);
        $this->assertEquals(0, $shards['failed']);
    }

    /**
     * @group functional
     */
    public function testExec()
    {
        $request = new Request('/test');
        $params = ['name' => 'ruflin'];
        $transport = new NullTransport();
        $response = $transport->exec($request, $params);

        $this->assertInstanceOf(Response::class, $response);

        $data = $response->getData();
        $this->assertEquals($params, $data['params']);
    }

    /**
     * @group unit
     */
    public function testResponse()
    {
        $resposeString = '';
        $response = new Response($resposeString);
        $this->transport->setResponse($response);
        $this->assertEquals($response, $this->transport->getResponse());
    }

    /**
     * @group unit
     */
    public function testGenerateDefaultResponse()
    {
        $params = ['blah' => 123];
        $response = $this->transport->generateDefaultResponse($params);
        $this->assertEquals([], $response->getTransferInfo());

        $responseData = $response->getData();
        $this->assertArrayHasKey('params', $responseData);
        $this->assertEquals($params, $responseData['params']);
    }
}
