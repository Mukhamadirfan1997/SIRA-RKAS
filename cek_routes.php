<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$routes = collect(app('router')->getRoutes())->map(function($r) {
    return ['uri' => $r->uri(), 'name' => $r->getName()];
});

foreach ($routes->sortBy('name') as $r) {
    if($r['name']) echo $r['name'] . ' => ' . $r['uri'] . "\n";
}
