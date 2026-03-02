<x-layout>
    <div style="text-align: center;"> {{-- This centers the H1 text --}}
        <h1>{{ Auth::user()->name }}, welcome to your private dashboard.</h1>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <div class="logout-button-container">
                <button type="submit">Logout</button>
            </div>
        </form>
    </div>
</x-layout>
