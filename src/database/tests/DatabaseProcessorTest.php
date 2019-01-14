<?php

namespace HyperfTest\Database;

use PDO;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Hyperf\Database\Connection;
use Hyperf\Database\Query\Builder;
use Hyperf\Database\Query\Processors\Processor;

class DatabaseProcessorTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testInsertGetIdProcessing()
    {
        $pdo = $this->createMock(ProcessorTestPDOStub::class);
        $pdo->expects($this->once())->method('lastInsertId')->with($this->equalTo('id'))->will($this->returnValue('1'));
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('insert')->once()->with('sql', ['foo']);
        $connection->shouldReceive('getPdo')->once()->andReturn($pdo);
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getConnection')->andReturn($connection);
        $processor = new Processor;
        $result = $processor->processInsertGetId($builder, 'sql', ['foo'], 'id');
        $this->assertSame(1, $result);
    }
}

class ProcessorTestPDOStub extends PDO
{
    public function __construct()
    {
        //
    }

    public function lastInsertId($sequence = null)
    {
        //
    }
}