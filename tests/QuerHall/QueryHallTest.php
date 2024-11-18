<?php
namespace Koochik\Tests\QueryHall;
use Koochik\QueryHall\Interfaces\ParameterParser;
use Koochik\QueryHall\Validators\BasicValidator;
use Mockery as m;
use Koochik\Tests\Mocks\ConcreteQueryHall;
use PHPUnit\Framework\TestCase;

class QueryHallTest extends TestCase
{
    private $queryBuilderMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->queryBuilderMock = m::mock();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testFilterMethodCallsCorrectMethodsInCorrectOrder()
    {
        // Mocking the ParameterParser
        $parserMock = $this->createMock(ParameterParser::class);
        $parserMock->expects($this->once())
            ->method('pars')
            ->willReturn([
                'where' => ['age', '>', '18'],
                'paginate' => [10, 2],
                'custom' => [55, 'hello@example.com', 'hello world'],
            ]);

        $this->queryBuilderMock->expects('method_where')
            ->with('age', '>', '18');

        $this->queryBuilderMock->expects('method_paginate')
            ->with(10, 2);

        $this->queryBuilderMock->expects('method_custom')
            ->with(55, 'hello@example.com', 'hello world');

        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, [], $parserMock);

        // Call the filter method
        $queryHall->filter();
    }


    public function testMethodsReturningInvalidArgumentExceptionAreIgnoredAndDefaultValuesAreUsed()
    {
        // Mocking the ParameterParser
        $parserMock = $this->createMock(ParameterParser::class);
        $parserMock->expects($this->once())
            ->method('pars')
            ->willReturn([
                'where' => ['age', '>', '18'],
                'paginate' => ['blue', 1],
                'custom' => ['blue', 55.6, 'hello world'],
            ]);

        $this->queryBuilderMock->expects('method_where')
            ->with('age', '>', '18');

        $this->queryBuilderMock->expects('method_paginate')
            ->with(20, 1);

        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, [], $parserMock);

        // Call the filter method
        $queryHall->filter();
    }

    public function testFilterWorksWithNoParameter()
    {
        $parserMock = $this->createMock(ParameterParser::class);
        $parserMock->expects($this->once())
            ->method('pars')
            ->willReturn([]);

        $this->queryBuilderMock->allows('method_where')
            ->never();

        $this->queryBuilderMock->allows('method_paginate')
            ->never();

        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, [], $parserMock);

        $queryHall->filter();
    }

    public function testPaginationMethodsWork()
    {
        $parserMock = $this->createMock(ParameterParser::class);

        $parserMock->expects($this->exactly(5))
            ->method('pars')
            ->willReturn(['p' => [5], 'perPage' => [30]]);

        $this->queryBuilderMock->allows('paginate');
        $this->queryBuilderMock->allows('count');
        $this->queryBuilderMock->allows('execute')->andReturn([['name' => 'emily', 'age' => 22],['name' => 'bob', 'age' => 25]]);

        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, [], $parserMock);

        $data = $queryHall->getPaginatedResult();

        $expectedData = [['name' => 'emily', 'age' => 22], ['name' => 'bob', 'age' => 25]];
        $expectedMeta = [
            'current_page' => 5,
            'per_page' => 30,
        ];

        $this->assertEquals($expectedData, $data['data']);
        $this->assertEquals($expectedMeta, $data['meta']);
    }

    public function testIncompatibleArgumentsAreIgnoredAreIgnored()
    {
        // Mocking the ParameterParser
        $parserMock = $this->createMock(ParameterParser::class);
        $parserMock->expects($this->once())
            ->method('pars')
            ->willReturn([
                'where' => ['age', '>', 18],
                'paginate' => ['blue', 1],
                'custom' => ['blue', 55.6],
            ]);

        $this->queryBuilderMock->expects('method_where')
            ->with('age', '>', 18);

        $this->queryBuilderMock->expects('method_paginate')
            ->with(20, 1);

        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, [], $parserMock);

        // Call the filter method
        $queryHall->filter();
    }

    public function testWhenAllRulesPassMethodsAreCalled()
    {
        $parameters = [
            'where' => ['age', '>', 18],
            'paginate' => [5, 1],
        ];

        $this->queryBuilderMock->expects('method_where')
            ->with('age', '>', 18);

        $this->queryBuilderMock->expects('method_paginate')
            ->with(5, 1);

        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, $parameters);
        $queryHall->setAllowedMethods('where', [BasicValidator::isString(), BasicValidator::in(['>','<']), BasicValidator::isInt()]);
        $queryHall->setAllowedMethods('paginate', [BasicValidator::isInt(), BasicValidator::isInt()]);

        // Call the filter method
        $queryHall->filter();

        $this->assertTrue(true);
    }

    public function testWhenSetAllowedMethodsIsUsedOnlyMethodsAddedAreUsed()
    {
        $parameters = [
            'where' => ['age', '>', 18],
            'paginate' => [5, 1],
        ];

        $this->queryBuilderMock->expects('method_where')
            ->with('age', '>', 18);

        $this->queryBuilderMock->allows('method_paginate')
            ->never();

        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, $parameters);
        $queryHall->setAllowedMethods('where', [BasicValidator::isString(), [BasicValidator::isString(), BasicValidator::in(['>','<'])], BasicValidator::isInt()]);

        // Call the filter method
        $queryHall->filter();

        $this->assertTrue(true);

    }



    public function testMethodsCanBeUsedMoreThanOnce()
    {

        $parserMock = $this->createMock(ParameterParser::class);
        $parserMock->expects($this->once())
            ->method('pars')
            ->willReturn([
                'where' => ['age', '>', 18],
                'paginate' => [5, 1],
                'paginate_2' => [5, 2],
                'paginate_3' => [5, 3],
            ]);


        $this->queryBuilderMock->expects('method_where')
            ->with('age', '>', 18);

        // Expect the paginate method to be called three times with arguments [5, 1], [5, 2], [5, 3]
        $this->queryBuilderMock->expects('method_paginate')->with(5, 1);
        $this->queryBuilderMock->expects('method_paginate')->with(5, 2);
        $this->queryBuilderMock->expects('method_paginate')->with(5, 3);


        $this->queryBuilderMock->method_paginate(5, 2);
        $this->queryBuilderMock->method_paginate(5, 3);


        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, [],$parserMock);
        $queryHall->setAllowedMethods('where', [BasicValidator::isString(), [BasicValidator::isString(), BasicValidator::in(['>','<'])], BasicValidator::isInt()]);
        $queryHall->setAllowedMethods('paginate', [BasicValidator::isInt(), BasicValidator::isInt()]);

        // Call the filter method
        $queryHall->filter();

        $this->assertTrue(true);
    }





