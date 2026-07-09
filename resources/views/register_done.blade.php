@include('static-layout/not-logged-header')

<div class="banner">
	<div class="content content-log">
		<h2 class="text-center">
			Congratulations!<br /><br />
			You have registered successfully. <br /><br />
			@if(isset($mailSent) && $mailSent)
				Please check your email to verify your account.
			@else
				Activation email could not be sent. Please use this link to activate your account:
				<br />
				<a href="{{ $activationUrl }}">{{ $activationUrl }}</a>
			@endif
		</h2>
		<br /><br />
		@if(isset($mailSent) && $mailSent)
			<h4 class="text-center">Please also check your Spam / Junk folder for the activation email.</h4>
		@endif
	</div>
</div>
	</body>
</html>
