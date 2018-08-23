@extends('app')

@section('content')

<div class="content padded">
    <div class="page-information">
        <h1>Withdrawal Form</h1>
    </div>
    
    <p>Please select an address to deliver to.  We will send the {{ FLDCAssetName() }} to this address on the blockchain.</p>

    <div>
        <form class="form-horizontal" role="form" method="POST" action="{{ route('recipient.withdraw.post') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            {{-- 
            <div class="form-group">
                <label class="col-md-4 control-label">E-Mail address</label>
                <div class="col-md-6">
                    <p class="form-control-static"><strong>{{ $user['email'] }}</strong></p>
                </div>
            </div>
             --}}

            @if ($addresses)
                {{-- expr --}}
                <div class="form-group">
                    <label class="col-md-3 control-label">Blockchain address</label>
                    <div class="col-md-9">
                        <select id="scan_distros_from_select" name="blockchain_address" class="form-control">
                            <option value="">- Choose One -</option>
                            @foreach ($addresses as $address)
                                <option value="{{ $address['address'] }}"{{ old('blockchain_address', $default_blockchain_address) == $address['address'] ? ' selected' : '' }}>{{ $address['address']}} ({{ formattedTokenQuantity($address['balances'][FLDCAssetName()] ?? 0)}} {{ FLDCAssetName() }})</option>
                            @endforeach
                        </select>

                        <p class="small">This is the address that will receive the tokens.  Please make sure this address is in a counterparty-compatible wallet.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label">Withdrawal Fee</label>
                    <div class="col-md-9">
                        <p class="form-control-static">Approximately xxx.xx {{ FLDCAssetName() }} (Unimplemented)</p>
                        <p class="small">This amount will be deducted from the amount you receive in order to pay for the transaction fee.  The exact amount is determined by market conditions.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-md-3 control-label">Confirmation</label>
                    <div class="col-md-9">
                        <label for="confirm"><input type="checkbox" name="confirm" id="confirm" value="1" style="margin-right: 6px;" /> Yes.  I want to withdraw all available {{ FLDCAssetName() }} to the address selected above.</label>
                        <p class="small">You must withdraw all available {{ FLDCAssetName() }}.</p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-3"></div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Withdraw Tokens</button>
                        </div>
                        <div class="col-md-6">
                            <a class="btn btn-default" href="{{ route('recipient.dashboard') }}">Cancel</a>
                        </div>
                    </div>
                </div>
            @else
                {{-- no addresses --}}
                <p>You do not have any confirmed addresses.  Please register an address using your <a href="{{ env('TOKENPASS_PROVIDER_HOST') }}">Tokenpass account</a>.</p>
            @endif

        </form>
    </div></div>
@endsection

@section('title')
    Withdraw FLDC
@stop
