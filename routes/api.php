<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\FitQuizController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SubCategoryController;
use App\Http\Controllers\Api\ProductAttributeController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\BusinessPageController;
use App\Http\Controllers\BundleController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'fitfloor-laravel-api']));

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/social-login', [AuthController::class, 'socialLogin']);
});

Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('api.jwt');

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);

Route::post('/discounts/validate', [DiscountController::class, 'validateCode']);
Route::get('/discounts/{code}', [DiscountController::class, 'show']);

Route::post('/fit-quiz/responses', [FitQuizController::class, 'store']);
Route::get('/public/banners', [BannerController::class, 'index']);
Route::get('/public/settings', [SystemSettingController::class, 'getPublicSettings']);
Route::get('/public/bundles', [BundleController::class, 'index']);
Route::get('/public/coupons', [DiscountController::class, 'publicCoupons']);

Route::middleware('api.jwt')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/fit-quiz/recommendations', [FitQuizController::class, 'recommendations']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::patch('/cart/items/{itemId}', [CartController::class, 'update']);
    Route::delete('/cart/items/{itemId}', [CartController::class, 'destroy']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{productId}', [FavoriteController::class, 'destroy']);

    // Admin Product Management
    Route::post('/products', [ProductController::class, 'store']);
    Route::patch('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::post('/products/bulk-import', [ProductController::class, 'bulkImport']);

    Route::post('/products/{id}/reviews', [ReviewController::class, 'store']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::patch('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

    // Refund Routes
    Route::get('/refunds', [RefundController::class, 'index']);
    Route::get('/refunds/{id}', [RefundController::class, 'show']);
    Route::post('/refunds', [RefundController::class, 'store']);
    Route::patch('/refunds/{id}', [RefundController::class, 'update']);

    // Category Routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::patch('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Sub Category Routes
    Route::get('/sub-categories', [SubCategoryController::class, 'index']);
    Route::post('/sub-categories', [SubCategoryController::class, 'store']);
    Route::patch('/sub-categories/{id}', [SubCategoryController::class, 'update']);
    Route::delete('/sub-categories/{id}', [SubCategoryController::class, 'destroy']);

    // Product Attribute Routes
    Route::get('/product-attributes', [ProductAttributeController::class, 'index']);
    Route::post('/product-attributes', [ProductAttributeController::class, 'store']);
    Route::patch('/product-attributes/{id}', [ProductAttributeController::class, 'update']);
    Route::delete('/product-attributes/{id}', [ProductAttributeController::class, 'destroy']);

    // Banner Routes
    Route::get('/banners', [BannerController::class, 'index']);
    Route::post('/banners', [BannerController::class, 'store']);
    Route::match(['post', 'patch'], '/banners/{id}', [BannerController::class, 'update']);
    Route::delete('/banners/{id}', [BannerController::class, 'destroy']);

    Route::get('/admin/dashboard-stats', [ProductController::class, 'dashboardStats']);
    Route::get('/admin/sales-analytics', [ProductController::class, 'salesAnalytics']);
    
    Route::apiResource('banners', BannerController::class);
    Route::apiResource('email-templates', EmailTemplateController::class);
    Route::apiResource('business-pages', BusinessPageController::class);
    Route::post('settings', [SystemSettingController::class, 'update']);
    Route::get('customers', [\App\Http\Controllers\Api\CustomerController::class, 'index']);
    Route::get('admin/reviews', [ReviewController::class, 'adminIndex']);
    Route::delete('admin/reviews/{id}', [ReviewController::class, 'destroy']);
    Route::patch('admin/reviews/{id}/toggle-approval', [ReviewController::class, 'toggleApproval']);
    Route::get('admin/loyalty/transactions', [\App\Http\Controllers\Api\LoyaltyPointController::class, 'index']);
    Route::get('admin/loyalty/stats', [\App\Http\Controllers\Api\LoyaltyPointController::class, 'stats']);
    Route::get('admin/employees', [\App\Http\Controllers\Api\EmployeeController::class, 'index']);
    Route::post('admin/employees', [\App\Http\Controllers\Api\EmployeeController::class, 'store']);
    Route::delete('admin/employees/{id}', [\App\Http\Controllers\Api\EmployeeController::class, 'destroy']);
    Route::get('admin/coupons', [DiscountController::class, 'index']);
    Route::post('admin/coupons', [DiscountController::class, 'store']);
    Route::patch('admin/coupons/{id}', [DiscountController::class, 'update']);
    Route::delete('admin/coupons/{id}', [DiscountController::class, 'destroy']);
    Route::patch('admin/coupons/{id}/toggle-status', [DiscountController::class, 'toggleStatus']);
    Route::get('admin/flash-deals', [\App\Http\Controllers\Api\FlashDealController::class, 'index']);
    Route::post('admin/flash-deals', [\App\Http\Controllers\Api\FlashDealController::class, 'store']);
    Route::patch('admin/flash-deals/{id}', [\App\Http\Controllers\Api\FlashDealController::class, 'update']);
    Route::delete('admin/flash-deals/{id}', [\App\Http\Controllers\Api\FlashDealController::class, 'destroy']);
    Route::patch('admin/flash-deals/{id}/toggle-publish', [\App\Http\Controllers\Api\FlashDealController::class, 'togglePublish']);
    Route::get('admin/flash-deals/{id}/products', [\App\Http\Controllers\Api\FlashDealProductController::class, 'products']);
    Route::post('admin/flash-deals/{id}/products', [\App\Http\Controllers\Api\FlashDealProductController::class, 'store']);
    Route::delete('admin/flash-deals/{id}/products/{productId}', [\App\Http\Controllers\Api\FlashDealProductController::class, 'destroy']);
    
    // Bundle Management
    Route::get('admin/bundles', [BundleController::class, 'adminIndex']);
    Route::post('admin/bundles', [BundleController::class, 'store']);
    Route::get('admin/bundles/{bundle}', [BundleController::class, 'show']);
    Route::patch('admin/bundles/{bundle}', [BundleController::class, 'update']);
    Route::delete('admin/bundles/{bundle}', [BundleController::class, 'destroy']);
});

Route::get('/public/business-pages/{slug}', [BusinessPageController::class, 'getPublicPage']);
