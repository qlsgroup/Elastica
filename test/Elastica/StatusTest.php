<?php

namespace Elastica\Test;

use Elastica\Exception\ResponseException;
use Elastica\Response;
use Elastica\Status;
use Elastica\Test\Base as BaseTest;
use Yoast\PHPUnitPolyfills\Polyfills\AssertIsType;

class StatusTest extends BaseTest
{
    use AssertIsType;

    /**
     * @group functional
     */
    public function testGetResponse()
    {
        $index = $this->_createIndex();
        $status = new Status($index->getClient());
        $this->assertInstanceOf(Response::class, $status->getResponse());
    }

    /**
     * @group functional
     */
    public function testGetIndexNames()
    {
        $indexName = 'test';
        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create([], true);
        $index = $this->_createIndex();
        $index->refresh();
        $index->forcemerge();

        $status = new Status($index->getClient());
        $names = $status->getIndexNames();

        self::assertIsArray($names);
        $this->assertContains($index->getName(), $names);

        foreach ($names as $name) {
            self::assertIsString($name);
        }
    }

    /**
     * @group functional
     */
    public function testIndexExists()
    {
        $indexName = 'elastica_test';
        $aliasName = 'elastica_test-alias';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);

        try {
            // Make sure index is deleted first
            $index->delete();
        } catch (ResponseException $e) {
        }

        $status = new Status($client);
        $this->assertFalse($status->indexExists($indexName));
        $index->create();

        usleep(10000);
        $status->refresh();
        $this->assertTrue($status->indexExists($indexName));
    }

    /**
     * @group functional
     */
    public function testAliasExists()
    {
        $aliasName = 'elastica_test-alias';

        $index1 = $this->_createIndex();
        $indexName = $index1->getName();

        $status = new Status($index1->getClient());

        foreach ($status->getIndicesWithAlias($aliasName) as $tmpIndex) {
            $tmpIndex->removeAlias($aliasName);
        }

        $this->assertFalse($status->aliasExists($aliasName));

        $index1->addAlias($aliasName);
        $status->refresh();
        $this->assertTrue($status->aliasExists($aliasName));

        $indicesWithAlias = $status->getIndicesWithAlias($aliasName);
        $this->assertEquals([$indexName], array_map(
            function ($index) {
                return $index->getName();
            }, $indicesWithAlias));
    }
}
