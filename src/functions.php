<?php

namespace Zheltikov\Queryf;

use ParseError;

/**
 * @param string $query
 * @param mixed  ...$params
 *
 * @return string
 */
function queryf(string $query, ...$params): string
{
    $offset = strpos($query, ';\'"`');
    if ($offset !== false) {
        parse_error($query, $offset, 'Saw dangerous characters in SQL query');
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
            parse_error($query, $idx, 'too few parameters for query');
        }

        $param =& $params[$current_param++];
        if ($c === 'd' || $c === 's' || $c === 'f' || $c === 'u') {
            append_value($result, $idx, $c, $param);
        } elseif ($c === 'm') {
            if (
                !is_string($param)
                && !is_int($param)
                && !is_double($param)
                && !is_bool($param)
                && !is_null($param)
            ) {
                parse_error($query, $idx, '%m expects int/float/string/bool');
            }

            append_value($result, $idx, $c, $param);
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
                parse_error(
                    $query,
                    $idx,
                    'expected %=d, %=f, %=s, %=u, or %=m'
                );
            }

            if (is_null($param)) {
                $result .= ' IS NULL';
            } else {
                $result .= ' = ';
                append_value($result, $idx, $type[0], $param);
            }
        } elseif ($c === 'V') {
            if (is_query($param)) {
                parse_error($query, $idx, '%V doesn\'t allow subquery');
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

                    append_value($result, $idx, 'v', $col);
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
                    parse_error(
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
                    parse_error(
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
                        append_value($result, $idx, $type[0], $val);
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
            parse_error($query, $idx, 'unknown % code');
        }
    }

    if ($after_percent) {
        parse_error($query, $idx, 'string ended with unfinished % code');
    }

    if ($current_param !== count($params)) {
        parse_error($query, 0, 'too many parameters specified for query');
    }

    return $result;
}
