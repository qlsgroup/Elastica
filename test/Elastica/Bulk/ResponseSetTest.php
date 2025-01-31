<?php

namespace Elastica\Test\Bulk;

use Elastica\Bulk;
use Elastica\Bulk\Action;
use Elastica\Bulk\ResponseSet;
use Elastica\Client;
use Elastica\Exception\Bulk\Response\ActionException;
use Elastica\Exception\Bulk\ResponseException;
use Elastica\Response;
use Elastica\Test\Base as BaseTest;
use Yoast\PHPUnitPolyfills\Polyfills\AssertIsType;
use Yoast\PHPUnitPolyfills\Polyfills\AssertStringContains;

class ResponseSetTest extends BaseTest
{
    use AssertIsType;
    use AssertStringContains;

    /**
     * @group unit
     */
    public function testConstructor()
    {
        list($responseData, $actions) = $this->_getFixture();

        $responseSet = $this->_createResponseSet($responseData, $actions);

        $this->assertEquals(200, $responseSet->getStatus());
        $this->assertEquals(12.3, $responseSet->getQueryTime());
        $this->assertEquals([
            'url' => 'http://127.0.0.1:9200/_bulk',
            'http_code' => 200,
        ], $responseSet->getTransferInfo());
    }

    /**
     * @group unit
     * @dataProvider isOkDataProvider
     */
    public function testIsOk($responseData, $actions, $expected)
    {
        $responseSet = $this->_createResponseSet($responseData, $actions);
        $this->assertEquals($expected, $responseSet->isOk());
    }

    /**
     * @group unit
     */
    public function testGetError()
    {
        list($responseData, $actions) = $this->_getFixture();
        $responseData['items'][1]['index']['ok'] = false;
        $responseData['items'][1]['index']['error'] = 'SomeExceptionMessage';
        $responseData['items'][2]['index']['ok'] = false;
        $responseData['items'][2]['index']['error'] = 'AnotherExceptionMessage';

        try {
            $this->_createResponseSet($responseData, $actions);
            $this->fail('Bulk request should fail');
        } catch (ResponseException $e) {
            $responseSet = $e->getResponseSet();

            $this->assertInstanceOf(ResponseSet::class, $responseSet);

            $this->assertTrue($responseSet->hasError());
            $this->assertEquals('SomeExceptionMessage', $responseSet->getError());
            $this->assertNotEquals('AnotherExceptionMessage', $responseSet->getError());

            $actionExceptions = $e->getActionExceptions();
            $this->assertCount(2, $actionExceptions);

            $this->assertInstanceOf(ActionException::class, $actionExceptions[0]);
            $this->assertSame($actions[1], $actionExceptions[0]->getAction());
            self::assertStringContainsStringIgnoringCase('SomeExceptionMessage', $actionExceptions[0]->getMessage());
            $this->assertTrue($actionExceptions[0]->getResponse()->hasError());

            $this->assertInstanceOf(ActionException::class, $actionExceptions[1]);
            $this->assertSame($actions[2], $actionExceptions[1]->getAction());
            self::assertStringContainsStringIgnoringCase('AnotherExceptionMessage', $actionExceptions[1]->getMessage());
            $this->assertTrue($actionExceptions[1]->getResponse()->hasError());
        }
    }

    /**
     * @group unit
     */
    public function testGetBulkResponses()
    {
        list($responseData, $actions) = $this->_getFixture();

        $responseSet = $this->_createResponseSet($responseData, $actions);

        $bulkResponses = $responseSet->getBulkResponses();
        self::assertIsArray($bulkResponses);
        $this->assertCount(3, $bulkResponses);

        foreach ($bulkResponses as $i => $bulkResponse) {
            $this->assertInstanceOf(Bulk\Response::class, $bulkResponse);
            $bulkResponseData = $bulkResponse->getData();
            self::assertIsArray($bulkResponseData);
            $this->assertArrayHasKey('_id', $bulkResponseData);
            $this->assertEquals($responseData['items'][$i]['index']['_id'], $bulkResponseData['_id']);
            $this->assertSame($actions[$i], $bulkResponse->getAction());
            $this->assertEquals('index', $bulkResponse->getOpType());
        }
    }

    /**
     * @group unit
     */
    public function testIterator()
    {
        list($responseData, $actions) = $this->_getFixture();

        $responseSet = $this->_createResponseSet($responseData, $actions);

        $this->assertCount(3, $responseSet);

        foreach ($responseSet as $i => $bulkResponse) {
            $this->assertInstanceOf(Bulk\Response::class, $bulkResponse);
            $bulkResponseData = $bulkResponse->getData();
            self::assertIsArray($bulkResponseData);
            $this->assertArrayHasKey('_id', $bulkResponseData);
            $this->assertEquals($responseData['items'][$i]['index']['_id'], $bulkResponseData['_id']);
            $this->assertSame($actions[$i], $bulkResponse->getAction());
            $this->assertEquals('index', $bulkResponse->getOpType());
        }

        $this->assertFalse($responseSet->valid());

        $responseSet->rewind();

        $this->assertEquals(0, $responseSet->key());
        $this->assertTrue($responseSet->valid());
        $this->assertInstanceOf(Bulk\Response::class, $responseSet->current());
    }

    public function isOkDataProvider()
    {
        list($responseData, $actions) = $this->_getFixture();

        yield [$responseData, $actions, true];
        $responseData['items'][2]['index']['ok'] = false;
        yield [$responseData, $actions, false];
    }

    protected function _createResponseSet(array $responseData, array $actions): ResponseSet
    {
        $client = $this->createMock(Client::class);

        $response = new Response($responseData, 200);
        $response->setQueryTime(12.3);
        $response->setTransferInfo([
            'url' => 'http://127.0.0.1:9200/_bulk',
            'http_code' => 200,
        ]);

        $client->expects($this->once())
            ->method('request')
            ->withAnyParameters()
            ->willReturn($response)
        ;

        $bulk = new Bulk($client);
        $bulk->addActions($actions);

        return $bulk->send();
    }

    protected function _getFixture(): array
    {
        $responseData = [
            'took' => 5,
            'items' => [
                [
                    'index' => [
                        '_index' => 'index',
                        '_type' => 'type',
                        '_id' => '1',
                        '_version' => 1,
                        'ok' => true,
                    ],
                ],
                [
                    'index' => [
                        '_index' => 'index',
                        '_type' => 'type',
                        '_id' => '2',
                        '_version' => 1,
                        'ok' => true,
                    ],
                ],
                [
                    'index' => [
                        '_index' => 'index',
                        '_type' => 'type',
                        '_id' => '3',
                        '_version' => 1,
                        'ok' => true,
                    ],
                ],
            ],
        ];
        $bulkResponses = [
            new Action(),
            new Action(),
            new Action(),
        ];

        return [$responseData, $bulkResponses];
    }
}
