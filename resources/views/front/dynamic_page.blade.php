@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$dynamic_page_detail->dynamic_page_banner) }}')">
    <div class="page-banner-bg"></div>
    <h1>{{ $dynamic_page_detail->dynamic_page_name }}</h1>
    <nav>
        <ol class="breadcrumb justify-content-center">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
            <li class="breadcrumb-item active">{{ $dynamic_page_detail->dynamic_page_name }}</li>
        </ol>
    </nav>
</div>

<div class="page-content">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                {!! clean($dynamic_page_detail->dynamic_page_content) !!}
            </div>
        </div>
    </div>
</div>

@endsection
