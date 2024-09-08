@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$page_other_item->customer_panel_page_banner) }}')">
	<div class="page-banner-bg"></div>
	<h1>{{ EDIT_PROPERTY }}</h1>
	<nav>
		<ol class="breadcrumb justify-content-center">
			<li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
			<li class="breadcrumb-item active">{{ EDIT_PROPERTY }}</li>
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

				<form action="{{ route('customer_property_update',$property->id) }}" method="post" enctype="multipart/form-data">
        			@csrf
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ PROPERTY_NAME }} *</label>
								<input type="text" name="property_name" class="form-control" value="{{ $property->property_name }}" autofocus>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ PROPERTY_SLUG }}</label>
								<input type="text" name="property_slug" class="form-control" value="{{ $property->property_slug }}">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ PROPERTY_DESCRIPTION }} *</label>
								<textarea name="property_description" class="form-control editor" cols="30" rows="10">{{ $property->property_description }}</textarea>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="">{{ CATEGORY }}</label>
								<select name="property_category_id" class="form-control select2">
                                    @foreach($property_category as $row)
                                    <option value="{{ $row->id }}" @if($row->id == $property->property_category_id) selected @endif>{{ $row->property_category_name }}</option>
                                    @endforeach
                                </select>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="">{{ LOCATION }}</label>
								<select name="property_location_id" class="form-control select2">
                                    @foreach($property_location as $row)
                                    <option value="{{ $row->id }}" @if($row->id == $property->property_location_id) selected @endif>{{ $row->property_location_name }}</option>
                                    @endforeach
                                </select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="">{{ ADDRESS }}</label>
								<textarea name="property_address" class="form-control h-70" cols="30" rows="10">{{ $property->property_address }}</textarea>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="">{{ PHONE_NUMBER }}</label>
								<textarea name="property_phone" class="form-control h-70" cols="30" rows="10">{{ $property->property_phone }}</textarea>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="">{{ EMAIL_ADDRESS }}</label>
								<textarea name="property_email" class="form-control h-70" cols="30" rows="10">{{ $property->property_email }}</textarea>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="">{{ MAP_IFRAME_CODE }}</label>
								<textarea name="property_map" class="form-control h-70" cols="30" rows="10">{{ $property->property_map }}</textarea>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ WEBSITE }}</label>
								<input type="text" name="property_website" class="form-control" value="{{ $property->property_website }}">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ EXISTING_FEATURED_PHOTO }}</label>
								<div>
                                    <img src="{{ asset('uploads/property_featured_photos/'.$property->property_featured_photo) }}" class="w-200" alt="">
                                </div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ CHANGE_PHOTO }}</label>
								<div>
                                    <input type="file" name="property_featured_photo">
                                </div>
							</div>
						</div>
					</div>


					<h4 class="mt_30">{{ FEATURES }}</h4>
					<div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ PRICE }} *</label>
                                <input type="text" name="property_price" class="form-control" value="{{ $property->property_price }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ BEDROOM }} *</label>
                                <input type="text" name="property_bedroom" class="form-control" value="{{ $property->property_bedroom }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ BATHROOM }} *</label>
                                <input type="text" name="property_bathroom" class="form-control" value="{{ $property->property_bathroom }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ SIZE }} *</label>
                                <input type="text" name="property_size" class="form-control" value="{{ $property->property_size }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ BUILT_YEAR }}</label>
                                <input type="text" name="property_built_year" class="form-control" value="{{ $property->property_built_year }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ GARAGE }}</label>
                                <input type="text" name="property_garage" class="form-control" value="{{ $property->property_garage }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ BLOCK }}</label>
                                <input type="text" name="property_block" class="form-control" value="{{ $property->property_block }}">
                            </div>
                        </div>
						<div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ FLOOR }}</label>
                                <input type="text" name="property_floor" class="form-control" value="{{ $property->property_floor }}">
                            </div>
                        </div>
						<div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ TYPE }}</label>
                                <select name="property_type" class="form-control">
									<option value="For Sale" @if($property->property_type == 'For Sale') selected @endif>For Sale</option>
									<option value="For Rent" @if($property->property_type == 'For Rent') selected @endif>For Rent</option>
								</select>
                            </div>
                        </div>
                    </div>


					<h4 class="mt_30">{{ OPENING_HOUR }}</h4>
					<div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ MONDAY }}</label>
                                <input type="text" name="property_oh_monday" class="form-control" value="{{ $property->property_oh_monday }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ TUESDAY }}</label>
                                <input type="text" name="property_oh_tuesday" class="form-control" value="{{ $property->property_oh_tuesday }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ WEDNESDAY }}</label>
                                <input type="text" name="property_oh_wednesday" class="form-control" value="{{ $property->property_oh_wednesday }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ THURSDAY }}</label>
                                <input type="text" name="property_oh_thursday" class="form-control" value="{{ $property->property_oh_thursday }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ FRIDAY }}</label>
                                <input type="text" name="property_oh_friday" class="form-control" value="{{ $property->property_oh_friday }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ SATURDAY }}</label>
                                <input type="text" name="property_oh_saturday" class="form-control" value="{{ $property->property_oh_saturday }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ SUNDAY }}</label>
                                <input type="text" name="property_oh_sunday" class="form-control" value="{{ $property->property_oh_sunday }}">
                            </div>
                        </div>
                    </div>


					<h4 class="mt_30">{{ EXISTING_SOCIAL_MEDIA }}</h4>
					<div class="row">

						@if($property_social_items->isEmpty())
						<div class="col-md-12">
							<span class="text-danger">{{ NO_RESULT_FOUND }}</span>
						</div>
						@else
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    @foreach($property_social_items as $row)
                                    <tr>
                                        <td>
                                        	@if($row->social_icon == 'Facebook')
                                        	@php $icon_code = 'fab fa-facebook-f'; @endphp

                                        	@elseif($row->social_icon == 'Twitter')
                                        	@php $icon_code = 'fab fa-twitter'; @endphp

                                        	@elseif($row->social_icon == 'LinkedIn')
                                        	@php $icon_code = 'fab fa-linkedin-in'; @endphp

                                        	@elseif($row->social_icon == 'YouTube')
                                        	@php $icon_code = 'fab fa-youtube'; @endphp

                                        	@elseif($row->social_icon == 'Pinterest')
                                        	@php $icon_code = 'fab fa-pinterest-p'; @endphp

                                        	@elseif($row->social_icon == 'GooglePlus')
                                        	@php $icon_code = 'fab fa-google-plus-g'; @endphp

                                        	@elseif($row->social_icon == 'Instagram')
                                        	@php $icon_code = 'fab fa-instagram'; @endphp

                                        	@endif
                                            <i class="{{ $icon_code }}"></i>
                                        </td>
                                        <td>{{ $row->social_url }}</td>
                                        <td>
                                            <a href="{{ route('customer_property_delete_social_item',$row->id) }}" class="badge badge-danger fz-14 mt_5" onClick="return confirm('{{ ARE_YOU_SURE }}');">{{ DELETE }}</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                        @endif

                    </div>


					<h4 class="mt_30">{{ NEW_SOCIAL_MEDIA }}</h4>
					<span class="btn btn-success add_social_more"><i class="fas fa-plus"></i></span>
					<div class="social_item">

					</div>

					<h4 class="mt_30">{{ AMENITIES }}</h4>
					<div class="row">
                        @php $i=0; @endphp
                        @foreach($amenity as $row)
                        @php $i++; @endphp
                        <div class="col-md-4">
                            <div class="form-check mb_10">
                                <input class="form-check-input amenity_check" name="amenity[]" type="checkbox" value="{{ $row->id }}" id="amenities{{ $i }}" @if(in_array($row->id,$existing_amenities_array)) checked @endif>
                                <label class="form-check-label" for="amenities{{ $i }}">
                                    {{ $row->amenity_name }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>


                    <h4 class="mt_30">{{ EXISTING_PHOTOS }}</h4>
                    <div class="row">
                    	@if($property_photos->isEmpty())
						<div class="col-md-12">
							<span class="text-danger">No Photos Found</span>
						</div>
						@else
                        @foreach($property_photos as $row)
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div>
                                        <img src="{{ asset('uploads/property_photos/'.$row->photo) }}" class="w-100-p property-photo-item" alt=""><br>
                                        <a href="{{ route('customer_property_delete_photo',$row->id) }}" class="badge badge-danger fz-14 mt_5" onClick="return confirm('{{ ARE_YOU_SURE }}');">{{ DELETE }}</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @endif
                    </div>

					<h4 class="mt_30">{{ NEW_PHOTOS }}</h4>
					<span class="btn btn-success add_photo_more"><i class="fas fa-plus"></i></span>
					<div class="photo_item">

					</div>


					<h4 class="mt_30">{{ EXISTING_VIDEOS }}</h4>
					<div class="row">
						@if($property_videos->isEmpty())
						<div class="col-md-12">
							<span class="text-danger">{{ NO_RESULT_FOUND }}</span>
						</div>
						@else
                        @foreach($property_videos as $row)
                        <div class="col-md-4 existing-video">
                            <iframe width="560" height="315" src="https://www.youtube.com/embed/{{ $row->youtube_video_id }}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            <br>
                            <a href="{{ route('customer_property_delete_video',$row->id) }}" class="badge badge-danger fz-14 mt_5" onClick="return confirm('{{ ARE_YOU_SURE }}');">{{ DELETE }}</a>
                        </div>
                        @endforeach
                        @endif
                    </div>


					<h4 class="mt_30">{{ NEW_VIDEOS }}</h4>
					<span class="btn btn-success add_video_more"><i class="fas fa-plus"></i></span>

					<div class="video_item">

					</div>



					<h4 class="mt_30">{{ EXISTING_ADDITIONAL_FEATURES }}</h4>
					<div class="row">
						@if($property_additional_features->isEmpty())
						<div class="col-md-12">
							<span class="text-danger">{{ NO_RESULT_FOUND }}</span>
						</div>
						@else
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                @foreach($property_additional_features as $row)
                                    <tr>
                                        <td>{{ $row->additional_feature_name }}</td>
                                        <td>{{ $row->additional_feature_value }}</td>
                                        <td>
                                            <a href="{{ route('admin_property_delete_additional_feature',$row->id) }}" class="badge badge-danger fz-14 mt_5" onClick="return confirm('{{ ARE_YOU_SURE }}');">{{ DELETE }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>


					<h4 class="mt_30">{{ NEW_ADDITIONAL_FEATURES }}</h4>
					<span class="btn btn-success add_additional_feature_more"><i class="fas fa-plus"></i></span>
					<div class="additional_feature_item">

					</div>


					<h4 class="mt_30">{{ SEO_SECTION }}</h4>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ TITLE }}</label>
								<input type="text" name="seo_title" class="form-control" value="{{ $property->seo_title }}">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ META_DESCRIPTION }}</label>
								<textarea name="seo_meta_description" class="form-control h-70" cols="30" rows="10">{{ $property->seo_meta_description }}</textarea>
							</div>
						</div>
					</div>

                    @if($allow_featured == 'Yes')
                        <h4 class="mt_30">{{ QUESTION_FEATURED }}</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <select name="is_featured" class="form-control">
                                        <option value="Yes" @if($property->is_featured == "Yes") selected @endif>{{ YES }}</option>
                                        <option value="No" @if($property->is_featured == "No") selected @endif>{{ NO }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endif

					<button type="submit" class="btn btn-primary">{{ UPDATE }}</button>
				</form>

			</div>
		</div>
	</div>
</div>


<div class="d_n">
	<div id="add_social">
		<div class="delete_social">
			<div class="row social_for_count">
				<div class="col-md-5">
					<div class="form-group">
						<select name="social_icon[]" class="form-control">
                            <option value="Facebook">{{ FACEBOOK }}</option>
                            <option value="Twitter">{{ TWITTER }}</option>
                            <option value="LinkedIn">{{ LINKEDIN }}</option>
                            <option value="YouTube">{{ YOUTUBE }}</option>
                            <option value="Pinterest">{{ PINTEREST }}</option>
                            <option value="GooglePlus">{{ GOOGLE_PLUS }}</option>
                            <option value="Instagram">{{ INSTAGRAM }}</option>
						</select>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<input type="text" name="social_url[]" class="form-control" placeholder="{{ URL }}">
					</div>
				</div>
				<div class="col-md-1">
					<span class="btn btn-danger remove_social_more"><i class="fas fa-minus"></i></span>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="d_n">
	<div id="add_photo">
		<div class="delete_photo">
			<div class="row photo_for_count">
				<div class="col-md-5">
					<div class="form-group">
						<input type="file" name="photo_list[]">
					</div>
				</div>
				<div class="col-md-1">
					<span class="btn btn-danger remove_photo_more"><i class="fas fa-minus"></i></span>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="d_n">
	<div id="add_video">
		<div class="delete_video">
			<div class="row video_for_count">
				<div class="col-md-5">
					<div class="form-group">
						<input type="text" name="youtube_video_id[]" class="form-control" placeholder="{{ YOUTUBE_VIDEO_ID }}">
					</div>
				</div>
				<div class="col-md-1">
					<span class="btn btn-danger remove_video_more"><i class="fas fa-minus"></i></span>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="d_n">
	<div id="add_additional_feature">
		<div class="delete_additional_feature">
			<div class="row additional_feature_for_count">
				<div class="col-md-5">
					<div class="form-group">
						<input type="text" name="additional_feature_name[]" class="form-control" placeholder="{{ NAME }}">
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<input type="text" name="additional_feature_value[]" class="form-control" placeholder="{{ VALUE }}">
					</div>
				</div>
				<div class="col-md-1">
					<span class="btn btn-danger remove_additional_feature_more"><i class="fas fa-minus"></i></span>
				</div>
			</div>
		</div>
	</div>
</div>


<script>
function countElements(class_name) {
    var numItems = $('.'+class_name).length
    return numItems;
}

// Social Item Check
$(document).on("click",".add_social_more",function() {
	if(countElements('social_for_count') > {{ $total_social_items-count($property_social_items) }}) {
		toastr.error('{{ MAX_ALLOWED_SOCIAL_ITEMS_FOR_YOU }} {{ $total_social_items }}')
	} else {
		var add_social = $("#add_social").html();
  		jQuery('.social_item').append(add_social);
	}
});
$(document).on("click",".remove_social_more",function(event){
	$(this).closest(".delete_social").remove();
});


// Photo Check
$(document).on("click",".add_photo_more",function() {
	if(countElements('photo_for_count') > {{ $total_photos-count($property_photos) }}) {
		toastr.error('{{ MAX_ALLOWED_PHOTOS_FOR_YOU }} {{ $total_photos }}')
	} else {
		var add_photo = $("#add_photo").html();
  		jQuery('.photo_item').append(add_photo);
	}
});
$(document).on("click",".remove_photo_more",function(event){
	$(this).closest(".delete_photo").remove();
});


// Video Check
$(document).on("click",".add_video_more",function() {
	if(countElements('video_for_count') > {{ $total_videos-count($property_videos) }}) {
		toastr.error('{{ MAX_ALLOWED_VIDEOS_FOR_YOU }} {{$total_videos}}')
	} else {
		var add_video = $("#add_video").html();
  		jQuery('.video_item').append(add_video);
	}
});
$(document).on("click",".remove_video_more",function(event){
	$(this).closest(".delete_video").remove();
});


// Additional Feature
$(document).on("click",".add_additional_feature_more",function() {
	if(countElements('additional_feature_for_count') > {{ $total_additional_features-count($property_additional_features) }}) {
		toastr.error('{{ MAX_ALLOWED_ADDITIONAL_FEATURES_FOR_YOU }} {{ $total_additional_features }}')
	} else {
		var add_additional_feature = $("#add_additional_feature").html();
  		jQuery('.additional_feature_item').append(add_additional_feature);
	}
});
$(document).on("click",".remove_additional_feature_more",function(event){
	$(this).closest(".delete_additional_feature").remove();
});


// Amenities
$('.amenity_check').on('click',function() {
  	if(this.checked) {
    	var total = $("input[name='amenity[]']:checked").length;
    	if(total > {{ $total_amenities }})
    	{
    		$(this).prop("checked", false);
    		toastr.error('{{ MAX_ALLOWED_AMENITIES_FOR_YOU }} {{ $total_amenities }}')
    	}
  	}
});
</script>

@endsection
