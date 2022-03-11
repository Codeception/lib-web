<?php

declare(strict_types=1);

namespace Codeception\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

use function mb_stripos;
use function mb_strlen;
use function mb_substr;
use function preg_replace;
use function sprintf;
use function strtr;
use function trim;

class Page extends Constraint
{
    /**
     * @var string
     */
    protected $uri;
    /**
     * @var string
     */
    protected $string;

    public function __construct(string $string, string $uri = '')
    {
        $this->string = $this->normalizeText($string);
        $this->uri = $uri;
    }

    /**
     * Evaluates the constraint for parameter $other. Returns true if the
     * constraint is met, false otherwise.
     *
     * @param string $other Value or object to evaluate.
     * @return bool
     */
    protected function matches($other): bool
    {
        $other = $this->normalizeText($other);
        return mb_stripos($other, $this->string, 0, 'UTF-8') !== false;
    }

    private function normalizeText(string $text): string
    {
        $text = strtr($text, "\r\n", "  ");
        return trim(preg_replace('/\\s{2,}/', ' ', $text));
    }

    /**
     * Returns a string representation of the constraint.
     */
    public function toString(): string
    {
        return sprintf(
            'contains "%s"',
            $this->string
        );
    }

    /**
     * @param string $pageContent
     */
    protected function failureDescription($pageContent): string
    {
        $message = $this->uriMessage('on page');
        $message .= "\n--> ";
        $message .= mb_substr($pageContent, 0, 300, 'utf-8');
        if (mb_strlen($pageContent, 'utf-8') > 300 && function_exists('codecept_output_dir')) {
            $message .= "\n[Content too long to display. See complete response in '"
                . codecept_output_dir() . "' directory]";
        }

        return $message . "\n--> " . $this->toString();
    }

    protected function uriMessage(string $onPage = ''): string
    {
        if (!$this->uri) {
            return '';
        }
        return "{$onPage} {$this->uri}";
    }
}
