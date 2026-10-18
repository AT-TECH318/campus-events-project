@extends('layouts.app')

@section('title', 'Categories')
@section('page', 'admin-categories')

@section('content')
    <section class="page-intro">
        <h1>Categories</h1>
        <p>Administrators can add, rename, and remove categories that are not used by an event.</p>
    </section>
    <form id="category-form" class="card mb-4">
        <div class="card-body">
            <h2 class="h5" id="category-form-title">Add a category</h2>
            <div id="form-errors" class="alert alert-danger d-none" role="alert"></div>
            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="category-name">Name</label>
                    <input class="form-control" id="category-name" name="name" required maxlength="255">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="category-description">Description</label>
                    <input class="form-control" id="category-description" name="description" maxlength="255">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <button class="btn btn-outline-secondary d-none" id="cancel-category-edit" type="button">Cancel</button>
                </div>
            </div>
        </div>
    </form>
    <p id="category-status" class="status-line" role="status">Loading categories…</p>
    <div id="category-list" class="vstack gap-3"></div>
@endsection
