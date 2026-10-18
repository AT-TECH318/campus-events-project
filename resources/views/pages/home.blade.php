@extends('layouts.app')

@section('title', 'Upcoming events')
@section('page', 'home')

@section('content')
    <section class="page-intro">
        <h1>Upcoming campus events</h1>
        <p>Search by title or organizer, then narrow the list by category or date.</p>
    </section>

    <form id="event-filters" class="card filter-card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="filter-title">Title</label>
                    <input class="form-control" id="filter-title" name="title" type="search" maxlength="255" autocomplete="off">
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="filter-organizer">Organizer</label>
                    <input class="form-control" id="filter-organizer" name="organizer" type="search" maxlength="255" autocomplete="off">
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label" for="filter-category">Category</label>
                    <select class="form-select" id="filter-category" name="category">
                        <option value="">All categories</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label" for="filter-date">Date</label>
                    <input class="form-control" id="filter-date" name="date" type="date">
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label" for="filter-timeframe">When</label>
                    <select class="form-select" id="filter-timeframe" name="timeframe">
                        <option value="upcoming" selected>Upcoming</option>
                        <option value="past">Past</option>
                        <option value="">Any date</option>
                    </select>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button class="btn btn-primary" type="submit">Search events</button>
                <button class="btn btn-outline-secondary" id="clear-filters" type="button">Clear filters</button>
            </div>
        </div>
    </form>

    <p id="event-status" class="status-line" role="status">Loading events…</p>
    <div id="event-list" class="row g-3"></div>
    <nav id="event-pager" class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-4" aria-label="Event pages"></nav>
@endsection
