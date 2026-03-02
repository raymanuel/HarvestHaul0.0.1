<x-layout>
<div class="login-container">
    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf

        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required>
        </div>

        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <button type="submit">Submit</button>
    </form>
</div>
</x-layout>
