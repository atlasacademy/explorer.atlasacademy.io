<?php

namespace App\Http\Controllers;

use App\Jobs\RefreshBucketJob;
use App\Models\Bucket;
use Illuminate\Http\Request;

class RefreshController extends Controller
{

    public function trigger(Request $request)
    {
        $bucketName = $request->get('bucket');
        $bucket = Bucket::query()->where('name', '=', $bucketName)->first();

        if (!$bucket
            || !env('REFRESH_KEY')
            || $request->get('key') !== env('REFRESH_KEY'))
            abort(401);

        $this->dispatch(new RefreshBucketJob($bucket->name));

        return 'Refreshing ...';
    }

}
