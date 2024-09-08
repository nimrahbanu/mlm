@extends('front.app_front')

@section('content')

    <div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$property_location_page_data->banner) }}')">
        <div class="page-banner-bg"></div>
        <h1>{{ $property_location_page_data->name }}</h1>
        <nav>
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
                <li class="breadcrumb-item active">{{ $property_location_page_data->name }}</li>
            </ol>
        </nav>
    </div>

    <div class="page-content popular-city">
        <div class="container">
            <div class="row">
                @foreach($orderwise_property_locations as $row)
                    @if($row->total == '')
                        @php $row->total = 0; @endphp
                    @endif
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <div class="popular-city-item" style="background-image: url('{{ asset('uploads/property_location_photos/'.$row->property_location_photo) }}');">
                            <div class="bg"></div>
                            <div class="text">
                                <h4>{{ $row->property_location_name }}</h4>

                                @php
                                    $qty = 0;
                                    $locationProperties = App\Models\Property::where('property_location_id', $row->id)->where('property_status','Active')->get();
                                    foreach ($locationProperties as $key => $categoryProperty) {
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

                                <p>{{ $qty }} {{ PROPERTIES }}</p>
                            </div>
                            <a href="{{ route('front_property_location_detail',$row->property_location_slug) }}"></a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection
