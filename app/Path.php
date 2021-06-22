<?php

namespace App;

class Path
{

    public static function dirname(string $path): string
    {
        $parts = explode('/', $path);
        $dirName = array_pop($parts);
        if ($dirName === '')
            $dirName = array_pop($parts);

        return $dirName;
    }

    public static function parent(string $path, int $amount = 1): string
    {
        $parts = explode('/', $path);
        $parent = implode('/', array_slice($parts, 0, $amount * -1));

        return $parent . '/';
    }

    public static function tree(string $path): array
    {
        $tree = [];
        $dirParts = array_slice(explode('/', $path), 0, -1);
        for ($i = 1; $i <= count($dirParts); $i++) {
            $tree[] = implode('/', array_slice($dirParts, 0, $i)) . '/';
        }

        return $tree;
    }

    public static function crumbs(string $path, string $bucketName): array
    {
        $crumbs = ['/' => 'Home'];

        if ($path === '/')
            return $crumbs;
        else
            $crumbs['/' . $bucketName] = $bucketName;

        $tree = static::tree($path);
        foreach ($tree as $_path) {
            $location = '/' . $bucketName . ($_path === '/' ? '' : '/' . $_path);
            $crumbs[$location] = static::dirname($_path);
        }

        return $crumbs;
    }

}
