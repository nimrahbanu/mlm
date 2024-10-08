@extends('front.app_front')

@section('content')

    <div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$news->banner) }}')">
	    <div class="page-banner-bg"></div>
        <h1>{{ $news->name }}</h1>
        <nav>
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
                <li class="breadcrumb-item active">{{ $news->name }}</li>
            </ol>
        </nav>
    </div>

    <div class="page-content">
        <div class="container">

            <div class="row">
                <div class="col-md-12">
                    {!! clean($news->detail) !!}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 faq">

                    <div class="panel-group" id="accordion1" role="tablist" aria-multiselectable="true">

                        @php $i=0; @endphp
                        @foreach ($faqs as $row)
                            @php $i++; @endphp

                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="heading{{ $i }}">
                                    <h4 class="panel-title">
                                        <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion1" href="#collapse{{ $i }}" aria-expanded="false" aria-controls="collapse{{ $i }}">
                                            {{ $row->news_title }}
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapse{{ $i }}" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading{{ $i }}">
                                    <div class="panel-body">
                                        {!! clean($row->news_content) !!}
                                    </div>
                                </div>
                            </div>

                        @endforeach

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
