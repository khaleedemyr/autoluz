<?php

use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\NewsletterSubscriberController as AdminNewsletterSubscriberController;
use App\Http\Controllers\Admin\SeoController as AdminSeoController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VideoController as AdminVideoController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleShareController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CommunityFollowController;
use App\Http\Controllers\CommunityGroupController;
use App\Http\Controllers\CommunityLiveChatController;
use App\Http\Controllers\CommunityMessageController;
use App\Http\Controllers\CommunityNotificationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\CreditSimulationController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleCompareController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');
Route::get('/berita', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/berita/{slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::post('/berita/{slug}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('articles.comments.store');
Route::post('/berita/{slug}/share', [ArticleShareController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('articles.share');
Route::get('/kategori/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/cari', [SearchController::class, 'index'])->name('search');
Route::get('/event', [EventController::class, 'index'])->name('events.index');
Route::get('/event/{slug}', [EventController::class, 'show'])->name('events.show');
Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('newsletter.store');
Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('push.subscribe');
Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])
    ->middleware('throttle:30,1')
    ->name('push.unsubscribe');
Route::get('/merek', [BrandController::class, 'index'])->name('brands.index');
Route::get('/merek/ikuti', [BrandController::class, 'feed'])->name('brands.following');
Route::get('/merek/{slug}/kendaraan/{vehicle}', [VehicleController::class, 'show'])->name('brands.vehicles.show');
Route::get('/merek/{slug}', [BrandController::class, 'show'])->name('brands.show');
Route::get('/bandingkan', [VehicleCompareController::class, 'index'])->name('vehicles.compare');
Route::get('/bandingkan/cari', [VehicleCompareController::class, 'search'])
    ->middleware('throttle:60,1')
    ->name('vehicles.compare.search');
Route::get('/simulasi-kredit', [CreditSimulationController::class, 'index'])->name('credit.simulate');
Route::get('/galeri', [GalleryController::class, 'index'])->name('galleries.index');
Route::get('/galeri/{slug}', [GalleryController::class, 'show'])->name('galleries.show');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/komunitas', [CommunityController::class, 'index'])->name('community.index');
Route::get('/komunitas/p/{post}', [CommunityController::class, 'show'])->name('community.show');
Route::get('/komunitas/grup', [CommunityGroupController::class, 'index'])->name('community.groups.index');
Route::get('/komunitas/g/{group:slug}', [CommunityGroupController::class, 'show'])->name('community.groups.show');

Route::middleware('auth')->group(function () {
    Route::get('/komunitas/grup/buat', [CommunityGroupController::class, 'create'])->name('community.groups.create');
    Route::post('/komunitas/grup', [CommunityGroupController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('community.groups.store');
    Route::post('/komunitas/g/{group:slug}/gabung', [CommunityGroupController::class, 'join'])
        ->middleware('throttle:30,1')
        ->name('community.groups.join');
    Route::get('/komunitas/g/{group:slug}/cari-anggota', [CommunityGroupController::class, 'searchUsers'])
        ->middleware('throttle:60,1')
        ->name('community.groups.search-users');
    Route::post('/komunitas/g/{group:slug}/anggota', [CommunityGroupController::class, 'addMember'])
        ->middleware('throttle:30,1')
        ->name('community.groups.add-member');
    Route::get('/komunitas/g/{group:slug}/pengaturan', [CommunityGroupController::class, 'edit'])
        ->name('community.groups.edit');
    Route::post('/komunitas/g/{group:slug}/pengaturan', [CommunityGroupController::class, 'update'])
        ->middleware('throttle:20,1')
        ->name('community.groups.update');

    Route::get('/komunitas/pengaturan', [ProfileController::class, 'edit'])->name('community.settings');
    Route::get('/komunitas/notifikasi', [CommunityNotificationController::class, 'index'])->name('community.notifications');
    Route::post('/komunitas/notifikasi/baca-semua', [CommunityNotificationController::class, 'markAllRead'])
        ->middleware('throttle:30,1')
        ->name('community.notifications.read-all');
    Route::post('/komunitas/notifikasi/{notification}/baca', [CommunityNotificationController::class, 'markRead'])
        ->middleware('throttle:60,1')
        ->name('community.notifications.read');

    Route::get('/komunitas/pesan', [CommunityMessageController::class, 'index'])->name('community.messages.index');
    Route::get('/komunitas/pesan/u/{username}', [CommunityMessageController::class, 'start'])
        ->where('username', '[A-Za-z0-9_]+')
        ->name('community.messages.start');
    Route::get('/komunitas/pesan/{conversation}', [CommunityMessageController::class, 'show'])->name('community.messages.show');
    Route::get('/komunitas/pesan/{conversation}/poll', [CommunityMessageController::class, 'poll'])
        ->middleware('throttle:120,1')
        ->name('community.messages.poll');
    Route::post('/komunitas/pesan/{conversation}', [CommunityMessageController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('community.messages.store');

    Route::get('/komunitas/live-chat', [CommunityLiveChatController::class, 'index'])->name('community.live-chat');
    Route::get('/komunitas/live-chat/teman', [CommunityLiveChatController::class, 'friends'])
        ->middleware('throttle:60,1')
        ->name('community.live-chat.friends');
    Route::post('/komunitas/live-chat/heartbeat', [CommunityLiveChatController::class, 'heartbeat'])
        ->middleware('throttle:60,1')
        ->name('community.live-chat.heartbeat');
    Route::get('/komunitas/live-chat/u/{username}', [CommunityLiveChatController::class, 'open'])
        ->where('username', '[A-Za-z0-9_]+')
        ->name('community.live-chat.open');
    Route::get('/komunitas/live-chat/{conversation}', [CommunityLiveChatController::class, 'show'])->name('community.live-chat.show');
    Route::get('/komunitas/live-chat/{conversation}/poll', [CommunityLiveChatController::class, 'poll'])
        ->middleware('throttle:120,1')
        ->name('community.live-chat.poll');
    Route::post('/komunitas/live-chat/{conversation}', [CommunityLiveChatController::class, 'send'])
        ->middleware('throttle:60,1')
        ->name('community.live-chat.send');

    Route::post('/komunitas/u/{username}/ikuti', [CommunityFollowController::class, 'toggle'])
        ->where('username', '[A-Za-z0-9_]+')
        ->middleware('throttle:30,1')
        ->name('community.follow');

    Route::post('/komunitas', [CommunityController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('community.store');
    Route::get('/komunitas/cari-artikel', [CommunityController::class, 'searchArticles'])
        ->middleware('throttle:60,1')
        ->name('community.search-articles');
    Route::get('/komunitas/cari-event', [CommunityController::class, 'searchEvents'])
        ->middleware('throttle:60,1')
        ->name('community.search-events');
    Route::post('/komunitas/p/{post}/balas', [CommunityController::class, 'reply'])
        ->middleware('throttle:10,1')
        ->name('community.reply');
    Route::post('/komunitas/p/{post}/like', [CommunityController::class, 'like'])
        ->middleware('throttle:60,1')
        ->name('community.like');
    Route::delete('/komunitas/p/{post}', [CommunityController::class, 'destroy'])
        ->middleware('throttle:20,1')
        ->name('community.destroy');
    Route::post('/komunitas/upload', [CommunityController::class, 'upload'])
        ->middleware('throttle:20,1')
        ->name('community.upload');
});

Route::get('/komunitas/u/{username}', [CommunityController::class, 'profile'])
    ->where('username', '[A-Za-z0-9_]+')
    ->name('community.profile');

Route::get('/dashboard', function () {
    if (auth()->user()?->is_admin) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('community.index');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [AdminArticleController::class, 'store'])->name('articles.store');
    Route::post('/articles/upload-image', [AdminArticleController::class, 'uploadImage'])->name('articles.upload-image');
    Route::get('/articles/{article}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');

    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/videos', [AdminVideoController::class, 'index'])->name('videos.index');
    Route::post('/videos/refresh', [AdminVideoController::class, 'refresh'])->name('videos.refresh');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}/toggle', [AdminCommentController::class, 'toggle'])->name('comments.toggle');
    Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/events', [AdminEventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [AdminEventController::class, 'create'])->name('events.create');
    Route::post('/events', [AdminEventController::class, 'store'])->name('events.store');
    Route::post('/events/upload-image', [AdminEventController::class, 'uploadImage'])->name('events.upload-image');
    Route::get('/events/{event}/edit', [AdminEventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [AdminEventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [AdminEventController::class, 'destroy'])->name('events.destroy');

    Route::get('/brands', [AdminBrandController::class, 'index'])->name('brands.index');
    Route::post('/brands', [AdminBrandController::class, 'store'])->name('brands.store');
    Route::put('/brands/{brand}', [AdminBrandController::class, 'update'])->name('brands.update');
    Route::delete('/brands/{brand}', [AdminBrandController::class, 'destroy'])->name('brands.destroy');

    Route::get('/vehicles', [AdminVehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/create', [AdminVehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [AdminVehicleController::class, 'store'])->name('vehicles.store');
    Route::post('/vehicles/upload-image', [AdminVehicleController::class, 'uploadImage'])->name('vehicles.upload-image');
    Route::get('/vehicles/{vehicle}/edit', [AdminVehicleController::class, 'edit'])->name('vehicles.edit');
    Route::put('/vehicles/{vehicle}', [AdminVehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [AdminVehicleController::class, 'destroy'])->name('vehicles.destroy');

    Route::get('/galleries', [AdminGalleryController::class, 'index'])->name('galleries.index');
    Route::get('/galleries/create', [AdminGalleryController::class, 'create'])->name('galleries.create');
    Route::post('/galleries', [AdminGalleryController::class, 'store'])->name('galleries.store');
    Route::get('/galleries/{gallery}/edit', [AdminGalleryController::class, 'edit'])->name('galleries.edit');
    Route::put('/galleries/{gallery}', [AdminGalleryController::class, 'update'])->name('galleries.update');
    Route::delete('/galleries/{gallery}', [AdminGalleryController::class, 'destroy'])->name('galleries.destroy');

    Route::get('/newsletter', [AdminNewsletterSubscriberController::class, 'index'])->name('newsletter.index');
    Route::patch('/newsletter/{subscriber}/toggle', [AdminNewsletterSubscriberController::class, 'toggle'])->name('newsletter.toggle');
    Route::delete('/newsletter/{subscriber}', [AdminNewsletterSubscriberController::class, 'destroy'])->name('newsletter.destroy');

    Route::post('/seo/generate', [AdminSeoController::class, 'generate'])->name('seo.generate');
});

require __DIR__.'/auth.php';
