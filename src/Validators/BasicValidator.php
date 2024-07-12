<?php

declare(strict_types=1);

namespace Koochik\QueryHall\Validators;

class BasicValidator
{

    public static function any(): \Closure
    {
        return fn ($value) => true;
    }

    public static function isInt(): \Closure
    {
        return fn ($value) => is_int($value);
    }

    public static function isString(): \Closure
    {
        return fn ($value) => is_string($value);
    }

    public static function isNumeric(): \Closure
    {
        return fn ($value) => is_numeric($value);
    }

    public static function contains(string $substring): \Closure
    {
        return fn ($value) => is_string($value) && str_contains($value, $substring);
    }

    public static function biggerThan(float|int $threshold): \Closure
    {
        return fn ($value) => is_numeric($value) && $value > $threshold;
    }

    public static function biggerThanOrEqual(float|int $threshold): \Closure
    {
        return fn ($value) => is_numeric($value) && $value >= $threshold;
    }

    public static function lessThan(float|int $threshold): \Closure
    {
        return fn ($value) => is_numeric($value) && $value < $threshold;
    }

    public static function lessThanOrEqual(float|int $threshold): \Closure
    {
        return fn ($value) => is_numeric($value) && $value <= $threshold;
    }

    public static function within(float|int $min, float|int $max): \Closure
    {
        return fn ($value) => is_numeric($value) && $value >= $min && $value <= $max;
    }

    public static function in(array $allowed): \Closure
    {
        return fn ($value) => in_array($value, $allowed, true);
    }

    public static function notIn(array $disallowed): \Closure
    {
        return fn ($value) => !in_array($value, $disallowed, true);
    }

    public static function matchesRegex(string $pattern): \Closure
    {
        return fn ($value) => is_string($value) && preg_match($pattern, $value) === 1;
    }


    public static function validate(array $arguments, array $rules): bool
    {
        if (count($arguments) !== count($rules)) {
            return false;
        }

        foreach ($arguments as $key => $arg) {
            if (isset($rules[$key])) {
                try {
                    self::assert($arg, $rules[$key]);
                } catch (\InvalidArgumentException $e) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public static function assert(mixed $value, array|\Closure $rules, mixed $default = null): mixed
    {
        $isValid = true;

        if ($rules instanceof \Closure){
            $isValid=$rules($value);
        }else{
            foreach ($rules as $rule) {

                try {
                    $result=!$rule($value);
                    if (!$rule($value)) {
                        $isValid = false;
                        break;
                    }
                }catch(\Exception $e){
                    $isValid = false;
                }

            }
        }

        if ($isValid) {
            return $value;
        } elseif ($default) {
            return $default;
        }

        throw new \InvalidArgumentException("The provided value does not meet the specified rules.");
    }
}
