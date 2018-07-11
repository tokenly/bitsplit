<header class="navbar navbar-default navbar-static-top primary-navbar" role="banner">
  	<div class="navbar-left">
		<div class="navbar-header">
		  <button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".navbar-collapse">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		  </button>
		  <a href="{{ URL::to('/') }}" class="navbar-brand"><img src="{{ asset('img/fldc/FLDC.jpg') }}" alt=""
	      style="
			width: 25px;
			margin-right: 10px;
			margin-top: -2px;
			float: left;"          
	       />
	       	<strong>BitSplit - FLDC</strong>
	       </a>
		</div>
	</div>
	<div class="navbar-right">
		<nav class="collapse navbar-collapse" role="navigation">
		  <ul class="nav navbar-nav">
				<li><a href="http://foldingcoin.net/" target="_blank"><i class="fa fa-globe"></i> FoldingCoin</a></li>
			@if (Auth::guest())
				<li><a href="{{ route('account.authorize') }}"><i class="fa fa-user"></i> Login/Register</a></li>
			@else
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-user"></i> {{ Auth::user()->username }} <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a href="{{ URL::to('/home') }}">BitSplit Dashboard</a></li>
						<li><a href="{{ env('TOKENPASS_PROVIDER_HOST') }}/dashboard" target="_blank">Account Settings</a></li>
	                    <li><a href="{{ route('account.api-keys') }}">API Keys</a></li>
						<li><a href="{{ url('/account/logout') }}">Logout</a></li>
					</ul>
				</li>
			@endif
			  <li class="dropdown">
				  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-list"></i> Public Distributions <span class="caret"></span></a>
				  <ul class="dropdown-menu" role="menu">
					  <li><a href="{{route('distribute.history')}}">All distributions</a></li>
					  <li><a href="{{route('distribute.official_fldc_history')}}">Official FLDC distributions</a></li>
				  </ul>
			  </li>
		  </ul>
		</nav>
	</div>
</header>