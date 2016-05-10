<p>
    Hello {{ $notify_data['username'] }}, you have received tokens from Bitsplit distribution #{{ $distro->id }} 
    @if(trim($distro->label) != '')
        "{{ $distro->label }}"
    @endif
</p>
<p>
    The following transactions have been received:
</p>
<ul>
    @foreach($notify_data['txs'] as $tx)
        <li>
            {{ rtrim(rtrim(number_format($tx->quantity/100000000,8),"0"),".") }} {{ $distro->asset }} to 
            <a href="https://blockscan.com/address/{{ $tx->destination }}">{{ $tx->destination }}</a>
            <br>
            TX ID: <a href="https://blocktrail.com/BTC/tx/{{ $tx->txid }}">{{ $tx->txid }}</a>
        </li>
    @endforeach
</ul>
