@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$page_other_item->customer_panel_page_banner) }}')">
	<div class="page-banner-bg"></div>
	<h1>{{ DASHBOARD }}</h1>
	<nav>
		<ol class="breadcrumb justify-content-center">
			<li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
			<li class="breadcrumb-item active">{{ DASHBOARD }}</li>
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

				<div class="row">
					<div class="col-md-12">
						<div class="dashboard-box dashboard-box-2">
						<h4 class='text-white text-center'>Helping provide start</h4>	 
						</div>
					</div>
					<!-- <div class="col-md-6">
						<div class="dashboard-box dashboard-box-2">
							<div class="text">{{ PENDING_PROPERTY_ITEMS }}</div>
							<div class="number">{{ $total_pending_property }}</div>
						</div>
					</div> -->

					<p>
					<div class="col-md-12">
						<div class="dashboard-box dashboard-box-3">
							<div class="table-responsive">
							<p>
								<a class="btn btn-primary" data-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
									1st person detail provide help 100 
								</a>
							</p>
							<div class="collapse" id="collapseExample">
								<div class="card card-body">
									<p>Name: <b>nimrah banu ansari</b></p>
									<p>phone no.: <b>nimrah banu ansari</b></p>
									<p>emial: <b>nimrah banu ansari</b></p>
								</div>
							</div>
							
							</div>
						</div>
					</div>


				</div>

			</div>
		</div>
	</div>
</div>

@endsection
