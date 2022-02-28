<?php

namespace Zheltikov\Queryf;

use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
use Zheltikov\Queryf\QueryArgumentType as Type;
use mysqli;

class Query
{
    protected QueryText $query_text;
    protected bool $unsafe_query = false;

    /**
     * @var QueryArgument[]
     */
    protected array $params = [];

    /**
     * @param QueryArgument[] $params
     */
    #[Pure]
    public function __construct(string $query_text, array $params = [])
    {
        $this->query_text = new QueryText($query_text);
        $this->params = $params;
        $this->unsafe_query = false;
    }

    /**
     * @param QueryArgument[] $params
     */
    public function render(?mysqli $conn, array $params = []): string
    {
        if (func_num_args() === 1) {
            $params = $this->params;
        }

        $query = $this->query_text->getQuery();

        $offset = strpos($query, ';\'"`');
        if ($offset !== false) {
            $this->parseError($query, $offset, 'Saw dangerous characters in SQL query');
        }

        $result = '';

        $current_param = 0;
        $after_percent = false;
        for ($idx = 0; $idx < strlen($query); $idx++) {
            $c = $query[$idx];

            if (!$after_percent) {
                if ($c !== '%') {
                    $result .= $c;
                } else {
                    $after_percent = true;
                }
                continue;
            }

            $after_percent = false;

            if ($c === '%') {
                $result .= '%';
                continue;
            }

            if ($current_param === count($params)) {
                $this->parseError($query, $idx, 'too few parameters for query');
            }

            $param =& $params[$current_param++];
            if ($c === 'd' || $c === 's' || $c === 'f' || $c === 'u') {
                $this->appendValue($result, $idx, $c, $param, $conn);
            } elseif ($c === 'm') {
                if (
                    !$param->isString()
                    && !$param->isInt()
                    && !$param->isDouble()
                    && !$param->isBool()
                    && !$param->isNull()
                ) {
                    $this->parseError($query, $idx, '%m expects int/float/string/bool');
                }

                $this->appendValue($result, $idx, $c, $param, $conn);
            } elseif ($c === 'K') {
                $result .= '/*';
                $this->appendComment($result, $param);
                $result .= '*/';
            } elseif ($c === 'T' || $c === 'C') {
                $this->appendColumnTableName($result, $param);
            } elseif ($c === '=') {
                $type = $this->advance($query, $idx, 1);

                if (
                    $type !== 'd'
                    && $type !== 's'
                    && $type !== 'f'
                    && $type !== 'u'
                    && $type !== 'm'
                ) {
                    $this->parseError(
                        $query,
                        $idx,
                        'expected %=d, %=f, %=s, %=u, or %=m'
                    );
                }

                if ($param->isNull()) {
                    $result .= ' IS NULL';
                } else {
                    $result .= ' = ';
                    $this->appendValue($result, $idx, $type[0], $param, $conn);
                }
            } elseif ($c === 'V') {
                if ($param->isQuery()) {
                    $this->parseError($query, $idx, '%V doesn\'t allow subquery');
                }

                $col_idx = 0;
                $row_len = 0;
                $first_row = true;
                $first_in_row = true;
                foreach ($param->getList() as $row) {
                    $first_in_row = true;
                    $col_idx = 0;

                    if (!$first_row) {
                        $result .= ', ';
                    }

                    $result .= '(';

                    foreach ($row->getList() as $col) {
                        if (!$first_in_row) {
                            $result .= ', ';
                        }

                        $this->appendValue($result, $idx, 'v', $col, $conn);
                        $col_idx++;
                        $first_in_row = false;

                        if ($first_row) {
                            $row_len++;
                        }
                    }

                    $result .= ')';

                    if ($first_row) {
                        $first_row = false;
                    } elseif ($col_idx !== $row_len) {
                        $this->parseError(
                            $query,
                            $idx,
                            'not all rows provided for %V formatter are the same size'
                        );
                    }
                }
            } elseif ($c === 'L') {
                $type = $this->advance($query, $idx, 1);

                if ($type === 'O' || $type === 'A') {
                    $result .= '(';
                    $sep = $type === 'O' ? ' OR ' : ' AND ';
                    $this->appendValueClauses($result, $idx, $sep, $param, $conn);
                    $result .= ')';
                } else {
                    if (!$param->isList()) {
                        $this->parseError(
                            $query,
                            $idx,
                            'expected array for %L formatter'
                        );
                    }

                    $first_param = true;
                    foreach ($param->getList() as $val) {
                        if (!$first_param) {
                            $result .= ', ';
                        }

                        $first_param = false;

                        if ($type === 'C') {
                            $this->appendColumnTableName($result, $val);
                        } else {
                            $this->appendValue($result, $idx, $type[0], $val, $conn);
                        }
                    }
                }
            } elseif ($c === 'U' || $c === 'W') {
                if ($c === 'W') {
                    $this->appendValueClauses($result, $idx, ' AND ', $param, $conn);
                } else {
                    $this->appendValueClauses($result, $idx, ', ', $param, $conn);
                }
            } elseif ($c === 'Q') {
                if ($param->isQuery()) {
                    $result .= $param->getQuery()->render();
                } else {
                    $result .= $param->asString();
                }
            } else {
                $this->parseError($query, $idx, 'unknown % code');
            }
        }

        if ($after_percent) {
            $this->parseError($query, $idx, 'string ended with unfinished % code');
        }

        if ($current_param !== count($params)) {
            $this->parseError($query, 0, 'too many parameters specified for query');
        }

        return $result;
    }

