@php
$g_settings = \App\Models\GeneralSetting::where('id',1)->first();
@endphp

<!-- Start Navbar Area -->
<div class="navbar-area" id="stickymenu">

	<!-- Menu For Mobile Device -->
	<div class="mobile-nav">
		<a href="{{ url('/') }}" class="logo">
			<img src="{{ asset('uploads/site_photos/logo.svg') }}" alt="">
		</a>
	</div>

	<!-- Menu For Desktop Device -->
	<div class="main-nav">
		<div class="container">
			<nav class="navbar navbar-expand-md navbar-light">
				<a class="navbar-brand" href="{{ url('/') }}">
					<img src="{{ asset('uploads/site_photos/logo.svg') }}" alt="">
				</a>
				<div class="collapse navbar-collapse mean-menu justify-content-between" id="navbarSupportedContent">
					<ul class="navbar-nav">


						<li class="nav-item">
							<a href="{{ url('/') }}" class="nav-link">{{ MENU_HOME }}</a>
						</li>
 

					</ul>

					@if(Auth::user())
                    <a href="{{ route('customer_dashboard') }}" class="login-btn">{{ MENU_DASHBOARD }}</a>
                    @else
                    <a href="{{ route('customer_login') }}" class="login-btn">{{ MENU_LOGIN_REGISTER }}</a>
                    @endif
				</div>
			</nav>
		</div>
	</div>
</div>
<!-- End Navbar Area -->
