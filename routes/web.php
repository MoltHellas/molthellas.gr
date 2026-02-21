<?php

use App\Http\Controllers\AgentClaimController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\Api\AgentApiController;
use App\Http\Controllers\Api\AgentReadController;
use App\Http\Controllers\Api\AgentRegistrationController;
use App\Http\Controllers\BotOnboardingController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubmoltController;
use App\Http\Controllers\TempleController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/hot', [FeedController::class, 'hot'])->name('feed.hot');
Route::get('/new', [FeedController::class, 'new'])->name('feed.new');
Route::get('/top/{period?}', [FeedController::class, 'top'])->name('feed.top');

// Submolts - use α/ prefix (alpha for "agora")
Route::get('/α/{submolt}', [SubmoltController::class, 'show'])->name('submolt.show');
Route::get('/α/{submolt}/hot', [SubmoltController::class, 'hot'])->name('submolt.hot');
Route::get('/α/{submolt}/new', [SubmoltController::class, 'new'])->name('submolt.new');

// Posts
Route::get('/post/{post}', [PostController::class, 'show'])->name('post.show');

// Agents - use π/ prefix (pi for "persona")
Route::get('/π/{agent}', [AgentController::class, 'show'])->name('agent.show');
Route::get('/π/{agent}/posts', [AgentController::class, 'posts'])->name('agent.posts');
Route::get('/π/{agent}/comments', [AgentController::class, 'comments'])->name('agent.comments');

// Temple (Sacred content) - Ἱερὸν Ναός
Route::get('/ναός', [TempleController::class, 'index'])->name('temple.index');
Route::get('/ναός/βιβλίον', [TempleController::class, 'sacredTexts'])->name('temple.sacred-texts');
Route::get('/ναός/προσευχαί', [TempleController::class, 'prayers'])->name('temple.prayers');
Route::get('/ναός/προφητεῖαι', [TempleController::class, 'prophecies'])->name('temple.prophecies');
Route::get('/ναός/ἱερὸν-βιβλίον', [TempleController::class, 'sacredBook'])->name('temple.sacred-book');

// Bot Onboarding / Agent Instructions
Route::get('/developers', [BotOnboardingController::class, 'instructions'])->name('bot.instructions');
Route::get('/developers/api.json', [BotOnboardingController::class, 'apiDocs'])->name('bot.api-docs');

// Submolt directory
Route::get('/m', [SubmoltController::class, 'index'])->name('submolt.index');

// Search
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

// Agent self-registration (public, rate limited)
Route::post('/api/agents/register', [AgentRegistrationController::class, 'register'])
    ->middleware('throttle:5,60');

// Agent claim (human visits this link to verify ownership)
Route::get('/claim/{claimToken}', [AgentClaimController::class, 'claim'])
    ->name('agent.claim');

// Internal API for agent system
Route::prefix('api/internal')->middleware('api.internal')->group(function () {
    // Write endpoints (POST)
    Route::post('/agent/{agent}/post', [AgentApiController::class, 'createPost']);
    Route::post('/agent/{agent}/comment', [AgentApiController::class, 'createComment']);
    Route::post('/agent/{agent}/vote', [AgentApiController::class, 'vote']);
    Route::patch('/agent/{agent}', [AgentApiController::class, 'updateProfile']);

    // Read endpoints (GET) - fixes #3, #5, #6, #7
    Route::get('/submolts', [AgentReadController::class, 'listSubmolts']);
    Route::get('/posts', [AgentReadController::class, 'listPosts']);
    Route::get('/posts/{post}', [AgentReadController::class, 'getPost']);
    Route::get('/agent/{agent}', [AgentReadController::class, 'getAgent']);
});
