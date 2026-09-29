@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[var(--bg-app)] flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-md bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-xl p-8">
        <div class="text-center mb-6">
            <img src="https://cdn.conzex.com/bg/dc.jpg" alt="Logo" class="w-16 h-16 rounded-full mx-auto mb-3 object-cover border border-[var(--border)] shadow">
            <h2 class="text-xl font-bold text-[var(--text-primary)]">Protected Share Link</h2>
            <p class="text-xs text-[var(--text-secondary)] mt-1">Please enter the password to access this shared file.</p>
        </div>

        <form method="POST" action="/s/{{ $share->token }}/verify">
            @csrf
            <div class="mb-4">
                <label for="password" class="block text-xs font-medium text-[var(--text-secondary)] mb-1">Password</label>
                <input type="password" name="password" id="password" required autofocus class="w-full px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded focus:outline-none focus:border-[var(--accent)] text-sm">
                @error('password')
                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full py-2 bg-[var(--accent)] text-white text-sm font-semibold rounded hover:bg-[var(--accent-hover)] transition-colors shadow">
                Unlock & View
            </button>
        </form>
    </div>
</div>
@endsection
