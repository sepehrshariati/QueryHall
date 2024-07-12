<?php
declare(strict_types=1);

namespace Koochik\Tests\Validators;

use Koochik\QueryHall\Validators\BasicValidator as V;
use PHPUnit\Framework\TestCase;

class BasicValidatorTest extends TestCase
{

    //======================================== The validate method ======================================

    public function testValidArgumentsPassesValidation()
    {
        $arguments = [11, 5.6, 'Sarah', 77, 'jane'];
        $rules = [
            [V::biggerThan(7), V::lessThan(100)],
            V::within(2, 8),
            V::in(['Sarah', 'jane']),
            V::in([1, 12, 66, 77]),
            [V::in(['jane', 'Sarah']), V::notIn(['jack'])]
        ];

        $result = V::validate($arguments, $rules);

        $this->assertTrue($result);
    }

    public function testInValidArgumentsFailsValidation()
    {
        $arguments = [11, 5.6, 'Sarah', 77, 'jane'];
        $rules = [
            [V::biggerThan(15), V::lessThan(100)],
            V::within(2, 8),
            V::in(['Sarah', 'jane']),
            V::in([1, 12, 66, 77]),
            [V::in(['jane', 'Sarah']), V::notIn(['jack'])]
        ];

        $result = V::validate($arguments, $rules);

        $this->assertFalse($result);
    }

    public function testInValidArgumentsDueToLowNumberOfArgumentsFailsValidation()
    {
        $arguments = [11, 5.6];
        $rules = [
            [V::biggerThan(2), V::lessThan(100)],
            V::within(2, 8),
            V::within(2, 8),
        ];

        $result = V::validate($arguments, $rules);

        $this->assertFalse($result);
    }


    public function testInValidArgumentsDueToExtraArgumentsFailsValidation()
    {
        $arguments = [11, 5.6, 'Sarah', 77, 'jane',66];
        $rules = [
            [V::biggerThan(10), V::lessThan(100)],
            V::within(2, 8),
            V::in(['Sarah', 'jane']),
            V::in([1, 12, 66, 77]),
            [V::in(['jane', 'Sarah']), V::notIn(['jack'])]
        ];

        $result = V::validate($arguments, $rules);

        $this->assertFalse($result);
    }


    //======================================== The Assert method ======================================


    public function testAssertThrowsErrorForInvalidValueAndNoDefault()
    {
        $this->expectException(\InvalidArgumentException::class);
        V::assert(674, [V::biggerThan(800), V::lessThan(1000)]);
    }

    public function testAssertDoesNotThrowErrorForValidValueAndReturnValue()
    {
        $value = V::assert(674, [V::biggerThan(1), V::lessThan(1000)]);
        $this->assertEquals(674, $value);
    }

    public function testAssertDoesNotThrowsErrorForInvalidValueWithDefaults()
    {
        $value = V::assert(674, [V::biggerThan(800), V::lessThan(1000)], 500);
        $this->assertEquals(500, $value);

    }

    public function testAssertDoesNotThrowsErrorForValidValueAndSingleRulePassed()
    {
        $value = V::assert(900, V::biggerThan(800));
        $this->assertEquals(900, $value);

    }

    public function testAssertThrowsErrorForInvalidValueAndSingleRulePassed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $value = V::assert(500, V::biggerThan(800));

    }

    public function testAssertThrowsErrorForInvalidValueAndSingleRulePassedButDefaultValueProvided()
    {
        $value = V::assert(500, V::biggerThan(800),444);
        $this->assertEquals(444, $value);

    }

    public function testInvalidArgumentTypeResultsInValidationFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $value = V::assert('saba', V::biggerThan(800));
    }

    public function testInvalidArgumentTypeButWithDefaultProvidedPassesValidation()
    {
        $value = V::assert('saba', V::biggerThan(800),444);
        $this->assertEquals(444, $value);

    }

}
