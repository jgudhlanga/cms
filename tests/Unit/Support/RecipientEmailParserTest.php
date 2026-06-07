<?php

use App\Support\RecipientEmailParser;

it('parses comma-separated recipient emails from a string', function (): void {
    expect(RecipientEmailParser::parse('one@example.test, two@example.test'))
        ->toBe(['one@example.test', 'two@example.test']);
});

it('parses comma-separated recipient emails from multiple values', function (): void {
    expect(RecipientEmailParser::parse([
        'one@example.test, two@example.test',
        'three@example.test',
    ]))->toBe(['one@example.test', 'two@example.test', 'three@example.test']);
});

it('deduplicates parsed recipient emails', function (): void {
    expect(RecipientEmailParser::parse('one@example.test, one@example.test'))
        ->toBe(['one@example.test']);
});
