@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ UNDER_CONSTRUCTION_PROPERTY }}</h1>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
            <div class="float-right d-inline">
                <a href="{{ route('admin_underconstruction_property_create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> {{ ADD_NEW }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ SERIAL }}</th>
                            <th>{{ FEATURED_PHOTO }}</th>
                            <th>{{ NAME }}, {{ CATEGORY }}, {{ LOCATION }}</th>
                            <th>{{ STATUS }}</th>
                            <th>{{ QUESTION_IS_FEATURED }}</th>
                            <th class="w_200">{{ ACTION }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i=0; @endphp

                        @foreach($property as $row)

                        @php $i++; @endphp

                        @php
                        $user_detail = \App\Models\User::where('id',$row->user_id)->first();
                        $admin_detail = \App\Models\Admin::where('id',$row->admin_id)->first();
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><img src="{{ asset('uploads/under_construction_property_featured_photos/'.$row->property_featured_photo) }}" alt="" class="w_200"></td>
                            <td>
                                @if($row->user_id==0)
                                <b>{{ $row->property_name }}</b><br>
                                <small>
                                    <b>{{ ADDED_BY }}: {{ ADMIN }}</b>
                                </small>
                                @endif

                                @if($row->admin_id==0)
                                <b>{{ $row->property_name }}</b><br>
                                <small><b>{{ ADDED_BY }}: <a href="{{ route('admin_customer_detail',$row->user_id) }}" target="_blank">{{ $user_detail->name }}</a></b></small>
                                @endif

                                <br>
                                {{ CATEGORY_COLON }} {{ $row->UnderConstructionPropertyCategory->property_category_name }}
                                <br>
                                {{ LOCATION_COLON }} {{ $row->UnderConstructionPropertyLocation->property_location_name }}
                            </td>
                            <td>
                                @if ($row->property_status == 'Active')
                                <a href="" onclick="propertyStatus({{ $row->id }})"><input type="checkbox" checked data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success" data-offstyle="danger"></a>
                                @else
                                    <a href="" onclick="propertyStatus({{ $row->id }})"><input type="checkbox" data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success" data-offstyle="danger"></a>
                                @endif
                            </td>
                            <td>
                                @if($row->is_featured == 'Yes')
                                <span class="badge badge-success">{{ $row->is_featured }}</span>
                                @else
                                <span class="badge badge-danger">{{ $row->is_featured }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="" class="btn btn-success btn-sm" data-toggle="modal" data-target="#detail_info{{ $row->id }}"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('admin_underconstruction_property_delete',$row->id) }}" class="btn btn-danger btn-sm" onClick="return confirm('{{ ARE_YOU_SURE }}');"><i class="fas fa-trash-alt"></i></a>

                                @if($row->user_id == 0)
                                <a href="{{ route('admin_underconstruction_property_edit',$row->id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                @endif
                            </td>
                        </tr>

