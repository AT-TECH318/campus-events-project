@extends('layouts.app')

@section('title', isset($eventId) ? 'Edit event' : 'Create event')
@section('page', 'organizer-event-form')
@section('body-attributes')
    @isset($eventId) data-event-id="{{ $eventId }}" @endisset
@endsection

@section('content')
    <p><a href="{{ route('organizer.events') }}">Back to managed events</a></p>
    <section class="page-intro">
        <h1>{{ isset($eventId) ? 'Edit event' : 'Create event' }}</h1>
    </section>
    <p id="form-status" class="status-line" role="status">{{ isset($eventId) ? 'Loading event…' : 'Loading categories…' }}</p>
    <form id="event-form" class="card d-none" novalidate>
        <div class="card-body">
            <div id="form-errors" class="alert alert-danger d-none" role="alert"></div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="title">Title</label>
                    <input class="form-control" id="title" name="title" required maxlength="255">
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required maxlength="5000"></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="category_id">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required></select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="venue">Venue</label>
                    <input class="form-control" id="venue" name="venue" required maxlength="255">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="event_date">Event date</label>
                    <input class="form-control" id="event_date" name="event_date" type="date" required>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="start_time">Start time</label>
                    <input class="form-control" id="start_time" name="start_time" type="time" required>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="end_time">End time</label>
                    <input class="form-control" id="end_time" name="end_time" type="time" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="maximum_capacity">Maximum capacity</label>
                    <input class="form-control" id="maximum_capacity" name="maximum_capacity" type="number" min="1" step="1" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="registration_deadline">Registration deadline</label>
                    <input class="form-control" id="registration_deadline" name="registration_deadline" type="datetime-local" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="draft">Draft — hidden from the public list</option>
                        <option value="published">Published — open for registration</option>
                        <option value="cancelled">Cancelled — kept for the record</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="banner">Banner image</label>
                    <input class="form-control" id="banner" name="banner" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">Optional JPEG, PNG, WEBP, or GIF up to 2 MB.</div>
                    <img id="banner-preview" class="banner-preview d-none" alt="">
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <button class="btn btn-primary" id="save-event" type="submit">Save event</button>
                @isset($eventId)
                    <button class="btn btn-outline-danger" id="delete-event" type="button">Delete event</button>
                @endisset
            </div>
        </div>
    </form>
@endsection
