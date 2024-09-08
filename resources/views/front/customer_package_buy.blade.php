@extends('front.app_front')

@section('content')

<script src="https://www.paypalobjects.com/api/checkout.js"></script>
@php $final_price = 0; @endphp


<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$page_other_item->customer_panel_page_banner) }}')">
	<div class="page-banner-bg"></div>
	<h1>{{ PAYMENT }}</h1>
	<nav>
		<ol class="breadcrumb justify-content-center">
			<li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
			<li class="breadcrumb-item active">{{ PAYMENT }}</li>
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

                    <div class="col-md-7">
                        <h5>{{ PAY_NOW }}</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">

                                @if($g_setting->paypal_status == 'Show')
                                <tr>
                                    <td>{{ PAY_WITH_PAYPAL }}</td>
                                    <td><div id="paypal-button"></div></td>
                                </tr>
                                @endif

                                @if($g_setting->stripe_status == 'Show')
                                <tr>
                                    <td>{{ PAY_WITH_STRIPE }}</td>
                                    <td>
                                        @php
                                            $final_price = session()->get('package_price');
                                            $final_price = session()->get('package_price')*session()->get('currency_value');
                                            $final_price = round($final_price,2);
                                            $cents = $final_price*100;
                                            $customer_email = session()->get('email');
                                        @endphp

                                        <form action="{{ route('customer_payment_stripe') }}" method="post">
                                            @csrf
                                            <script
                                                src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                                                data-key="{{ $g_setting->stripe_public_key }}"
                                                data-amount="{{ $cents }}"
                                                data-name="{{ env('APP_NAME') }}"
                                                data-description=""
                                                data-image="{{ asset('public/images/stripe_icon.png') }}"
                                                data-currency="{{ session()->get('currency_name') }}"
                                                data-email="{{ $customer_email }}"
                                            >
                                            </script>
                                        </form>
                                    </td>
                                </tr>
                                @endif



                                @if($g_setting->razorpay_status == 'Show')
                                <tr>
                                    <td>{{ PAY_WITH_RAZORPAY }}</td>
                                    <td>
                                        <form action="{{ route('customer_payment_razorpay') }}" method="POST">
                                            @csrf
                                            @php
                                                $final_price = session()->get('package_price');
                                                $final_price = session()->get('package_price')*session()->get('currency_value');
                                                $final_price = round($final_price,2);
                                                $customer_email = session()->get('email');
                                            @endphp
                                            <script src="https://checkout.razorpay.com/v1/checkout.js"
                                                    data-key="{{ $g_setting->razorpay_key_id }}"
                                                    data-amount= "{{ $final_price*100 }}"
                                                    data-buttontext="Pay with Razorpay"
                                                    data-name="{{ env('APP_NAME') }}"
                                                    data-description="{{ env('APP_NAME') }}"
                                                    data-image="{{ asset('uploads/payment_gateway_icons/razorpay.png') }}"
                                                    data-prefill.name=""
                                                    data-currency="{{ session()->get('currency_name') }}"
                                                    data-prefill.email="{{ $customer_email }}"
                                                    data-theme.color="#0b29a2">
                                            </script>
                                        </form>
                                    </td>
                                </tr>
                                @endif



                                @if($g_setting->flutterwave_status == 'Show')
                                <tr>
                                    <td>{{ PAY_WITH_FLUTTERWAVE }}</td>
                                    <td>
                                        <button type="submit" class="btn btn-primary flutterwave-button" onClick="makePayment()">{{ PAY_WITH_FLUTTERWAVE }}</button>
                                    </td>
                                </tr>
                                @endif


                                @if($g_setting->mollie_status == 'Show')
                                <tr>
                                    <td>{{ PAY_WITH_MOLLIE }}</td>
                                    <td>
                                        <form action="{{ route('customer_payment_mollie') }}" method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-primary mollie-button">{{ PAY_WITH_MOLLIE }}</button>
                                        </form>
                                    </td>
                                </tr>
                                @endif


                            </table>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <h5>{{ SELECTED_PACKAGE }}</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="w-200">{{ PACKAGE_NAME }} </td>
                                    <td>{{ session()->get('package_name') }}</td>
                                </tr>
                                <tr>
                                    <td class="w-200">{{ PACKAGE_PRICE }} </td>
                                    <td>{{ session()->get('currency_symbol') }}{{ session()->get('package_price')*session()->get('currency_value') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
				</div>
			</div>
		</div>
	</div>
</div>


@php
    $paypal_mode = $g_setting->paypal_environment;
    $client = $g_setting->paypal_client_id;
    $secret = $g_setting->paypal_secret_key;
@endphp

@if($paypal_mode == 'sandbox')
    @php
        $paypal_url = 'https://api.sandbox.paypal.com/v1/';
        $env_type = 'sandbox';
    @endphp
@elseif($paypal_mode == 'production')
    @php
        $paypal_url = 'https://api.paypal.com/v1/';
        $env_type = 'production';
    @endphp
@endif

<script>
    paypal.Button.render({
        env: '{{ $env_type }}',
        client: {
            sandbox: '{{ $client }}',
            production: '{{ $client }}'
        },
        locale: 'en_US',
        style: {
            size: 'medium',
            color: 'blue',
            shape: 'rect',
        },

        // Set up a payment
        payment: function (data, actions) {
            return actions.payment.create({

                redirect_urls:{
                    return_url: '{{ url("customer/payment/paypal") }}'
                },

                transactions: [{
                    amount: {
                        total: '{{ $final_price }}',
                        currency: '{{ session()->get("currency_name") }}'
                    }
                }]
          });
        },

        // Execute the payment
        onAuthorize: function (data, actions) {
            return actions.redirect();
        }
    }, '#paypal-button');
</script>


<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
    function makePayment() {
        FlutterwaveCheckout({
            public_key: "{{ $g_setting->flutterwave_public_key }}",
            tx_ref: "RX1",
            amount: {{ $final_price }},
            currency: "{{ session()->get('currency_name') }}",
            country: "{{ $g_setting->flutterwave_country }}",
            payment_options: " ",
            customer: {
                email: "{{ Auth::user()->email }}",
                phone_number: "{{ Auth::user()->phone }}",
                name: "{{ Auth::user()->name }}",
            },
            callback: function (data) {
                var tnx_id = data.transaction_id;
                var _token = "{{ csrf_token() }}";
                var package_id = '{{ session()->get('package_id') }}';
                $.ajax({
                    type: 'post',
                    data : {tnx_id,_token,package_id},
                    url: "{{ route('customer_payment_flutterwave') }}",
                    success: function (response) {
                        window.location.href = "{{ URL::to('customer/package/purchase/history') }}";
                    },
                    error: function(err) {}
                });
            },
            customizations: {
                title: "{{ env('APP_NAME') }}",
                logo: "{{ asset('uploads/payment_gateway_icons/flutterwave.png') }}",
            },
        });
    }
</script>

@endsection
