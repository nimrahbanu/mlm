@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ ADD_CURRENCY }}</h1>

    <div class="row">
        <div class="col-md-6"> 
            <form action="{{ route('admin_currency_store') }}" method="post">
                @csrf
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
                        <div class="float-right d-inline">
                            <a href="{{ route('admin_currency_view') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> {{ VIEW_ALL }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">{{ NAME }} *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" autofocus>
                        </div>
                        <div class="form-group">
                            <label for="">{{ SYMBOL }} *</label>
                            <input type="text" name="symbol" class="form-control" value="{{ old('symbol') }}" autofocus>
                        </div>
                        <div class="form-group">
                            <label for="">{{ VALUE }} *</label>
                            <input type="text" name="value" class="form-control" value="{{ old('value') }}" autofocus>
                        </div>
                        <div class="form-group">
                            <label for="">{{ IS_DEFAULT }} *</label>
                            <select name="is_default" class="form-control">
                                <option value="Yes">{{ YES }}</option>
                                <option value="No">{{ NO }}</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">{{ SUBMIT }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
