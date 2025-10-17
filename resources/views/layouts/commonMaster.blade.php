<!DOCTYPE html>
<html lang="{{ session()->get('locale') ?? app()->getLocale() }}" dir="{{ $configData['textDirection'] }}"
	class="{{ $configData['style'] }}-style {{ $navbarFixed ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $menuFlipped ?? '' }} {{ $menuOffcanvas ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
	data-theme="{{ $configData['theme'] }}" data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{url('/')}}" data-framework="laravel"
	data-template="{{ $configData['layout'] . '-menu-' . $configData['theme'] . '-' . $configData['style'] }}">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

	<title>@yield('title') |
		{{ config('variables.templateName') ? config('variables.templateName') : 'TemplateName' }} -
		{{ config('variables.templateSuffix') ? config('variables.templateSuffix') : 'TemplateSuffix' }}</title>

	<meta name="description" content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
	<meta name="keywords" content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}">
	<!-- laravel CRUD token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<!-- Canonical SEO -->
	<link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}">
	<!-- Favicon -->
	{{-- <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" /> --}}

	<!-- Include Styles -->
	@include('layouts/sections/styles')

	<!-- Include Scripts for customizer, helper, analytics, config -->
	@include('layouts/sections/scriptsIncludes')

    @livewireStyles

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>

<body>
    <div id="preloader">
        <div id="loading-wrapper" class="show">
            <div id="loading-text">
                <img src="{{ asset("assets/img/logo/GCPI.png") }}" style="width: 140px" alt="">
            </div>
            <div id="loading-content"></div>
        </div>
    </div>

	<!-- Layout Content -->
    @yield('layoutContent')
	<!--/ Layout Content -->

	<!-- Include Scripts -->
	@include('layouts/sections/scripts')

    @livewireScripts

    <!-- Core Scripts -->
    @yield('vendor-script')
    @yield('page-script')
    @stack('scripts')
</body>

</html>
