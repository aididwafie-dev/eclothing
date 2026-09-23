<html>
	<head>
		<title>{{ $siteTitle ?? 'PLAS' }}</title>
		<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="_token" content="{{ csrf_token() }}" />

		{{-- The login screen is the app's start_url, so the install metadata
		     has to be here as well as on the signed-in layout. --}}
		<link rel="manifest" href="/manifest.webmanifest">
		<meta name="theme-color" content="#0b1f3a">
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
		<meta name="apple-mobile-web-app-title" content="PLAS">
		<link rel="apple-touch-icon" href="/front_end/icons/apple-touch-icon.png">
		
		<link rel="stylesheet" href="{{ asset('front_end/bootstrap-3.3.7-dist/css/bootstrap.min.css') }}">
		<link rel="stylesheet" href="{{ asset('front_end/font-awesome-4.7.0/css/font-awesome.min.css') }}">
		<link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800" rel="stylesheet">
		<link href="{{ asset('front_end/css/style.css') }}" rel="stylesheet" />
		<!-- jQuery library -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<!-- Latest compiled JavaScript -->
		<script src="{{ asset('front_end/bootstrap-3.3.7-dist/js/bootstrap.min.js') }}"></script>
		<script type="text/javascript">
			// Same worker as the signed-in layout; see public/sw.js.
			if ('serviceWorker' in navigator) {
				window.addEventListener('load', function () {
					navigator.serviceWorker.register('/sw.js').catch(function () {});
				});
			}
		</script>

	</head>
	<body class="auth">
		@include('static-layout/popup-alert')
		{{-- The sign-in screens have no topbar, so the toggle floats in the
		     same top-right corner it occupies once signed in. --}}
		@include('static-layout/language-toggle', ['langToggleClass' => 'lang-toggle-floating'])
