<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use App\Models\Amenity;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Currency;
use App\Models\DynamicPage;
use App\Models\Faq;
use App\Models\Package;
use App\Models\PackagePurchase;
use App\Models\Property;
use App\Models\PropertyAdditionalFeature;
use App\Models\PropertyAmenity;
use App\Models\PropertyCategory;
use App\Models\PropertyLocation;
use App\Models\PropertyPhoto;
use App\Models\PropertySocialItem;
use App\Models\PropertyVideo;
use App\Models\Review;
use App\Models\SocialMediaItem;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailToAllSubscribers;
use DB;
use Auth;

class ClearDatabaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin:admin');
    }

    public function index()
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        
        // amenities
        Amenity::truncate();

        // blogs
        $blog_data = Blog::get();
        foreach($blog_data as $row) {
            @unlink(public_path('uploads/post_photos/'.$row->post_photo));
        }
        Blog::truncate();

        // categories
        Category::truncate();

        // comments
        Comment::truncate();

        // currencies
        Currency::truncate();
        $currency = new Currency();
        $currency->name = 'USD';
        $currency->symbol = '$';
        $currency->value = '1';
        $currency->is_default = 'Yes';
        $currency->save();


        // dynamic_pages
        $dynamic_page_data = DynamicPage::get();
        foreach($dynamic_page_data as $row) {
            @unlink(public_path('uploads/page_banners/'.$row->dynamic_page_banner));
        }
        DynamicPage::truncate();

        
        // email_templates
        // can not remove

        // faqs
        Faq::truncate();

        // general_settings
        // can not remove

        // home_advertisements
        // can not remove

        // language_admin_panel_texts
        // can not remove

        // language_menu_texts
        // can not remove

        // language_notification_texts
        // can not remove

        // language_website_texts
        // can not remove

        // packages
        Package::truncate();

        // package_purchases
        PackagePurchase::truncate();

        // page_about_items
        // can not remove

        // page_blog_items
        // can not remove

        // page_contact_items
        // can not remove

        // page_faq_items
        // can not remove

        // page_home_items
        // can not remove

        // page_other_items
        // can not remove

        // page_pricing_items
        // can not remove

        // page_privacy_items
        // can not remove

        // page_property_category_items
        // can not remove

        // page_property_items
        // can not remove

        // page_property_location_items
        // can not remove

        // page_term_items
        // can not remove

        // properties
        $property_data = Property::get();
        foreach($property_data as $row) {
            @unlink(public_path('uploads/property_featured_photos/'.$row->property_featured_photo));
        }
        Property::truncate();
        
        
        // property_additional_features
        PropertyAdditionalFeature::truncate();


        // property_amenities
        PropertyAmenity::truncate();


        // property_categories
        $property_category_data = PropertyCategory::get();
        foreach($property_category_data as $row) {
            @unlink(public_path('uploads/property_category_photos/'.$row->property_category_photo));
        }
        PropertyCategory::truncate();


        // property_locations
        $property_location_data = PropertyLocation::get();
        foreach($property_location_data as $row) {
            @unlink(public_path('uploads/property_location_photos/'.$row->property_location_photo));
        }
        PropertyLocation::truncate();


        // property_photos
        $property_photo_data = PropertyPhoto::get();
        foreach($property_photo_data as $row) {
            @unlink(public_path('uploads/property_photos/'.$row->photo));
        }
        PropertyPhoto::truncate();

        
        // property_social_items
        PropertySocialItem::truncate();
        
        // property_videos
        PropertyVideo::truncate();


        // reviews
        Review::truncate();


        // social_media_items
        SocialMediaItem::truncate();


        // testimonials
        $testimonial_data = Testimonial::get();
        foreach($testimonial_data as $row) {
            @unlink(public_path('uploads/testimonials/'.$row->photo));
        }
        Testimonial::truncate();
        
        
        // users
        $user_data = User::get();
        foreach($user_data as $row) {
            if($row->photo!='') {
                @unlink(public_path('uploads/user_photos/'.$row->photo));    
            }
            if($row->banner!='') {
                @unlink(public_path('uploads/user_photos/'.$row->banner));    
            }
        }
        User::truncate();


        // wishlists
        Wishlist::truncate();
        
                
        

        return redirect()->back()->with('success', SUCCESS_DATABASE_CLEAR);
    }

}
