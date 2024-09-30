@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">E-Pin transfer Master</h1>

<form action="{{ route('e_pin_transfer_store') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
            <div class="float-right d-inline">
                <a href="{{ route('e_pin_transfer') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i>
                    {{ VIEW_ALL }}</a>
            </div>
        </div>
        <div class="card-body">
        <div class="form-group">
                <label for="">Provided By</label>
                <select name="provided_by" class="form-control select2">
                    @foreach($users as $row)
                    <option value="{{ $row->user_id }}">{{ $row->name }}, ({{ $row->user_id }})</option>
                    @endforeach
                </select>
                     <!-- <input type="text" name="provided_by" class="form-control" value="{{ old('provided_by') }}" autofocus> -->
            </div>
            <!-- <div class="form-group">
                <label for="">MemberID</label>
                <input type="text" name="member_id" class="form-control" value="{{ old('member_id') }}" autofocus>
            </div> -->
            <div class="form-group">
                <label for="">Member Name</label>
                <select name="member_id" class="form-control select2">
                    @foreach($users as $row)
                    <option value="{{ $row->user_id }}">{{ $row->name }}, ({{ $row->user_id }})</option>
                     <!-- <input type="hidden" name="member_id" class="form-control" value="{{ $row->id }}" autofocus> -->
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label for="">Balance</label>
                <input type="text" name="balance" class="form-control" value="{{ $g_setting->e_pin_charge}}" readonly>
            </div>
            <div class="form-group">
                <label for="">Quantity</label>
                <input type="text" name="quantity" class="form-control" value="{{ old('quatity') }}" >
            </div>
            

            <button type="submit" class="btn btn-success btn-block mb_40">{{ SUBMIT }}</button>
        </div>
    </div>

</form>

@endsection