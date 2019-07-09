<?php
use PHPUnit\Framework\TestCase;
use datagutten\image_host\imma_gr;

final class imma_grTest extends TestCase
{
    public function testUpload()
    {
        $host = new imma_gr();
        $upload = $host->upload(__DIR__.'/1024px-PHP-logo.svg.png');
        $this->assertStringContainsString('https://imma.gr/', $upload);
    }
}