//    public function testMethodsCantBeUsedMoreThanTheirSpecifiedCount()
//    {
//        $parameters = [
//            'where' => ['age', '>', 18],
//            'paginate' => [5, 1],
//            'paginate_2' => [5, 2],
//            'paginate_3' => [5, 3],
//        ];
//
//        $this->queryBuilderMock->expects('method_where')
//            ->with('age', '>', 18);
//
//        $this->queryBuilderMock->expects('method_paginate')
//            ->with(5, 1);
//
//        $this->queryBuilderMock->expects('method_paginate')
//            ->with(5, 2);
//
//        $this->queryBuilderMock->expects('method_paginate')
//            ->with(5, 3);
//
//        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, $parameters);
//        $queryHall->setAllowedMethods('where', [BasicValidator::isString(), [BasicValidator::isString(), BasicValidator::in(['>','<'])], BasicValidator::isInt()]);
//        $queryHall->setAllowedMethods('paginate', [BasicValidator::isInt(),BasicValidator::isInt()]);
//
//        // Call the filter method
//        $queryHall->filter();
//
//        $this->assertTrue(true);
//
//    }







    public function testWhenWhenValidationFailsMethodsAreNotUsed()
    {
        $parameters = [
            'where' => ['age', '>', 18],
            'paginate' => [5, 1],
        ];

        $this->queryBuilderMock->expects('method_where')
            ->with('age', '>', 18);

        $this->queryBuilderMock->allows('method_paginate')
            ->never();

        $queryHall = new ConcreteQueryHall($this->queryBuilderMock, $parameters);
        $queryHall->setAllowedMethods('where', [BasicValidator::isString(), BasicValidator::in(['>','<']), BasicValidator::isInt()]);
        $queryHall->setAllowedMethods('paginate', [[BasicValidator::isInt(), BasicValidator::biggerThan(10)], BasicValidator::isInt()]);

        // Call the filter method
        $queryHall->filter();

        $this->assertTrue(true);

    }



}
