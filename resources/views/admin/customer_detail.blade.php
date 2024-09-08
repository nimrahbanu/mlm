@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ CUSTOMER_DETAIL }}</h1>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
                    <div class="float-right d-inline">
                        <a href="{{ route('admin_customer_view') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> {{ BACK_TO_PREVIOUS }}</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <form action="{{route('edit_customer',[$customer_detail->id])}}" method="post">
                            @csrf
                        <table class="table table-bordered">
                            
                            <tr>
                                <td>{{ NAME }}</td>
                                <td> <input type="text" class="form-control" name="name" value="{{ $customer_detail->name }}"> </td>
                            </tr>
                            <tr>
                                <td>{{ EMAIL }}</td>
                                <td> <input type="text" class="form-control" name="email" value="{{ $customer_detail->email }}"> </td>
                            </tr>
                            <tr>
                                <td>{{ PHONE }}</td>
                                <td> <input type="text" class="form-control" name="phone" value="{{ $customer_detail->phone }}"> </td>
                            </tr>
                            <tr>
                                <td>Password</td>
                                <td> <input type="password" class="form-control" name="password" value="{{ $customer_detail->password }}"> </td>
                            </tr>
                            
                        </table>
                        <button type="submit" class="btn btn-success">Update</button>
                        </form>
                    </div>
                </div>
                  <div class="card-body">
                    <div class="table-responsive">
                        <form action="{{route('edit_customer_status',[$customer_detail->id])}}" method="post">
                            @csrf
                        <table class="table table-bordered">
                            
                            <tr>
                                <td>is_active</td>
                                <td> 
                                    <select name="is_active" id="" class="form-control">
                                        <option value="" >select active</option>
                                        <option value="1" {{ $customer_detail->is_active == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $customer_detail->is_active == '0' ? 'selected' : '' }}>Pending</option>
                                    </select>
                            </tr>
                            <tr>
                                <td>is_green</td>
                                <td> 
                                    <select name="is_green" id="" class="form-control">
                                    <option value="" >select green</option>
                                        <option value="1" {{ $customer_detail->is_green == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $customer_detail->is_green == "0" ? 'selected' : '' }}>Pending</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>status</td>
                                <td> 
                                    <select name="status" id="" class="form-control">
                                    <option value="" >select status</option>
                                        <option value="Active" {{ $customer_detail->status == "Active" ? 'selected' : '' }}>Active</option>
                                        <option value="InActive" {{ $customer_detail->status == "InActive" ? 'selected' : '' }}>InActive</option>
                                        <option value="Block" {{ $customer_detail->status == "Block" ? 'selected' : '' }}>Block</option>
                                    </select>
                                 </td>
                            </tr>
                            <tr>
                                <td>activated_date</td>
                                <td> <input type="datetime-local" class="form-control" name="activated_date" value="{{ $customer_detail->activated_date }}"> </td>
                            </tr>
                            <tr>
                                <td>green_date</td>
                                <td> <input type="datetime-local" class="form-control" name="green_date" value="{{ $customer_detail->green_date }}"> </td>
                            </tr>
                            <tr>
                                <td>activated_date</td>
                                <td> 
                                    <select name="package_id" id="" class="form-control">
                                    <option value="" >select Package</option>
                                        <option value="1" {{ $customer_detail->package_id == "1" ? 'selected' : '' }}>welcome</option>
                                        <option value="2" {{ $customer_detail->package_id == "2" ? 'selected' : '' }}>star</option>
                                        <option value="3" {{ $customer_detail->package_id == "3" ? 'selected' : '' }}>silver</option>
                                        <option value="4" {{ $customer_detail->package_id == "4" ? 'selected' : '' }}>gold</option>
                                        <option value="5" {{ $customer_detail->package_id == "5" ? 'selected' : '' }}>platinum</option>
                                        <option value="6" {{ $customer_detail->package_id == "6" ? 'selected' : '' }}>ruby</option>
                                        <option value="7" {{ $customer_detail->package_id == "7" ? 'selected' : '' }}>emerald</option>
                                        <option value="8" {{ $customer_detail->package_id == "7" ? 'selected' : '' }}>diamond</option>
                                    </select>
                                 </td>
                            </tr>                        
                        </table>
                        <button type="submit" class="btn btn-success">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection