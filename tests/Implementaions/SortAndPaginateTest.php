<?php

namespace Koochik\Tests\Implementaions;

use Doctrine\DBAL\DriverManager;
use Koochik\QueryHall\ConcreteImplementation\SortAndPaginate;
use PHPUnit\Framework\TestCase;

class SortAndPaginateTest extends TestCase
{
    private \Doctrine\DBAL\Connection $connection;

    protected function setUp(): void
    {
        // Create an in-memory SQLite database
        $connectionParams = [
            'url' => 'sqlite:///:memory:',
            'driver' => 'pdo_sqlite',
        ];
        $this->connection = DriverManager::getConnection($connectionParams);

        // Create the "users" table
        $schema = new \Doctrine\DBAL\Schema\Schema();
        $usersTable = $schema->createTable('users');
        $usersTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $usersTable->addColumn('name', 'string', ['length' => 255]);
        $usersTable->addColumn('lastName', 'string', ['length' => 255]);
        $usersTable->addColumn('age', 'integer');
        $usersTable->addColumn('isActive', 'boolean');
        $usersTable->addColumn('height', 'float');
        $usersTable->setPrimaryKey(['id']);

        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeQuery($sql);
        }

        // Insert some test data
        $this->insertTestData();

        $userCount = $this->connection->fetchOne('SELECT COUNT(*) FROM users');
        $this->assertEquals(10, $userCount, 'There should be exactly 10 users inserted.');
    }

    private function insertTestData(): void
    {
        $usersData = [
            ['id' => 1, 'name' => 'John', 'lastName' => 'Doe', 'age' => 25, 'isActive' => true, 'height' => 179.5],
            ['id' => 2, 'name' => 'Alice', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => false, 'height' => 165.3],
            ['id' => 3, 'name' => 'Michael', 'lastName' => 'Smith', 'age' => 28, 'isActive' => true, 'height' => 182.0],
            ['id' => 4, 'name' => 'Emily', 'lastName' => 'Brown', 'age' => 35, 'isActive' => true, 'height' => 170.8],
            ['id' => 5, 'name' => 'Daniel', 'lastName' => 'Williams', 'age' => 22, 'isActive' => false, 'height' => 176.5],
            ['id' => 6, 'name' => 'Olivia', 'lastName' => 'Jones', 'age' => 27, 'isActive' => true, 'height' => 168.9],
            ['id' => 7, 'name' => 'James', 'lastName' => 'Taylor', 'age' => 32, 'isActive' => true, 'height' => 175.2],
            ['id' => 8, 'name' => 'Sophia', 'lastName' => 'Anderson', 'age' => 29, 'isActive' => false, 'height' => 160.0],
            ['id' => 9, 'name' => 'Benjamin', 'lastName' => 'Martinez', 'age' => 31, 'isActive' => true, 'height' => 180.6],
            ['id' => 10, 'name' => 'Mia', 'lastName' => 'Hernandez', 'age' => 26, 'isActive' => false, 'height' => 172.4],
        ];

        foreach ($usersData as $userData) {
            $this->connection->insert('users', $userData);
        }

    }

    // =============================================                =============================================
    // =============================================   Pagination   =============================================
    // =============================================                =============================================

    public function testPaginationWorksForTheFirstPage(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [5]]);
        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 1, 'name' => 'John', 'lastName' => 'Doe', 'age' => 25, 'isActive' => 1, 'height' => 179.5],
            ['id' => 2, 'name' => 'Alice', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => '', 'height' => 165.3],
            ['id' => 3, 'name' => 'Michael', 'lastName' => 'Smith', 'age' => 28, 'isActive' => 1, 'height' => 182.0],
            ['id' => 4, 'name' => 'Emily', 'lastName' => 'Brown', 'age' => 35, 'isActive' => 1, 'height' => 170.8],
            ['id' => 5, 'name' => 'Daniel', 'lastName' => 'Williams', 'age' => 22, 'isActive' => '', 'height' => 176.5]];

        $this->assertEquals($expectedData, $result['data']);

        $expectedMeta = [
            'current_page' => 1,
            'per_page' => 5,
            'last_page' => 2,
            'total' => 10,
            'from' => 1,
            'to' => 5,
        ];

        $this->assertEquals($expectedMeta, $result['meta']);

    }

    public function testPaginationWorksForTheSecondPage(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [2], 'perPage' => [3]]);
        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 4, 'name' => 'Emily', 'lastName' => 'Brown', 'age' => 35, 'isActive' => 1, 'height' => 170.8],
            ['id' => 5, 'name' => 'Daniel', 'lastName' => 'Williams', 'age' => 22, 'isActive' => '', 'height' => 176.5],
            ['id' => 6, 'name' => 'Olivia', 'lastName' => 'Jones', 'age' => 27, 'isActive' => 1, 'height' => 168.9],
        ];

        $this->assertEquals($expectedData, $result['data']);

        $expectedMeta = [
            'current_page' => 2,
            'per_page' => 3,
            'last_page' => 4,
            'total' => 10,
            'from' => 4,
            'to' => 6,
        ];

        $this->assertEquals($expectedMeta, $result['meta']);

    }

    public function testPaginationWorksLastPage(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');
        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [4], 'perPage' => [3]]);
        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [['id' => 10, 'name' => 'Mia', 'lastName' => 'Hernandez', 'age' => 26, 'isActive' => false, 'height' => 172.4],
        ];

        $this->assertEquals($expectedData, $result['data']);

    }

    public function testPaginationWorksBeyondLastPage(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');
        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [12], 'perPage' => [5]]);
        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [];

        $this->assertEquals($expectedData, $result['data']);

    }

    // =============================================                =============================================
    // =============================================      Sort      =============================================
    // =============================================                =============================================

    public function testSortWorksDescending(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [2], 'sort' => ['id', -1]]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 10, 'name' => 'Mia', 'lastName' => 'Hernandez', 'age' => 26, 'isActive' => false, 'height' => 172.4],

            ['id' => 9, 'name' => 'Benjamin', 'lastName' => 'Martinez', 'age' => 31, 'isActive' => true, 'height' => 180.6],
        ];

        $this->assertEquals($expectedData, $result['data']);

    }

    public function testSortWorksAscending(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [2], 'sort' => ['id', 1]]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 1, 'name' => 'John', 'lastName' => 'Doe', 'age' => 25, 'isActive' => 1, 'height' => 179.5],
            ['id' => 2, 'name' => 'Alice', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => '', 'height' => 165.3],
        ];

        $this->assertEquals($expectedData, $result['data']);

    }

    // =============================================                =============================================
    // =============================================     Where      =============================================
    // =============================================                =============================================

    public function testWhereWorksWithEqualOperator(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [2], 'sort' => ['id', -1], 'where' => ['name', '=', 'Emily']]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 4, 'name' => 'Emily', 'lastName' => 'Brown', 'age' => 35, 'isActive' => 1, 'height' => 170.8],
        ];

        $this->assertEquals($expectedData, $result['data']);

    }

    public function testWhereWorksWithNotEqualOperator(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [5], 'sort' => ['id', -1], 'where' => ['name', '!=', 'Emily']]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 10, 'name' => 'Mia', 'lastName' => 'Hernandez', 'age' => 26, 'isActive' => false, 'height' => 172.4],
            ['id' => 9, 'name' => 'Benjamin', 'lastName' => 'Martinez', 'age' => 31, 'isActive' => true, 'height' => 180.6],
            ['id' => 8, 'name' => 'Sophia', 'lastName' => 'Anderson', 'age' => 29, 'isActive' => false, 'height' => 160.0],
            ['id' => 7, 'name' => 'James', 'lastName' => 'Taylor', 'age' => 32, 'isActive' => true, 'height' => 175.2],
            ['id' => 6, 'name' => 'Olivia', 'lastName' => 'Jones', 'age' => 27, 'isActive' => true, 'height' => 168.9],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testWhereWorksWithGreaterThanOperator(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [3], 'sort' => ['age', -1], 'where' => ['age', '>', 30]]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 4, 'name' => 'Emily', 'lastName' => 'Brown', 'age' => 35, 'isActive' => true, 'height' => 170.8],
            ['id' => 7, 'name' => 'James', 'lastName' => 'Taylor', 'age' => 32, 'isActive' => true, 'height' => 175.2],
            ['id' => 9, 'name' => 'Benjamin', 'lastName' => 'Martinez', 'age' => 31, 'isActive' => true, 'height' => 180.6],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testWhereWorksWithGreaterThanOrEqualOperator(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [20], 'where' => ['age', '>=', 30]]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 2, 'name' => 'Alice', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => false, 'height' => 165.3],
            ['id' => 4, 'name' => 'Emily', 'lastName' => 'Brown', 'age' => 35, 'isActive' => true, 'height' => 170.8],
            ['id' => 7, 'name' => 'James', 'lastName' => 'Taylor', 'age' => 32, 'isActive' => true, 'height' => 175.2],
            ['id' => 9, 'name' => 'Benjamin', 'lastName' => 'Martinez', 'age' => 31, 'isActive' => true, 'height' => 180.6],
        ];
        $this->assertEquals($expectedData, $result['data']);
    }

    public function testWhereWorksWithLessThanOrEqualOperator(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [5], 'sort' => ['age', 1], 'where' => ['age', '<=', 27]]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 5, 'name' => 'Daniel', 'lastName' => 'Williams', 'age' => 22, 'isActive' => false, 'height' => 176.5],
            ['id' => 1, 'name' => 'John', 'lastName' => 'Doe', 'age' => 25, 'isActive' => true, 'height' => 179.5],
            ['id' => 10, 'name' => 'Mia', 'lastName' => 'Hernandez', 'age' => 26, 'isActive' => false, 'height' => 172.4],
            ['id' => 6, 'name' => 'Olivia', 'lastName' => 'Jones', 'age' => 27, 'isActive' => true, 'height' => 168.9],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testWhereWorksWithLessThanOperator(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [2], 'where' => ['age', '<', 25]]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 5, 'name' => 'Daniel', 'lastName' => 'Williams', 'age' => 22, 'isActive' => false, 'height' => 176.5],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testWhereWorksWithLikeOperator(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [20], 'sort' => ['id', 1], 'where' => ['name', 'LIKE', 'i']]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 2, 'name' => 'Alice', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => false, 'height' => 165.3],
            ['id' => 3, 'name' => 'Michael', 'lastName' => 'Smith', 'age' => 28, 'isActive' => true, 'height' => 182.0],
            ['id' => 4, 'name' => 'Emily', 'lastName' => 'Brown', 'age' => 35, 'isActive' => true, 'height' => 170.8],
            ['id' => 5, 'name' => 'Daniel', 'lastName' => 'Williams', 'age' => 22, 'isActive' => false, 'height' => 176.5],
            ['id' => 6, 'name' => 'Olivia', 'lastName' => 'Jones', 'age' => 27, 'isActive' => true, 'height' => 168.9],
            ['id' => 8, 'name' => 'Sophia', 'lastName' => 'Anderson', 'age' => 29, 'isActive' => false, 'height' => 160.0],
            ['id' => 9, 'name' => 'Benjamin', 'lastName' => 'Martinez', 'age' => 31, 'isActive' => true, 'height' => 180.6],
            ['id' => 10, 'name' => 'Mia', 'lastName' => 'Hernandez', 'age' => 26, 'isActive' => false, 'height' => 172.4],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testWhereOperatorsAreCombinedViaAnd(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [20], 'sort' => ['id', 1], 'where' => ['name', 'LIKE', 'i'], 'where_2' => ['age', '>', '28']]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 2, 'name' => 'Alice', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => false, 'height' => 165.3],
            ['id' => 4, 'name' => 'Emily', 'lastName' => 'Brown', 'age' => 35, 'isActive' => true, 'height' => 170.8],
            ['id' => 8, 'name' => 'Sophia', 'lastName' => 'Anderson', 'age' => 29, 'isActive' => false, 'height' => 160.0],
            ['id' => 9, 'name' => 'Benjamin', 'lastName' => 'Martinez', 'age' => 31, 'isActive' => true, 'height' => 180.6],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testOrWhereWorks(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [20], 'sort' => ['id', 1], 'where' => ['age', '<', '25'], 'orWhere' => ['name', '=', 'Alice']]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 2, 'name' => 'Alice', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => false, 'height' => 165.3],
            ['id' => 5, 'name' => 'Daniel', 'lastName' => 'Williams', 'age' => 22, 'isActive' => false, 'height' => 176.5],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }


    // =============================================                =============================================
    // =============================================   Integration  =============================================
    // =============================================                =============================================



    public function comprehensiveBehaviorTest(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [2], 'perPage' => [5], 'sort' => ['id', 1], 'where' => ['age', '<', '25'],'where_2' => ['invalidColumnName', '<', '25'], 'orWhere' => ['name', '=', 'Alice']]);

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 2, 'name' => 'Alice', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => false, 'height' => 165.3],
            ['id' => 5, 'name' => 'Daniel', 'lastName' => 'Williams', 'age' => 22, 'isActive' => false, 'height' => 176.5],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }


    // =============================================                =============================================
    // =============================================   Transformers =============================================
    // =============================================                =============================================



    public function testBasicTransformerAppliedToResults(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [2]]);

        // Add a simple transformer that modifies the name field
        $SortAndPaginate->setTransformer(function($row) {
            $row['name'] = strtoupper($row['name']);
            return $row;
        });

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 1, 'name' => 'JOHN', 'lastName' => 'Doe', 'age' => 25, 'isActive' => 1, 'height' => 179.5],
            ['id' => 2, 'name' => 'ALICE', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => '', 'height' => 165.3],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testTransformerWithComplexDataModification(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [2]]);

        // Transform data into a different structure
        $SortAndPaginate->setTransformer(function($row) {
            return [
                'fullName' => $row['name'] . ' ' . $row['lastName'],
                'details' => [
                    'age' => $row['age'],
                    'heightInMeters' => $row['height'] / 100,
                    'status' => $row['isActive'] ? 'Active' : 'Inactive'
                ]
            ];
        });

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            [
                'fullName' => 'John Doe',
                'details' => [
                    'age' => 25,
                    'heightInMeters' => 1.795,
                    'status' => 'Active'
                ]
            ],
            [
                'fullName' => 'Alice Johnson',
                'details' => [
                    'age' => 30,
                    'heightInMeters' => 1.653,
                    'status' => 'Inactive'
                ]
            ]
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testTransformerWithFieldFiltering(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [2]]);

        // Transform to only include specific fields
        $SortAndPaginate->setTransformer(function($row) {
            return [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        });

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Alice']
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testTransformerChaining(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [2]]);

        // Test method chaining with transformer
        $result = $SortAndPaginate
            ->setTransformer(function($row) {
                return ['name' => $row['name']];
            })
            ->getPaginatedResult();

        $expectedData = [
            ['name' => 'John'],
            ['name' => 'Alice']
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testTransformerWithSortingAndFiltering(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, [
            'p' => [1],
            'perPage' => [3],
            'sort' => ['age', -1],
            'where' => ['isActive', '=', true]
        ]);

        $SortAndPaginate->setTransformer(function($row) {
            return [
                'name' => $row['name'],
                'age' => $row['age'],
                'ageGroup' => $row['age'] < 30 ? 'Young' : 'Adult'
            ];
        });

        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['name' => 'Emily', 'age' => 35, 'ageGroup' => 'Adult'],
            ['name' => 'James', 'age' => 32, 'ageGroup' => 'Adult'],
            ['name' => 'Benjamin', 'age' => 31, 'ageGroup' => 'Adult']
        ];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testNullTransformer(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')->from('users');

        $SortAndPaginate = new SortAndPaginate($queryBuilder, ['p' => [1], 'perPage' => [2]]);

        // Don't set any transformer
        $result = $SortAndPaginate->getPaginatedResult();

        $expectedData = [
            ['id' => 1, 'name' => 'John', 'lastName' => 'Doe', 'age' => 25, 'isActive' => 1, 'height' => 179.5],
            ['id' => 2, 'name' => 'Alice', 'lastName' => 'Johnson', 'age' => 30, 'isActive' => '', 'height' => 165.3],
        ];

        $this->assertEquals($expectedData, $result['data']);
    }



    protected function tearDown(): void
    {
        // Close the database connection
        $this->connection->close();
    }



}
