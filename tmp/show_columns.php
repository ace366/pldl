<?php
chdir(__DIR__.'/../'); // ← ここがズレる場合は後述
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = ['children','guardians','child_guardian'];

echo "DB_DRIVER=".DB::getDriverName().PHP_EOL;

foreach ($tables as $t) {
    echo PHP_EOL."== {$t} ==".PHP_EOL;
    if (!Schema::hasTable($t)) { echo "NO TABLE".PHP_EOL; continue; }
    $cols = Schema::getColumnListing($t);
    echo "COLUMNS: ".implode(', ', $cols).PHP_EOL;
}
