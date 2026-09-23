@php
	// The toggle keeps the visitor where they are: the route sends them back to
	// the page they switched from.
	$currentLocale = app()->getLocale() === 'ms' ? 'ms' : 'en';
@endphp
<div class="lang-toggle {{ $langToggleClass ?? '' }}" role="group" aria-label="{{ __('app.language') }}">
	<i class="fa fa-globe lang-toggle-icon" aria-hidden="true"></i>
	<a href="{{ route('language.switch', 'en') }}" class="lang-option{{ $currentLocale === 'en' ? ' is-active' : '' }}"
		lang="en" title="{{ __('app.language_english') }}"
		@if($currentLocale === 'en') aria-current="true" @endif>EN</a>
	<span class="lang-sep" aria-hidden="true">|</span>
	<a href="{{ route('language.switch', 'ms') }}" class="lang-option{{ $currentLocale === 'ms' ? ' is-active' : '' }}"
		lang="ms" title="{{ __('app.language_malay') }}"
		@if($currentLocale === 'ms') aria-current="true" @endif>BM</a>
</div>
