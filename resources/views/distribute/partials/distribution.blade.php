<?php
    $num_tx = $row->addressCount();
    $num_complete = $row->countComplete();
    if($num_complete >= $num_tx){
        // echo '<i class="fa fa-check text-success" title="Complete"></i> '.number_format($num_tx);
    }
    else{
        // echo '<span class="distro-'.$row->id.'-complete-count">'.number_format($num_complete).'</span>/'.number_format($num_tx);
    }
?>
<div class="distribution-index__row">
    <div>
        <p class="distribution-index__row__date">
            <span>{{ $row->created_at->format('F j, Y')  }}</span>
        </p>
        <p class="distribution-index__row__title">
            @if($row->complete == 1)
                <i class="fa fa-check-circle success"></i>
            @else
                <div class="spinner">
                    <div class="bounce1"></div>
                    <div class="bounce2"></div>
                    <div class="bounce3"></div>
                </div>
            @endif
            <span>
                <span>{{ rtrim(rtrim(number_format($row->asset_total / 100000000, 8),"0"),".") }}</span>
                <span>{{ $row->asset }}</span>
            </span>
            <span>distributed to</span>
            <span>{{ $num_complete }} folders</span>
        </p>
        @if ($row->isOnchainDistribution())
            <p class="distribution-index__row__address">
                <span>Origin Address:</span>
                <a href="{{ 'xchain.io/address/'.$row->deposit_address }}">{{ $row->deposit_address }}</a>
            </p>
        @endif
        @if ($row->isOffchainDistribution())
            <p class="distribution-index__row__address">
                <span>Offchain Distribution</span>
            </p>
        @endif
        <div class="distribution-index__row__status">
            <span>Status:</span>
            <?php
                if($row->complete == 1){
                    echo '<span>Complete</span>';
                }
                elseif($row->hold == 1){
                    echo '<strong>HOLD</strong>';
                }
                else{
                ?>
                    @include('distribute.partials.distribution-status', ['distro' => $row])
                <?php
                }
            ?>
        </div>
    </div>
    <div>
        <a href="{{ route('distribute.details', $row->deposit_address) }}" class="distribution-index__row__cta" title="View details">
            <span>View Distribution Details</span>
        </a>
    </div>
</div>