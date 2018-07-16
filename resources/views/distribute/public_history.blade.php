@extends('app')

@section('content')
    <div class="content padded">
        <div class="page-information">
            <h1>{{$type}} Token Distributions</h1>
        </div>
        <div class="col-lg-6">
            @if($distros->isEmpty())
            <p>
                No distributions found.
            </p>
            @else
                @foreach($distros as $row)
                    @include('distribute.partials.distribution')
                @endforeach

                <table class="table table-bordered table-striped distro-history-table" style="font-size: 12px;">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Token Total</th>
                        <th>Status</th>
                        <th>TX</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($distros as $row)
                        <tr>
                            <td>
                                <strong>#{{ $row->id }}</strong>
                                @if($row->label != '')
                                    {{ $row->label }}
                                @endif
                                <br>
                                <small>Address: {{ substr($row->deposit_address, 0, 7) }}..</small>
                            </td>
                            <td>
                                {{ rtrim(rtrim(number_format($row->asset_total / 100000000, 8),"0"),".") }}
                                {{ $row->asset }}
                            </td>
                            <td class="distro-{{ $row->id }}-status-text">
                                <?php
                                if($row->complete == 1){
                                    echo '<span class="text-success">Complete</span>';
                                }
                                elseif($row->hold == 1){
                                    echo '<strong>HOLD</strong>';
                                }
                                else{
                                    switch($row->stage){
                                        case 0:
                                            echo '<span class="text-warning">Initializing</span>';
                                            break;
                                        case 1:
                                            echo '<span class="text-warning">Collecting Tokens</span>';
                                            break;
                                        case 2:
                                            echo '<span class="text-warning">Collecting Fuel</span>';
                                            break;
                                        case 3:
                                            echo '<span class="text-info">Priming Inputs</span>';
                                            break;
                                        case 4:
                                            echo '<span class="text-info">Preparing Transactions</span>';
                                            break;
                                        case 5:
                                            echo '<span class="text-info">Broadcasting Transactions</span>';
                                            break;
                                        case 6:
                                            echo '<span class="text-info">Confirming Broadcasts</span>';
                                            break;
                                        case 7:
                                            echo '<span class="text-success">Performing Cleanup</span>';
                                            break;
                                        case 8:
                                            echo '<span class="text-success">Finalizing Cleanup</span>';
                                            break;
                                        default:
                                            echo '(unknown)';
                                            break;

                                    }
                                }
                                ?>
                            </td>
                            <td id="distro-{{ $row->id }}-table-complete-count-cont">
                                <?php
                                $num_tx = $row->addressCount();
                                $num_complete = $row->countComplete();
                                if($num_complete >= $num_tx){
                                    echo '<i class="fa fa-check text-success" title="Complete"></i> '.number_format($num_tx);
                                }
                                else{
                                    echo '<span class="distro-'.$row->id.'-complete-count">'.number_format($num_complete).'</span>/'.number_format($num_tx);
                                }
                                ?>
                            </td>
                            <td id="distro-{{ $row->id }}-table-actions">
                                <a href="{{ route('distribute.details', $row->deposit_address) }}" class="btn btn-info btn-sm" title="View details"><i class="fa fa-info"></i></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
            {{ $distros->links() }}

        </div>
    </div>
@endsection

@section('title')
    Bitsplit Distributions History
@stop
