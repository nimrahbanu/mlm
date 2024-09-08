@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">E-pin view</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
            <div class="float-right d-inline">
                <a href="{{ route('admin_property_category_create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> {{ ADD_NEW }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>{{ SERIAL }}</th>
                        <th>Member Id</th>
                        <th>{{ NAME }}</th>
                        <th>Balance</th>
                        <th>E-Pin</th>
                        <th>Status</th>
                        <th>Admin Approve</th>
                        <th>Date</th>
                        <th>{{ ACTION }}</th>
                    </tr>
                    </thead>
                    <tbody>
                        @php $i=0; @endphp
                        @foreach($property_category as $row)
                        <tr>
                        <td>{{ $loop->iteration }}</td>

                            <td>{{ $row->member_id }}</td>
                            <td>{{ $row->member_name }}</td>
                            <td>{{ $row->balance }}</td>
                            <td>{{ $row->e_pin }}</td>
                            <td>
                                @if ($row->status == '1')
                                    <a href="" onclick="epinStatus({{ $row->id }})"><input type="checkbox" checked data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success" data-offstyle="danger"></a>
                                @else
                                    <a href="" onclick="epinStatus({{ $row->id }})"><input type="checkbox" data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success" data-offstyle="danger"></a>
                                @endif
                            </td>
                            <td>
                            @if ($row->flag == '1')
                                <a href="" onclick="epinFlag({{ $row->id }})"><input type="checkbox" checked data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success" data-offstyle="danger"></a>
                                @else
                                    <a href="" onclick="epinFlag({{ $row->id }})"><input type="checkbox" data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success" data-offstyle="danger"></a>
                                @endif
                            </td>
                            <td>{{date('d M,Y', strtotime($row->created_at))}} </td>
                            <td>
                                <a href="{{ route('admin_property_category_delete',$row->id) }}" class="btn btn-danger btn-sm" onClick="return confirm('{{ ARE_YOU_SURE }}');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
                {{ $property_category->links() }}
            </div>
        </div>
    </div>
    <script>
        function epinStatus(id){
            $.ajax({
                type:"get",
                url:"{{url('/admin/epin-status/')}}"+"/"+id,
                success:function(response){
                   toastr.success(response)
                },
                error:function(err){
                    console.log(err);
                }
            })
        }
        function epinFlag(id){
            $.ajax({
                type:"get",
                url:"{{url('/admin/epin-flag/')}}"+"/"+id,
                success:function(response){
                   toastr.success(response)
                },
                error:function(err){
                    console.log(err);
                }
            })
        }
    </script>
@endsection
