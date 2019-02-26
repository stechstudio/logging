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
    public function __invoke(Logger &$logger): void
    {
        $this->setStechFormatter($logger);
    }

    /**
     * Sets up the key=value, pair format that we like best
     */
    public function setStechFormatter(Logger &$logger): void
    {
        $formatter = new MonologFormatter;
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }
    }
}
