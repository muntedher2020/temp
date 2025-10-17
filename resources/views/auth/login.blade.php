@php
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'تسجيل الدخول')

@section('vendor-style')
    <!-- Vendor -->
    <link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />
@endsection

@section('page-style')
    <!-- Page -->
    <link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-auth.css')}}">
    <link rel="stylesheet" href="{{asset('assets/vendor/fonts/FugazOne/FugazOne-font.css')}}">
@endsection

@section('vendor-script')
    <script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
    <script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
    <script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>
@endsection

@section('page-script')
    <script src="{{asset('assets/js/pages-auth.js')}}"></script>
@endsection

@section('content')
    <div class="authentication-wrapper authentication-cover">
        <!-- Logo -->
        <a href="{{url('/')}}" class="gap-2 auth-cover-brand d-flex align-items-center">
            <span class="app-brand-logo demo">
                <img src="{{ asset('assets/img/logo/GCPI.png') }}" class="rounded img-fluid" style="width: 90px;">
            </span>
            <span class="app-brand-text demo text-heading fw-bold fs-1">
                <span class="feqrah">GCPI</span>
                <h5 class="mb-2 fw-semibold text-center">موانيء العراق</h5>
            </span>
        </a>
        {{-- <a href="{{url('/')}}" class="gap-2 auth-cover-brand d-flex align-items-center">
            <span class="app-brand-logo demo">@include('_partials.macros',["width"=>25,"withbg"=>'#666cff'])</span>
            <span class="app-brand-text demo text-heading fw-bold">{{config('variables.templateName')}}</span>
        </a> --}}
        <!-- /Logo -->
        <div class="m-0 authentication-inner row">
            <!-- Login -->
            <div class="px-4 py-4 d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-sm-5">
                <div class="pt-5 mx-auto w-px-400 pt-lg-0">
                    <h4 class="mb-2 fw-semibold text-center">مرحباً بك من جديد</h4>
                    <p class="mb-4 text-center">يرجى تسجيل الدخول إلى حسابك وبدء العمل</p>

                    <form class="mb-3" id="formAuthentication" method="POST" action="{{ Route('login') }}">
                        @csrf
                        {{-- <form id="formAuthentication" class="mb-3" action="{{url('/')}}" method="GET"> --}}
                        <div class="mb-3 form-floating form-floating-outline">
                            <input type="text" class="form-control" id="email" name="email" value="{{ old('email') }}" autofocus autocomplete="username"
                                placeholder="Enter your email or username" autofocus>
                            <label for="email">البريد الالكتروني</label>
                        </div>
                        <div class="mb-3">
                            <div class="form-password-toggle">
                                <div class="input-group input-group-merge">
                                    <div class="form-floating form-floating-outline">
                                        <input type="password" id="password" class="form-control" name="password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" />
                                        <label for="password">كلمة المرور</label>
                                    </div>
                                    <span class="cursor-pointer input-group-text"><i
                                            class="mdi mdi-eye-off-outline"></i></span>
                                </div>
                            </div>
                        </div>
                        {{-- <div class="mb-3 d-flex justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" name="remember" type="checkbox" id="remember-me">
                                <label class="form-check-label" for="remember-me">
                                    Remember Me
                                </label>
                            </div>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="mb-1 float-end">
                                    <span>Forgot Password ?</span>
                                </a>
                            @endif
                        </div> --}}

                        <button type="submit" class="btn btn-primary d-grid w-100">
                            تسجيل الدخول
                        </button>
                    </form>
                </div>
            </div>
            <!-- /Login -->

           <!-- /Left Section -->
           <div class="p-3 pb-2 d-none d-lg-flex col-lg-7 col-xl-8 align-items-center justify-content-center">
            <img src="{{asset('assets/img/illustrations/1688557739.png') }}"
                class="auth-cover-illustration w-50" alt="auth-illustration"
                data-app-light-img="illustrations/1688557739.png"
                data-app-dark-img="illustrations/1688557739.png" />
            {{-- <img src="{{asset('assets/img/illustrations/auth-cover-login-mask-'.$configData['style'].'.png') }}"
                class="authentication-image" alt="mask" style="margin-right: 400px !important"
                data-app-light-img="illustrations/GCPI.png"
                data-app-dark-img="illustrations/GCPI.png" /> --}}
        </div>
        <!-- /Left Section -->
        </div>
    </div>
@endsection
