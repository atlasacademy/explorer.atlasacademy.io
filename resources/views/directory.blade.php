@extends('_template')

@section('content')
    <nav>
        <ol class="breadcrumb">
            @foreach ($crumbs as $path => $name)
                <li class="breadcrumb-item">
                    <a href="{{ $path }}">{{ $name }}</a>
                </li>
            @endforeach
        </ol>
    </nav>
    <table class="table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Updated</th>
        </tr>
        </thead>
        <tbody>
        <tr class="table-warning">
            <td>
                <a href="{{ $parent }}">..</a>
            </td>
            <td></td>
            <td></td>
        </tr>
        @foreach ($files as $file)
            <tr class="{{ $file->isDirectory() ? "table-warning": "" }}">
                <td>
                    <a href="{{ $file->location($bucket) }}" {{ $file->isDirectory() ? '' : 'target="_blank"' }}>
                        {{ $file->filename() }}
                    </a>
                </td>
                <td>{{ $file->isDirectory() ? '' : $file->sizeForHumans() }}</td>
                <td>
                    {{ $file->modified_at ? $file->modified_at->diffForHumans() : null }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

