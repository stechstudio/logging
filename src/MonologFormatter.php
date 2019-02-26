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

class MonologFormatter extends LineFormatter implements FormatterInterface
{
    /** @var string */
    protected $dateFormat = 'c';

    /** @var string */
    protected $transactionID;

    /** @var bool */
    protected $allowInlineLineBreaks = true;

    /** @var bool */
    protected $includeStacktraces = false;

    public function __construct(
        ?string $format = null,
        ?string $dateFormat = null,
        bool $allowInlineLineBreaks = false,
        bool $ignoreEmptyContextAndExtra = false
    ) {
        $this->transactionID = uniqid();
        parent::__construct($format, DateTime::ISO8601, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * Formats a log record.
     *
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
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

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    protected function normalize($data, $depth = 0)
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
