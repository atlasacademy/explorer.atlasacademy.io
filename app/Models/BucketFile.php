<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BucketFile extends Model
{

    protected $table = 'bucket_files';
    protected $dates = ['modified_at'];
    public $timestamps = false;

    public function filename(): string
    {
        $parts = explode('/', $this->key);

        return implode('/', array_slice($parts, $this->isDirectory() ? -2 : -1));
    }

    public function isDirectory(): bool
    {
        return substr($this->key, -1) === '/';
    }

    public function location(Bucket $bucket): string
    {
        if ($this->isDirectory())
            return "/{$bucket->name}/{$this->key}";

        return "https://{$bucket->name}.{$bucket->server}/{$this->key}";
    }

    public function sizeForHumans(): string
    {
        $size = $this->size;
        $precision = 2;

        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {}
        return round($size, $precision) . ' ' . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
    }

}