# QueryHall

QueryHall is a PHP library that allows you to sort, filter, and paginate query builder instances of a database abstraction layer of your choice, such as Doctrine DBAL or Eloquent. It ships with a ready-to-use concrete class for DBAL that allows filtering, sorting, and paginating Doctrine DBAL queries based on a PSR request object (using the query parameters).

## Features


- **Abstract Query Builder**: Easily integrate with various database abstraction layers.
- **Sorting**: Sort your queries based on multiple criteria.
- **Filtering**: Apply complex filters to your queries.
- **Pagination**: Efficiently paginate your query results.
- **PSR-7 Compatibility**: Works seamlessly with PSR-7 request objects.
- **Flexible Query Parsing Algorithm**: Easily parse query parameters with a customizable parser that can be swapped.
- **Built-in BasicValidator**: Validate filter rules with the built-in `BasicValidator`.
- **Highly Customizable**: Customize almost every aspect of this library, including


## Installation

Install the library via Composer:

```bash
composer require koochik/queryhall
```

## Using the built-in Dbal implementaion

```
$app->get('/users', function (Request $request) {
    $queryString = $request->getUri()->getQuery();
    parse_str($queryString, $queryParams);

    $connectionParams = [
        'dbname' => 'DataBase',
        'user' => 'root',
        'password' => '',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ];

    $conn = DriverManager::getConnection($connectionParams);
    $queryBuilder = $conn->createQueryBuilder();
    $queryBuilder->select('*')->from('users');

    $queryHall = new SortAndPaginate($queryBuilder, $queryParams);
    $response = new Response();
    $data = $queryHall->getPaginatedResult();
    
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});
```

Then the request 
```users?where=[id,>,2]&sort=[name,-1]&perPage=5&p=2```
would Get users with `id > 2`, Sort by `name` in descending order, paginate with 5 items per page, and return the result from page 2.
the result is somthing like
```

```
```json
{
    "data": [
        {
            "id": 4,
            "name": "Emily",
            "lastName": "Brown",
            "age": 35,
            "isActive": 1,
            "height": 170.8
        },
        {
            "id": 5,
            "name": "Daniel",
            "lastName": "Williams",
            "age": 22,
            "isActive": 0,
            "height": 176.5
        },
        {
            "id": 9,
            "name": "Benjamin",
            "lastName": "Martinez",
            "age": 31,
            "isActive": 1,
            "height": 180.6
        }
    ],
    "meta": {
        "current_page": 2,
        "per_page": 5,
        "last_page": 2,
        "total": 8,
        "from": 6,
        "to": 8
    }
}
```


