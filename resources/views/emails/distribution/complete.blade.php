<p>
    Hello {{ $user->username }}, your Token distribution #{{ $distro->id }} (<strong>{{ $distro->asset }}</strong>) has been completed.
</p>
<p>
    <a href="{{ route('distribute.details', $distro->deposit_address) }}">Click here</a> to view the full distribution details,
    or view it on the Blockchain here:
    <a href="https://blocktrail.com/BTC/address/{{ $distro->deposit_address }}">BlockTrail</a> |
    <a href="https://blockscan.com/address/{{ $distro->deposit_address }}">BlockScan</a>.
</p>
