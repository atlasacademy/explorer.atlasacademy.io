<?php

namespace App\Jobs;

use App\Models\Bucket;
use App\Models\BucketFile;
use App\Path;
use Aws\S3\S3Client;
use Illuminate\Support\Carbon;

class RefreshBucketJob extends Job
{

    private string $bucketName;

    public function __construct(string $bucketName)
    {
        $this->bucketName = $bucketName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /** @var Bucket|null $bucket */
        $bucket = Bucket::query()->where('name', '=', $this->bucketName)->first();
        if (!$bucket) {
            return;
        }

//        if ($bucket->updated_at->greaterThan(Carbon::now()->subMinutes(15))) {
//            return;
//        }

        $bucket->touch();

        $client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => "https://{$bucket->server}",
            'credentials' => [
                'key' => env('S3_KEY'),
                'secret' => env('S3_SECRET'),
            ],
        ]);

        $marker = null;
        $count = 0;
        $bucket->files()->update(['stale' => true]);
        $directories = [];

        do {
            $params = ['Bucket' => $bucket->name];
            if ($marker)
                $params['Marker'] = $marker;

            $objects = $client->listObjects($params);
            $count += count($objects['Contents']);

            $data = [];
            foreach ($objects['Contents'] as $object) {
                if (substr($object['Key'], -1) === '/')
                    continue;

                $tree = Path::tree($object['Key']);
                foreach ($tree as $path) {
                    $directories[$path] = 1;
                }

                $data[] = [
                    'bucket_id' => $bucket->id,
                    'key' => $object['Key'],
                    'parent' => Path::parent($object['Key']),
                    'size' => $object['Size'],
                    'modified_at' => $object['LastModified'],
                    'stale' => false,
                ];
            }

            BucketFile::query()->upsert($data, ['bucket_id', 'key']);

            if ($objects['IsTruncated'])
                $marker = $objects['NextMarker'];

            // sleep(1);
        } while ($objects['IsTruncated']);

        BucketFile::query()->upsert(
            array_map(function ($dir) use ($bucket) {
                return [
                    'bucket_id' => $bucket->id,
                    'key' => $dir,
                    'parent' => Path::parent($dir, 2),
                    'size' => 0,
                    'modified_at' => null,
                    'stale' => false,
                ];
            }, array_keys($directories)),
            ['bucket_id', 'key']
        );

        $bucket->files()->where('stale', '=', true)->delete();
    }

}
