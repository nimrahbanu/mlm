@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ EDIT_PAYMENT_SETTING }}</h1>

    <form action="{{ route('admin_payment_update') }}" method="post">
        @csrf

        <div class="card shadow mb-4 t-left">
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <a class="nav-link active" id="p1_tab" data-toggle="pill" href="#p1" role="tab" aria-controls="p1" aria-selected="true">{{ PAYPAL }}</a>
                            <a class="nav-link" id="p2_tab" data-toggle="pill" href="#p2" role="tab" aria-controls="p2" aria-selected="false">{{ STRIPE }}</a>
                            <a class="nav-link" id="p3_tab" data-toggle="pill" href="#p3" role="tab" aria-controls="p3" aria-selected="false">{{ RAZORPAY }}</a>
                            <a class="nav-link" id="p4_tab" data-toggle="pill" href="#p4" role="tab" aria-controls="p4" aria-selected="false">{{ FLUTTERWAVE }}</a>
                            <a class="nav-link" id="p5_tab" data-toggle="pill" href="#p5" role="tab" aria-controls="p5" aria-selected="false">{{ MOLLIE }}</a>
                        </div>
                    </div>
                    <div class="col-9">
                        <div class="tab-content" id="v-pills-tabContent">
                            <div class="tab-pane fade show active" id="p1" role="tabpanel" aria-labelledby="p1_tab">
                                <div class="form-group">
                                    <label for="">{{ PAYPAL_ENVIRONMENT }}</label>
                                    <select name="paypal_environment" class="form-control">
                                        <option value="sandbox" @if($g_setting->paypal_environment == 'sandbox') selected @endif>{{ SANDBOX }}</option>
                                        <option value="production" @if($g_setting->paypal_environment == 'production') selected @endif>{{ PRODUCTION }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="">{{ PAYPAL_CLIENT_ID }}</label>
                                    <input type="text" class="form-control" name="paypal_client_id" value="{{ $g_setting->paypal_client_id }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ PAYPAL_SECRET_KEY }}</label>
                                    <input type="text" class="form-control" name="paypal_secret_key" value="{{ $g_setting->paypal_secret_key }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ STATUS }}</label>
                                    <select name="paypal_status" class="form-control">
                                        <option value="Show" @if($g_setting->paypal_status == 'Show') selected @endif>{{ SHOW }}</option>
                                        <option value="Hide" @if($g_setting->paypal_status == 'Hide') selected @endif>{{ HIDE }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="p2" role="tabpanel" aria-labelledby="p2_tab">
                                <div class="form-group">
                                    <label for="">{{ STRIPE_PUBLIC_KEY }}</label>
                                    <input type="text" class="form-control" name="stripe_public_key" value="{{ $g_setting->stripe_public_key }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ STRIPE_SECRET_KEY }}</label>
                                    <input type="text" class="form-control" name="stripe_secret_key" value="{{ $g_setting->stripe_secret_key }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ STATUS }}</label>
                                    <select name="stripe_status" class="form-control">
                                        <option value="Show" @if($g_setting->stripe_status == 'Show') selected @endif>{{ SHOW }}</option>
                                        <option value="Hide" @if($g_setting->stripe_status == 'Hide') selected @endif>{{ HIDE }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="p3" role="tabpanel" aria-labelledby="p3_tab">
                                <div class="form-group">
                                    <label for="">{{ RAZORPAY_KEY_ID }}</label>
                                    <input type="text" class="form-control" name="razorpay_key_id" value="{{ $g_setting->razorpay_key_id }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ RAZORPAY_KEY_SECRET }}</label>
                                    <input type="text" class="form-control" name="razorpay_key_secret" value="{{ $g_setting->razorpay_key_secret }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ STATUS }}</label>
                                    <select name="razorpay_status" class="form-control">
                                        <option value="Show" @if($g_setting->razorpay_status == 'Show') selected @endif>{{ SHOW }}</option>
                                        <option value="Hide" @if($g_setting->razorpay_status == 'Hide') selected @endif>{{ HIDE }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="p4" role="tabpanel" aria-labelledby="p4_tab">
                                <div class="form-group">
                                    <label for="">{{ FLUTTERWAVE_COUNTRY }}</label>
                                    <input type="text" class="form-control" name="flutterwave_country" value="{{ $g_setting->flutterwave_country }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ FLUTTERWAVE_PUBLIC_KEY }}</label>
                                    <input type="text" class="form-control" name="flutterwave_public_key" value="{{ $g_setting->flutterwave_public_key }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ FLUTTERWAVE_SECRET_KEY }}</label>
                                    <input type="text" class="form-control" name="flutterwave_secret_key" value="{{ $g_setting->flutterwave_secret_key }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ STATUS }}</label>
                                    <select name="flutterwave_status" class="form-control">
                                        <option value="Show" @if($g_setting->flutterwave_status == 'Show') selected @endif>{{ SHOW }}</option>
                                        <option value="Hide" @if($g_setting->flutterwave_status == 'Hide') selected @endif>{{ HIDE }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="p5" role="tabpanel" aria-labelledby="p5_tab">
                                <div class="form-group">
                                    <label for="">{{ MOLLIE_API_KEY }}</label>
                                    <input type="text" class="form-control" name="mollie_api_key" value="{{ $g_setting->mollie_api_key }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ STATUS }}</label>
                                    <select name="mollie_status" class="form-control">
                                        <option value="Show" @if($g_setting->mollie_status == 'Show') selected @endif>{{ SHOW }}</option>
                                        <option value="Hide" @if($g_setting->mollie_status == 'Hide') selected @endif>{{ HIDE }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-success btn-block">{{ UPDATE }}</button>
    </form>

@endsection