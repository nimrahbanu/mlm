@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ PROPERTY_CATEGORY }}</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
            <div class="float-right d-inline">
                <a href="{{ route('e_pin_transfer_create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> {{ ADD_NEW }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>{{ SERIAL }}</th>
                        <th>Member Given BY</th>
                        <th>Balance</th>
                        <th>E-Pin</th>
                        <th>E-Pin Used by</th>
                        <th>Date</th>
                       
                    </tr>
                    </thead>
                    <tbody>
                        @php $i=0; @endphp
                        @foreach($property_category as $row)
                        <tr>
                        <td>{{ $loop->iteration }}</td>

                            <td>{{ @$row->providedByData->name }} <br> ({{@$row->providedByData->user_id}})</td>
                            <td>{{ $row->balance }}</td>
                            <td>{{ $row->e_pin }} </td>
                            <td>{{ @$row->MemberData->name }} <br> ({{@$row->MemberData->user_id}})</td>   
                            <td>{{date('d M,Y,H:i:s', strtotime($row->created_at))}} </td>
                           
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
