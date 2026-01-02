<?php

namespace datagutten\image_host\tests;

use datagutten\image_host\ImageHost;
use datagutten\image_host\sites;
use PHPUnit\Framework\TestCase;


class ImageHostTest extends TestCase
{
    public function testGetSites()
    {
        $sites = ImageHost::getSites();
        $this->assertArrayHasKey('cubeupload', $sites);
        $this->assertEquals(sites\cubeupload::class, $sites['cubeupload']);
    }

    public function testGetSite()
    {
        $site = ImageHost::getSite('cubeupload');
        $this->assertEquals(sites\cubeupload::class, $site);
    }
}
