@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">Redis</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
        <div class="float-right d-inline">
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Star 1</th>
                        <th>Silver 2</th>
                        <th>Gold 3</th>
                        <th>Platinum 4</th>
                        <th>Ruby 5</th>
                        <th>Emerald 6</th>
                        <th>Diamond 7</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($success['arraydata'] as $user_id)
                    <tr>
                        <td> 
                            @if($success['lastUserId'] == $user_id)
                            <b class="bg-warning p-2" title="star transaction">{{ $user_id }}</b>
                            @else
                            {{ $user_id }}
                            @endif
                            @if($success['third_level_last_user_id'] == $user_id)
                            <span class="p-3 bg-info bg-opacity-10 border border-info border-start-0 rounded-end" title="silver transaction">{{ $success['third_level_last_user_id'] }}</span>  
                            @endif
                            
                        </td>
                        <td>{{ $success['helpReceivedCounts'][$user_id] }}</td>
                        <td>{{ $success['silver'][$user_id] }}</td>
                        <td>{{ $success['gold'][$user_id] }}</td>
                        <td>{{ $success['platinum'][$user_id] }}</td>
                        <td>{{ $success['ruby'][$user_id] }}</td>
                        <td>{{ $success['emrald'][$user_id] }}</td>
                        <!-- <td>{{ $success['diamond'][$user_id] }}</td> -->
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    
        <p> redis ID</p>
        <ul>
            <li>
                first level : {{$success['a1']}}
            </li>
            <li>
                second level : {{$success['a2']}}
            </li>
            <li>
                third level : {{$success['a3']}}
            </li>
            <li>
                fourth level : {{$success['a4']}}
            </li>
            <li>
                fives level : {{$success['a5']}}
            </li>
            <li>
                six level : {{$success['a6']}}
            </li>

            <li>
                seven level : {{$success['a7']}}
            </li>
        </ul>
            <!-- @foreach($success['third_level_users'] as $third_level_users)
            
                <p >  {{$third_level_users}}  </p>
            @endforeach
             -->
    </div>
    <hr>
</div>
@endsection