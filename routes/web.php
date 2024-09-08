<?php
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\HomeAdvertisementController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\CustomerController as CustomerControllerForAdmin;
use App\Http\Controllers\Admin\DashboardController as DashboardControllerForAdmin;
use App\Http\Controllers\Admin\DynamicPageController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\LoginController as LoginControllerForAdmin;
use App\Http\Controllers\Admin\PageAboutController;
use App\Http\Controllers\Admin\PageBlogController;
use App\Http\Controllers\Admin\PageContactController;
use App\Http\Controllers\Admin\PagePricingController;
use App\Http\Controllers\Admin\PagePropertyCategoryController;
use App\Http\Controllers\Admin\PagePropertyLocationController;
use App\Http\Controllers\Admin\PagePropertyController;
use App\Http\Controllers\Admin\PageFaqController;
use App\Http\Controllers\Admin\PageHomeController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\PageOtherController;
use App\Http\Controllers\Admin\PagePrivacyController;
use App\Http\Controllers\Admin\PageTermController;
use App\Http\Controllers\Admin\CategoryController as CategoryControllerForAdmin;
use App\Http\Controllers\Admin\BlogController as BlogControllerForAdmin;
use App\Http\Controllers\Admin\AmenityController as AmenityControllerForAdmin;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\EpinController as EpinControllerForAdmin;
use App\Http\Controllers\Admin\PropertyLocationController as PropertyLocationControllerForAdmin;
use App\Http\Controllers\Admin\PropertyController as PropertyControllerForAdmin;
use App\Http\Controllers\Admin\UnderConstructionPropertyController as UnderConstructionPropertyControllerForAdmin;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SocialMediaItemController;
use App\Http\Controllers\Admin\FaqController as FaqControllerForAdmin;
use App\Http\Controllers\Admin\PackageController as PackageControllerForAdmin;
use App\Http\Controllers\Admin\PurchaseHistoryController as PurchaseHistoryControllerForAdmin;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\ClearDatabaseController;
use App\Http\Controllers\Admin\PageDreamPropertyController;


use App\Http\Controllers\Front\CurrencyController as CurrencyControllerForFront;
use App\Http\Controllers\Front\AboutController;
use App\Http\Controllers\Front\PricingController;
use App\Http\Controllers\Front\BlogController as BlogControllerForFront;
use App\Http\Controllers\Front\CategoryController as CategoryControllerForFront;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Front\FaqController as FaqControllerForFront;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\PageController;
use App\Http\Controllers\Front\PrivacyController;
use App\Http\Controllers\Front\TermController;
use App\Http\Controllers\Front\CustomerAuthController;
use App\Http\Controllers\Front\CustomerController as CustomerControllerForFront;
use App\Http\Controllers\Front\PropertyController as PropertyControllerForFront;

use Illuminate\Support\Facades\Route;


/* --------------------------------------- */
/* Front End */
/* --------------------------------------- */
// Route::get('/', [HomeController::class,'index']);

Route::get('/dream_life_login', function () {
    return view('admin.dream_life_login');
});


Route::post('currency', [CurrencyControllerForFront::class,'index'])
    ->name('front_currency');

Route::get('about', [AboutController::class,'index'])
    ->name('front_about');

Route::get('pricing', [PricingController::class,'index'])
    ->name('front_pricing');

Route::get('blog', [BlogControllerForFront::class,'index'])
    ->name('front_blogs');

Route::get('post/{slug}', [BlogControllerForFront::class,'detail'])
    ->name('front_post');

Route::post('post/comment', [BlogControllerForFront::class,'comment'])
    ->name('front_comment');

Route::get('category/{slug}', [CategoryControllerForFront::class,'detail'])
    ->name('front_category');

Route::post('search', [SearchController::class,'index']);

Route::get('search', function() {abort(404);});

Route::get('faq', [FaqControllerForFront::class,'index'])
    ->name('front_faq');

Route::get('page/{slug}', [PageController::class,'detail'])
    ->name('front_dynamic_page');

Route::get('contact', [ContactController::class,'index'])
    ->name('front_contact');

Route::post('contact/store', [ContactController::class,'send_email'])
    ->name('front_contact_form');

Route::get('terms-and-conditions', [TermController::class,'index'])
    ->name('front_terms_and_conditions');

Route::get('privacy-policy', [PrivacyController::class,'index'])
    ->name('front_privacy_policy');

Route::get('property/{slug}', [PropertyControllerForFront::class,'detail'])
    ->name('front_property_detail');

Route::post('property/property/send-message', [PropertyControllerForFront::class,'send_message'])
    ->name('front_property_detail_send_message');

Route::post('property/property/report-property', [PropertyControllerForFront::class,'report_property'])
    ->name('front_property_detail_report_property');



Route::get('property/category/all', [PropertyControllerForFront::class,'category_all'])
    ->name('front_property_category_all');

Route::get('property/category/{slug}', [PropertyControllerForFront::class,'category_detail'])
    ->name('front_property_category_detail');

Route::get('property/location/all', [PropertyControllerForFront::class,'location_all'])
    ->name('front_property_location_all');

Route::get('property/location/{slug}', [PropertyControllerForFront::class,'location_detail'])
    ->name('front_property_location_detail');

