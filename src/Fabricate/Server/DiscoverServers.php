<?php

declare(strict_types=1);

namespace Fabricate\Server;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

class DiscoverServers
{
    /** @return list<class-string<MCPServer>> */
    public static function within(string $path, string $namespace): array
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

            if (! class_exists($class) || ! is_subclass_of($class, MCPServer::class)) {
                continue;
            }

            if ((new ReflectionClass($class))->isAbstract()) {
                continue;
            }

            $found[] = $class;
        }

        return $found;
    }
}
