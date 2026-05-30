<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DateExpression
{
    public static function monthlyGroup(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql' => "DATE_FORMAT($column, '%Y-%m')",
            'pgsql' => "TO_CHAR($column, 'YYYY-MM')",
            default => "strftime('%Y-%m', $column)",
        };
    }
}
