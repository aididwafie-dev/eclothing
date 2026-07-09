<html>
	<head>
		<title>{{ $siteTitle ?? 'PLAS' }}</title>
		<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="_token" content="{{ csrf_token() }}" />
		
		<link rel="stylesheet" href="{{ asset('front_end/bootstrap-3.3.7-dist/css/bootstrap.min.css') }}">
		<link rel="stylesheet" href="{{ asset('front_end/font-awesome-4.7.0/css/font-awesome.min.css') }}">
		<link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800" rel="stylesheet">
		<link href="{{ asset('front_end/css/style.css') }}" rel="stylesheet" />
		<!-- jQuery library -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<!-- Latest compiled JavaScript -->
		<script src="{{ asset('front_end/bootstrap-3.3.7-dist/js/bootstrap.min.js') }}"></script>
		
	</head>
	<body class="auth">
		@include('static-layout/popup-alert')
