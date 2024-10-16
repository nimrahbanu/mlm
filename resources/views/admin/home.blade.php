@extends('admin.app_admin')

@section('admin_content')
<?php
use Illuminate\Support\Facades\Redis;
?>
    <div class="row">
        <div class="col-xl-12 col-md-12 mb-2">
            <h1 class="h3 mb-3 text-gray-800">{{ DASHBOARD }}</h1>
        </div>
    </div>
    <button class=" btn btn-success mb-2" onclick="updateRedis('cron',null)">
                            Dispatch cron
                        </button>
    <!-- Box Start -->
    <div class="row dashboard-page">

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="h4 font-weight-bold text-success mb-1">Total Members</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $total_customers }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="h4 font-weight-bold text-success mb-1">Active Members</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $total_active_customers }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="h4 font-weight-bold text-success mb-1">InActive Members</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $total_pending_customers }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="h4 font-weight-bold text-success mb-1">Total Pin Business</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $total_pins }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="h4 font-weight-bold text-success mb-1">Total Paid Help</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cart-arrow-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="h4 font-weight-bold text-success mb-1">Total Pending Help</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cart-arrow-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <h3 class="mb-4">Set Redis Level Admin</h3>
                    <div class="list-group">
                        <button class="list-group-item list-group-item-action btn btn-success mb-2" onclick="updateRedis('bronze', null)">
                            Set Redis to Bronze Level Admin
                        </button>
                        <button class="list-group-item list-group-item-action btn btn-success mb-2" onclick="updateRedis('star', null)">
                            Set Redis to Star Level Admin
                        </button>
                        <button class="list-group-item list-group-item-action btn btn-success mb-2" onclick="updateRedis('silver', null)">
                            Set Redis to Silver Level Admin
                        </button>
                        <button class="list-group-item list-group-item-action btn btn-success mb-2" onclick="updateRedis('gold', null)">
                            Set Redis to Gold Level Admin
                        </button>
                        <button class="list-group-item list-group-item-action btn btn-success mb-2" onclick="updateRedis('sapphire', null)">
                            Set Redis to Sapphire Level Admin
                        </button>
                        <button class="list-group-item list-group-item-action btn btn-success mb-2" onclick="updateRedis('platinum', null)">
                            Set Redis to Platinum Level Admin
                        </button>
                        <button class="list-group-item list-group-item-action btn btn-success mb-2" onclick="updateRedis('diamond', null)">
                            Set Redis to Diamond Level Admin
                        </button>
                        <button class="list-group-item list-group-item-action btn btn-success mb-2" onclick="updateRedis('admin', 'PHC123456')">
                            Set Redis to Admin Level Admin
                        </button>
                    </div>
                </div>
            </div>
        </div>
       
        <p> redis ID</p>
        <ul>
            <li>
                first level :  {{Redis::get('last_user_id')}}
            </li>
            <li>
                second level :         {{Redis::get('silver_level_user_id')}}

            </li>
            <li>
                third level :        {{Redis::get('gold_level_last_user_id')}}

            </li>
            <li>
                fourth level :         {{Redis::get('platinum_level_last_user_id')}}

            </li>
            <li>
                fives level :         {{Redis::get('ruby_level_last_user_id')}}

            </li>
            <li>
                six level :         {{Redis::get('emrald_level_last_user_id')}}

            </li>

            <li>
                seven level :        {{Redis::get('diamond_level_last_user_id')}}

            </li>
        </ul>
<script>
    // function updateRedis(level, userId) {
    //     fetch('/update-redis', {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json',
    //             'X-CSRF-TOKEN': '{{ csrf_token() }}' // Include CSRF token for security
    //         },
    //         body: JSON.stringify({ level: level, userId: userId })
    //     })
    //     .then(response => response.json())
    //     .then(data => {
    //         if (data.success) {
    //             alert('Redis updated successfully for ' + level + ' level admin.');
    //         } else {
    //             alert('Failed to update Redis: ' + data.message);
    //         }
    //     })
    //     .catch(error => {
    //         console.error('Error:', error);
    //         alert('An error occurred. Please try again.');
    //     });
    // }

    
</script>
<script>
function updateRedis(level) {
    $.ajax({
        type: "get",
        url: "{{url('/admin/update-redis/')}}" + "/" + level,
        success: function(response) {
            toastr.success(response)
        },
        error: function(err) {
            console.log(err);
        }
    })
}
</script>


    </div>
    <!-- // Box End -->
@endsection