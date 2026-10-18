@extends('layouts.app')

@section('title', 'Users')
@section('page', 'admin-users')

@section('content')
    <section class="page-intro">
        <h1>Users</h1>
        <p>Administrators can review accounts and change roles. The last administrator cannot be demoted.</p>
    </section>
    <p id="user-status" class="status-line" role="status">Loading users…</p>
    <div id="user-list" class="vstack gap-3"></div>
@endsection
