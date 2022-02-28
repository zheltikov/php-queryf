# php-queryf

This class represents queries to execute against a MySQL database.

DO NOT ENCODE SQL VALUES DIRECTLY. That's evil. The library will try to prevent this kind of thing. All values for where
clauses, inserts, etc. should be parameterized via the encoding methods below. This will make your code more robust and
reliable while also avoiding common security issues.

Usage is simple; construct the query using special printf-like markup, provide parameters for the substitution, and then
hand to the database libraries. Alternatively, you can call one of render*()
methods to see the actual SQL it would run.

Example:

```injectablephp
use Zheltikov\Queryf\Query;
use Zheltikov\Queryf\QueryArgument;
use Zheltikov\Queryf\QueryArgumentType as Type;

$q = new Query(
    'SELECT foo, bar FROM Table WHERE id = %d',
    [
        QueryArgument::fromDynamic(Type::Int, 17),
    ]
);

echo 'query: ' . $q->renderInsecure();
```

```injectablephp
use Zheltikov\Queryf\Query;
use Zheltikov\Queryf\QueryArgument;
use Zheltikov\Queryf\QueryArgumentType as Type;

$condition = QueryArgument::fromDynamic(Type::PairList);
$condition->getPairs()[] = [
    QueryArgument::fromDynamic(Type::String, 'id1'),
    QueryArgument::newInt(7),
];
$condition->getPairs()[] = [
    QueryArgument::newString('id2'),
    QueryArgument::newInt(14),
];

$q = new Query(
    'SELECT %LC FROM %T WHERE %W',
    [
        QueryArgument::fromDynamic(
            Type::List,
            QueryArgument::newString('id1_type'),
            QueryArgument::newString('data'),
        ),
        QueryArgument::newString('assoc_info'),
        $condition,
    ]
);

```

Values for substitution into the query should be mixed values. Composite values expected by some codes such as %W, %U,
etc., are also mixed objects that have array or map values.

Codes:

- `%s`, `%d`, `%u`, `%f` - strings, integers, unsigned integers or floats; `NULL` if a null is passed in.
- `%m` - mixed, gets converted to string/integer/float/boolean. nulls become `"NULL"`, throws otherwise
- `%=s`, `%=d`, `%=u`, `%=f`, `%=m` - like the previous except suitable for comparison, so `"%s"` becomes `" = VALUE"`.
  nulls become `"IS NULL"`
- `%T` - a table name. enclosed with ``` `` ```.
- `%C` - like `%T`, except for column names. Optionally supply two-/three-tuple to define qualified column name or
  qualified column name with an alias. `["table_name", "column_name"]` will become ``"`table_name`.`column_name`"``
  and
  `["table_name", "column_name", "alias"]` will become ``"`table_name`.`column_name` AS `alias`"``.
- `%V` - VALUES style row list; expects a list of lists, each of the same length.
- `%Ls`, `%Ld`, `%Lu`, `%Lf`, `%Lm` - strings/ints/uints/floats separated by commas
- `%LC` - list of column names separated by commas. Optionally supplied as a list of two-/three-tuples to define
  qualified column names or qualified column names with aliases. Similar to `%C`.
- `%LO`, `%LA` - key/value pair rendered as `key1=val1 OR/AND key2=val2` (similar to `%W`)
- `%U`, `%W` - keys and values suitable for UPDATE and WHERE clauses, respectively. `%U`
  becomes ``"`col1` = val1, `col2` = val2"`` and `%W` becomes ``"`col1` = val1 AND `col2` = val2"``. Does not currently
  support unsigned integers.
- `%Q` - literal string, evil evil. don't use.
- `%K` - an SQL comment. Will put the `/*` and `*/` for you.
- `%%` - literal `%` character.

For more details, check out <https://github.com/facebook/squangle>.
