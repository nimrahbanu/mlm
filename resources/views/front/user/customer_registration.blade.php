@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/') }}')">
	<div class="page-banner-bg"></div>
	<h1>{{ REGISTRATION }}</h1>
	<nav>
		<ol class="breadcrumb justify-content-center">
			<li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
			<li class="breadcrumb-item active">{{ REGISTRATION }}</li>
		</ol>
	</nav>
</div>


<div class="page-content pt_50 pb_60">
	<div class="container">
		<div class="row cart">

			<div class="col-md-12">
				<div class="reg-login-form">
					<div class="inner">

						@php
							$g_setting = \App\Models\GeneralSetting::where('id',1)->first();
						@endphp

						<form action="{{ route('customer_registration_store') }}" method="post">
							@csrf
							<div class="form-group">
								<label for="">{{ NAME }}</label>
								<input type="text" class="form-control" name="name" value="{{old('name')}}">
							</div>
							<div class="form-group">
								<label for="">{{ EMAIL_ADDRESS }}</label>
								<input type="email" class="form-control" name="email" value="{{old('email')}}">
							</div>
							<div class="form-group">
								<label for="">{{ PASSWORD }}</label>
								<input type="password" class="form-control" name="password" value="{{old('password')}}">
							</div>
							<div class="form-group">
								<label for="">{{ RETYPE_PASSWORD }}</label>
								<input type="password" class="form-control" name="re_password" value="{{old('re_password')}}">
							</div>
							<div class="form-group">
                                <label for="">SponsorID</label>
                                <input type="text" class="form-control" name="sponsor_id" value="{{old('sponsor_id')}}">
                            </div>
                          
                            <div class="form-group">
                                <label>Mobile No</label>
                                <input  class="form-control" name="phone" value="{{old('phone')}}" type="tel" id="phone" name="phone" >
                            </div>
                            <div class="form-group">
                                <label>Phone Pay No.</label>
                                <input  class="form-control" name="phone_pay_no" type="tel" id="phone" name="phone" 
                                    value="{{old('phone_pay_no')}}">
                            </div>
							<div class="form-group">
								<label>Confirm Phone Pay No.</label>
								<input 	 class="form-control" name="confirm_phone_pay_no" type="tel" id="phone" name="phone" 
									value="{{old('confirm_phone_pay_no')}}">
							</div>
							<div class="form-group">
								<label>Registration code</label>
								<input type="text" class="form-control" name="registration_code"
									value="{{old('registration_code')}}">
							</div>
						 
							<div class="form-group">
								<label>USDT Wallet</label>
								<input type="text" class="form-control" name="ustd_no" value="{{old('ustd_no')}}">
							</div>
							@if($g_setting->google_recaptcha_status == 'Show')
							<div class="form-group">
								<div class="g-recaptcha" data-sitekey="{{ $g_setting->google_recaptcha_site_key }}"></div>
							</div>
							@endif
							<button type="submit" class="btn btn-primary">{{ MAKE_REGISTRATION }}</button>
							<div class="new-user">
								{{ HAVE_AN_ACCOUNT }} <a href="{{ route('customer_login') }}" class="link">{{ LOGIN_NOW }}</a>
							</div>
						</form>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>

@endsection
