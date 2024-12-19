<?php

namespace Koochik\QueryHall\ConcreteImplementation;

use Koochik\QueryHall\QueryHall;

final class SortAndPaginate extends QueryHall
{
    private bool $is_PostgreSQL = false;
    private int $parameterCounter = 0;

    public function TypePostgreSQL(): void
    {
        $this->is_PostgreSQL = true;
    }

    protected function paginate($query, $perPage, $page): void
    {
        $offset = ($page - 1) * $perPage;
        $query->setFirstResult($offset)->setMaxResults($perPage);
    }

    protected function getPaginationMeta($query, $perPage, $page): array
    {
        if ($this->is_PostgreSQL) {
            $query->select('COUNT(*) OVER() as total');
        } else {
            $query->select('COUNT(*) as total');
        }

        $result = $query->executeQuery()->fetchAssociative();
        $total = $result ? $result['total'] : 0;
        $lastPage = ceil($total / $perPage);

        return [
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'total' => $total,
            'from' => ($page - 1) * $perPage + 1,
            'to' => min($page * $perPage, $total),
        ];
    }

    protected function fetchResult($query): array
    {
        return $query->executeQuery()->fetchAllAssociative();
    }

    protected function filter_sort($query, $basedOn, $order): void
    {
        $orderDirection = ($order < 0) ? 'DESC' : 'ASC';
        $query->orderBy($basedOn, $orderDirection);
    }

    private function generateUniqueParamName(string $column): string
    {
        $this->parameterCounter++;
        return $column . '_param_' . $this->parameterCounter;
    }

    protected function filter_where($query, string $column, string $condition, $value): void
    {
        $paramName = $this->is_PostgreSQL ? $this->generateUniqueParamName($column) : $column;

        switch ($condition) {
            case '=':
            case '!=':
            case '<':
            case '>':
            case '>=':
            case '<=':
                $query->andWhere($column . ' ' . $condition . ' :' . $paramName);
                $query->setParameter($paramName, $value);
                break;
            case 'LIKE':
                $query->andWhere($column . ' LIKE :' . $paramName);
                $query->setParameter($paramName, '%' . $value . '%');
                break;
            default:
                break;
        }
    }

    protected function filter_orWhere($query, string $column, string $condition, $value): void
    {
        $paramName = $this->is_PostgreSQL ? $this->generateUniqueParamName($column) : $column;

        switch ($condition) {
            case '=':
            case '!=':
            case '<':
            case '>':
            case '>=':
            case '<=':
                $query->orWhere($column . ' ' . $condition . ' :' . $paramName);
                $query->setParameter($paramName, $value);
                break;
            case 'LIKE':
                $query->orWhere($column . ' LIKE :' . $paramName);
                $query->setParameter($paramName, '%' . $value . '%');
                break;
            default:
                break;
        }
    }
}