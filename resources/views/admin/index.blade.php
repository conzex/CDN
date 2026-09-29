@extends('layouts.app')

@section('content')
<div class="flex flex-col h-screen">
    @include('admin.partials.header')
    @include('admin.partials.toolbar')
    <main class="flex-1 overflow-auto p-4" id="main-content">
        @include('admin.partials.grid')
        @include('admin.partials.list')
    </main>
    @include('admin.partials.modals')
</div>
@endsection
