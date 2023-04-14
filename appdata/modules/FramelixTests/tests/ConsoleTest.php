<?php

use Framelix\Framelix\Utils\Shell;
use Framelix\FramelixTests\TestCase;

final class ConsoleTest extends TestCase
{
    public function testShell(): void
    {
        $shell = Shell::prepare('framelix_console ' . FRAMELIX_MODULE . ' appWarmup');
        $shell->execute();
        $this->assertSame(0, $shell->status, $shell->getOutputText());
        $this->assertStringContainsString('[S]', $shell->getOutputText());
        $this->assertStringContainsString('appWarmup', $shell->getOutputText());
    }
}