Route::get('agent/{type}/{id}', [PropertyControllerForFront::class,'agent_detail'])
    ->name('front_property_agent_detail');

Route::post('property-result', [PropertyControllerForFront::class,'property_result'])
    ->name('front_property_result');

Route::get('search-property-result', [PropertyControllerForFront::class,'search_property_result'])
    ->name('search-front_property_result');

Route::get('customer/wishlist/add/{id}', [PropertyControllerForFront::class,'wishlist_add'])
    ->name('front_add_wishlist');

    Route::get('customer/ajax-wishlist/add/{id}', [PropertyControllerForFront::class,'ajax_wishlist_add'])
    ->name('front_add_ajax_wishlist');






/* --------------------------------------- */
/* Customer Authemtication */
/* --------------------------------------- */
Route::get('customer/login', [CustomerAuthController::class,'login'])
    ->name('customer_login');

Route::post('customer/login/store', [CustomerAuthController::class,'login_store'])
    ->name('customer_login_store');

Route::get('customer/logout', [CustomerAuthController::class,'logout'])
    ->name('customer_logout');

Route::get('customer/register', [CustomerAuthController::class,'registration'])
    ->name('customer_registration');

Route::post('customer/registration/store', [CustomerAuthController::class,'registration_store'])
    ->name('customer_registration_store');

Route::get('customer/registration/verify/{token}/{email}', [CustomerAuthController::class,'registration_verify'])
    ->name('customer_registration_verify');

Route::get('customer/forget-password', [CustomerAuthController::class,'forget_password'])
    ->name('customer_forget_password');

Route::post('customer/forget-password/store', [CustomerAuthController::class,'forget_password_store'])
    ->name('customer_forget_password_store');

Route::get('customer/reset-password/{token}/{email}', [CustomerAuthController::class,'reset_password']);
Route::post('customer/reset-password/update', [CustomerAuthController::class,'reset_password_update'])
    ->name('customer_reset_password_update');



/* --------------------------------------- */
/* Customer Profile */
/* --------------------------------------- */
Route::get('customer/dashboard', [CustomerControllerForFront::class,'dashboard'])
    ->name('customer_dashboard');

Route::get('customer/taking-help', [CustomerControllerForFront::class,'taking_help'])
->name('taking_help');
    
Route::get('customer/giving-help', [CustomerControllerForFront::class,'giving_help'])
->name('giving_help');

Route::get('customer/view-direct', [CustomerControllerForFront::class,'view_direct'])
->name('view_direct');

Route::get('customer/view-downline', [CustomerControllerForFront::class,'view_downline'])
->name('view_downline');

Route::post('payment/approve/{payment}', [CustomerControllerForFront::class, 'approvePayment'])->name('payment.approve');

Route::get('customer/package', [CustomerControllerForFront::class,'package'])
    ->name('customer_package');

Route::get('customer/package/free/{id}', [CustomerControllerForFront::class,'free_enroll'])
    ->name('customer_package_free_enroll');

Route::get('customer/package/paid/buy/{id}', [CustomerControllerForFront::class,'buy_package'])
    ->name('customer_package_buy');


Route::post('customer/payment/stripe', [CustomerControllerForFront::class,'stripe'])->name('customer_payment_stripe');
Route::get('customer/payment/paypal', [CustomerControllerForFront::class,'paypal']);
Route::post('customer/payment/razorpay',[CustomerControllerForFront::class,'razorpay'])->name('customer_payment_razorpay');
Route::post('customer/payment/flutterwave',[CustomerControllerForFront::class,'flutterwave'])->name('customer_payment_flutterwave');
Route::post('customer/payment/mollie',[CustomerControllerForFront::class,'mollie'])->name('customer_payment_mollie');
Route::get('customer/payment/mollie-notify',[CustomerControllerForFront::class,'mollie_notify'])->name('customer_payment_mollie_notify');



Route::get('customer/package/purchase/history', [CustomerControllerForFront::class,'purchase_history'])
    ->name('customer_package_purchase_history');

Route::get('customer/package/purchase/{id}', [CustomerControllerForFront::class,'purchase_history_detail'])
    ->name('customer_package_purchase_history_detail');

Route::get('customer/package/invoice/{id}', [CustomerControllerForFront::class,'invoice'])
    ->name('customer_package_purchase_invoice');

Route::get('customer/profile-change', [CustomerControllerForFront::class,'update_profile'])
    ->name('customer_update_profile');

Route::post('customer/profile-change/update', [CustomerControllerForFront::class,'update_profile_confirm'])
    ->name('customer_update_profile_confirm');

Route::get('customer/password-change', [CustomerControllerForFront::class,'update_password'])
    ->name('customer_update_password');

Route::post('customer/password-change/update', [CustomerControllerForFront::class,'update_password_confirm'])
    ->name('customer_update_password_confirm');

Route::get('customer/photo-change', [CustomerControllerForFront::class,'update_photo'])
    ->name('customer_update_photo');

Route::post('customer/photo-change/update', [CustomerControllerForFront::class,'update_photo_confirm'])
    ->name('customer_update_photo_confirm');

Route::get('customer/banner-change', [CustomerControllerForFront::class,'update_banner'])
    ->name('customer_update_banner');

Route::post('customer/banner-change/update', [CustomerControllerForFront::class,'update_banner_confirm'])
    ->name('customer_update_banner_confirm');


