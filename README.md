## Install
First, add the private repository to your `composer.json` file like so:
```json
"repositories": [
  {
    "type": "vcs",
    "url": "git@github.com:stechstudio/logging.git"
  }
]
```
Then you can just run composer:
```
composer require stechstudio/logging
```

## Configuration
Edit your `config/logging.php` to look like this:

```php
<?php
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */
    'default' => env('LOG_CHANNEL', 'stack'),
    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */
    'channels' => [
        'lambda_stack' => [
            'driver' => 'stack',
            'channels' => ['stderr', 'logentries'],
            'ignore_exceptions' => false,
        ],
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],
        'single' => [
            'driver' => 'single',
            'tap' => [STS\Logging\MonologTap::class],
            'path' => '/var/log/local/' . strtolower(env('APP_NAME')) . '.log',
            'permission' => 0664,
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'tap' => [STS\Logging\MonologTap::class],
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],
        'logentries' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => LogEntriesHandler::class,
            'tap' => [MonologTap::class],
            'with' => [
                'token' => env('LOGENTRIES_TOKEN'),
            ],
        ],
        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],
    ],
];

```
