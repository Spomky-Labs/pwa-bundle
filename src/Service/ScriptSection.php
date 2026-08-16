<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

use function sprintf;
use Stringable;

/**
 * Assembles a named block of the generated service worker script.
 *
 * The section title and the comments are only rendered when debug mode is enabled; otherwise the
 * generated code is returned untouched. Callers can therefore document what they generate without
 * surrounding every explanation with a conditional and a heredoc.
 */
final class ScriptSection implements Stringable
{
    private const EOL = "\n";

    private const BANNER_FILLER = '****************************************************';

    /**
     * @var list<string>
     */
    private array $body = [];

    private function __construct(
        private readonly string $title,
        private readonly bool $debug,
    ) {
    }

    public static function create(string $title, bool $debug): self
    {
        return new self($title, $debug);
    }

    /**
     * Adds a comment block, rendered in debug mode only. Multi-line values are split so that every
     * single line gets its own comment marker.
     */
    public function comment(string $line, string ...$lines): self
    {
        if ($this->debug === false) {
            return $this;
        }

        $block = '';
        foreach ([$line, ...$lines] as $entry) {
            foreach (explode(self::EOL, $entry) as $row) {
                $block .= rtrim('// ' . $row) . self::EOL;
            }
        }
        $this->body[] = $block . self::EOL;

        return $this;
    }

    /**
     * Adds a piece of generated code, always rendered.
     */
    public function code(string $code): self
    {
        $this->body[] = $code;

        return $this;
    }

    public function render(): string
    {
        $body = implode('', $this->body);
        if ($this->debug === false) {
            return $body;
        }

        return self::EOL . self::EOL . $this->banner($this->title) . self::EOL
            . $body
            . $this->banner('END ' . $this->title) . str_repeat(self::EOL, 4);
    }

    public function __toString(): string
    {
        return $this->render();
    }

    private function banner(string $title): string
    {
        return sprintf('/%s %s %s/', self::BANNER_FILLER, $title, self::BANNER_FILLER);
    }
}
