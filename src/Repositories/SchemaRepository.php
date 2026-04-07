<?php

namespace Naimul\DbVisualizer\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaRepository
{
    public function columns($table)
    {
        return Schema::getColumnListing($table);
    }

    public function allTables()
    {
        $tables = DB::select('SHOW TABLES');

        return array_map(function ($table) {
            return array_values((array) $table)[0];
        }, $tables);
    }
}
