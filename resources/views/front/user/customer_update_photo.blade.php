@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$page_other_item->customer_panel_page_banner) }}')">
	<div class="page-banner-bg"></div>
	<h1>{{ EDIT_PHOTO }}</h1>
	<nav>
		<ol class="breadcrumb justify-content-center">
			<li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
			<li class="breadcrumb-item active">{{ EDIT_PHOTO }}</li>
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

				<form action="{{ route('customer_update_photo_confirm') }}" method="post" enctype="multipart/form-data">
                    @csrf
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ EXISTING_PHOTO }}</label>
								<div>
									@if($user_data->photo == '')
									<img src="{{ asset('uploads/user_photos/default_photo.jpg') }}" alt="" class="w-200">
									@else
									<img src="{{ asset('uploads/user_photos/'.$user_data->photo) }}" alt="" class="w-200">
									@endif
								</div>
							</div>
						</div>
						<div class="col-md-12">
							<div class="form-group">
								<label for="">{{ CHANGE_PHOTO }}</label>
								<div>
									<input type="file" name="photo">
								</div>
							</div>
						</div>
					</div>
					<button type="submit" class="btn btn-primary">{{ UPDATE }}</button>
				</form>

			</div>
		</div>
	</div>
</div>

@endsection
