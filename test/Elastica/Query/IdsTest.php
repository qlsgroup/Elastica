<?php

namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\Ids;
use Elastica\Query\Type;
use Elastica\Test\Base as BaseTest;

class IdsTest extends BaseTest
{
    protected $_index;
    protected $_type;

    protected function set_up()
    {
        parent::set_up();

        if (!$this->_isFunctionalGroup()) {
            return;
        }

        $index = $this->_createIndex();

        $type1 = $index->getType('_doc');

        $doc = new Document(1, ['name' => 'hello world']);
        $type1->addDocument($doc);

        $doc = new Document(2, ['name' => 'nicolas ruflin']);
        $type1->addDocument($doc);

        $doc = new Document(3, ['name' => 'ruflin']);
        $type1->addDocument($doc);

        $index->refresh();

        $this->_type = $type1;
        $this->_index = $index;
    }

    /**
     * @group functional
     */
    public function testSetIdsSearchSingle()
    {
        $query = new Ids();
        $query->setIds('1');

        $resultSet = $this->_type->search($query);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetIdsSearchArray()
    {
        $query = new Ids();
        $query->setIds(['1', '2']);

        $resultSet = $this->_type->search($query);

        $this->assertEquals(2, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testAddIdsSearchSingle()
    {
        $query = new Ids();
        $query->addId('3');

        $resultSet = $this->_type->search($query);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testComboIdsSearchArray()
    {
        $query = new Ids();

        $query->setIds(['1', '2']);
        $query->addId('3');

        $resultSet = $this->_type->search($query);

        $this->assertEquals(3, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeSingleSearchSingle()
    {
        $query = new Ids();

        $query->setIds('1');

        $resultSet = $this->_index->search($query);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeSingleSearchArray()
    {
        $query = new Ids();

        $query->setIds(['1', '2']);

        $resultSet = $this->_index->search($query);

        $this->assertEquals(2, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeSingleSearchSingleDocInOtherType()
    {
        $query = new Ids();

        // Doc 4 is in the second type...
        $query->setIds('4');

        $resultSet = $this->_index->search($query);

        // ...therefore 0 results should be returned
        $this->assertEquals(0, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testSetTypeSingleSearchArrayDocInOtherType()
    {
        $query = new Ids();

        // Doc 4 is in the second type...
        $query->setIds(['1', '4']);

        $resultSet = $this->_index->search($query);

        // ...therefore only 1 result should be returned
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group unit
     */
    public function testQueryTypeAndTypeCollision()
    {
        // This test ensures that Elastica\Type and Elastica\Query\Type
        // do not collide when used together, which at one point
        // happened because of a use statement in Elastica\Query\Ids
        // Test goal is to make sure a Fatal Error is not triggered
        //
        // adapted fix for Elastica\Filter\Type
        // see https://github.com/ruflin/Elastica/pull/438
        $queryType = new Type();
        $filter = new Ids();
    }
}
