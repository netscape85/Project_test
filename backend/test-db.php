<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    DB::connection()->getPdo();
    echo "✅ Conexión exitosa a la base de datos!\n";
    echo "Base de datos: " . DB::connection()->getDatabaseName() . "\n";
    echo "Driver: " . DB::connection()->getDriverName() . "\n";
} catch (\Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}
