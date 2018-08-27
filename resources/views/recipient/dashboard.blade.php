@extends('app')

@section('content')

<div class="content padded">
    <div class="page-information">
        <h1>Dashboard</h1>
    </div>
    
    <div>
        <div class="dashboard__main">
            <div class="dashboard__main__content">
                <h2>My Foldingcoin</h2>
                <div class="welcome-section-divider"></div>

                @foreach ($addresses as $address)
                @if ($address['balances'][FLDCAssetName()] ?? false)
                    <p>
                        You have {{ formattedTokenQuantity($address['balances'][FLDCAssetName()]) }} {{ FLDCAssetName() }}
                        in address {{ $address['address'] }}
                    </p>
                    <p>
                        <a 
                            class="recipient--cta" 
                            href="{{ route('recipient.withdraw', ['address' => $address['address']]) }}">Withdraw Now</a>
                    </p>
                @endif
                @endforeach

                @if (!count($addresses))
                    <p>You do not have any FLDC awarded to your verified addresses at this time.  To verify an address, visit your <a href="{{ env('TOKENPASS_PROVIDER_HOST') }}/dashboard" target="_blank">Tokenpass account settings</a> and follow the instructions there to verify an address.</p>
                @endif

            </div>
        </div>



    </div>
</div>
@endsection

@section('title')
    FLDC Dashboard
@stop