    /**
     * Raise an exception with, hopefully, a helpful error message.
     *
     * @throws InvalidArgumentException
     */
    protected function parseError(string $s, int $offset, string $message): void
    {
        $msg = sprintf('Parse error at offset %d: %s, query: %s', $offset, $message, $s);
        throw new InvalidArgumentException($msg);
    }

    protected function appendValue(string &$s, int $offset, string $type, QueryArgument $d, ?mysqli $connection): void
    {
        $query = $this->query_text->getQuery();

        if ($d->isString()) {
            if ($type !== 's' && $type !== 'v' && $type !== 'm') {
                $this->formatStringParseError($query, $offset, $type, Type::String);
            }

            $value = $d->asString();
            $s .= '"';
            $this->appendEscapedString($s, $value, $connection);
            $s .= '"';
        } elseif ($d->isBool()) {
            if ($type !== 'v' && $type !== 'm') {
                $this->formatStringParseError($query, $offset, $type, Type::Bool);
            }

            $s .= $d->asString();
        } elseif ($d->isInt()) {
            if ($type !== 'd' && $type !== 'v' && $type !== 'm' && $type !== 'u') {
                $this->formatStringParseError($query, $offset, $type, Type::Int);
            }

            if ($type === 'u') {
                $s .= $d->getInt();
            } else {
                $s .= $d->asString();
            }
        } elseif ($d->isDouble()) {
            if ($type !== 'f' && $type !== 'v' && $type !== 'm') {
                $this->formatStringParseError($query, $offset, $type, Type::Double);
            }

            $s .= $d->asString();
        } elseif ($d->isQuery()) {
            $s .= $d->getQuery()->render($connection);
        } elseif ($d->isNull()) {
            $s .= 'NULL';
        } else {
            $this->formatStringParseError($query, $offset, $type, $d->typeName());
        }
    }

    protected function appendComment(string &$s, QueryArgument $d): void
    {
        $str = $d->asString();
        $str = str_replace('/*', ' / * ', $str);
        $str = str_replace('*/', ' * / ', $str);
        $s .= $str;
    }

    protected function appendColumnTableName(string &$s, QueryArgument $d): void
    {
        if ($d->isString()) {
            $s .= '`';

            foreach ($d->getString() as $char) {
                // Toss in an extra ` if we see one.
                if ($char === '`') {
                    $s .= '`';
                }

                $s .= $char;
            }

            $s .= '`';
        } elseif ($d->isTwoTuple()) {
            // If a two-tuple is provided we have a qualified column name
            $t = $d->getTwoTuple();
            $this->appendColumnTableName($s, $t[0]);
            $s .= '.';
            $this->appendColumnTableName($s, $t[1]);
        } elseif ($d->isThreeTuple()) {
            // If a three-tuple is provided we have a qualified column name
            // with an alias. This is helpful for constructing JOIN queries.
            $t = $d->getThreeTuple();
            $this->appendColumnTableName($s, $t[0]);
            $s .= '.';
            $this->appendColumnTableName($s, $t[1]);
            $s .= ' AS ';
            $this->appendColumnTableName($s, $t[2]);
        } else {
            $s .= $d->asString();
        }
    }

    /**
     * Raise an exception for format string/value mismatches
     */
    protected function formatStringParseError(
        string $query_text,
        int $offset,
        string $format_specifier,
        Type $value_type,
    ): void {
        $this->parseError(
            $query_text,
            $offset,
            sprintf(
                'invalid value type %s (%s) for format string %%%s',
                $value_type->name,
                $value_type->value,
                $format_specifier
            )
        );
    }

    /**
     * Consume the next x bytes from s, updating offset, and raising an
     * exception if there aren't sufficient bytes left.
     */
    protected function advance(string $s, int &$offset, int $num): string
    {
        if (strlen($s) <= $offset + $num) {
            $this->parseError($s, $offset, 'unexpected end of string');
        }

        $offset += $num;

        return substr($s, $offset - $num + 1, $num);
    }

    protected function appendValueClauses(
        string &$ret,
        int $idx,
        string $sep,
        QueryArgument $param,
        ?mysqli $connection,
    ): void {
        $query = $this->query_text->getQuery();

        if (!$param->isPairList()) {
            $this->parseError(
                $query,
                $idx,
                sprintf(
                    'object expected for %%Lx but received %s (%s)',
                    $param->typeName()->name,
                    $param->typeName()->value,
                ),
            );
        }

        // Sort these to get consistent query ordering (mainly for
        // testing, but also aesthetics of the final query).
        $first_param = true;
        foreach ($param->getPairs() as $pair) {
            if (!$first_param) {
                $ret .= $sep;
            }

            $first_param = false;
            $this->appendColumnTableName($ret, $pair[0]);

            if ($pair[1]->isNull() && $sep[0] !== ',') {
                $ret .= ' IS NULL';
            } else {
                $ret .= ' = ';
                $this->appendValue($ret, $idx, 'v', $pair[1], $connection);
            }
        }
    }
}
