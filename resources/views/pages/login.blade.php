@extends('layouts.app')

@section('title', 'Log in')
@section('page', 'login')

@section('content')
    <section class="auth-panel">
        <h1>Log in</h1>
        <p>Use the email and password for your campus account.</p>
        <form id="login-form" class="card" novalidate>
            <div class="card-body">
                <div id="form-errors" class="alert alert-danger d-none" role="alert"></div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" autocomplete="username" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-primary" type="submit">Log in</button>
                <p class="mt-3 mb-0">Need an account? <a href="{{ route('register') }}">Register</a></p>
            </div>
        </form>
    </section>
@endsection
