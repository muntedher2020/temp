@php
    $customizerHidden = 'customizer-hide';
    $configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'وصول خاطئ')

@section('page-style')
    <!-- Page -->
    <link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-misc.css')}}">
@endsection

@section('content')

    <div class="row row-cols-1 row-cols-lg-2 g-4 center p-5 m-4">
        <div class="col">
            <h2 class="mb-4 mx-2 text-center">403 - تم رفض الوصول</h2>
            <h4 class="mb-4 mx-2 text-center">عفوًا، ليس لديك إذن للوصول إلى هذه الصفحة.</h4>
            <h6 class="mx-2 text-center">
                <a href="{{ Route('login') }}" class="mx-2 text-center btn btn-primary waves-effect waves-light">تسجيل الدخول</a>
            </h6>

            {{--<h5 class="mx-2 text-align-justify">قد يقوم خادم الويب بإرجاع رمز حالة HTTP 403 Forbidden استجابةً لطلب من العميل لصفحة ويب أو مورد للإشارة إلى أنه يمكن الوصول إلى الخادم وفهم الطلب، لكنه يرفض اتخاذ أي إجراء آخر. استجابات رمز الحالة 403 هي نتيجة تكوين خادم الويب لرفض الوصول، لسبب ما، إلى المورد المطلوب من قبل العميل.</h5>--}}
        </div>
        <div class="col text-center">
            <img src="{{ asset('assets/img/illustrations/misc-coming-soon-illustration.png')}}" alt="misc-coming-soon" class="img-fluid zindex-1" width="190">
        </div>
    </div>

@endsection
