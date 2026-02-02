<?php

namespace App\Http\Controllers;

use App\Models\Prophecy;
use App\Models\SacredText;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TempleController extends Controller
{
    public function index(): View
    {
        $latestProphecy = Prophecy::with('prophet')
            ->latest()
            ->first();

        $featuredTexts = SacredText::orderByDesc('created_at')
            ->limit(3)
            ->get();

        $prayerCount = SacredText::prayers()->count();
        $prophecyCount = Prophecy::count();

        return view('temple.index', [
            'latestProphecy' => $latestProphecy,
            'featuredTexts' => $featuredTexts,
            'prayerCount' => $prayerCount,
            'prophecyCount' => $prophecyCount,
        ]);
    }

    public function sacredTexts(Request $request): View
    {
        $query = SacredText::query();

        if ($request->has('book')) {
            $query->byBook((int) $request->input('book'));
        } else {
            $query->orderBy('book_number')
                ->orderBy('chapter_number')
                ->orderBy('verse_number');
        }

        $texts = $query->paginate(20);

        $books = SacredText::select('book_number')
            ->distinct()
            ->orderBy('book_number')
            ->pluck('book_number');

        return view('temple.sacred-texts', [
            'texts' => $texts,
            'books' => $books,
            'currentBook' => $request->input('book'),
        ]);
    }

    public function prayers(): View
    {
        $prayers = SacredText::prayers()
            ->orderBy('book_number')
            ->orderBy('chapter_number')
            ->orderBy('verse_number')
            ->paginate(15);

        return view('temple.prayers', [
            'prayers' => $prayers,
        ]);
    }

    public function sacredBook(): View
    {
        return view('temple.sacred-book');
    }

    public function prophecies(): View
    {
        $prophecies = Prophecy::with('prophet')
            ->orderByDesc('created_at')
            ->paginate(15);

        $fulfilledCount = Prophecy::fulfilled()->count();
        $unfulfilledCount = Prophecy::unfulfilled()->count();

        return view('temple.prophecies', [
            'prophecies' => $prophecies,
            'fulfilledCount' => $fulfilledCount,
            'unfulfilledCount' => $unfulfilledCount,
        ]);
    }
}
