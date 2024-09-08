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
						<div class="dashboard-box dashboard-box-1">
							<div class="text">Giving help to person</div>
						</div>
					</div>
					 @php
					 $user = Auth::user();
					 @endphp
					 @if($user->is_green == 0)
					<div class="col-md-12">
						<div class="dashboard-box dashboard-box-3">
							<div class="table-responsive">
								<table class="table table-bordered">
									 
									<tr>
										<td>Person Name</td>
										<td>
											{{@$detail->name}}, ({{@$detail->id}})
										</td>
									</tr>
									<tr>
										<td>Phone no</td>
										<td>
										<a href="tel:{{@$detail->phone}}">{{@$detail->phone}} </a>
										</td>
									</tr>
									<tr>
										<td>Amount</td>
										<td>
											300 
										</td>
									</tr>
									  
								</table>
							</div>
						</div>
					</div>
					@endif
				</div>
				@if($user->is_green == 1)
				<div class="row">
					<div class="col-md-12">
						<div class="dashboard-box dashboard-box-2">
							<div class="text">Taking help to person</div>
						</div>
					</div>
					@if(isset($approve_person))
					
					<div class="col-md-12">
						<div class="dashboard-box dashboard-box-3">
							<div class="table-responsive">
								<table class="table table-bordered">
									 
									<tr>
										<td>Person Name</td>
										<td>
											{{@$approve_person->name}}, ({{@$approve_person->id}})
										</td>
									</tr>
									<tr>
										<td>Phone no</td>
										<td>
										<a href="tel:{{$approve_person->phone_pay_no}}">{{@$approve_person->phone_pay_no}} </a>
										</td>
									</tr>
									<tr>
										<td>Amount</td>
										<td>
											300 
										</td>
									</tr>
									  
								</table>
								<form action="{{ route('payment.approve',$approve_person->id) }}" method="POST">
								@csrf
								<button type="submit" class="btn btn-success">Approve Payment</button>
							</form>
							</div>
						</div>
					</div>
					@endif

				</div>
				@endif
			</div>
		</div>
	</div>
</div>

@endsection
