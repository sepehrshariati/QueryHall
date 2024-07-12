<?php
namespace Koochik\Tests\Mocks;
use Koochik\QueryHall\QueryHall;
use Koochik\QueryHall\Validators\BasicValidator;

class ConcreteQueryHall extends QueryHall
{
    public function filter_where($Query, $column, $condition, $value)
    {
        return $Query->method_where($column, $condition, $value);
    }

    public function filter_paginate($Query, $perPage, $page)
    {
        $validated_perPage = BasicValidator::assert($perPage, [BasicValidator::within(1, 99), BasicValidator::isInt()], 20);
        $validated_page = BasicValidator::assert($page, [BasicValidator::within(1, 99), BasicValidator::isInt()], 1);

        return $Query->method_paginate($validated_perPage, $validated_page);

    }

    public function filter_custom($Query, $first, $second, $third)
    {

        $validated_first = BasicValidator::assert($first, BasicValidator::isInt());
        $validated_second = BasicValidator::assert($second, [BasicValidator::contains('@'), BasicValidator::isString()]);
        $validated_third = BasicValidator::assert($third, BasicValidator::isString());

        return $Query->method_custom($validated_first, $validated_second, $validated_third);

    }

    public function filter_new($Query, $first)
    {

        return $Query->method_new($first);

    }


    public function getPaginationMeta($query, $perPage, $page): array
    {
        $query->count();

        return [
            'current_page' => $page,
            'per_page' => $perPage,
        ];

    }

    public function fetchResult($query): array
    {
        return $query->execute();
    }

    public function paginate($query, $perPage, $page): void
    {
        $query->paginate($perPage, $page);

    }


}
