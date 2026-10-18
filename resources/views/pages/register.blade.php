@extends('layouts.app')

@section('title', 'Register')
@section('page', 'register')

@section('content')
    <section class="auth-panel">
        <h1>Create an account</h1>
        <p>New accounts are students. An administrator can change a role later.</p>
        <form id="register-form" class="card" novalidate>
            <div class="card-body">
                <div id="form-errors" class="alert alert-danger d-none" role="alert"></div>
                <div class="mb-3">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-control" id="name" name="name" type="text" autocomplete="name" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" autocomplete="username" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" required minlength="8">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8">
                </div>
                <button class="btn btn-primary" type="submit">Create account</button>
                <p class="mt-3 mb-0">Already registered? <a href="{{ route('login') }}">Log in</a></p>
            </div>
        </form>
    </section>
@endsection
