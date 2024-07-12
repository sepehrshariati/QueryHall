<?php

namespace Koochik\Tests\Parsers;

use Koochik\QueryHall\Parsers\PSRArrayNotationParser;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class PSRArrayNotationParserTest extends TestCase
{
    public function testItParsesCorrectlyFromPsrRequestObjects()
    {
        // Create a mock for the request object
        $requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        // Define the query parameters to be returned by the mock
        $queryParameters = [
            'person' => "['john',25]",
            'location' => "['new york','london']",
            'numbers' => '[1,2,3,4]',
            'fruit' => "['apple']",
            'color' => "['blue']",
            'single' => "['single item']",
            'empty' => '[]',
            'one' => "['only one']",
            'numbers_only' => '[5]',
            'special characters' => "['&','# &[]']",

        ];
        $requestMock->expects($this->once())
            ->method('getQueryParams')
            ->willReturn($queryParameters);

        // Create an instance of the PSRArrayNotationParser with the mock request
        $parser = new PSRArrayNotationParser();

        // Test the pars method
        $expectedResult = [
            'person' => ['john', 25],
            'location' => ['new york', 'london'],
            'numbers' => [1, 2, 3, 4],
            'fruit' => ['apple'],
            'color' => ['blue'],
            'single' => ['single item'],
            'empty' => [],
            'one' => ['only one'],
            'numbers_only' => [5],
            'special characters' => ['&', '# &[]'],
        ];
        $this->assertEquals($expectedResult, $parser->pars($requestMock));
    }


    public function testItParsesCorrectlyFromArrays()
    {

        $parser = new PSRArrayNotationParser();

        $ParametersArray = [
            'person' => ['john', 25],
            'location' => ['new york', 'london'],
            'numbers' => [1, 2, 3, 4],
            'fruit' => ['apple'],
            'color' => ['blue'],
            'single' => ['single item'],
            'empty' => [],
            'one' => ['only one'],
            'numbers_only' => [5],
            'special characters' => ['&', '# &[]'],
        ];

        $this->assertEquals($ParametersArray, $parser->pars($ParametersArray));
    }

    public function testSingleValuesAreParsedFromArrays()
    {

        $parser = new PSRArrayNotationParser();

        $ParametersArray = [
            'person' => ['john', 25],
            'location' => ['new york', 'london'],
            'numbers' => [1, 2, 3, 4],
            'fruit' => ['apple'],
            'color' => ['blue'],
            'single' => ['single item'],
            'empty' => [],
            'one' => ['only one'],
            'numbers_only' => [5],
            'special characters' => ['&', '# &[]'],
            'single_number' => 55,
            'single_string' => 'flower'
        ];

        $this->assertEquals($ParametersArray, $parser->pars($ParametersArray));
    }


    public function testSingleValuesAreParsedFromRequestObjects()
    {
        // Create a mock for the request object
        $requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        // Define the query parameters to be returned by the mock
        $queryParameters = [
            'person' => "['john',25]",
            'location' => "['new york','london']",
            'numbers' => '[1,2,3,4]',
            'fruit' => "['apple']",
            'color' => "['blue']",
            'single' => "['single item']",
            'empty' => '[]',
            'one' => "['only one']",
            'numbers_only' => '[5]',
            'special characters' => "['&','# &[]']",
            'single_number' => '55',
            'single_string' => 'flower'
        ];
        $requestMock->expects($this->once())
            ->method('getQueryParams')
            ->willReturn($queryParameters);

        // Create an instance of the PSRArrayNotationParser with the mock request
        $parser = new PSRArrayNotationParser();

        // Test the pars method
        $expectedResult = [
            'person' => ['john', 25],
            'location' => ['new york', 'london'],
            'numbers' => [1, 2, 3, 4],
            'fruit' => ['apple'],
            'color' => ['blue'],
            'single' => ['single item'],
            'empty' => [],
            'one' => ['only one'],
            'numbers_only' => [5],
            'special characters' => ['&', '# &[]'],
            'single_number' => ['55'],
            'single_string' => ['flower']
        ];
        $this->assertEquals($expectedResult, $parser->pars($requestMock));
    }

    public function testItIgnoresMalformedArguments()
    {
        // Create a mock for the request object
        $requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        // Define the query parameters to be returned by the mock
        $queryParameters = [
            'person' => "['john',25]",
            'location' => "['new york','london']",
            'numbers' => '[1,2,3,4]',
            'fruit' => "['apple']",
            'color' => "['blue']",
            'single' => "['single item']",
            'empty' => '[]',
            'one' => "['only one']",
            'numbers_only' => '[5]',
            'special characters' => "['&','# &[]']",
            'single_number' => '55',
            'single_string' => 'flower',
            'malformed' => '[flower',
            'malformed2' => 'flower]',
            'malformed3' => 'flo]wer'
        ];
        $requestMock->expects($this->once())
            ->method('getQueryParams')
            ->willReturn($queryParameters);

        // Create an instance of the PSRArrayNotationParser with the mock request
        $parser = new PSRArrayNotationParser();

        // Test the pars method
        $expectedResult = [
            'person' => ['john', 25],
            'location' => ['new york', 'london'],
            'numbers' => [1, 2, 3, 4],
            'fruit' => ['apple'],
            'color' => ['blue'],
            'single' => ['single item'],
            'empty' => [],
            'one' => ['only one'],
            'numbers_only' => [5],
            'special characters' => ['&', '# &[]'],
            'single_number' => ['55'],
            'single_string' => ['flower']
        ];


        $this->assertEquals($expectedResult, $parser->pars($requestMock));

    }


    public function testItIgnoresWhiteSpacesBeforeAndAfterBrackets()
    {
        // Create a mock for the request object
        $requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        // Define the query parameters to be returned by the mock
        $queryParameters = [
            'person' => "['john',25]",
            'location' => "['new york','london']",
            'numbers' => '[1,2,3,4]  ',
            'fruit' => "['apple']",
            'color' => "  ['blue']  ",
            'single' => "  ['single item']  ",
            'empty' => '[]',
            'one' => "['only one']",
            'numbers_only' => '[5]',
            'special characters' => "['&','# &[]']",
            'single_number' => '55',
            'single_string' => 'flower',
            'malformed' => '[flower',
            'malformed2' => 'flower]',
            'malformed3' => 'flo]wer'
        ];
        $requestMock->expects($this->once())
            ->method('getQueryParams')
            ->willReturn($queryParameters);

        // Create an instance of the PSRArrayNotationParser with the mock request
        $parser = new PSRArrayNotationParser();

        // Test the pars method
        $expectedResult = [
            'person' => ['john', 25],
            'location' => ['new york', 'london'],
            'numbers' => [1, 2, 3, 4],
            'fruit' => ['apple'],
            'color' => ['blue'],
            'single' => ['single item'],
            'empty' => [],
            'one' => ['only one'],
            'numbers_only' => [5],
            'special characters' => ['&', '# &[]'],
            'single_number' => ['55'],
            'single_string' => ['flower']
        ];


        $this->assertEquals($expectedResult, $parser->pars($requestMock));

    }






}