Route::get('customer/property/detail/{id}', [CustomerControllerForFront::class,'property_view_detail'])
    ->name('customer_property_view_detail');

Route::get('customer/property/add', [CustomerControllerForFront::class,'property_add'])
    ->name('customer_property_add');

Route::post('customer/property/add/store', [CustomerControllerForFront::class,'property_add_store'])
    ->name('customer_property_add_store');

Route::get('customer/property/delete/{id}', [CustomerControllerForFront::class,'property_delete'])
    ->name('customer_property_delete');

Route::get('customer/property/edit/{id}', [CustomerControllerForFront::class,'property_edit'])
    ->name('customer_property_edit');

Route::post('customer/property/update/{id}', [CustomerControllerForFront::class,'property_update'])
    ->name('customer_property_update');

Route::get('customer/reviews', [CustomerControllerForFront::class,'my_reviews'])
    ->name('customer_my_reviews');

Route::get('customer/review/edit/{id}', [CustomerControllerForFront::class,'review_edit'])
    ->name('customer_my_review_edit');

Route::post('customer/review/update/{id}', [CustomerControllerForFront::class,'review_update'])
    ->name('customer_my_review_update');

Route::get('customer/review/delete/{id}', [CustomerControllerForFront::class,'review_delete'])
    ->name('customer_my_review_delete');

Route::get('customer/wishlist', [CustomerControllerForFront::class,'wishlist'])
    ->name('customer_wishlist');

Route::get('customer/wishlist/delete/{id}', [CustomerControllerForFront::class,'wishlist_delete'])
    ->name('customer_wishlist_delete');

Route::get('customer/property/delete-social-item/{id}', [CustomerControllerForFront::class,'property_delete_social_item'])
    ->name('customer_property_delete_social_item');

Route::get('customer/property/delete-photo/{id}', [CustomerControllerForFront::class,'property_delete_photo'])
    ->name('customer_property_delete_photo');

Route::get('customer/property/delete-video/{id}', [CustomerControllerForFront::class,'property_delete_video'])
    ->name('customer_property_delete_video');

Route::get('customer/property/delete-additional-feature/{id}', [CustomerControllerForFront::class,'property_delete_additional_feature'])
    ->name('customer_property_delete_additional_feature');

Route::post('customer/review', [CustomerControllerForFront::class,'submit_review'])
    ->name('customer_review');



/* --------------------------------------- */
/* --------------------------------------- */
/* --------------------------------------- */
/* ADMIN SECTION */
/* --------------------------------------- */
/* --------------------------------------- */
/* --------------------------------------- */

/* --------------------------------------- */
/* Login and profile management */
/* --------------------------------------- */
Route::get('admin/dashboard', [DashboardControllerForAdmin::class,'index'])
    ->name('admin_dashboard');

Route::get('admin', function () {return redirect('admin/login');});

Route::get('admin/login', [LoginControllerForAdmin::class,'login'])
    ->name('admin_login');

Route::post('admin/login/store', [LoginControllerForAdmin::class,'login_check'])
    ->name('admin_login_store');

Route::get('admin/logout', [LoginControllerForAdmin::class,'logout'])
    ->name('admin_logout');

Route::get('admin/forget-password', [LoginControllerForAdmin::class,'forget_password'])
    ->name('admin_forget_password');

Route::post('admin/forget-password/store', [LoginControllerForAdmin::class,'forget_password_check'])
    ->name('admin_forget_password_store');

Route::get('admin/reset-password/{token}/{email}', [LoginControllerForAdmin::class,'reset_password']);

Route::post('admin/reset-password/update', [LoginControllerForAdmin::class,'reset_password_update'])
    ->name('admin_reset_password_update');

Route::get('admin/password-change', [ProfileController::class,'password'])
    ->name('admin_password_change');

Route::post('admin/password-change/update', [ProfileController::class,'password_update'])
    ->name('admin_password_change_update');

Route::get('admin/profile-change', [ProfileController::class,'profile'])
    ->name('admin_profile_change');

Route::post('admin/profile-change/update', [ProfileController::class,'profile_update'])
    ->name('admin_profile_change_update');

Route::get('admin/photo-change', [ProfileController::class,'photo'])
    ->name('admin_photo_change');

Route::post('admin/photo-change/update', [ProfileController::class,'photo_update'])
    ->name('admin_photo_change_update');

Route::get('admin/banner-change', [ProfileController::class,'banner'])
    ->name('admin_banner_change');

Route::post('admin/banner-change/update', [ProfileController::class,'banner_update'])
    ->name('admin_banner_change_update');




/* --------------------------------------- */
/* Payment */
/* --------------------------------------- */
Route::get('admin/payment/view', [SettingController::class,'payment_edit'])
    ->name('admin_payment');

Route::post('admin/payment/update', [SettingController::class,'payment_update'])
    ->name('admin_payment_update');



/* --------------------------------------- */
/* Currency */
/* --------------------------------------- */
Route::get('admin/currency/view', [CurrencyController::class,'index'])
    ->name('admin_currency_view');

Route::get('admin/currency/create', [CurrencyController::class,'create'])
    ->name('admin_currency_create');

Route::post('admin/currency/store', [CurrencyController::class,'store'])
    ->name('admin_currency_store');

