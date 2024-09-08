@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$property_category_page_data->banner) }}')">
    <div class="page-banner-bg"></div>
    <h1>{{ $property_category_page_data->name }}</h1>
    <nav>
        <ol class="breadcrumb justify-content-center">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
            <li class="breadcrumb-item active">{{ $property_category_page_data->name }}</li>
        </ol>
    </nav>
</div>

<div class="page-content popular-category">
    <div class="container">

        <!--  -->

        <div class="popular-category-box">
        @foreach($orderwise_property_categories as $row)
                @if($row->total == '')
                    @php $row->total = 0; @endphp
                @endif
					<div class="popular-category-item" style="background-image: url({{ asset('uploads/property_category_photos/'.$row->property_category_photo) }});">
						<div class="bg"></div>
						<div class="text">
							<h4>{{ $row->property_category_name }}</h4>

                            @php
                                $qty = 0;
                                $categoryProperties = App\Models\Property::where('property_category_id', $row->id)->where('property_status','Active')->get();
                                foreach ($categoryProperties as $key => $categoryProperty) {
                                    if($categoryProperty->user_id != 0){
                                        $activePackage = App\Models\PackagePurchase::where('user_id',$categoryProperty->user_id)->where('currently_active',1)->first();
                                        if($activePackage->package_end_date >= date('Y-m-d')){
                                            $qty += 1;
                                        }
                                    }else{
                                        $qty += 1;
                                    }
                                }
                            @endphp


							<p class="text-uppercase mb-3">{{ $qty }} {{ PROPERTIES }}</p>
							<p class="d-none d-md-block">{{ Str::words($row->property_category_description,15) }}</p>

						</div>
						<a href="{{ route('front_property_category_detail',$row->property_category_slug) }}"></a>
					</div>
			@endforeach
		</div>
    </div>
</div>

@endsection
