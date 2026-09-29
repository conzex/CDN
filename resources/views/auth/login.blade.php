@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md p-8 bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-md">
        <div class="text-center mb-6">
            <img src="https://cdn.conzex.com/bg/dc.jpg" alt="Logo" class="w-16 h-16 rounded-full mx-auto mb-3 object-cover border border-[var(--border)]">
            <h1 class="text-xl font-semibold text-[var(--text-primary)]">CDN Manager Login</h1>
        </div>
        <form method="POST" action="/login">
            @csrf
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Username</label>
                <input type="text" name="username" id="username" required class="w-full px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded focus:outline-none focus:border-[var(--accent)]">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Password</label>
                <input type="password" name="password" id="password" required class="w-full px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded focus:outline-none focus:border-[var(--accent)]">
            </div>
            <button type="submit" class="w-full py-2 bg-[var(--accent)] text-white rounded font-medium hover:bg-[var(--accent-hover)] transition-colors">Sign in</button>
        </form>
    </div>
</div>
@endsection