Route::get('admin/currency/delete/{id}', [CurrencyController::class,'destroy'])
    ->name('admin_currency_delete');

Route::get('admin/currency/edit/{id}', [CurrencyController::class,'edit'])
    ->name('admin_currency_edit');

Route::post('admin/currency/update/{id}', [CurrencyController::class,'update'])
    ->name('admin_currency_update');


/* --------------------------------------- */
/* Blog Category */
/* --------------------------------------- */
Route::get('admin/category/view', [CategoryControllerForAdmin::class,'index'])
    ->name('admin_category_view');

Route::get('admin/category/create', [CategoryControllerForAdmin::class,'create'])
    ->name('admin_category_create');

Route::post('admin/category/store', [CategoryControllerForAdmin::class,'store'])
    ->name('admin_category_store');

Route::get('admin/category/delete/{id}', [CategoryControllerForAdmin::class,'destroy'])
    ->name('admin_category_delete');

Route::get('admin/category/edit/{id}', [CategoryControllerForAdmin::class,'edit'])
    ->name('admin_category_edit');

Route::post('admin/category/update/{id}', [CategoryControllerForAdmin::class,'update'])
    ->name('admin_category_update');


/* --------------------------------------- */
/* Blog */
/* --------------------------------------- */
Route::get('admin/blog/view', [BlogControllerForAdmin::class,'index'])
    ->name('admin_blog_view');

Route::get('admin/blog/create', [BlogControllerForAdmin::class,'create'])
    ->name('admin_blog_create');

Route::post('admin/blog/store', [BlogControllerForAdmin::class,'store'])
    ->name('admin_blog_store');

Route::get('admin/blog/delete/{id}', [BlogControllerForAdmin::class,'destroy'])
    ->name('admin_blog_delete');

Route::get('admin/blog/edit/{id}', [BlogControllerForAdmin::class,'edit'])
    ->name('admin_blog_edit');

Route::post('admin/blog/update/{id}', [BlogControllerForAdmin::class,'update'])
    ->name('admin_blog_update');


/* --------------------------------------- */
/* Blog Comment */
/* --------------------------------------- */
Route::get('admin/comment/approved', [CommentController::class,'approved'])
    ->name('admin_comment_approved');

Route::get('admin/comment/make-pending/{id}', [CommentController::class,'make_pending'])
    ->name('admin_comment_make_pending');

Route::get('admin/comment/pending', [CommentController::class,'pending'])
    ->name('admin_comment_pending');

Route::get('admin/comment/make-approved/{id}', [CommentController::class,'make_approved'])
    ->name('admin_comment_make_approved');

Route::get('admin/comment/delete/{id}', [CommentController::class,'destroy'])
    ->name('admin_comment_delete');


/* --------------------------------------- */
/* Dynamic Pages */
/* --------------------------------------- */
Route::get('admin/dynamic-page/view', [DynamicPageController::class,'index'])
    ->name('admin_dynamic_page_view');

Route::get('admin/dynamic-page/create', [DynamicPageController::class,'create'])
    ->name('admin_dynamic_page_create');

Route::post('admin/dynamic-page/store', [DynamicPageController::class,'store'])
    ->name('admin_dynamic_page_store');

Route::get('admin/dynamic-page/delete/{id}', [DynamicPageController::class,'destroy'])
    ->name('admin_dynamic_page_delete');

Route::get('admin/dynamic-page/edit/{id}', [DynamicPageController::class,'edit'])
    ->name('admin_dynamic_page_edit');

Route::post('admin/dynamic-page/update/{id}', [DynamicPageController::class,'update'])
    ->name('admin_dynamic_page_update');



/* --------------------------------------- */
/* Testimonial */
/* --------------------------------------- */
Route::get('admin/testimonial/view', [TestimonialController::class,'index'])
    ->name('admin_testimonial_view');

Route::get('admin/testimonial/create', [TestimonialController::class,'create'])
    ->name('admin_testimonial_create');

Route::post('admin/testimonial/store', [TestimonialController::class,'store'])
    ->name('admin_testimonial_store');

Route::get('admin/testimonial/delete/{id}', [TestimonialController::class,'destroy'])
    ->name('admin_testimonial_delete');

Route::get('admin/testimonial/edit/{id}', [TestimonialController::class,'edit'])
    ->name('admin_testimonial_edit');

Route::post('admin/testimonial/update/{id}', [TestimonialController::class,'update'])
    ->name('admin_testimonial_update');


/* --------------------------------------- */
/* Amenity */
/* --------------------------------------- */
Route::get('admin/amenity/view', [AmenityControllerForAdmin::class,'index'])
    ->name('admin_amenity_view');

Route::get('admin/amenity/create', [AmenityControllerForAdmin::class,'create'])
    ->name('admin_amenity_create');

Route::post('admin/amenity/store', [AmenityControllerForAdmin::class,'store'])
    ->name('admin_amenity_store');

Route::get('admin/amenity/delete/{id}', [AmenityControllerForAdmin::class,'destroy'])
    ->name('admin_amenity_delete');

Route::get('admin/amenity/edit/{id}', [AmenityControllerForAdmin::class,'edit'])
    ->name('admin_amenity_edit');

