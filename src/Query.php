<?php

namespace Zheltikov\Queryf;

use InvalidArgumentException;
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
                $this->appendValue($result, $idx, $c, $param);
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

                $this->appendValue($result, $idx, $c, $param);
            } elseif ($c === 'K') {
                $result .= '/*';
                append_comment($result, $param);
                $result .= '*/';
            } elseif ($c === 'T' || $c === 'C') {
                append_column_table_name($result, $param);
            } elseif ($c === '=') {
                $type = advance($query, $idx, 1);

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
                    $this->appendValue($result, $idx, $type[0], $param);
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

                        $this->appendValue($result, $idx, 'v', $col);
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
                $type = advance($query, $idx, 1);

                if ($type === 'O' || $type === 'A') {
                    $result .= '(';
                    $sep = $type === 'O' ? ' OR ' : ' AND ';
                    append_value_clauses($result, $idx, $sep, $param);
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
                            append_column_table_name($result, $val);
                        } else {
                            $this->appendValue($result, $idx, $type[0], $val);
                        }
                    }
                }
            } elseif ($c === 'U' || $c === 'W') {
                if ($c === 'W') {
                    append_value_clauses($result, $idx, ' AND ', $param);
                } else {
                    append_value_clauses($result, $idx, ', ', $param);
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
                $this->formatStringParseError($query, $offset, $type, 'string');
            }

            $value = $d->asString();
            $s .= '"';
            $this->appendEscapedString($s, $value, $connection);
            $s .= '"';
        } elseif ($d->isBool()) {
            if ($type !== 'v' && $type !== 'm') {
                $this->formatStringParseError($query, $offset, $type, 'bool');
            }

            $s .= $d->asString();
        } elseif ($d->isInt()) {
            if ($type !== 'd' && $type !== 'v' && $type !== 'm' && $type !== 'u') {
                $this->formatStringParseError($query, $offset, $type, 'int');
            }

            if ($type === 'u') {
                $s .= $d->getInt();
            } else {
                $s .= $d->asString();
            }
        } elseif ($d->isDouble()) {
            if ($type !== 'f' && $type !== 'v' && $type !== 'm') {
                $this->formatStringParseError($query, $offset, $type, 'double');
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

    protected function formatStringParseError(
        string $query_text,
        int $offset,
        string $format_specifier,
        string $value_type
    ): void {
        $this->parseError(
            $query_text,
            $offset,
            sprintf(
                'invalid value type %s for format string %%%s',
                $value_type,
                $format_specifier
            )
        );
    }
}
