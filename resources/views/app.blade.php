<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta charset="utf-8">
		<title>@yield('title') | BitSplit</title>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
		<link href='//fonts.googleapis.com/css?family=Raleway' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" href="{{ asset('/css/jquery-ui.css') }}">
		<link rel="stylesheet" href="{{ asset('/css/jquery.fancybox.css') }}">
		<link href="{{ asset('/css/bootstrap.min.css') }}" rel="stylesheet">
		<!--[if lt IE 9]>
			<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
		<link href="{{ asset('/css/jquery.datetimepicker.css') }}" rel="stylesheet">
		<link href="{{ asset('/css/daterangepicker-bs3.css') }}" rel="stylesheet">
		<link href="{{ asset('/css/styles.css') }}" rel="stylesheet">
		<!-- <link rel="icon" type="image/png" href="" /> -->
		@if(isset($header_scripts))
			<?php echo $header_scripts; ?>
		@endif
	</head>
	<body class="">
	<header class="navbar navbar-default navbar-static-top" role="banner">
	  <div class="container">
		<div class="navbar-header">
		  <button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".navbar-collapse">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		  </button>
		  <a href="{{ URL::to('/') }}" class="navbar-brand"><!--<img src="" alt="" /> --> <strong><i class="fa fa-code-fork"></i> BitSplit</strong></a>
		</div>
		<nav class="collapse navbar-collapse" role="navigation">
		  <ul class="nav navbar-nav">
			  <li><a href="{{ URL::to('/') }}"><i class="fa fa-home"></i> Home</a></li>
				<li><a href="https://github.com/tokenly/bitsplit" target="_blank"><i class="fa fa-github-alt"></i> Github</a></li>
				<li><a href="http://tokenly.com" target="_blank"><i class="fa fa-globe"></i> Tokenly</a></li>
			@if (Auth::guest())
				<li><a href="{{ route('account.authorize') }}"><i class="fa fa-user"></i> Login/Register</a></li>
			@else
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-user"></i> {{ Auth::user()->username }} <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a href="{{ URL::to('/home') }}">BitSplit Dashboard</a></li>
						<li><a href="{{ env('TOKENPASS_PROVIDER_HOST') }}/dashboard" target="_blank">Account Settings</a></li>
						<li><a href="{{ url('/account/logout') }}">Logout</a></li>
					</ul>
				</li>
			@endif				
		  </ul>
		</nav>
	  </div>
	</header>

	<!-- Begin Body -->
	<div class="container">
		<div class="row">
			<div class="col-md-12 main-content">
				@if(Session::has('message'))
					<p class="alert {{ Session::get('message-class') }}">{{ Session::get('message') }}</p>
				@endif
				@yield('content')
			</div> 
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="footer text-center">
					<small class="tagline">
						&copy; {{ date('Y') }} Tokenly
					</small>
				</div>
			</div>
		</div>
	</div>
	<div class="pockets-url" style="display: none;"></div>
	<div class="pockets-image-blue" style="display: none;"></div>	
	<!-- script references -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="{{ asset('/js/bootstrap.min.js') }}"></script>
	<script src="{{ asset('/js/jquery-ui.min.js') }}"></script>
		@if(isset($footer_scripts))
			<?php echo $footer_scripts; ?>
		@endif	
	<script src="{{ asset('/js/moment.js') }}"></script>
	<script src="{{ asset('/js/jquery.fancybox.pack.js') }}"></script>
	<script src="{{ asset('/js/daterangepicker.js') }}"></script>
	<script src="{{ asset('/js/jquery.datetimepicker.js') }}"></script>
	<script src="{{ asset('/js/scripts.js') }}"></script>
	<!--Start of Tawk.to Script-->
	<script type="text/javascript">
	var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
	(function(){
	var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
	s1.async=true;
	@if(isset($tawk_override) AND $tawk_override)
		s1.src='https://embed.tawk.to/{{ $tawk_override }}/default';
	@else
		s1.src='https://embed.tawk.to/561c61b6f207135a361e4100/default';
	@endif
	s1.charset='UTF-8';
	s1.setAttribute('crossorigin','*');
	s0.parentNode.insertBefore(s1,s0);
	})();
	</script>
	<!--End of Tawk.to Script-->	
	</body>
</html>
