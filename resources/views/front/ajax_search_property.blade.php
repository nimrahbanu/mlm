@foreach ($properties as $property)

            @if($property->user_id !=0)
                @php
                    $t_data = \App\Models\PackagePurchase::where('user_id',$property->user_id)->where('currently_active',1)->first();
                @endphp
                @if($t_data->package_end_date < date('Y-m-d'))
                    @continue
                @endif
            @endif

            <div class="col-lg-6 col-md-12">
                <div class="property-item border">
                    <div class="photo">
                        <a href="{{ route('front_property_detail',$property->property_slug) }}"><img src="{{ asset('uploads/property_featured_photos/'.$property->property_featured_photo) }}" alt=""></a>
                        <div class="featured-text">{{ FEATURED }}</div>
                    </div>
                    <div class="text">

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="badges">
                                <div class="category">
                                    <a href="{{ route('front_property_category_detail',$property->rPropertyCategory->property_category_slug) }}" class="text-white">{{ $property->rPropertyCategory->property_category_name }}</a>
                                </div>
                                <div class="@if($property->property_type == 'For Sale') inner-sale @else inner-rent @endif">
                                    {{ $property->property_type }}
                                </div>
                            </div>

                            <div class="wishlist-btn">
                                <a href="javascript:;" onclick="addToWishlist('{{ $property->id }}')"><i class="fas fa-heart"></i></a>
                                {{-- <a href="{{ route('front_add_wishlist',$property->id) }}"><i class="fas fa-heart"></i></a> --}}
                            </div>
                        </div>

                        <h3><a href="{{ route('front_property_detail',$property->property_slug) }}">{{ $property->property_name }}</a></h3>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="location">
                                <i class="fas fa-map-marker-alt"></i> {{ $property->rPropertyLocation->property_location_name }}
                            </div>

                            @php
                                $count=0;
                                $total_number = 0;
                                $overall_rating = 0;
                                $reviews = \App\Models\Review::where('property_id',$property->id)->where('status',1)->get();
                            @endphp

                            @if($reviews->isEmpty())

                            @else

                            @foreach($reviews as $item)
                                @php
                                    $count++;
                                    $total_number = $total_number + $item->rating;
                                @endphp
                            @endforeach

                            @php
                                $overall_rating = $total_number/$count;
                            @endphp

                            @if($overall_rating>0 && $overall_rating<=1)
                                @php $overall_rating = 1; @endphp

                            @elseif($overall_rating>1 && $overall_rating<=1.5)
                                @php $overall_rating = 1.5; @endphp

                            @elseif($overall_rating>1.5 && $overall_rating<=2)
                                @php $overall_rating = 2; @endphp

                            @elseif($overall_rating>2 && $overall_rating<=2.5)
                                @php $overall_rating = 2.5; @endphp

                            @elseif($overall_rating>2.5 && $overall_rating<=3)
                                @php $overall_rating = 3; @endphp

                            @elseif($overall_rating>3 && $overall_rating<=3.5)
                                @php $overall_rating = 3.5; @endphp

                            @elseif($overall_rating>3.5 && $overall_rating<=4)
                                @php $overall_rating = 4; @endphp

                            @elseif($overall_rating>4 && $overall_rating<=4.5)
                                @php $overall_rating = 4.5; @endphp

                            @elseif($overall_rating>4.5 && $overall_rating<=5)
                                @php $overall_rating = 5; @endphp

                            @endif

                            @endif

                            <div class="review">
                                @if($overall_rating == 5)
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                @elseif($overall_rating == 4.5)
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                @elseif($overall_rating == 4)
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                @elseif($overall_rating == 3.5)
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                    <i class="far fa-star"></i>
                                @elseif($overall_rating == 3)
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                @elseif($overall_rating == 2.5)
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                @elseif($overall_rating == 2)
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                @elseif($overall_rating == 1.5)
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                @elseif($overall_rating == 1)
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                @elseif($overall_rating == 0)
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <i class="far fa-star"></i>
                                @endif
                            </div>
                        </div>

                        <div class="bed-bath-size">
                                <div class="item">
                                <svg class="img-fluid" width="28" height="22" viewBox="0 0 28 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 21V14C1.00313 12.9401 1.42557 11.9245 2.17503 11.175C2.9245 10.4256 3.9401 10.0031 5 10H23C24.0599 10.0031 25.0755 10.4256 25.825 11.175C26.5744 11.9245 26.9969 12.9401 27 14V21M22 10H4V3.5C4.00198 2.83757 4.26601 2.20283 4.73442 1.73442C5.20283 1.26601 5.83757 1.00198 6.5 1H21.5C22.1624 1.00198 22.7972 1.26601 23.2656 1.73442C23.734 2.20283 23.998 2.83757 24 3.5V10H22Z" stroke="url(#paint0_linear_521_192)" stroke-width="1.7837" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M1 21V20.5C1.00115 20.1025 1.15956 19.7217 1.44061 19.4406C1.72167 19.1596 2.10253 19.0012 2.5 19H25.5C25.8975 19.0012 26.2783 19.1596 26.5594 19.4406C26.8404 19.7217 26.9988 20.1025 27 20.5V21M5 10V9C5.00148 8.47002 5.21267 7.96218 5.58743 7.58743C5.96218 7.21267 6.47002 7.00148 7 7H12C12.53 7.00148 13.0378 7.21267 13.4126 7.58743C13.7873 7.96218 13.9985 8.47002 14 9M14 9V10M14 9C14.0015 8.47002 14.2127 7.96218 14.5874 7.58743C14.9622 7.21267 15.47 7.00148 16 7H21C21.53 7.00148 22.0378 7.21267 22.4126 7.58743C22.7873 7.96218 22.9985 8.47002 23 9V10" stroke="url(#paint1_linear_521_192)" stroke-width="1.7837" stroke-linecap="round" stroke-linejoin="round"/>
                                    <defs>
                                    <linearGradient id="paint0_linear_521_192" x1="2.78082" y1="3.82051" x2="19.3879" y2="5.47769" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#B90000"/>
                                    <stop offset="1" stop-color="#FF4A4A"/>
                                    </linearGradient>
                                    <linearGradient id="paint1_linear_521_192" x1="2.78082" y1="8.97436" x2="19.2192" y2="11.3177" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#B90000"/>
                                    <stop offset="1" stop-color="#FF4A4A"/>
                                    </linearGradient>
                                    </defs>
                                </svg>

                                    <div class="text">{{ $property->property_bedroom }} {{ BED }}</div>
                                </div>
                                <div class="item">
                                <svg class="img-fluid" width="25" height="20" viewBox="0 0 25 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20.8333 0C18.9917 0 17.5 1.49167 17.5 3.33333H15.8333V5H20.8333V3.33333H19.1667C19.1667 2.34083 19.8408 1.66667 20.8333 1.66667C21.8258 1.66667 22.5 2.34083 22.5 3.33333V8.33333H0V10H0.99L2.265 16.3283V16.3542C2.36179 16.7791 2.56578 17.1721 2.85753 17.4958C3.14928 17.8195 3.51908 18.0631 3.93167 18.2033L3.33333 20H5L5.54667 18.3333H19.4533L20 20H21.6667L21.0675 18.2033C21.94 17.9367 22.6175 17.2367 22.8125 16.3542V16.3283L24.0108 10H25V8.33333H24.1667V3.33333C24.1667 1.49167 22.675 0 20.8333 0ZM2.6825 10H22.3433L21.1717 16.0158C21.0742 16.3767 20.7683 16.6667 20.3383 16.6667H4.74C4.29333 16.6667 3.99083 16.3708 3.90667 15.9892L2.6825 10Z" fill="url(#paint0_linear_521_197)"/>
                                    <defs>
                                    <linearGradient id="paint0_linear_521_197" x1="1.71233" y1="2.82051" x2="17.6926" y2="4.35381" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#B90000"/>
                                    <stop offset="1" stop-color="#FF4A4A"/>
                                    </linearGradient>
                                    </defs>
                                </svg>

                                    <div class="text">{{ $property->property_bathroom }} {{ BATH }}</div>
                                </div>
                                <div class="item">
                                <svg class="img-fluid" width="24" height="20" viewBox="0 0 24 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0H24V20H0V0ZM22.5 18.3333V1.66667H1.5V18.3333H22.5ZM6.50391 11.1068L5.49609 12.2266L2.73047 9.16667L5.49609 6.10677L6.50391 7.22656L5.50781 8.33333H10.5V10H5.50781L6.50391 11.1068ZM17.4961 11.1068L18.4922 10H13.5V8.33333H18.4922L17.4961 7.22656L18.5039 6.10677L21.2695 9.16667L18.5039 12.2266L17.4961 11.1068Z" fill="url(#paint0_linear_521_200)"/>
                                <defs>
                                <linearGradient id="paint0_linear_521_200" x1="1.64384" y1="2.82051" x2="16.9959" y2="4.23461" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#B90000"/>
                                <stop offset="1" stop-color="#FF4A4A"/>
                                </linearGradient>
                                </defs>
                                </svg>

                                    <div class="text">{{ $property->property_size }}</div>
                                </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                                <div class="price">
                                    @if(!session()->get('currency_symbol'))
                                        ${{ number_format($property->property_price) }}
                                    @else
                                        {{ session()->get('currency_symbol') }}{{ number_format($property->property_price*session()->get('currency_value')) }}
                                    @endif/-
                                </div>

                            <a class="details-btn" href="{{ route('front_property_detail',$property->property_slug) }}">Details</a>
                        </div>
                    </div>
            </div>
                </div>
            </div>
        @endforeach

        <div class="col-12">
            {{ $properties->links() }}
        </div>