Route::post('admin/amenity/update/{id}', [AmenityControllerForAdmin::class,'update'])
    ->name('admin_amenity_update');


/* --------------------------------------- */
/* Property Category */
/* --------------------------------------- */
Route::get('admin/property-category/view', [EpinControllerForAdmin::class,'index'])
    ->name('admin_e_pin_master');

Route::get('admin/e_pin-used-view', [EpinControllerForAdmin::class,'e_pin_used_view'])
    ->name('e_pin_used_view');

Route::get('admin/e-pin-transfer', [EpinControllerForAdmin::class,'e_pin_transfer'])
    ->name('e_pin_transfer');

Route::get('admin/property-category/create', [EpinControllerForAdmin::class,'create'])
    ->name('admin_property_category_create');


Route::post('admin/e-pin/store', [EpinControllerForAdmin::class,'admin_e_pin_store'])
    ->name('admin_e_pin_store');
    
Route::get('admin/e-pin-transfer-create', [EpinControllerForAdmin::class,'e_pin_transfer_create'])
    ->name('e_pin_transfer_create');

Route::post('admin/e-pin-transfer/store', [EpinControllerForAdmin::class,'e_pin_transfer_store'])
->name('e_pin_transfer_store');
    
Route::get('admin/property-category/delete/{id}', [EpinControllerForAdmin::class,'destroy'])
    ->name('admin_property_category_delete');

Route::get('admin/property-category/edit/{id}', [EpinControllerForAdmin::class,'edit'])
    ->name('admin_property_category_edit');

Route::post('admin/property-category/update/{id}', [EpinControllerForAdmin::class,'update'])
    ->name('admin_property_category_update');


/* --------------------------------------- */
/* Property Location */
/* --------------------------------------- */
Route::get('admin/property-location/view', [PropertyLocationControllerForAdmin::class,'index'])
    ->name('admin_property_location_view');

Route::get('admin/property-location/create', [PropertyLocationControllerForAdmin::class,'create'])
    ->name('admin_property_location_create');

Route::post('admin/property-location/store', [PropertyLocationControllerForAdmin::class,'store'])
    ->name('admin_property_location_store');

Route::get('admin/property-location/delete/{id}', [PropertyLocationControllerForAdmin::class,'destroy'])
    ->name('admin_property_location_delete');

Route::get('admin/property-location/edit/{id}', [PropertyLocationControllerForAdmin::class,'edit'])
    ->name('admin_property_location_edit');

Route::post('admin/property-location/update/{id}', [PropertyLocationControllerForAdmin::class,'update'])
    ->name('admin_property_location_update');



/* --------------------------------------- */
/* Property */
/* --------------------------------------- */
Route::get('admin/property/view', [PropertyControllerForAdmin::class,'index'])
    ->name('admin_property_view');

Route::get('admin/property/create', [PropertyControllerForAdmin::class,'create'])
    ->name('admin_property_create');

Route::post('admin/property/store', [PropertyControllerForAdmin::class,'store'])
    ->name('admin_property_store');

Route::get('admin/property/delete/{id}', [PropertyControllerForAdmin::class,'destroy'])
    ->name('admin_property_delete');

Route::get('admin/property/edit/{id}', [PropertyControllerForAdmin::class,'edit'])
    ->name('admin_property_edit');

Route::post('admin/property/update/{id}', [PropertyControllerForAdmin::class,'update'])
    ->name('admin_property_update');

Route::get('admin/property/delete-social-item/{id}', [PropertyControllerForAdmin::class,'delete_social_item'])
    ->name('admin_property_delete_social_item');

Route::get('admin/property/delete-photo/{id}', [PropertyControllerForAdmin::class,'delete_photo'])
    ->name('admin_property_delete_photo');

Route::get('admin/property/delete-video/{id}', [PropertyControllerForAdmin::class,'delete_video'])
    ->name('admin_property_delete_video');


Route::get('admin/property/delete-additional-feature/{id}', [PropertyControllerForAdmin::class,'delete_additional_feature'])
    ->name('admin_property_delete_additional_feature');


    Route::get('admin/property-status/{id}', [PropertyControllerForAdmin::class,'change_status']);
    Route::get('admin/property-approve-status/{id}', [PropertyControllerForAdmin::class,'property_approve_status']);
    Route::get('admin/property-photo-approve-status/{id}', [PropertyControllerForAdmin::class,'property_photo_approve_status']);
    Route::get('admin/property-video-approve-status/{id}', [PropertyControllerForAdmin::class,'property_video_approve_status']);

/* --------------------------------------- */
/*Under Construction Property */
/* --------------------------------------- */
Route::get('admin/under-construction-property/view', [UnderConstructionPropertyControllerForAdmin::class,'index'])
    ->name('admin_underconstruction_property_view');

Route::get('admin/under-construction-property/create', [UnderConstructionPropertyControllerForAdmin::class,'create'])
    ->name('admin_underconstruction_property_create');

Route::post('admin/under-construction-property/store', [UnderConstructionPropertyControllerForAdmin::class,'store'])
    ->name('admin_underconstruction_property_store');

Route::get('admin/under-construction-property/delete/{id}', [UnderConstructionPropertyControllerForAdmin::class,'destroy'])
    ->name('admin_underconstruction_property_delete');

