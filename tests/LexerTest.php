<?php

use Pest\Expectation;
use RyanChandler\BladeParser\Lexer;
use RyanChandler\BladeParser\TokenType;

function lexer(string $source): Expectation
{
    return expect(Lexer::generate($source));
}

it('can generate literal tokens', function () {
    lexer('Hello, world!')
        ->sequence(
            fn ($token) => $token->type->toBe(TokenType::Literal)->slice->toBe('Hello, world!')
        );
});

it('can generate echo tokens', function () {
    lexer('{{ $test }}{{$foo}}')
        ->sequence(
            fn ($token) => $token->type->toBe(TokenType::Echo)->slice->toBe('{{ $test }}'),
            fn ($token) => $token->type->toBe(TokenType::Echo)->slice->toBe('{{$foo}}')
        );
});

it('can generate directive tokens', function () {
    lexer('@php @if(true) @error("name") @livewireScripts()')
        ->sequence(
            fn ($token) => $token->type->toBe(TokenType::Directive)->slice->toBe('@php'),
            noop(...),
            fn ($token) => $token->type->toBe(TokenType::Directive)->slice->toBe('@if(true)'),
            noop(...),
            fn ($token) => $token->type->toBe(TokenType::Directive)->slice->toBe('@error("name")'),
            noop(...),
            fn ($token) => $token->type->toBe(TokenType::Directive)->slice->toBe('@livewireScripts()'),
        );
});
