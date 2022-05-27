<?php

namespace App\Http\Controllers;

use App\Models\Bucket;
use App\Path;

class DirectoryController extends Controller
{

    public function root(string $bucketName)
    {
        /** @var Bucket|null $bucket */
        $bucket = Bucket::query()->where('name', '=', $bucketName)->first();
        if (!$bucket)
            abort(404);

        $path = '/';
        $parent = '/';
        $crumbs = Path::crumbs($path, $bucket->name);

        $files = $bucket->files()
            ->where('parent', '=', $path)
            ->orderBy('key', 'ASC')
            ->get();

        return view('directory', compact('bucket', 'files', 'parent', 'crumbs'));
    }

    public function display(string $bucketName, string $path)
    {
        /** @var Bucket|null $bucket */
        $bucket = Bucket::query()->where('name', '=', $bucketName)->first();
        if (!$bucket)
            abort(404);

        $parent = Path::parent($path);
        $parent = '/' . $bucket->name . ($parent === '/' ? '' : '/' . $parent);

        $crumbs = Path::crumbs($path, $bucket->name);

        $files = $bucket->files()
            ->where('parent', '=', $path . '/')
            ->orderByRaw('"key" not like \'%/\', length(`key`)')
            ->orderBy('key', 'ASC')
            ->get();
        if (!$files->count())
            abort(404);

        return view('directory', compact('bucket', 'files', 'parent', 'crumbs'));
    }

}
