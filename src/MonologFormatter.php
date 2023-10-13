<?php declare(strict_types=1);

/**
 * Package: logging
 * Create Date: 2019-02-25
 * Created Time: 18:26
 */

namespace STS\Logging;

use DateTime;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class MonologFormatter extends LineFormatter implements FormatterInterface
{
    /** @var string */
    protected string $dateFormat = 'c';

    /** @var string */
    protected $transactionID;

    /** @var bool */
    protected bool $allowInlineLineBreaks = true;

    /** @var bool */
    protected bool $includeStacktraces = false;

    public function __construct(
        ?string $format = null,
        ?string $dateFormat = null,
        ?bool $allowInlineLineBreaks =  null,
        ?bool $ignoreEmptyContextAndExtra =  null
    ) {
        $dateFormat = $dateFormat?: DateTime::ISO8601;
        $allowInlineLineBreaks = $allowInlineLineBreaks?: false;
        $ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra?: false;

        $this->transactionID = uniqid();
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * Formats a log record.
     */
    public function format(LogRecord $record): string
    {
        $record = $this->stsContext($record);
        $record = $this->normalize($record);
        $data = $this->getLogData($record);
        $output = sprintf('%s  priority=%s', $record['datetime'], $record['level_name']);
        foreach ($data as $key => $value) {
            if (in_array($key, ['message', 'exception'])) {
                continue;
            }
            $output .= sprintf(', %s="%s"', $key, $this->stringify($value));
        }
        $output .= sprintf(', message="%s"', $this->stringify($data['message']));
        if (array_key_exists('exception', $data)) {
            $output .= sprintf(', exception="%s"', $this->stringify($data['exception']));
        }
        return $output . "\n";
    }

    /** Ensure we have out context info added the way we like it. */
    protected function stsContext(LogRecord $record): LogRecord
    {
        $record['context']['transactionID'] = $this->transactionID;
        $record['context']['environment'] = env('APP_ENV', 'unknown');
        $record['context']['httpHost'] = 'CLI';

        // Add some core parameters
        if ($this->exists('HTTP_HOST', $_SERVER)) {
            $record['context']['httpHost'] = $_SERVER['HTTP_HOST'];
        }

        if ($this->exists('REMOTE_ADDR')) {
            $record['context']['IP'] = $_SERVER['REMOTE_ADDR'];
        }
        if ($this->exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $record['context']['IP'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (env('CORRELATION_ID', false) !== false) {
            $record['context']['correlationID'] = $_ENV['CORRELATION_ID'];
        }
        if (env('PLANROOM_HOST', false) !== false) {
            $record['context']['planroomHost'] = env('PLANROOM_HOST');
        }

        return $record;
    }

    protected function exists(string $key, ?array $collection = []): bool
    {
        $collection = $collection?: $_SERVER;
        return array_key_exists($key, $collection);
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    protected function normalize(mixed $data, int $depth = 0)
    {
        if (is_array($data) &&
            isset($data['message']) &&
            substr($data['message'], 0, 9) === 'exception' &&
            isset($data['context']['exception']) &&
            $data['context']['exception'] instanceof \Exception) {
            $data['message'] = $data['context']['exception'];
        }
        return parent::normalize($data);
    }

    /**
     * Get the data from record that we actually want to log
     */
    protected function getLogData(array $record): array
    {
        $data = [];
        // Merge context into record parameters
        $data = array_replace($data, $record['context']);
        // Merge extra into record parameters
        $data = array_replace($data, $record['extra']);
        $data['message'] = $record['message'];
        return $data;
    }
}
