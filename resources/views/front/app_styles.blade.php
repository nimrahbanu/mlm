<!-- All CSS -->
<link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/jquery-ui.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/animate.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/select2-bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/meanmenu.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/spacing.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/jquery.timepicker.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/toastr.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}">
@php
$g_settings = \App\Models\GeneralSetting::where('id',1)->first();
@endphp
@if($g_settings->layout_direction == 'rtl')
    <link rel="stylesheet" href="{{ asset('frontend/css/rtl.css') }}">
@endif
