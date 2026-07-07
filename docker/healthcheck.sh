#!/bin/sh
set -e

php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

if (! config('app.key')) {
    fwrite(STDERR, 'APP_KEY missing'.PHP_EOL);
    exit(1);
}

\$host = getenv('REDIS_HOST') ?: 'redis';
\$port = (int) (getenv('REDIS_PORT') ?: 6379);
\$connection = @fsockopen(\$host, \$port, \$errno, \$errstr, 2);

if (! \$connection) {
    fwrite(STDERR, 'Redis unavailable: '.\$errstr.PHP_EOL);
    exit(1);
}

fclose(\$connection);
"
