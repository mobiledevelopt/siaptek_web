<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// run the test logic
\Illuminate\Support\Facades\Cache::flush();
\Tests\Traits\CronTestSetup::setUpCron();