<!-- Modal -->
<div class="modal fade modal_property_detail" id="detail_info{{ $row->id }}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ PROPERTY_DETAIL }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="form-group">
                    <label for="">{{ NAME }}</label>
                    <div>{{ $row->property_name }}</div>
                </div>

                <div class="form-group">
                    <label for="">{{ SLUG }}</label>
                    <div>{{ $row->property_slug }}</div>
                </div>

                <div class="form-group">
                    <label for="">{{ DESCRIPTION }}</label>
                    <div>{!! clean($row->property_description) !!}</div>
                </div>

                <div class="form-group">
                    <label for="">{{ PROPERTY_CATEGORY }}</label>
                    <div>{{ $row->property_category_name }}</div>
                </div>

                <div class="form-group">
                    <label for="">{{ PROPERTY_LOCATION }}</label>
                    <div>{{ $row->property_location_name }}</div>
                </div>

                <div class="form-group">
                    <label for="">{{ ADDRESS }}</label>
                    <div>{!! clean(nl2br($row->property_address)) !!}</div>
                </div>

                <div class="form-group">
                    <label for="">{{ PHONE }}</label>
                    <div>{!! clean(nl2br($row->property_phone)) !!}</div>
                </div>

                <div class="form-group">
                    <label for="">{{ EMAIL }}</label>
                    <div>{!! clean(nl2br($row->property_email)) !!}</div>
                </div>

                <div class="form-group">
                    <label for="">{{ MAP }}</label>
                    <div>{!! $row->property_map !!}</div>
                </div>

                <div class="form-group">
                    <label for="">{{ WEBSITE }}</label>
                    <div><a href="{{ $row->property_website }}" target="_blank">{{ $row->property_website }}</a></div>
                </div>

                <div class="form-group">
                    <label for="">{{ FEATURED_PHOTO }}</label>
                    <div><img src="{{ asset('uploads/under_construction_property_featured_photos/'.$row->property_featured_photo) }}" alt="" class="w_200"></div>
                </div>


                <div class="form-group">
                    <label for="">{{ FEATURES }}</label>

                    <div class="row bdb bdt">
                        <div class="col-md-3"><b>{{ PRICE }}</b>:</div>
                        <div class="col-md-9">{{ $row->property_price }}</div>
                    </div>

                    <div class="row bdb">
                        <div class="col-md-3"><b>{{ BEDROOM }}</b>:</div>
                        <div class="col-md-9">{{ $row->property_bedroom }}</div>
                    </div>

                    <div class="row bdb">
                        <div class="col-md-3"><b>{{ BATHROOM }}</b>:</div>
                        <div class="col-md-9">{{ $row->property_bathroom }}</div>
                    </div>

                    <div class="row bdb">
                        <div class="col-md-3"><b>{{ SIZE }}</b>:</div>
                        <div class="col-md-9">{{ $row->property_size }}</div>
                    </div>

                    @if($row->property_built_year != '')
                    <div class="row bdb">
                        <div class="col-md-3"><b>{{ BUILT_YEAR }}</b>:</div>
                        <div class="col-md-9">{{ $row->property_built_year }}</div>
                    </div>
                    @endif

                    @if($row->property_garage != '')
                    <div class="row bdb">
                        <div class="col-md-3"><b>{{ GARAGE }}</b>:</div>
                        <div class="col-md-9">{{ $row->property_garage }}</div>
                    </div>
                    @endif

                    @if($row->property_block != '')
                    <div class="row bdb">
                        <div class="col-md-3"><b>{{ BLOCK }}</b>:</div>
                        <div class="col-md-9">{{ $row->property_block }}</div>
                    </div>
                    @endif

                    @if($row->property_floor != '')
                    <div class="row bdb">
                        <div class="col-md-3"><b>{{ FLOOR }}</b>:</div>
                        <div class="col-md-9">{{ $row->property_floor }}</div>
                    </div>
                    @endif

                    <div class="row bdb">
                        <div class="col-md-3"><b>{{ TYPE }}</b>:</div>
                        <div class="col-md-9">{{ $row->property_type }}</div>
                    </div>
                </div>



                <div class="form-group">
                    <label for="">{{ OPENING_HOUR }}</label>

                    <div class="row bdb bdt">
                        <div class="col-md-3">
                            <b>{{ MONDAY }}</b>:
                        </div>
                        <div class="col-md-9">
                            {{ $row->property_oh_monday }}
                        </div>
                    </div>

                    <div class="row bdb">
                        <div class="col-md-3">
                            <b>{{ TUESDAY }}</b>:
                        </div>
                        <div class="col-md-9">
                            {{ $row->property_oh_tuesday }}
                        </div>
                    </div>

                    <div class="row bdb">
                        <div class="col-md-3">
                            <b>{{ WEDNESDAY }}</b>:
                        </div>
                        <div class="col-md-9">
                            {{ $row->property_oh_wednesday }}
                        </div>
                    </div>

                    <div class="row bdb">
                        <div class="col-md-3">
                            <b>{{ THURSDAY }}</b>:
                        </div>
                        <div class="col-md-9">
                            {{ $row->property_oh_thursday }}
                        </div>
                    </div>

                    <div class="row bdb">
                        <div class="col-md-3">
                            <b>{{ FRIDAY }}</b>:
                        </div>
                        <div class="col-md-9">
                            {{ $row->property_oh_friday }}
                        </div>
                    </div>

                    <div class="row bdb">
                        <div class="col-md-3">
                            <b>{{ SATURDAY }}</b>:
                        </div>
                        <div class="col-md-9">
                            {{ $row->property_oh_saturday }}
                        </div>
                    </div>

                    <div class="row bdb">
                        <div class="col-md-3">
                            <b>{{ SUNDAY }}</b>:
                        </div>
                        <div class="col-md-9">
                            {{ $row->property_oh_sunday }}
                        </div>
                    </div>

                </div>


                <div class="form-group">
                    <label for="">{{ SOCIAL_MEDIA }}</label>
                    @php
                    $i=0;
                    $social_items = DB::table('property_social_items')->where('property_id',$row->id)->get();
                    @endphp
                    @foreach($social_items as $item)
                    @php $i++; @endphp
                    <div class="row bdb @if($i==1) bdt @endif">
                        <div class="col-md-3">
                            {{ $item->social_icon }}
                        </div>
                        <div class="col-md-9">
                            <a href="{{ $item->social_url }}" target="_blank">{{ URL_TO_CLICK }}</a>
                        </div>
                    </div>
                    @endforeach
                </div>


                <div class="form-group">
                    <label for="">{{ AMENITIES }}</label>
                    @php
                    $i=0;
                    $amenities = DB::table('property_amenities')
                        ->join('amenities','property_amenities.amenity_id','amenities.id')
                        ->select('property_amenities.*', 'amenities.amenity_name')
                        ->where('property_amenities.property_id',$row->id)
                        ->get();
                    @endphp
                    @foreach($amenities as $item)
                    @php $i++; @endphp
                    <div class="row bdb @if($i==1) bdt @endif">
                        <div class="col-md-12">
                            {{ $i.'. '.$item->amenity_name }}
                        </div>
                    </div>
                    @endforeach
                </div>


                <div class="form-group">
                    <label for="">{{ PHOTO }}s</label>

                    @php
                    $photos = DB::table('property_photos')->where('property_id',$row->id)->get();
                    @endphp

                    <div class="row">
                        @foreach($photos as $item)
                        <div class="col-md-4">
                            <div class="mb_10">
                                <img src="{{ asset('uploads/under_construction_property_photos/'.$item->photo) }}" alt="" class="w_100_p">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>


                <div class="form-group">
                    <label for="">{{ VIDEOS }}</label>

                    @php
                    $videos = DB::table('property_videos')->where('property_id',$row->id)->get();
                    @endphp

                    <div class="row">
                        @foreach($videos as $item)
                        <div class="col-md-4">
                            <div class="mb_10 existing-video">
                                <iframe width="560" height="315" src="https://www.youtube.com/embed/{{ $item->youtube_video_id }}" title="{{ YOUTUBE_VIDEO_PLAYER}}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>


                <div class="form-group">
                    <label for="">{{ ADDITIONAL_FEATURES }}</label>

                    @php
                    $i=0;
                    $additional_features = DB::table('property_additional_features')->where('property_id',$row->id)->get();
                    @endphp

                    @foreach($additional_features as $item)
                    @php $i++; @endphp
                    <div class="row bdb @if($i==1) bdt @endif">
                    <div class="col-md-3">
                        {{ $item->additional_feature_name }}
                    </div>
                    <div class="col-md-9">
                        {{ $item->additional_feature_value }}
                    </div>
                    </div>
                    @endforeach

                </div>



            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">{{ CLOSE }}</button>
            </div>
        </div>
    </div>
</div>
<!-- // Modal -->


                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function propertyStatus(id){
            $.ajax({
                type:"get",
                url:"{{url('/admin/under-construction-property-status/')}}"+"/"+id,
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
