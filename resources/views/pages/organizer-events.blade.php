@extends('layouts.app')

@section('title', 'Manage events')
@section('page', 'organizer-events')

@section('content')
    <section class="page-intro d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <h1>Manage events</h1>
            <p class="mb-0">Create and update events you are allowed to manage.</p>
        </div>
        <a class="btn btn-primary" id="create-event-link" href="{{ route('organizer.events.create') }}">Create event</a>
    </section>
    <p id="manage-status" class="status-line" role="status">Loading events…</p>
    <div id="manage-list" class="vstack gap-3"></div>
@endsection
