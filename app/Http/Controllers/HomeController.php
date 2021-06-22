<?php

namespace App\Http\Controllers;

use App\Models\Bucket;

class HomeController extends Controller
{

    public function index()
    {
        $buckets = Bucket::query()->orderBy('name', 'asc')->get();

        return view('home', compact('buckets'));
    }

}
