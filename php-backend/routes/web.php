<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'index'])->name('index');
Route::get('/team', [PublicController::class, 'team'])->name('team');
Route::get('/projects', [PublicController::class, 'projects'])->name('projects');
Route::get('/ceo-message', [PublicController::class, 'ceoMessage'])->name('ceo.message');

Route::get('/login', [PublicController::class, 'loginForm'])->name('login');
Route::post('/login', [PublicController::class, 'login'])->name('login.submit');
Route::get('/logout', [PublicController::class, 'logout'])->name('logout');
Route::get('/uploads/{filename}', [ApiController::class, 'uploadedFile'])
    ->where('filename', '.*')
    ->name('uploads.show');

Route::match(['get', 'post'], '/editor', [AdminController::class, 'editor'])->middleware('admin')->name('editor');
Route::match(['get', 'post'], '/editor/team', [AdminController::class, 'editorTeam'])->middleware('admin')->name('editor.team');
Route::match(['get', 'post'], '/editor/projects', [AdminController::class, 'editorProjects'])->middleware('admin')->name('editor.projects');
Route::match(['get', 'post'], '/editor/ceo-message', [AdminController::class, 'editorCeoMessage'])->middleware('admin')->name('editor.ceo');
Route::get('/editor/contacts', [AdminController::class, 'editorContacts'])->middleware('admin')->name('editor.contacts');
Route::match(['get', 'post'], '/users', [AdminController::class, 'users'])->middleware('admin')->name('users');

Route::post('/api/contact', [ApiController::class, 'contact'])->name('api.contact');
Route::post('/api/upload-image', [ApiController::class, 'uploadImage'])->middleware('admin')->name('api.upload-image');
Route::post('/api/upload-file', [ApiController::class, 'uploadFile'])->middleware('admin')->name('api.upload-file');
Route::match(['get', 'post'], '/api/items', [ApiController::class, 'items'])->name('api.items');
