@extends('app')

@section('content')

<div class="content padded">
    <div class="page-information">
        <h1>Withdrawal Form</h1>
    </div>
    
    <p>Please select an address to deliver to.  We will send the FLDC to this address on the blockchain.</p>

    <div>
        <form class="form-horizontal" role="form" method="POST" action="{{ route('recipient.withdraw.post') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <div class="form-group">
                <label class="col-md-4 control-label">E-Mail address</label>
                <div class="col-md-6">
                    <p class="form-control-static"><strong>{{ $user['email'] }}</strong></p>
                </div>
            </div>

            @if ($addresses)
                {{-- expr --}}
                <div class="form-group">
                    <label class="col-md-4 control-label">Blockchain address</label>
                    <div class="col-md-6">
                        <select id="scan_distros_from_select" name="blockchain_address" class="form-control">
                            <option value="">- Choose One -</option>
                            @foreach ($addresses as $address)
                                <option value="{{ $address['address'] }}">{{ $address['address']}} </option>
                            @endforeach
                        </select>

                        <p class="small">This is the counterparty-compatible address that will receive the tokens</p>
                    </div>
                </div>
            @else
                {{-- no addresses --}}
                <p>You do not have any confirmed addresses.  Please register an address using your <a href="{{ env('TOKENPASS_PROVIDER_HOST') }}">Tokenpass account</a>.</p>
            @endif

            <div class="form-group">
                <div class="row">
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Withdraw Tokens</button>
                    </div>
                    <div class="col-md-3">
                        <a class="btn btn-default" href="{{ route('recipient.dashboard') }}">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div></div>
@endsection

@section('title')
    FLDC Dashboard
@stop
