@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ CUSTOMER_REVIEWS }}</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>{{ SERIAL }}</th>
                        <th>{{ PROPERTY_FEATURED_PHOTO }}</th>
                        <th>{{ PROPERTY_NAME }}</th>
                        <th>{{ CUSTOMER_NAME }}</th>
                        <th class="w_200">{{ RATING }}</th>
                        <th class="w_200">{{ REVIEW }}</th>
                        <th class="w_200">{{ STATUS }}</th>
                        <th>{{ ACTION }}</th>
                    </tr>
                    </thead>
                    <tbody>
                        @php $i=0; @endphp
                        @foreach($reviews as $row)
                            @php
                                $single_property_item = \App\Models\Property::where('id', $row->property_id)->first();
                                $customer_detail = \App\Models\User::where('id',$row->agent_id)->first();
                            @endphp
                            @php $i++; @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <img src="{{ asset('uploads/property_featured_photos/'.$single_property_item->property_featured_photo) }}" alt="" class="w_150">
                                </td>
                                <td>
                                    {{ $single_property_item->property_name }} <br>
                                    <a href="{{ route('front_property_detail',$single_property_item->property_slug) }}" class="badge badge-success" target="_blank">{{ SEE_DETAIL }}</a>
                                </td>
                                <td>
                                    {{ $customer_detail->name }}
                                    <a href="{{ route('admin_customer_detail',$customer_detail->id) }}" class="badge badge-success" target="_blank">{{ SEE_DETAIL }}</a>
                                </td>
                                <td>
                                    <div class="my-review">
                                        @if($row->rating == 5)
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        @elseif($row->rating == 4)
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="far fa-star"></i>
                                        @elseif($row->rating == 3)
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="far fa-star"></i>
                                            <i class="far fa-star"></i>
                                        @elseif($row->rating == 2)
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="far fa-star"></i>
                                            <i class="far fa-star"></i>
                                            <i class="far fa-star"></i>
                                        @elseif($row->rating == 1)
                                            <i class="fas fa-star"></i>
                                            <i class="far fa-star"></i>
                                            <i class="far fa-star"></i>
                                            <i class="far fa-star"></i>
                                            <i class="far fa-star"></i>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {!! clean(nl2br($row->review)) !!}
                                </td>
                                <td>
                                    @if ($row->status == '1')
                                    <a href="" onclick="ReviewApproveStatus({{ $row->id }})"><input type="checkbox" checked data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success" data-offstyle="danger"></a>
                                    @else
                                        <a href="" onclick="ReviewApproveStatus({{ $row->id }})"><input type="checkbox" data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success" data-offstyle="danger"></a>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin_delete_customer_review',$row->id) }}" class="btn btn-danger btn-sm" onClick="return confirm('{{ ARE_YOU_SURE }}');"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<script>
      function ReviewApproveStatus(id){
            $.ajax({
                type:"get",
                url:"{{url('/admin/review-approve-status/')}}"+"/"+id,
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
