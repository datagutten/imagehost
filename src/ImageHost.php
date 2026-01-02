<?php

namespace datagutten\image_host;

use FilesystemIterator;
use SplFileInfo;
use datagutten\image_host\sites\exceptions;

class ImageHost
{
    /**
     * @return sites\image_host[]
     */
    public static function getSites(): array
    {
        $iterator = new FilesystemIterator(__DIR__ . '/../sites', FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
        $sites = [];

        /**
         * @var $fileInfo SplFileInfo
         */
        foreach ($iterator as $fileInfo)
        {
            if ($fileInfo->getExtension() != 'php')
                continue;
            if ($fileInfo->isDir())
                continue;
            if ($fileInfo->getBasename() == 'image_host.php')
                continue;

            $class = 'datagutten\\image_host\\sites\\' . $fileInfo->getBasename('.php');
            $slug = basename($class);
            $sites[$slug] = $class;
        }

        return $sites;
    }

    /**
     * @param string $string Site slug
     * @return string Site class path
     * @throws exceptions\SiteNotFoundException Site not found (invalid site slug)
     */
    public static function getSite(string $string): string
    {
        $class = 'datagutten\\image_host\\sites\\' . $string;
        if (class_exists($class))
            return $class;

        throw new exceptions\SiteNotFoundException($string);
    }
}