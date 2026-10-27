<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CareersController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\LeadFormController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{division}', [BrandController::class, 'show'])->name('brands.show');
Route::get('/catalogue', [CatalogueController::class, 'index'])->name('catalogue');

Route::get('/order', [OrderController::class, 'show'])->name('order.show');
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/order/list', [OrderController::class, 'add'])->name('order.add');
    Route::post('/order/list/update', [OrderController::class, 'update'])->name('order.update');
    Route::post('/order/list/remove', [OrderController::class, 'remove'])->name('order.remove');
});

Route::get('/trade-account', [LeadFormController::class, 'account'])->name('account.create');
Route::get('/principals', [LeadFormController::class, 'principal'])->name('principals');
Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
Route::get('/careers/apply', [LeadFormController::class, 'application'])->name('careers.apply');
Route::get('/careers/{vacancy}', [CareersController::class, 'show'])->name('careers.show');

Route::middleware('throttle:forms')->group(function () {
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
    Route::post('/trade-account', [LeadFormController::class, 'storeAccount'])->name('account.store');
    Route::post('/principals', [LeadFormController::class, 'storePrincipal'])->name('principals.store');
    Route::post('/careers/apply', [LeadFormController::class, 'storeApplication'])->name('careers.store');
    Route::post('/contact', [LeadFormController::class, 'storeEnquiry'])->name('contact.store');
});
Route::get('/thanks', [LeadFormController::class, 'thanks'])->name('thanks');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/photo-credits', [PageController::class, 'credits'])->name('credits');
Route::get('/sitemap', [PageController::class, 'sitemapPage'])->name('sitemap.page');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/leads', [Admin\LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/export', [Admin\LeadController::class, 'export'])->name('leads.export');
    Route::get('/leads/{lead}', [Admin\LeadController::class, 'show'])->name('leads.show');
    Route::patch('/leads/{lead}', [Admin\LeadController::class, 'update'])->name('leads.update');
    Route::post('/leads/{lead}/notes', [Admin\LeadController::class, 'note'])->name('leads.note');
    Route::get('/leads/{lead}/files/{file}', [Admin\LeadController::class, 'file'])->name('leads.file');

    Route::middleware('role:admin,credit')->group(function () {
        Route::get('/credit', [Admin\CreditController::class, 'index'])->name('credit.index');
        Route::post('/credit/{lead}/approve', [Admin\CreditController::class, 'approve'])->name('credit.approve');
        Route::post('/accounts', [Admin\TradeAccountController::class, 'store'])->name('accounts.store');
        Route::patch('/accounts/{account}', [Admin\TradeAccountController::class, 'update'])->name('accounts.update');
    });
    Route::middleware('role:admin,sales,credit')->get('/accounts', [Admin\TradeAccountController::class, 'index'])->name('accounts.index');

    Route::middleware('role:admin,sales')->group(function () {
        Route::resource('products', Admin\ProductController::class)->except('show');
    });

    Route::middleware('role:admin,hr')->group(function () {
        Route::resource('vacancies', Admin\VacancyController::class)->except('show');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    });
});
