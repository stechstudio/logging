<?php declare(strict_types=1);

/**
 * Package: logging
 * Create Date: 2019-02-25
 * Created Time: 18:46
 */

namespace STS\Logging;

use Illuminate\Log\Logger;

class MonologTap
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        $this->setStechFormatter($logger);

        $this->setDefaultContext($logger);
    }

    /**
     * Sets up the key=value, pair format that we like best
     */
    public function setStechFormatter(Logger $logger): void
    {
        $formatter = new MonologFormatter;
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }
    }

    /**
     * Sets up a series of standardized contexts for us.
     */
    public function setDefaultContext(Logger $logger): void
    {
        $logger->pushProcessor(function ($record) {
            if (! in_array($record['channel'], ['local', 'staging', 'production', 'lumen'])) {
                $record['context']['channel'] = $record['channel'];
            }
            // Add some core parameters
            if (array_key_exists('HTTP_HOST', $_SERVER)) {
                $record['context']['httpHost'] = $_SERVER['HTTP_HOST'];
            } else {
                $record['context']['httpHost'] = 'CLI';
            }
            if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
                $record['context']['IP'] = $_SERVER['REMOTE_ADDR'];
            }
            if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                $record['context']['IP'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            $record['context']['transactionID'] = uniqid();
            if (array_key_exists('CORRELATION_ID', $_ENV)) {
                $record['context']['correlationID'] = $_ENV['CORRELATION_ID'];
            }
            if (! env('PLANROOM_HOST', false)) {
                $record['context']['planroomHost'] = env('PLANROOM_HOST');
            }
            return $record;
        });
    }
}
