<?php

namespace RyanChandler\BladeParser;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** @internal */
class Lexer
{
    /** @var string[] */
    protected array $source;

    protected int $i = -1;

    protected int $line = 1;

    protected string $previous;

    protected string $current = '';

    protected string $next = '';

    protected string $buffer = '';

    protected array $tokens = [];

    public static function generate(string $source): Collection
    {
        return (new static($source))->all();
    }

    public function __construct(string $source)
    {
        $this->source = Str::of($source)->replace('<?php', '@php')->replace('?>', '@endphp')->replaceMatches('/\r\n|\r|\n/', "\n")->split(1)->toArray();
    }

    /** @return \Illuminate\Support\Collection<\RyanChandler\BladeToolkit\Parser\Token> */
    public function all(): Collection
    {
        $this->read();

        while (true) {
            if ($this->i >= count($this->source)) {
                break;
            }

            if ($this->current . $this->next === '{{') {
                $this->tokens[] = $this->echo();
            } elseif ($this->current === '@' && $this->next !== '@') {
                $this->tokens[] = $this->directive();
            } else {
                $this->buffer .= $this->current;
                $this->read();
            }
        }

        $this->literal();

        return Collection::make($this->tokens);
    }

    /** @internal */
    protected function echo(): Token
    {
        $this->literal();

        $raw = '{{';

        $this->read();
        $this->read();

        while (true) {
            if ($this->i >= count($this->source)) {
                break;
            }

            if ($this->current . $this->next === '}}') {
                $raw .= '}}';

                $this->read();
                $this->read();

                break;
            }

            $raw .= $this->current;

            $this->read();
        }

        return new Token(TokenType::Echo, $raw, $this->line);
    }

    /** @internal */
    protected function directive(): Token
    {
        $this->literal();

        $match = $this->current;
        $hasFoundDirectiveName = false;
        $parens = 0;

        $this->read();

        while (true) {
            if ($this->i >= count($this->source)) {
                break;
            }

            $match .= $this->current;

            if (($this->current === '(' || ctype_space($this->current)) && ! $hasFoundDirectiveName) {
                $hasFoundDirectiveName = true;
            }

            if (ctype_space($this->current) && (! ctype_space($this->next) || $this->next !== '(')) {
                if ($hasFoundDirectiveName) {
                    break;
                }

                return new Token(TokenType::Directive, $match, $this->line);
            }

            if ($hasFoundDirectiveName && $this->current === '(') {
                $parens++;
            }

            if ($parens === 0 && in_array($this->current, [')', "\n"])) {
                break;
            }

            if ($this->current === ')') {
                $parens--;

                // We should probably be checking for a new-line character here too since we'll want to preserve it.
                if ($parens === 0 && $hasFoundDirectiveName) {
                    $this->read();

                    break;
                }
            }

            $this->read();
        }

        return new Token(TokenType::Directive, trim($match), $this->line);
    }

    /** @internal */
    protected function literal(): void
    {
        if (Str::length($this->buffer) > 0) {
            $this->tokens[] = new Token(TokenType::Literal, $this->buffer, $this->line);
            $this->buffer = '';
        }
    }

    /** @internal */
    protected function read()
    {
        $this->i += 1;
        $this->previous = $this->current;
        $this->current = $this->source[$this->i] ?? '';

        if ($this->previous === "\n") {
            $this->line += 1;
        }

        if ($this->i + 1 < count($this->source)) {
            $this->next = $this->source[$this->i + 1];
        }
    }
}
