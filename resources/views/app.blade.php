<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta charset="utf-8">
		<title>@yield('title') | BitSplit</title>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.6.0/css/font-awesome.min.css">
		<link href='//fonts.googleapis.com/css?family=Raleway' rel='stylesheet' type='text/css'>
		<link href="https://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet">
		<link rel="stylesheet" href="{{ asset('/css/jquery-ui.css') }}">
		<link rel="stylesheet" href="{{ asset('/css/jquery.fancybox.css') }}">
		<link href="{{ asset('/css/bootstrap.min.css') }}" rel="stylesheet">
		<!--[if lt IE 9]>
			<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
		<link href="{{ asset('/css/jquery.datetimepicker.css') }}" rel="stylesheet">
		<link href="{{ asset('/css/daterangepicker-bs3.css') }}" rel="stylesheet">
		<link href="{{ asset('/css/styles.css') }}" rel="stylesheet">
		<link href="{{ asset('/css/navigation.css') }}" rel="stylesheet">
		<link href="{{ asset('/css/footer.css') }}" rel="stylesheet">

		<link href="{{ asset('/img/favicon.ico') }}" rel="shortcut icon" type="image/x-icon">

		<!-- <link rel="icon" type="image/png" href="" /> -->
		@if(isset($header_scripts))
			<?php echo $header_scripts; ?>
		@endif
	</head>
	<body>
		<div id="app">
			<navigation></navigation>
			
			<div class="main-content">
				@if(Session::has('message'))
					<div class="alert-container">
						<p class="alert {{ Session::get('message-class') }}">{{ Session::get('message') }}</p>
					</div>
				@endif
				@yield('content')
			</div>
			<footer-section></footer-section>
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
		<script src="{{ asset('/js/vue_dev.js') }}"></script>
		@yield('page_scripts')
	</body>
</html>

<script src="https://cdn.jsdelivr.net/npm/vue-resource@1.3.5"></script>

<script src="https://unpkg.com/vuex@3.0.1/dist/vuex.js"></script>

<script>
	
	Vue.component('footer-section', {

		template: `
			@include('partials.footer')
		`,
		watch: {
		},
		props: {
		},
		data() {
			return {
			}
		},
		methods: {
		},
		computed: {
		}
	});

	Vue.component('navigation', {

		template: `
			@include('partials.header')
		`,
		watch: {
		},
		props: {
		},
		data() {
			return {
			}
		},
		methods: {
		},
		computed: {
		}
	});

	var vm = new Vue({
		el: '#app',
	    http: {
	      emulateJSON: true,
	      emulateHTTP: true
	    },
		data() {
			return {
			}
		},
		props: {

		},
		methods: {
		},
		computed: {
		},
		created: function() {
		},
		mounted: function() {
		}
	});
</script>
