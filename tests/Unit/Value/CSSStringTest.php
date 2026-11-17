<?php

declare(strict_types=1);

namespace Sabberworm\CSS\Tests\Unit\Value;

use PHPUnit\Framework\TestCase;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Sabberworm\CSS\Settings;
use Sabberworm\CSS\Value\CSSString;
use Sabberworm\CSS\Value\PrimitiveValue;
use Sabberworm\CSS\Value\Value;

/**
 * @covers \Sabberworm\CSS\Value\CSSString
 * @covers \Sabberworm\CSS\Value\PrimitiveValue
 * @covers \Sabberworm\CSS\Value\Value
 */
final class CSSStringTest extends TestCase
{
    /**
     * @test
     */
    public function isPrimitiveValue(): void
    {
        $subject = new CSSString('');

        self::assertInstanceOf(PrimitiveValue::class, $subject);
    }

    /**
     * @test
     */
    public function isValue(): void
    {
        $subject = new CSSString('');

        self::assertInstanceOf(Value::class, $subject);
    }

    /**
     * @test
     */
    public function getStringReturnsStringProvidedToConstructor(): void
    {
        $string = 'coffee';
        $subject = new CSSString($string);

        self::assertSame($string, $subject->getString());
    }

    /**
     * @test
     */
    public function setStringSetsString(): void
    {
        $subject = new CSSString('');
        $string = 'coffee';

        $subject->setString($string);

        self::assertSame($string, $subject->getString());
    }

    /**
     * @test
     */
    public function getLineNumberByDefaultReturnsNull(): void
    {
        $subject = new CSSString('');

        self::assertNull($subject->getLineNumber());
    }

    /**
     * @test
     */
    public function getLineNumberReturnsLineNumberProvidedToConstructor(): void
    {
        $lineNumber = 42;
        $subject = new CSSString('', $lineNumber);

        self::assertSame($lineNumber, $subject->getLineNumber());
    }

    /**
     * @test
     */
    public function getArrayRepresentationIncludesClassName(): void
    {
        $subject = new CSSString('');

        $result = $subject->getArrayRepresentation();

        self::assertSame('CSSString', $result['class']);
    }

    /**
     * @test
     */
    public function getArrayRepresentationIncludesContents(): void
    {
        $contents = 'What is love?';
        $subject = new CSSString($contents);

        $result = $subject->getArrayRepresentation();

        self::assertSame($contents, $result['contents']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unterminatedStringBreakProvider(): array
    {
        return [
            'line feed' => ["\n"],
            'carriage return' => ["\r"],
            'form feed' => ["\f"],
        ];
    }

    /**
     * @test
     *
     * @dataProvider unterminatedStringBreakProvider
     */
    public function lenientParsingDropsOnlyDeclarationWithUnterminatedString(string $lineBreak): void
    {
        $css = "a{content:\"abc{$lineBreak}} b{color:blue} c{color:green}";

        $result = (new Parser($css))->parse()->render();

        self::assertSame("a {}\nb {color: blue;}\nc {color: green;}", $result);
    }

    /**
     * @test
     */
    public function lenientParsingKeepsLaterRulesAfterUnterminatedStringInMediaRule(): void
    {
        $css = "@media screen{a{content:\"abc\n} b{color:blue}} c{color:green}";

        $result = (new Parser($css))->parse()->render();

        self::assertStringContainsString('b {color: blue;}', $result);
        self::assertStringContainsString('c {color: green;}', $result);
    }

    /**
     * @test
     */
    public function strictParsingThrowsForUnterminatedString(): void
    {
        $this->expectException(UnexpectedTokenException::class);

        (new Parser("a{content:\"abc\n}", Settings::create()->beStrict()))->parse();
    }

    /**
     * @test
     */
    public function parsingAllowsEscapedNewlineInQuotedString(): void
    {
        $result = (new Parser("a{content:\"x\\\ny\";color:red}", Settings::create()->beStrict()))
            ->parse()->render();

        self::assertStringContainsString('color: red;', $result);
    }
}