Route::get('admin/under-construction-property/edit/{id}', [UnderConstructionPropertyControllerForAdmin::class,'edit'])
    ->name('admin_underconstruction_property_edit');

Route::post('admin/under-construction-property/update/{id}', [UnderConstructionPropertyControllerForAdmin::class,'update'])
    ->name('admin_underconstruction_property_update');

Route::get('admin/under-construction-property/delete-social-item/{id}', [UnderConstructionPropertyControllerForAdmin::class,'delete_social_item'])
    ->name('admin_underconstruction_property_delete_social_item');

Route::get('admin/under-construction-property/delete-photo/{id}', [UnderConstructionPropertyControllerForAdmin::class,'delete_photo'])
    ->name('admin_underconstruction_property_delete_photo');

Route::get('admin/under-construction-property/delete-video/{id}', [UnderConstructionPropertyControllerForAdmin::class,'delete_video'])
    ->name('admin_underconstruction_property_delete_video');

Route::get('admin/under-construction-property/delete-additional-feature/{id}', [UnderConstructionPropertyControllerForAdmin::class,'delete_additional_feature'])
    ->name('admin_underconstruction_property_delete_additional_feature');

Route::get('admin/under-construction-property-status/{id}', [UnderConstructionPropertyControllerForAdmin::class,'change_status']);
Route::get('admin/epin-status/{id}', [EpinControllerForAdmin::class,'change_status']);
Route::get('admin/epin-flag/{id}', [EpinControllerForAdmin::class,'change_flag']);

/* --------------------------------------- */
/* Review Settings */
/* --------------------------------------- */
Route::get('admin/admin-review/view', [ReviewController::class,'view_admin_review'])
    ->name('admin_view_admin_review');

Route::post('admin/admin-review/store', [ReviewController::class,'store_admin_review'])
    ->name('admin_store_admin_review');

Route::post('admin/admin-review/update/{id}', [ReviewController::class,'update_admin_review'])
    ->name('admin_update_admin_review');

Route::get('admin/admin-review/delete/{id}', [ReviewController::class,'delete_admin_review'])
    ->name('admin_delete_admin_review');

Route::get('admin/customer-review/view', [ReviewController::class,'view_customer_review'])
    ->name('admin_view_customer_review');

Route::get('admin/customer-review/delete/{id}', [ReviewController::class,'delete_customer_review'])
    ->name('admin_delete_customer_review');

Route::get('admin/review-approve-status/{id}', [ReviewController::class,'review_approve_status']);


/* --------------------------------------- */
/* General Settings */
/* --------------------------------------- */
Route::get('admin/setting/general', [SettingController::class,'edit'])
    ->name('admin_setting_general');

Route::post('admin/setting/general/update', [SettingController::class,'update'])
    ->name('admin_setting_general_update');


/* --------------------------------------- */
/* Advertisements */
/* --------------------------------------- */
Route::get('admin/advertisement/home', [HomeAdvertisementController::class,'edit'])
    ->name('admin_home_advertisement');

Route::post('admin/advertisement/home/update', [HomeAdvertisementController::class,'update'])
    ->name('admin_home_advertisement_update');


/* --------------------------------------- */
/* Language Settings */
/* --------------------------------------- */
Route::get('admin/language/menu/view', [LanguageController::class,'language_menu_text'])
    ->name('admin_language_menu_text');

Route::post('admin/language/menu/update', [LanguageController::class,'language_menu_text_update'])
    ->name('admin_language_menu_text_update');

Route::get('admin/language/menu/create', [LanguageController::class,'admin_language_menu_text_create'])
->name('admin_language_menu_text_create');

Route::post('admin/language/menu/store', [LanguageController::class,'admin_language_menu_text_store'])
->name('admin_language_menu_text_store');



Route::get('admin/language/website/view', [LanguageController::class,'language_website_text'])
    ->name('admin_language_website_text');

Route::post('admin/language/website/update', [LanguageController::class,'language_website_text_update'])
    ->name('admin_language_website_text_update');

Route::get('admin/language/website/create', [LanguageController::class,'admin_language_website_text_create'])
->name('admin_language_website_text_create');

Route::post('admin/language/website/store', [LanguageController::class,'admin_language_website_text_store'])
->name('admin_language_website_text_store');


Route::get('admin/language/notification/view', [LanguageController::class,'language_notification_text'])
    ->name('admin_language_notification_text');

Route::post('admin/language/notification/update', [LanguageController::class,'language_notification_text_update'])
    ->name('admin_language_notification_text_update');

Route::get('admin/language/notification/create', [LanguageController::class,'admin_language_notification_text_create'])
    ->name('admin_language_notification_text_create');

Route::post('admin/language/notification/store', [LanguageController::class,'admin_language_notification_text_store'])
    ->name('admin_language_notification_text_store');


Route::get('admin/language/admin-panel/view', [LanguageController::class,'language_admin_panel_text'])
    ->name('admin_language_admin_panel_text');

Route::get('admin/language/admin-panel/create', [LanguageController::class,'language_admin_panel_text_create'])
->name('language_admin_panel_text_create');

Route::post('admin/language/admin-panel/update', [LanguageController::class,'language_admin_panel_text_update'])
    ->name('admin_language_admin_panel_text_update');

Route::post('admin/language/admin-panel/store', [LanguageController::class,'language_admin_panel_text_store'])
    ->name('language_admin_panel_text_store');

