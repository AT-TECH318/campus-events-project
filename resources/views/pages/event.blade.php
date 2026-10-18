@extends('layouts.app')

@section('title', 'Event details')
@section('page', 'event')
@section('body-attributes')
    data-event-id="{{ $eventId }}"
@endsection

@section('content')
    <p><a href="{{ route('home') }}">Back to events</a></p>
    <article id="event-detail">
        <p id="event-status" class="status-line" role="status">Loading event…</p>
    </article>
@endsection
