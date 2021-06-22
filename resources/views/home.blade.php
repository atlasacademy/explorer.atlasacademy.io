@extends('_template')

@section('content')
    <table class="table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Updated</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($buckets as $bucket)
            <tr class="table-warning">
                <td>
                    <a href="/{{ $bucket->name }}/">
                        {{ $bucket->name }}
                    </a>
                </td>
                <td>
                    {{ $bucket->updated_at->diffForHumans() }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

