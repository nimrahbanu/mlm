@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$page_other_item->customer_panel_page_banner) }}')">
	<div class="page-banner-bg"></div>
	<h1>{{ PACKAGES }}</h1>
	<nav>
		<ol class="breadcrumb justify-content-center">
			<li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
			<li class="breadcrumb-item active">{{ PACKAGES }}</li>
		</ol>
	</nav>
</div>


<div class="page-content">
	<div class="container">
		<div class="row">
			<div class="col-md-3">
				<div class="user-sidebar">
					@include('front.customer_sidebar')
				</div>
			</div>
			<div class="col-md-9">

				<div class="row pricing">
					@foreach($package as $row)
					<div class="col-lg-4">
						<div class="card mb-5 mb-lg-0">
							<div class="card-body">
								<h5 class="card-title text-muted text-uppercase text-center">{{ $row->package_name }}</h5>
								<h6 class="card-price text-center">{{ session()->get('currency_symbol') }}{{ round($row->package_price*session()->get('currency_value'),2) }}<span class="period">/{{ $row->valid_days }} {{ DAYS }}</span></h6>
								<hr>
								<ul class="fa-ul">
									<li><span class="fa-li"><i class="fas fa-check"></i></span>{{ $row->total_properties }} {{ PROPERTY_ALLOWED }}</li>
									<li><span class="fa-li"><i class="fas fa-check"></i></span>{{ $row->total_properties }} {{ AMENITIES_PER_PROPERTY }}</li>
									<li><span class="fa-li"><i class="fas fa-check"></i></span>{{ $row->total_photos }} {{ PHOTOS_PER_PROPERTY }}</li>
									<li><span class="fa-li"><i class="fas fa-check"></i></span>{{ $row->total_videos }} {{ VIDEOS_PER_PROPERTY }}</li>
									<li><span class="fa-li"><i class="fas fa-check"></i></span>{{ $row->total_social_items }} {{ SOCIAL_ITEMS_PER_PROPERTY }}</li>
									<li><span class="fa-li"><i class="fas fa-check"></i></span>{{ $row->total_additional_features }} {{ ADDITIONAL_FEATURES_PER_PROPERTY }}</li>
                                    <li>
                                        @if($row->allow_featured == 'Yes')
                                            <span class="fa-li"><i class="fas fa-check"></i></span>
                                            {{ FEATURED_PROPERTY_ALLOWED }}
                                        @else
                                            <span class="fa-li"><i class="fas fa-times"></i></span>
                                            {{ FEATURED_PROPERTY_NOT_ALLOWED }}
                                        @endif
                                    </li>
								</ul>

								@if($row->package_type == 'Free')
								<a href="{{ route('customer_package_free_enroll',$row->id) }}" class="btn btn-block btn-primary">
                                    {{ ENROLL_NOW }}
								</a>

								@else
								<a href="{{ route('customer_package_buy',$row->id) }}" class="btn btn-block btn-primary">
                                    {{ BUY_NOW }}
								</a>
								@endif

							</div>
						</div>
					</div>
					@endforeach

				</div>

			</div>
		</div>
	</div>
</div>

@endsection
