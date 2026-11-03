<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\EntityConfiguration;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

abstract class DiscoverEntities
{
    /** @var class-string<EntityConfiguration> The configuration base class a discovered class must extend. */
    protected static string $configuration;

    /**
     * Finds every concrete configuration under a PSR-4 folder.
     *
     * @param string $path The folder the namespace maps to.
     * @param string $namespace The namespace the folder's classes live in.
     * @return list<class-string<EntityConfiguration>>
     */
    protected static function _within(string $path, string $namespace): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $root = rtrim(realpath($path) ?: $path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        $found = [];

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $class = rtrim($namespace, '\\').'\\'.str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                substr($file->getRealPath() ?: $file->getPathname(), strlen($root)),
            );

            if (! class_exists($class) || ! is_subclass_of($class, static::$configuration)) {
                continue;
            }

            if (new ReflectionClass($class)->isAbstract()) {
                continue;
            }

            $found[] = $class;
        }

        return $found;
    }
}
