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

        return view('directory', compact('bucket', 'files', 'parent', 'crumbs', 'path'));
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
            ->get();
        if (!$files->count())
            abort(404);

        $directories = $files
            ->filter(fn ($file) => preg_match('/\/$/', $file->key))
            ->sortBy('key', SORT_NATURAL)
            ->values();
        $remaining = $files
            ->filter(fn ($file) => !preg_match('/\/$/', $file->key))
            ->sortBy('key', SORT_NATURAL)
            ->values();

        $files = $directories->merge($remaining);

        return view('directory', compact('bucket', 'files', 'parent', 'crumbs', 'path'));
    }

}
