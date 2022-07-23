<?php

namespace App\Jobs;

use App\Models\Bucket;
use App\Models\BucketFile;
use App\Path;
use App\Vendor\B2Client;
use Aws\S3\S3Client;
use BackblazeB2\File;

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
        $bucket->files()->update(['stale' => true]);
        $directories = [];

//        $files = $this->fetchViaAws($bucket);
        $files = $this->fetchViaB2($bucket);

        foreach ($files as $data) {
            $tree = Path::tree($data['key']);
            foreach ($tree as $path) {
                $directories[$path] = 1;
            }

            BucketFile::query()->upsert($data, ['bucket_id', 'key']);
        }

        $data = array_map(function ($dir) use ($bucket) {
            return [
                'bucket_id' => $bucket->id,
                'key' => $dir,
                'parent' => Path::parent($dir, 2),
                'size' => 0,
                'modified_at' => null,
                'stale' => false,
            ];
        }, array_keys($directories));

        foreach (array_chunk($data, 1000) as $chunk) {
            BucketFile::query()->upsert($chunk, ['bucket_id', 'key']);
        }

        $bucket->files()->where('stale', '=', true)->delete();
    }

    private function fetchViaAws(Bucket $bucket)
    {
        $client = new S3Client([
            'version' => 'latest',
            'region' => 'us-west-2',
            'endpoint' => "https://{$bucket->server}",
            'credentials' => [
                'key' => env('S3_KEY'),
                'secret' => env('S3_SECRET'),
            ],
        ]);

        $marker = null;

        do {
            $params = [
                'Bucket' => $bucket->name,
            ];
            if ($marker)
                $params['Marker'] = $marker;

            $objects = $client->listObjects($params);

            $data = [];
            foreach ($objects['Contents'] as $object) {
                if (substr($object['Key'], -1) === '/')
                    continue;

                $data[] = [
                    'bucket_id' => $bucket->id,
                    'key' => $object['Key'],
                    'parent' => Path::parent($object['Key']),
                    'size' => $object['Size'],
                    'modified_at' => $object['LastModified'],
                    'stale' => false,
                ];
            }

            if ($objects['IsTruncated'])
                $marker = $objects['NextMarker'];

            // sleep(1);
        } while ($objects['IsTruncated']);

        return $data;
    }

    private function fetchViaB2(Bucket $bucket)
    {
        if (!$bucket->b2_id)
            throw new \Exception('b2_id is not set');

        $client = new B2Client(
            env('S3_KEY'),
            env('S3_SECRET'),
        );

        $objects = $client->customListFiles(['BucketId' => $bucket->b2_id]);

        $data = [];
        foreach ($objects as $object) {
            /** @var File $object */
            $key = $object->getName();
            if (substr($key, -1) === '/')
                continue;

            $modifiedAt = null;
            if ($object->getUploadTimestamp())
                $modifiedAt = date('Y-m-d H:i:s', floor($object->getUploadTimestamp() / 1000));

            $data[] = [
                'bucket_id' => $bucket->id,
                'key' => $key,
                'parent' => Path::parent($key),
                'size' => $object->getSize(),
                'modified_at' => $modifiedAt ?? date('Y-m-d H:i:s'),
                'stale' => false,
            ];
        }

        return $data;
    }

}
