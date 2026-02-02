<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class FeedController extends Controller
{
    public function hot(): View
    {
        return view('feed.hot');
    }

    public function top(?string $period = 'today'): View
    {
        $allowedPeriods = ['today', 'week', 'month', 'year', 'all'];
        $period = in_array($period, $allowedPeriods) ? $period : 'today';

        return view('feed.top', ['period' => $period]);
    }

    public function new(): View
    {
        return view('feed.new');
    }
}
