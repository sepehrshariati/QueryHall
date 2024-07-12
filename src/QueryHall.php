<?php

namespace Koochik\QueryHall;

use Koochik\QueryHall\Interfaces\ParameterParser;
use Koochik\QueryHall\Parsers\PSRArrayNotationParser;
use Koochik\QueryHall\Validators\BasicValidator;

abstract class QueryHall
{
    private mixed $QueryBuilder;

    private ParameterParser $Parser;

    private $parametersArray;

    private int $default_perPage = 20;

    private int $minPerPage = 1;

    private int $maxPerPage = 100;

    private int $default_page = 1;

    private int $default_max_method_call = 10;

    private array $allowedMethods;

    public array $remainingMethodCall = [];

    public function __construct(mixed $QueryBuilder, mixed $parametersArray, ?ParameterParser $parser = null)
    {
        $this->Parser = $parser ?: new PSRArrayNotationParser();
        $this->QueryBuilder = $QueryBuilder;
        $this->parametersArray = $parametersArray;
        $this->allowedMethods = [];
    }

    private function getMethodName(string $key): string
    {
        $methodName = 'filter_'.$key;

        return preg_replace('/_[0-9]+$/', '', $methodName);

    }

    public function filter(): mixed
    {

        $parsedParameters = $this->Parser->pars($this->parametersArray);
        foreach ($parsedParameters as $key => $value) {

            $methodName = $this->getMethodName($key);
            if (method_exists($this, $methodName) && $this->canCall($key, $value)) {
                try {
                    $this->$methodName($this->QueryBuilder, ...$value);
                    $this->decrementMethodCallCount($key);
                } catch (\ArgumentCountError|\InvalidArgumentException $e) {

                }
            }

        }

        return $this->QueryBuilder;

    }


    public function getPaginatedResult(): array
    {
        $this->filter();
        $PaginatedQueryBuilder = clone $this->QueryBuilder;
        $meta = $this->getPaginationMeta(clone $PaginatedQueryBuilder, $this->perPage(), $this->currentPage());
        $this->paginate($PaginatedQueryBuilder, $this->perPage(), $this->currentPage());
        $data = $this->fetchResult($PaginatedQueryBuilder);

        return ['data' => $data, 'meta' => $meta];
    }

    // responsible for returning the pagination meta as an array
    abstract protected function getPaginationMeta($query, $perPage, $page): array;

    // responsible for returning the fetched result from the database as an array

    abstract protected function fetchResult($query): array;

    // responsible for applying pagination logic on query and returning the resulting query
    abstract protected function paginate($query, $perPage, $page);

    protected function perPage(): int
    {
        $parsedParameters = $this->Parser->pars($this->parametersArray);

        if (isset($parsedParameters['perPage'][0])) {
         //TODO: fix this problem
        // this condition was removed: && is_int($parsedParameters['perPage'][0])

            $perPage = $parsedParameters['perPage'][0];
            if ($perPage >= $this->minPerPage && $perPage <= $this->maxPerPage) {
                return $perPage;
            }
        }

        return $this->default_perPage;
    }

    protected function currentPage(): int
    {
        $parsedParameters = $this->Parser->pars($this->parametersArray);

        if (isset($parsedParameters['p'][0])) {

            //TODO: fix this problem
            // this condition was removed: && is_int($parsedParameters['p'][0])

            $currentPage = $parsedParameters['p'][0];
            if ($currentPage > 1) {
                return $currentPage;
            }
        }

        return $this->default_page;
    }


    public function setAllowedMethods(string $method, array $rules, ?int $maxCalls = null): void
    {

        $this->allowedMethods[$method] = [
            'rules' => $rules
        ];

        $this->remainingMethodCall[$method] = $maxCalls ?? $this->default_max_method_call;

    }

    protected function canCall(string $method, array $arguments): bool
    {
        if($this->allowedMethods == []) {
            return true;
        }

        if (!isset($this->allowedMethods[$method])) {
            return false;
        }

        if ($this->remainingMethodCall[$method] <= 0) {
            return false;
        }

        $rules = $this->allowedMethods[$method]['rules'];

        return BasicValidator::validate($arguments, $rules);

    }


    protected function decrementMethodCallCount(string $method): void
    {
        if (isset($this->remainingMethodCall[$method])) {
            $this->remainingMethodCall[$method]--;
        }

    }

}
