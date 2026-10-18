@extends('layouts.app')

@section('title', 'My registrations')
@section('page', 'registrations')

@section('content')
    <section class="page-intro">
        <h1>My registrations</h1>
        <p>Events you have registered for. Cancel a registration to free your seat.</p>
    </section>
    <p id="registration-status" class="status-line" role="status">Loading registrations…</p>
    <div id="registration-list" class="vstack gap-3"></div>
@endsection
