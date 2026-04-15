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
        $tables = DB::select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public'
        ");

        return array_map(function ($table) {
            return array_values((array) $table)[0];
        }, $tables);
    }
}
