@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$page_other_item->customer_panel_page_banner) }}')">
	<div class="page-banner-bg"></div>
	<h1>{{ EDIT_PROFILE_INFORMATION }}</h1>
	<nav>
		<ol class="breadcrumb justify-content-center">
			<li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
			<li class="breadcrumb-item active">{{ EDIT_PROFILE_INFORMATION }}</li>
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
							<div class="text">Taking help to person</div>
						</div>
					</div>
					 @php
					 $user = Auth::user();
					 @endphp
					<div class="col-md-12">
						<div class="dashboard-box dashboard-box-3">
							<div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Person Name</th>
                                            <th>Phone no</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($data as $detail)
                                        <tr>
                                            <td class="text-uppercase">{{$detail->receiverByData->name}}  </td> 
                                            <td class="text-uppercase">{{$detail->receiverByData->phone_pay_no}}  </td> 
                                            <td>{{$detail->amount}}  </td> 
                                            <td>{{date('d M,Y', strtotime($detail->created_at))}} </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				 
			</div>
		</div>
	</div>
</div>

@endsection