/* --------------------------------------- */
/* Page Settings */
/* --------------------------------------- */
Route::get('admin/page-home/edit', [PageHomeController::class,'edit'])
    ->name('admin_page_home_edit');
Route::post('admin/page-home/update', [PageHomeController::class,'update'])
    ->name('admin_page_home_update');

Route::get('admin/page-about/edit', [PageAboutController::class,'edit'])
    ->name('admin_page_about_edit');
Route::post('admin/page-about/update', [PageAboutController::class,'update'])
    ->name('admin_page_about_update');

Route::get('admin/page-blog/edit', [PageBlogController::class,'edit'])
    ->name('admin_page_blog_edit');
Route::post('admin/page-blog/update', [PageBlogController::class,'update'])
    ->name('admin_page_blog_update');

Route::get('admin/page-faq/edit', [PageFaqController::class,'edit'])
    ->name('admin_page_faq_edit');
Route::post('admin/page-faq/update', [PageFaqController::class,'update'])
    ->name('admin_page_faq_update');

Route::get('admin/page-contact/edit', [PageContactController::class,'edit'])
    ->name('admin_page_contact_edit');
Route::post('admin/page-contact/update', [PageContactController::class,'update'])
    ->name('admin_page_contact_update');

Route::get('admin/page-pricing/edit', [PagePricingController::class,'edit'])
    ->name('admin_page_pricing_edit');
Route::post('admin/page-pricing/update', [PagePricingController::class,'update'])
    ->name('admin_page_pricing_update');

Route::get('admin/page-property-category/edit', [PagePropertyCategoryController::class,'edit'])
    ->name('admin_page_property_category_edit');
Route::post('admin/page-property-category/update', [PagePropertyCategoryController::class,'update'])
    ->name('admin_page_property_category_update');

Route::get('admin/page-property-location/edit', [PagePropertyLocationController::class,'edit'])
    ->name('admin_page_property_location_edit');
Route::post('admin/page-property-location/update', [PagePropertyLocationController::class,'update'])
    ->name('admin_page_property_location_update');

Route::get('admin/page-property/edit', [PagePropertyController::class,'edit'])
    ->name('admin_page_property_edit');
Route::post('admin/page-property/update', [PagePropertyController::class,'update'])
    ->name('admin_page_property_update');

Route::get('admin/page-term/edit', [PageTermController::class,'edit'])
    ->name('admin_page_term_edit');
Route::post('admin/page-term/update', [PageTermController::class,'update'])
    ->name('admin_page_term_update');

Route::get('admin/page-privacy/edit', [PagePrivacyController::class,'edit'])
    ->name('admin_page_privacy_edit');
Route::post('admin/page-privacy/update', [PagePrivacyController::class,'update'])
    ->name('admin_page_privacy_update');

Route::get('admin/page-other/edit', [PageOtherController::class,'edit'])
    ->name('admin_page_other_edit');
Route::post('admin/page-other/update', [PageOtherController::class,'update'])
    ->name('admin_page_other_update');

    Route::get('admin/dream-property-location/edit', [PageDreamPropertyController::class,'edit'])
    ->name('admin_dream_property_location_edit');
Route::post('admin/dream-property-location/update', [PageDreamPropertyController::class,'update'])
    ->name('admin_dream_property_location_update');



/* --------------------------------------- */
/* FAQ - Admin */
/* --------------------------------------- */
Route::get('admin/news/view', [FaqControllerForAdmin::class,'index'])
    ->name('admin_news_view');

Route::get('admin/faq/create', [FaqControllerForAdmin::class,'create'])
    ->name('admin_faq_create');

Route::post('admin/faq/store', [FaqControllerForAdmin::class,'store'])
    ->name('admin_faq_store');

Route::get('admin/faq/delete/{id}', [FaqControllerForAdmin::class,'destroy'])
    ->name('admin_faq_delete');

Route::get('admin/faq/edit/{id}', [FaqControllerForAdmin::class,'edit'])
    ->name('admin_faq_edit');

Route::post('admin/faq/update/{id}', [FaqControllerForAdmin::class,'update'])
    ->name('admin_faq_update');

Route::get('admin/news-status/{id}', [FaqControllerForAdmin::class,'news_change_status']);

Route::get('admin/department', [FaqControllerForAdmin::class,'department'])->name('department');
Route::get('admin/department-status/{id}', [FaqControllerForAdmin::class,'department_status']);
Route::get('admin/department-create', [FaqControllerForAdmin::class,'department_create'])->name('department_create');
Route::post('admin/department-store', [FaqControllerForAdmin::class,'department_store'])->name('department_store');

Route::get('admin/department/delete/{id}', [FaqControllerForAdmin::class,'department_destroy'])
    ->name('department_destroy');

Route::get('admin/support', [FaqControllerForAdmin::class,'support'])->name('support');
Route::get('admin/support-edit/{id}', [FaqControllerForAdmin::class,'support_edit'])->name('support_edit');

Route::post('admin/admin_support_update/{id}', [FaqControllerForAdmin::class,'admin_support_update'])
    ->name('admin_support_update');

Route::get('admin/support-status/{id}', [FaqControllerForAdmin::class,'support_status'])->name('support_status');

