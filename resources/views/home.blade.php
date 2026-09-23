@include('static-layout/not-logged-header')

<div class="banner">
	<div class="content content-log">

		<img src="{{ $siteLogoUrl ?? asset('front_end/images/logo.png') }}" class="img-responsive center-block" alt="logo" />
		<h2 class="text-center">{{ $siteTitle ?? 'Personnel Logistic Accounting System' }}</h2>
<br />

		<form autocomplete="off" method="post" action="{{ url('/user/login-check') }}" name="login-form" id="login-form" class="login-form">

			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.login.service_id') }}</label>
				<input class="form-control" type="text" id="s_id" name="s_id" placeholder="{{ __('app.login.service_id_placeholder') }}" required />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.login.password') }}</label>
				<input class="form-control" type="password" id="password" name="password" placeholder="{{ __('app.login.password_placeholder') }}" required />
			</div>

			<div class="form-group text-center">
				<input class="btn btn-success btn-lg" type="submit" value="{{ __('app.login.sign_in') }}" id="submit" name="submit" /> <br />
				<a href="{{ url('/forgot-password') }}" class="redirect-link">{{ __('app.login.forgot_password') }}</a>
			</div>
		</form>

		<div class="log-btns text-center"><small>{{ __('app.login.not_registered') }}</small>
			<a href="{{ url('/register') }}" class="btn btn-info btn-block btn-log">{{ __('app.login.register_now') }}</a>
			<a href="{{ url('/site-admin') }}" class="btn btn-sm btn-default btn-block btn-log">{{ __('app.login.admin_login') }}</a>
		</div>



	</div>
</div>
<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		history.forward(1);
	});
</script>
</body>

</html>
