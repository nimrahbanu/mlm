@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$property_page_data->banner) }}')">
    <div class="page-banner-bg"></div>
    <h1>{{ $property_page_data->name }}</h1>
    <nav>
        <ol class="breadcrumb justify-content-center">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
            <li class="breadcrumb-item active">{{ $property_page_data->name }}</li>
        </ol>
    </nav>
</div>

<div class="page-content">
    <div class="container">
        <div class="row property pt_0 pb_0">

            <div class="col-lg-4 col-md-6 col-sm-12">

                <form id="searchFormId">

                    <div class="property-filter">

                        <div class="lf-heading">
                            {{ FILTERS }}
                        </div>

                        <div class="lf-widget">
                            <input type="text" id="text" name="text" class="form-control" placeholder="{{ FIND_ANYTHING }}" value="{{ request()->has('text') ? request()->get('text') : '' }}">
                        </div>

                        <div class="lf-widget">
                            <h2>{{ TYPE }}</h2>

                            <select name="property_type" class="form-control" id="property_type">
								<option value="" >{{ ALL }}</option>
                                @if (request()->has('property_type'))
                                    <option {{ request()->get('property_type') ==  'sale' ? 'selected' : ''  }}  value="sale" >{{ FOR_SALE }}</option>
								    <option {{ request()->get('property_type') ==  'rent' ? 'selected' : ''  }} value="rent" >{{ FOR_RENT }}</option>
                                    <option  {{ request()->get('property_type') ==  'For Home Stay' ? 'selected' : ''  }} value="For Home Stay">{{ FOR_HOME_STAY }}</option>
                                    <option  {{ request()->get('property_type') ==  'For Construction' ? 'selected' : ''  }} value="For Construction">{{ FOR_CONSTRUCTION }}</option>
                                @else
                                <option value="sale" >{{ FOR_SALE }}</option>
								<option value="rent" >{{ FOR_RENT }}</option>
    							<option value="For Home Stay">{{ FOR_HOME_STAY }}</option>
								<option value="For Construction">{{ FOR_CONSTRUCTION }}</option>
                                @endif


							</select>
                        </div>

                        @php
                            $sort_cat = [];
                            if(request()->has('category')){
                                foreach(request()->get('category') as $cat){
                                    array_push($sort_cat,(int)$cat);
                                }
                            }
                        @endphp

                        <div class="lf-widget">
                            <h2>{{ CATEGORIES }}</h2>
                            @php $ii=0; @endphp
                            @foreach($property_categories as $index => $row)
                                <div class="form-check">
                                    <input {{ in_array($row->id ,$sort_cat) ? 'checked' : '' }} name="category[]" class="form-check-input" type="checkbox" value="{{ $row->id }}" id="cat{{ $index }}">
                                    <label class="form-check-label" for="cat{{ $index }}">
                                        {{ $row->property_category_name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        @php
                            $sort_aminity = [];
                            if(request()->has('amenity')){
                                foreach(request()->get('amenity') as $cat){
                                    array_push($sort_aminity,(int)$cat);
                                }
                            }
                        @endphp

                        <div class="lf-widget">
                            <h2>{{ AMENITIES }}</h2>
                            @foreach($amenities as $index => $row)
                                <div class="form-check">
                                    <input {{ in_array($row->id ,$sort_aminity) ? 'checked' : '' }} name="amenity[]" class="form-check-input" type="checkbox" value="{{ $row->id }}" id="amn{{ $index }}" >
                                    <label class="form-check-label" for="amn{{ $index }}">
                                        {{ $row->amenity_name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>


                        @php
                            $sort_loc = [];
                            if(request()->has('location')){
                                foreach(request()->get('location') as $cat){
                                    array_push($sort_loc,(int)$cat);
                                }
                            }
                        @endphp

                        <div class="lf-widget">
                            <h2>{{ LOCATIONS }}</h2>
                            @foreach($property_locations as $index => $row)
                                <div class="form-check">
                                    <input {{ in_array($row->id ,$sort_loc) ? 'checked' : '' }} name="location[]" class="form-check-input" type="checkbox" value="{{ $row->id }}" id="loc{{ $index }}">
                                    <label class="form-check-label" for="loc{{ $index }}">
                                        {{ $row->property_location_name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="form-group">
                            <input type="submit" class="form-control filter-button" value="{{ FILTER }}">
                        </div>

                    </div>


                </form>


            </div>

            <div class="col-lg-8 col-md-6 col-sm-12">
                <div class="contruction-fillter">
                    <ul>
                        <li><a href="#" class="active">All</a></li>
                        <li><a href="#">Ongoing</a></li>
                        <li><a href="#">Completed</a></li>
                        <li><a href="#">Upcomming</a></li>
                    </ul>
                </div>

                <div class="right-area">

                    <div class="row d-none" id="loader-area">
                        <div class="col-12 text-center mt-5">
                            <div>
                                <img src="{{ asset('loader.gif') }}" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="row property-list" id="content-area">
                        <div class="col-12 text-center mt-5">
                            <div>
                                <img src="{{ asset('loader.gif') }}" alt="">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>



<script>
    let loaderHtml = $("#loader-area").html();
    (function($) {
        "use strict";
        $(document).ready(function () {

            loadPropertyUsingAjax();

            $("#searchFormId").on('submit', function(e){
                e.preventDefault();
                submitSearchForm()
            })

            $("#property_type").on('change', function(){
                submitSearchForm()
            })

            $(".form-check-input").on('click', function(){
                submitSearchForm()
            })

            $("#text").on('keyup', function(e){
                if(e.target.keyCode === '13'){
                    submitSearchForm()
                }
            })

        });
    })(jQuery);

    function loadPropertyUsingAjax(){
        submitSearchForm()
    }

    function submitSearchForm(){
        $('#content-area').html(loaderHtml);

        $.ajax({
            type: 'get',
            data: $('#searchFormId').serialize(),
            url: "{{ route('search-front_property_result') }}",
            success: function (response) {
                $('#content-area').html(response);
            },
            error: function(err) {}
        });
    }


    function addToWishlist(id){
        let url = "{{ url('customer/ajax-wishlist/add/') }}" + "/" +id;
        $.ajax({
            type: 'get',
            url: url,
            success: function (response) {
                if(response.is_success){
                     Swal.fire({icon: 'success',title: '',html: response.message})

                }else{
                    Swal.fire({icon: 'error',title: '',html: response.message})

                }

            },
            error: function(err) {}
        });

    }


</script>

@endsection
