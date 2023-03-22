<?php

namespace Utils;

use Framelix\Framelix\Utils\PhpDocParser;
use PHPUnit\Framework\TestCase;

use function json_encode;

final class PhpDocParserTest extends TestCase
{

    public function tests(): void
    {
        $doc = "/**
     * Parse
     *   Multiline
     * @param string \$phpDocCommentNoComment
     * @param string \$phpDocComment Comment
     *  foo
     * @param \$notype
     *  foo
     * @param \$notype2 Comment2
     * @param \$notypeAndComment
     * @justaflag
     * @return array ['description' => 'string', '@xxx' => ['xxx', 'xxx', ...]]
     */";
        $this->assertSame(
            '{"description":["Parse","  Multiline"],"annotations":[{"type":"param","value":["string $phpDocCommentNoComment"]},{"type":"param","value":["string $phpDocComment Comment"," foo"]},{"type":"param","value":["$notype"," foo"]},{"type":"param","value":["$notype2 Comment2"]},{"type":"param","value":["$notypeAndComment"]},{"type":"","value":["@justaflag"]},{"type":"return","value":["array [\'description\' => \'string\', \'@xxx\' => [\'xxx\', \'xxx\', ...]]"]}]}',
            json_encode(PhpDocParser::parse($doc))
        );
        $this->assertSame(
            '{"phpDocCommentNoComment":{"name":"phpDocCommentNoComment","type":"string","description":[]},"phpDocComment":{"name":"phpDocComment","type":"string","description":[" Comment"," foo"]},"notype":{"name":"notype","type":null,"description":[" foo"]},"notype2":{"name":"notype2","type":null,"description":[" Comment2"]},"notypeAndComment":{"name":"notypeAndComment","type":null,"description":[]}}',
            json_encode(PhpDocParser::parseVariableDescriptions($doc))
        );
    }
}
