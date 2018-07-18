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
	       	<strong>MergedFolding</strong>
	       </a>
		</div>
	</div>
	<div class="navbar-right">
		<nav class="collapse navbar-collapse" role="navigation">
		  <ul class="nav navbar-nav">
			@if (Auth::guest())
				<li>
					<a href="{{ route('account.authorize') }}">
						<span class="navbar-cta">Get Started</span>
					</a></li>
				<li><a href="{{ route('account.authorize') }}">Login</a></li>
				<li><a href="http://foldingcoin.net/" target="_blank">What is FoldingCoin?</a></li>
			@else
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-user"></i> {{ Auth::user()->username }} <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a href="{{ URL::to('/home') }}">BitSplit Dashboard</a></li>
						<li><a href="{{ env('TOKENPASS_PROVIDER_HOST') }}/dashboard" target="_blank">Account Settings</a></li>
	                    <li><a href="{{ route('account.api-keys') }}">API Keys</a></li>
	                    @if(Auth::user()->admin)
							<li>
								<a href="{{ route('account.admin.users') }}">
									<span>User Dashboard (Admin)</span>
									<span class="number-badge">{{ User::needsApprovalCount() }}</span>
								</a>
							</li>
	                    @endif
						<li><a href="{{ url('/account/logout') }}">Logout</a></li>
					</ul>
				</li>
				<li><a href="http://foldingcoin.net/" target="_blank">FoldingCoin.net</a></li>
			@endif
			  <li class="dropdown">
				  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Public Distributions <span class="caret"></span></a>
				  <ul class="dropdown-menu" role="menu">
					  <li><a href="{{route('distribute.history')}}">All distributions</a></li>
					  <li><a href="{{route('distribute.official_fldc_history')}}">Official FLDC distributions</a></li>
				  </ul>
			  </li>
		  </ul>
		</nav>
	</div>
</header>