Route::get('admin/gift', [FaqControllerForAdmin::class,'gift'])->name('gift');

    

/* --------------------------------------- */
/* Package - Admin */
/* --------------------------------------- */
Route::get('admin/package/view', [PackageControllerForAdmin::class,'index'])
    ->name('admin_package_view');

Route::get('admin/package/create', [PackageControllerForAdmin::class,'create'])
    ->name('admin_package_create');

Route::post('admin/package/store', [PackageControllerForAdmin::class,'store'])
    ->name('admin_package_store');

Route::get('admin/package/delete/{id}', [PackageControllerForAdmin::class,'destroy'])
    ->name('admin_package_delete');

Route::get('admin/package/edit/{id}', [PackageControllerForAdmin::class,'edit'])
    ->name('admin_package_edit');

Route::post('admin/package/update/{id}', [PackageControllerForAdmin::class,'update'])
    ->name('admin_package_update');



/* --------------------------------------- */
/* Email Template - Admin */
/* --------------------------------------- */
Route::get('admin/email-template/view', [EmailTemplateController::class,'index'])
    ->name('admin_email_template_view');

Route::get('admin/email-template/edit/{id}', [EmailTemplateController::class,'edit'])
    ->name('admin_email_template_edit');

Route::post('admin/email-template/update/{id}', [EmailTemplateController::class,'update'])
    ->name('admin_email_template_update');


/* --------------------------------------- */
/* Social Media - Admin */
/* --------------------------------------- */
Route::get('admin/social-media/view', [SocialMediaItemController::class,'index'])
    ->name('admin_social_media_view');

Route::get('admin/social-media/create', [SocialMediaItemController::class,'create'])
    ->name('admin_social_media_create');

Route::post('admin/social-media/store', [SocialMediaItemController::class,'store'])
    ->name('admin_social_media_store');

Route::get('admin/social-media/delete/{id}', [SocialMediaItemController::class,'destroy'])
    ->name('admin_social_media_delete');

Route::get('admin/social-media/edit/{id}', [SocialMediaItemController::class,'edit'])
    ->name('admin_social_media_edit');

Route::post('admin/social-media/update/{id}', [SocialMediaItemController::class,'update'])
    ->name('admin_social_media_update');




/* --------------------------------------- */
/* Purchase History - Admin */
/* --------------------------------------- */
Route::get('admin/purchase-history/view', [PurchaseHistoryControllerForAdmin::class,'index'])
    ->name('admin_purchase_history_view');

Route::get('admin/purchase-history/detail/{id}', [PurchaseHistoryControllerForAdmin::class,'detail'])
    ->name('admin_purchase_history_detail');

Route::get('admin/purchase-history/invoice/{id}', [PurchaseHistoryControllerForAdmin::class,'invoice'])
    ->name('admin_purchase_history_invoice');



/* --------------------------------------- */
/* Customer - Admin */
/* --------------------------------------- */
Route::get('admin/customer/view', [CustomerControllerForAdmin::class,'index'])
    ->name('admin_customer_view');

Route::get('admin/customer/detail/{id}', [CustomerControllerForAdmin::class,'detail'])
    ->name('admin_customer_detail');

Route::post('admin/customer/admin_customer_detail/{id}', [CustomerControllerForAdmin::class,'edit_customer'])
->name('edit_customer');

Route::post('admin/customer/edit_customer_status/{id}', [CustomerControllerForAdmin::class,'edit_customer_status'])
->name('edit_customer_status');

Route::get('admin/customer/admin_downline_view', [CustomerControllerForAdmin::class,'admin_downline_view'])
->name('admin_downline_view');

Route::get('admin/customer/admin_direct_view', [CustomerControllerForAdmin::class,'admin_direct_view'])
->name('admin_direct_view');

Route::get('admin/customer/admin_activate_member', [CustomerControllerForAdmin::class,'admin_activate_member'])
->name('admin_activate_member');

Route::post('admin/customer/admin_activate_member', [CustomerControllerForAdmin::class,'activate_member'])
->name('admin_activate_member');


Route::get('admin/customer/getMemberName', [CustomerControllerForAdmin::class,'getMemberName'])
->name('get_member_name');

Route::get('admin/customer/admin_commitment_history', [CustomerControllerForAdmin::class,'admin_commitment_history'])
->name('admin_commitment_history');

Route::get('admin/customer/admin_payment_report_view', [CustomerControllerForAdmin::class,'admin_payment_report_view'])
->name('admin_payment_report_view');



Route::get('admin/customer/view_sponsor_help', [CustomerControllerForAdmin::class,'view_sponsor_help'])
->name('view_sponsor_help');



Route::get('admin/customer/delete/{id}', [CustomerControllerForAdmin::class,'destroy'])
    ->name('admin_customer_delete');

Route::get('admin/customer-status/{id}', [CustomerControllerForAdmin::class,'change_status']);



Route::get('admin/clear-database', [ClearDatabaseController::class,'index'])
    ->name('admin_clear_database');

Route::get('active-users', [CustomerControllerForAdmin::class,'active_users']);//dome
Route::get('get-sponser/{id}', [CustomerControllerForAdmin::class,'get_sponser']);//dome
Route::get('admin_redis_view', [CustomerControllerForAdmin::class,'redis_view'])->name('redis');//dome
