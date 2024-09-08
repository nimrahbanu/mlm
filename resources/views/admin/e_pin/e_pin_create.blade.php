@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">E-Pin Master</h1>

<form action="{{ route('admin_e_pin_store') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
            <div class="float-right d-inline">
                <a href="{{ route('admin_e_pin_master') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i>
                    {{ VIEW_ALL }}</a>
            </div>
        </div>
        <div class="card-body">
            <!-- <div class="form-group">
                <label for="">MemberID</label>
                <input type="text" name="member_id" class="form-control" value="{{ old('member_id') }}" autofocus>
            </div>
            <div class="form-group">
                <label for="">Member Name</label>
                <input type="text" name="member_name" class="form-control" value="{{ old('member_name') }}" autofocus>
            </div> -->
            <div class="form-group">
                <label for="">Balance</label>
                <input type="text" name="balance" class="form-control" value="{{ $g_setting->e_pin_charge}}" readonly>
            </div>
            <div class="form-group">
                <label for="">Quantity</label>
                <input type="text" name="quantity" class="form-control" value="{{ old('quatity') }}" >
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-md-6">
                        <label for="">{{ STATUS }}</label>
                        <select name="status" class="form-control">
                            <option value="1">{{ ACTIVE}}</option>
                            <option value="0">{{ PENDING }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="">Admin Aprove</label>
                        <select name="flag" class="form-control">
                            <option value="1">{{ YES }}</option>
                            <option value="0">{{ NO }}</option>
                        </select>
                    </div>
                </div>

            </div>

            <button type="submit" class="btn btn-success btn-block mb_40">{{ SUBMIT }}</button>
        </div>
    </div>

</form>

@endsection