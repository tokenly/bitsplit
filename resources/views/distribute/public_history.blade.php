@extends('app')

@section('content')
    <div class="content padded">
        <div class="page-information">
            <h1>{{$type}} Token Distributions</h1>
        </div>
        <div>
            @if($distros->isEmpty())
            <p>
                No distributions found.
            </p>
            @else
                <div class="distribution-index">
                    @foreach($distros as $row)
                        <div class="distribution-index__container half">
                            @include('distribute.partials.distribution')
                        </div>
                    @endforeach
                </div>
            @endif
            {{ $distros->links() }}
        </div>
    </div>
@endsection

@section('title')
    Bitsplit Distributions History
@stop
