<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/home', [HomeController::class, 'show']);
    Route::get('/videos', [HomeController::class, 'videos'])->middleware('throttle:60,1');

    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{slug}', [ArticleController::class, 'show']);
    Route::post('/articles/{slug}/comments', [ArticleController::class, 'comments'])->middleware('throttle:8,1');
    Route::post('/articles/{slug}/share', [ArticleController::class, 'share'])->middleware('throttle:30,1');
    Route::get('/categories/{slug}', [ArticleController::class, 'category']);
    Route::get('/search', [ArticleController::class, 'search']);

    Route::get('/shop', [ShopController::class, 'index']);
    Route::get('/shop/stores/{slug}', [ShopController::class, 'showStore']);
    Route::get('/shop/products/{slug}', [ShopController::class, 'show']);
    Route::get('/shop/cart', [ShopController::class, 'cart']);
    Route::post('/shop/cart', [ShopController::class, 'addToCart'])->middleware('throttle:40,1');
    Route::patch('/shop/cart/{item}', [ShopController::class, 'updateCart'])->middleware('throttle:60,1');
    Route::delete('/shop/cart/{item}', [ShopController::class, 'removeCart'])->middleware('throttle:60,1');
    Route::get('/shop/wishlist', [ShopController::class, 'wishlist']);
    Route::post('/shop/wishlist', [ShopController::class, 'toggleWishlist'])->middleware('throttle:40,1');

    Route::get('/community', [CommunityController::class, 'index']);
    Route::get('/community/posts/{post}', [CommunityController::class, 'show']);
    Route::get('/community/users/{username}', [CommunityController::class, 'profile'])->where('username', '[A-Za-z0-9_]+');
    Route::get('/community/groups', [CommunityController::class, 'groups']);
    Route::get('/community/groups/{slug}', [CommunityController::class, 'showGroup']);

    Route::get('/events', [CatalogController::class, 'events']);
    Route::get('/events/{slug}', [CatalogController::class, 'event']);
    Route::post('/events/{slug}/share', [CatalogController::class, 'shareEvent'])->middleware('throttle:30,1');
    Route::get('/brands', [CatalogController::class, 'brands']);
    Route::get('/brands/{slug}', [CatalogController::class, 'brand']);
    Route::get('/brands/{brandSlug}/vehicles/{vehicleSlug}', [CatalogController::class, 'vehicle']);
    Route::get('/compare', [CatalogController::class, 'compare']);
    Route::get('/compare/search', [CatalogController::class, 'compareSearch'])->middleware('throttle:60,1');
    Route::get('/credit', [CatalogController::class, 'credit']);
    Route::get('/galleries', [CatalogController::class, 'galleries']);
    Route::get('/galleries/{slug}', [CatalogController::class, 'gallery']);

    Route::get('/support', [SupportController::class, 'current'])->middleware('throttle:60,1');
    Route::get('/support/poll', [SupportController::class, 'poll'])->middleware('throttle:60,1');
    Route::post('/support', [SupportController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/legal/privacy', [SupportController::class, 'privacy']);
    Route::get('/legal/faq', [SupportController::class, 'faq']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/me', [AuthController::class, 'updateProfile']);

        Route::get('/shop/checkout', [CheckoutController::class, 'show']);
        Route::get('/shop/checkout/cities', [CheckoutController::class, 'cities'])->middleware('throttle:60,1');
        Route::get('/shop/checkout/districts', [CheckoutController::class, 'districts'])->middleware('throttle:60,1');
        Route::post('/shop/checkout/quote', [CheckoutController::class, 'quote'])->middleware('throttle:30,1');
        Route::post('/shop/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1');
        Route::get('/shop/checkouts/{number}', [CheckoutController::class, 'checkoutShow']);
        Route::post('/shop/checkouts/{number}/pay', [CheckoutController::class, 'checkoutPay'])->middleware('throttle:20,1');
        Route::get('/shop/orders', [CheckoutController::class, 'orders']);
        Route::get('/shop/orders/{number}', [CheckoutController::class, 'orderShow']);
        Route::post('/shop/orders/{number}/pay', [CheckoutController::class, 'orderPay'])->middleware('throttle:20,1');
        Route::post('/shop/products/{slug}/reviews', [ShopController::class, 'review'])->middleware('throttle:10,1');

        Route::post('/community', [CommunityController::class, 'store'])->middleware('throttle:20,1');
        Route::post('/community/posts/{post}/replies', [CommunityController::class, 'reply'])->middleware('throttle:20,1');
        Route::post('/community/posts/{post}/like', [CommunityController::class, 'like'])->middleware('throttle:60,1');
        Route::delete('/community/posts/{post}', [CommunityController::class, 'destroy'])->middleware('throttle:20,1');
        Route::post('/community/users/{username}/follow', [CommunityController::class, 'follow'])
            ->where('username', '[A-Za-z0-9_]+')
            ->middleware('throttle:30,1');
        Route::post('/community/groups', [CommunityController::class, 'createGroup'])->middleware('throttle:10,1');
        Route::post('/community/groups/{slug}/join', [CommunityController::class, 'joinGroup'])->middleware('throttle:30,1');
        Route::get('/community/messages', [CommunityController::class, 'inbox']);
        Route::post('/community/messages/u/{username}', [CommunityController::class, 'startMessage'])->where('username', '[A-Za-z0-9_]+');
        Route::get('/community/messages/{conversation}', [CommunityController::class, 'messages']);
        Route::post('/community/messages/{conversation}', [CommunityController::class, 'sendMessage'])->middleware('throttle:30,1');
        Route::get('/community/messages/{conversation}/poll', [CommunityController::class, 'pollMessages'])->middleware('throttle:120,1');
        Route::get('/community/live-chat/friends', [CommunityController::class, 'liveFriends'])->middleware('throttle:60,1');
        Route::post('/community/live-chat/u/{username}', [CommunityController::class, 'liveOpen'])->where('username', '[A-Za-z0-9_]+');
        Route::post('/community/live-chat/{conversation}', [CommunityController::class, 'liveSend'])->middleware('throttle:60,1');
        Route::get('/community/live-chat/{conversation}/poll', [CommunityController::class, 'pollMessages'])->middleware('throttle:120,1');
        Route::get('/community/notifications', [CommunityController::class, 'notifications']);
        Route::post('/community/notifications/read-all', [CommunityController::class, 'markNotificationsRead']);
        Route::get('/community/search/articles', [CommunityController::class, 'searchArticles']);
        Route::get('/community/search/events', [CommunityController::class, 'searchEvents']);
        Route::get('/community/search/vehicles', [CommunityController::class, 'searchVehicles']);

        Route::middleware('seller')->prefix('seller')->group(function () {
            Route::get('/dashboard', [SellerController::class, 'dashboard']);
            Route::get('/products', [SellerController::class, 'products']);
            Route::get('/products/form/{id?}', [SellerController::class, 'productForm']);
            Route::post('/products', [SellerController::class, 'storeProduct']);
            Route::post('/products/{id}', [SellerController::class, 'updateProduct']);
            Route::delete('/products/{id}', [SellerController::class, 'destroyProduct']);
            Route::get('/orders', [SellerController::class, 'orders']);
            Route::get('/orders/{number}', [SellerController::class, 'orderShow']);
            Route::put('/orders/{number}', [SellerController::class, 'orderUpdate']);
            Route::get('/settings', [SellerController::class, 'settings']);
            Route::post('/settings', [SellerController::class, 'updateSettings']);
        });
    });
});
