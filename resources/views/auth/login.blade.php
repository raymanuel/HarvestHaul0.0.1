<x-guest-layout>

    <style>
        .brand-title { color: #2D8A37; font-size: 1.8rem; font-weight: 700; margin-bottom: 2rem; }
    </style>

    <a href="/" class="brand-title no-underline inline-block mb-8">
        HarvestHaul
        <br><br>
    </a>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required autofocus>
        </div>

        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <button type="submit">Submit</button>
    </form>
</x-guest-layout>
