@php
$user = Auth::user();
$g_setting = \App\Models\GeneralSetting::where('id',1)->first();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    @if(isset($g_setting->favicon))
    <link rel="icon" type="image/png" href="{{ asset('uploads/site_photos/'.$g_setting->favicon) }}">
    @endif
    <title>{{ ADMIN_PANEL }}</title>

    @include('admin.app_styles')

    <link href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">

    @include('admin.app_scripts')

</head>

<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

        @php
            $route = Route::currentRouteName();
        @endphp

        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin_dashboard') }}">
            <div class="sidebar-brand-text mx-3 ttn">
                <div class="left">
                    @if(isset($g_setting->favicon))
                    <img src="{{ asset('uploads/site_photos/'.$g_setting->favicon) }}" alt="">
                    @endif
                </div>
                <div class="right">
                    {{ env('APP_NAME') }}
                </div>
            </div>
        </a>

        <!-- Divider -->
        <hr class="sidebar-divider my-0">

        <!-- Dashboard -->
        <li class="nav-item {{ $route == 'admin_dashboard' ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin_dashboard') }}">
                <i class="fas fa-fw fa-home"></i>
                <span>{{ DASHBOARD }}</span>
            </a>
        </li>
      <!-- Customer -->
      
        <!-- General Settings -->
        <li class="nav-item {{ $route == 'admin_customer_view'||$route =='admin_downline_view'||$route =='admin_direct_view'||$route =='admin_activate_member' ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMember" aria-expanded="true" aria-controls="collapseMember">
                <i class="fas fa-folder"></i>
                <span>Manage Member</span>
            </a>
            <div id="collapseMember" class="collapse {{ $route == 'admin_customer_view'||$route == 'admin_downline_view'||$route =='admin_direct_view'||$route =='admin_social_media_edit'||$route == 'admin_currency_view'||$route == 'admin_activate_member'||$route == 'admin_currency_edit' ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('admin_customer_view') }}">View All Members</a>
                    <a class="collapse-item" href="{{ route('admin_downline_view') }}">View Downline Member</a>
                    <a class="collapse-item" href="{{ route('admin_direct_view') }}">View Direct Member</a>
                    <a class="collapse-item" href="{{ route('admin_activate_member') }}">Member Activation</a>
                </div>
            </div>
        </li>

        <!-- Language Settings -->
        <li class="nav-item {{ $route =='admin_commitment_history'||$route =='admin_payment_report_view'||$route =='admin_language_notification_text'||$route =='admin_language_admin_panel_text' ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLanguage" aria-expanded="true" aria-controls="collapseLanguage">
                <i class="fas fa-folder"></i>
                <span>Manage Transaction</span>
            </a>
            <div id="collapseLanguage" class="collapse {{ $route =='admin_commitment_history'||$route =='admin_payment_report_view'||$route =='admin_language_notification_text'||$route =='admin_language_admin_panel_text' ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item " href="{{ route('admin_commitment_history') }}">View Commitment History</a>
                    <a class="collapse-item " href="{{ route('admin_payment_report_view') }}">View GH PH Report</a>
                </div>
            </div>
        </li>
          <!-- Language Settings -->
          <li class="nav-item {{ $route =='view_sponsor_help' ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsetransaction" aria-expanded="true" aria-controls="collapsetransaction">
                <i class="fas fa-folder"></i>
                <span>Income Reports</span>

            </a>
            <div id="collapsetransaction" class="collapse {{ $route =='view_sponsor_help' ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="{{ route('view_sponsor_help') }}">View Sponsor Help</a>

                </div>
            </div>
        </li>


        <!-- Page Settings -->
        <!-- <li class="nav-item {{ $route =='View_sponsor_help' ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSponsor" aria-expanded="true" aria-controls="collapseSponsor">
                <i class="fas fa-folder"></i>
                <span>Income Reports</span>
            </a>
            <div id="collapseSponsor" class="collapse {{ $route =='View_sponsor_help' ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('view_sponsor_help') }}">View Sponsor Help</a>
                </div>
            </div>
        </li> -->


        <!-- Blog Settings -->
        <li class="nav-item {{ $route == 'admin_news_view'||$route == 'admin_category_create'||$route == 'admin_category_edit'||$route =='admin_blog_view'||$route =='admin_blog_create'||$route =='admin_blog_edit'||$route =='admin_comment_approved'||$route =='admin_comment_pending' ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBlog" aria-expanded="true" aria-controls="collapseBlog">
                <i class="fas fa-folder"></i>
                <span>Manage News</span>
            </a>
            <div id="collapseBlog" class="collapse {{ $route == 'admin_news_view'||$route == 'admin_category_create'||$route == 'admin_category_edit'||$route =='admin_blog_view'||$route =='admin_blog_create'||$route =='admin_blog_edit'||$route =='admin_comment_approved'||$route =='admin_comment_pending' ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">

                    <a class="collapse-item" href="{{ route('admin_news_view') }}">Add News</a>
                    <!-- <a class="collapse-item" href="{{ route('admin_news_view') }}">List News</a> -->

                </div>
            </div>
        </li>

        <!-- Website Settings -->
        <li class="nav-item {{ $route == 'admin_e_pin_master'||$route == 'e_pin_transfer'||$route == 'e_pin_used_view'||$route == 'admin_property_category_create'||$route == 'admin_testimonial_create'||$route == 'admin_testimonial_edit' ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseWebsite" aria-expanded="true" aria-controls="collapseWebsite">
                <i class="fas fa-folder"></i>
                <span>Manage E-pin </span>
            </a>
            <div id="collapseWebsite" class="collapse {{ $route == 'admin_e_pin_master'||$route == 'e_pin_transfer'||$route == 'e_pin_used_view'||$route == 'admin_testimonial_view'||$route == 'admin_testimonial_create'||$route == 'admin_testimonial_edit' ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('admin_e_pin_master') }}">E-Pin Master</a>
                    <a class="collapse-item" href="{{ route('e_pin_used_view') }}">Used E-Pin</a>
                    <a class="collapse-item" href="{{ route('e_pin_transfer') }} ">E-Pin Transfer</a>
                </div>
            </div>
        </li>


        <!-- Property Settings -->
        <li class="nav-item {{ $route == 'department'||$route == 'support' ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseProperty" aria-expanded="true" aria-controls="collapseProperty">
                <i class="fas fa-folder"></i>
                <span>Ticket Support</span>
            </a>
            <div id="collapseProperty" class="collapse {{ $route == 'department'||$route == 'support'  ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('department') }}">Department Mater</a>
                    <!-- <a class="collapse-item" href="{{ route('admin_amenity_view') }}">Priority Master</a>
                    <a class="collapse-item" href="{{ route('admin_property_view') }}">List Priority</a> -->
                    <a class="collapse-item" href="{{ route('support') }}">List Ticket</a>
                </div>
            </div>
        </li>

        <li class="nav-item {{ $route == 'gift' ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseGift" aria-expanded="true" aria-controls="collapseGift">
                <i class="fas fa-folder"></i>
                <span>Gift Feature</span>
            </a>
            <div id="collapseGift" class="collapse {{ $route == 'gift'||$route == 'admin_property_edit' ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('gift') }}">gift</a>
                
                </div>
            </div>
        </li>

        <li class="nav-item {{ $route == 'admin_setting_general'||$route =='admin_payment'||$route =='redis'||$route =='admin_social_media_view'||$route =='admin_social_media_create'||$route =='admin_social_media_store'||$route =='admin_social_media_edit' ? 'active' : '' }}">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSetting" aria-expanded="true" aria-controls="collapseSetting">
                <i class="fas fa-folder"></i>
                <span>{{ SETTINGS }}</span>
            </a>
            <div id="collapseSetting" class="collapse {{ $route == 'admin_setting_general'||$route =='redis'||$route == 'admin_payment'||$route == 'admin_social_media_view'||$route =='admin_social_media_create'||$route =='admin_social_media_store'||$route =='admin_social_media_edit'||$route == 'admin_currency_view'||$route == 'admin_currency_create'||$route == 'admin_currency_edit' ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="{{ route('admin_setting_general') }}">{{ GENERAL_SETTING }}</a>
                    <a class="collapse-item" href="{{ route('admin_payment') }}">{{ PAYMENT_SETTING }}</a>
                    <a class="collapse-item" href="{{ route('redis') }}">Redis</a>
                    <!-- <a class="collapse-item" href="{{ route('admin_social_media_view') }}">{{ SOCIAL_MEDIA }}</a> -->
                </div>
            </div>
        </li>


        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>
    </ul>
    <!-- End of Sidebar -->


    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <!-- Sidebar Toggle (Topbar) -->
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>

                <!-- Topbar Navbar -->
                <ul class="navbar-nav ml-auto">


                    <!-- Nav Item - Alerts -->
                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="btn btn-info btn-sm mt-3" href="{{ url('/') }}" target="_blank">
                            {{ VISIT_WEBSITE }}
                        </a>
                    </li>

                    <div class="topbar-divider d-none d-sm-block"></div>
                    <!-- Nav Item - User Information -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600">{{ $user->name }}</span>
                            <img class="img-profile rounded-circle" src="{{ asset('uploads/user_photos/'.$user->photo) }}">
                        </a>
                        <!-- Dropdown - User Information -->
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">

                            <a class="dropdown-item" href="{{ route('admin_profile_change') }}">
                                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> {{ CHANGE_PROFILE }}
                            </a>
                            <a class="dropdown-item" href="{{ route('admin_password_change') }}">
                                <i class="fas fa-unlock-alt fa-sm fa-fw mr-2 text-gray-400"></i> {{ CHANGE_PASSWORD }}
                            </a>
                            <a class="dropdown-item" href="{{ route('admin_photo_change') }}">
                                <i class="fas fa-image fa-sm fa-fw mr-2 text-gray-400"></i> {{ CHANGE_PHOTO }}
                            </a>
                            <a class="dropdown-item" href="{{ route('admin_banner_change') }}">
                                <i class="fas fa-image fa-sm fa-fw mr-2 text-gray-400"></i> {{ CHANGE_BANNER }}
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('admin_logout') }}">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> {{ LOGOUT }}
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            <!-- End of Topbar -->
            <!-- Begin Page Content -->
            <div class="container-fluid">

                @yield('admin_content')

            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content -->

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

@include('admin.app_scripts_footer')

</body>
</html>
