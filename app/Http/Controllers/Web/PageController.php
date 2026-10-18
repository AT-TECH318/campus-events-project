<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function event(string $event): View
    {
        return view('pages.event', ['eventId' => $event]);
    }

    public function login(): View
    {
        return view('pages.login');
    }

    public function register(): View
    {
        return view('pages.register');
    }

    public function registrations(): View
    {
        return view('pages.registrations');
    }

    public function organizerEvents(): View
    {
        return view('pages.organizer-events');
    }

    public function createEvent(): View
    {
        return view('pages.organizer-event-form');
    }

    public function editEvent(string $event): View
    {
        return view('pages.organizer-event-form', ['eventId' => $event]);
    }

    public function adminCategories(): View
    {
        return view('pages.admin-categories');
    }

    public function adminUsers(): View
    {
        return view('pages.admin-users');
    }
}
