<!DOCTYPE html>
<html>

<head>
    @isset($pageConfigs)
        {!! Helper::updatePageConfig($pageConfigs) !!}
    @endisset
    @php
        $configData = Helper::appClasses();
    @endphp

    @extends('layouts/commonMaster')

    @php
        /* Display elements */
        $contentNavbar = $contentNavbar ?? true;
        $isNavbar = $isNavbar ?? true;
        $isMenu = $isMenu ?? true;
        $isFlex = $isFlex ?? false;
        $isFooter = $isFooter ?? true;
        $customizerHidden = $customizerHidden ?? '';
        $pricingModal = $pricingModal ?? false;

        /* HTML Classes */
        $navbarDetached = 'navbar-detached';
        $menuFixed = isset($configData['menuFixed']) ? $configData['menuFixed'] : '';
        $navbarFixed = isset($configData['navbarFixed']) ? $configData['navbarFixed'] : '';
        $footerFixed = isset($configData['footerFixed']) ? $configData['footerFixed'] : '';
        $menuCollapsed = isset($configData['menuCollapsed']) ? $configData['menuCollapsed'] : '';
        $menuFlipped = isset($configData['menuFlipped']) ? $configData['menuFlipped'] : '';
        /* $menuOffcanvas = (isset($configData['menuOffcanvas']) ? $configData['menuOffcanvas'] : ''); */

        /* Content classes */
        $container = $container ?? 'container-xxl';
    @endphp

</head>

@section('layoutContent')
    <div class="layout-wrapper layout-content-navbar {{ $isMenu ? '' : 'layout-without-menu' }}">
        <div class="layout-container">

            @if ($isMenu)
                {{-- @include('layouts/sections/menu/verticalMenu') --}}
                @php
                    $roles = Spatie\Permission\Models\Role::pluck('name')->toArray();
                @endphp
                @role($roles)
                    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                        <div class="app-brand demo">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">
                                    <img src="{{ asset('assets/img/logo/GCPI.png') }}" class="rounded img-fluid"
                                        style="width: 50px;">
                                    {{-- @include('_partials.macros', ['width' => 25, 'withbg' => '#666cff']) --}}
                                </span>
                                <span class="app-brand-text demo menu-text fw-bold ms-2 fs-5">ادارة البيانات</span>
                            </a>

                            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                                <svg width="22" height="22" viewBox="0 0 22 22" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M11.4854 4.88844C11.0081 4.41121 10.2344 4.41121 9.75715 4.88844L4.51028 10.1353C4.03297 10.6126 4.03297 11.3865 4.51028 11.8638L9.75715 17.1107C10.2344 17.5879 11.0081 17.5879 11.4854 17.1107C11.9626 16.6334 11.9626 15.8597 11.4854 15.3824L7.96672 11.8638C7.48942 11.3865 7.48942 10.6126 7.96672 10.1353L11.4854 6.61667C11.9626 6.13943 11.9626 5.36568 11.4854 4.88844Z"
                                        fill="currentColor" fill-opacity="0.6" />
                                    <path
                                        d="M15.8683 4.88844L10.6214 10.1353C10.1441 10.6126 10.1441 11.3865 10.6214 11.8638L15.8683 17.1107C16.3455 17.5879 17.1192 17.5879 17.5965 17.1107C18.0737 16.6334 18.0737 15.8597 17.5965 15.3824L14.0778 11.8638C13.6005 11.3865 13.6005 10.6126 14.0778 10.1353L17.5965 6.61667C18.0737 6.13943 18.0737 5.36568 17.5965 4.88844C17.1192 4.41121 16.3455 4.41121 15.8683 4.88844Z"
                                        fill="currentColor" fill-opacity="0.38" />
                                </svg>
                            </a>
                        </div>

                        <div class="menu-inner-shadow"></div>

                        <ul class="py-1 menu-inner">
                            @can('Dashboards')
                                {{-- لوحة التحكم --}}
                                <li class="menu-item {{ request()->is('/') ? 'active' : '' }}">
                                    <a href="{{ Route('Dashboard') }}" class="menu-link">
                                        <i class="menu-icon tf-icons mdi mdi-home-outline"></i>
                                        <div>{{ trans('sidebar.dashboard') }}</div>
                                    </a>
                                </li>
                            @endcan

                            {{-- Dynamic Menu Items --}}
                            @dynamicMenu

                            {{-- المستخدمين --}}
                            @can('users')
                                <li
                                    class="menu-item {{ request()->is('Administrators-Accounts') ? 'active open' : (request()->is('Customers-Accounts') ? 'active open' : '') }}">
                                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                                        <i class='menu-icon tf-icons mdi mdi-account-outline'></i>
                                        <span class="menu-title">{{ trans('sidebar.Users accounts') }}</span>
                                    </a>
                                    <ul class="menu-sub">
                                        @role(['OWNER', 'Administrator', 'Supervisor'])
                                            <li class="menu-item {{ request()->is('Administrators-Accounts') ? 'active' : '' }}">
                                                <a href="{{ Route('Administrators-Accounts.index') }}" class="menu-link">
                                                    <i class=""></i>
                                                    <div>{{ trans('sidebar.Admin accounts') }}</div>
                                                </a>
                                            </li>
                                        @endrole
                                        <li class="menu-item {{ request()->is('Customers-Accounts') ? 'active' : '' }}">
                                            <a href="{{ Route('Customers-Accounts.index') }}" class="menu-link">
                                                <i class=""></i>
                                                <div>{{ trans('sidebar.Customer accounts') }}</div>
                                            </a>
                                        </li>

                                    </ul>
                                </li>
                            @endcan

                            {{-- التصاريح والأدوار --}}
                            @if (Auth::check())
                                @can('permissions-roles')
                                    <li
                                        class="menu-item {{ request()->is('Permissions&Roles/Account-Permissions') ? 'active open' : (request()->is('Permissions&Roles/Account-Roles') ? 'active open' : '') }}">
                                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                                            <i class='menu-icon tf-icons mdi mdi-shield-account'></i>
                                            <span class="menu-title">{{ trans('sidebar.Permissions and roles') }}</span>
                                        </a>
                                        <ul class="menu-sub">
                                            @can('permissions')
                                                <li
                                                    class="menu-item {{ request()->is('Permissions&Roles/Account-Permissions') ? 'active' : '' }}">
                                                    <a href="{{ Route('Account-Permissions.index') }}" class="menu-link">
                                                        <i class=""></i>
                                                        <div>{{ trans('sidebar.Permissions') }}</div>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('roles')
                                                <li
                                                    class="menu-item {{ request()->is('Permissions&Roles/Account-Roles') ? 'active' : '' }}">
                                                    <a href="{{ Route('Account-Roles.index') }}" class="menu-link">
                                                        <i class=""></i>
                                                        <div>{{ trans('sidebar.Roles') }}</div>
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                            @endif

                            {{-- أدوات التطوير --}}
                            @role(['OWNER'])
                                <li
                                    class="menu-item {{ request()->is('Module-Generator', 'ModuleManager', 'basic-groups','data-management','report-generator','dashboard-builder') ? 'active open' : '' }}">
                                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                                        <i class='menu-icon tf-icons mdi mdi-tools'></i>
                                        <span class="menu-title">أدوات التطوير</span>
                                    </a>
                                    <ul class="menu-sub">
                                        <li class="menu-item {{ request()->is('Module-Generator') ? 'active' : '' }}">
                                            <a href="{{ Route('Module-Generator') }}" class="menu-link">
                                                <i class="mdi mdi-auto-fix"></i>
                                                <div>مولد الوحدات</div>
                                            </a>
                                        </li>
                                        {{-- إدارة الوحدات --}}
                                        <li class="menu-item {{ request()->is('ModuleManager') ? 'active' : '' }}">
                                            <a href="{{ Route('ModuleManager') }}" class="menu-link">
                                                <i class="mdi mdi-cog"></i>
                                                <div>إدارة الوحدات</div>
                                            </a>
                                        </li>
                                    </ul>
                                    <ul class="menu-sub">
                                        {{-- المجموعات الأساسية --}}
                                        @can('BasicGroup-list')
                                            <li class="menu-item {{ request()->is('basic-groups*') ? 'active' : '' }}">
                                                <a href="{{ route('basic-groups.index') }}" class="menu-link">
                                                    <i class="mdi mdi-folder-multiple-outline"></i>
                                                    <div>المجموعات الأساسية</div>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                    <ul class="menu-sub">
                                        {{-- إدارة البيانات --}}
                                        @can('data-management-access')
                                            <li class="menu-item {{ request()->is('data-management*') ? 'active' : '' }}">
                                                <a href="{{ route('data-management.index') }}" class="menu-link">
                                                    <i class="menu-icon tf-icons mdi mdi-database-export"></i>
                                                    <div>إدارة البيانات</div>
                                                </a>
                                            </li>
                                        @endcan
                                        {{-- مولد التقارير --}}
                                        @can('report-generator-access')
                                            <li class="menu-item {{ request()->is('report-generator*') ? 'active' : '' }}">
                                                <a href="{{ route('report-generator.index') }}" class="menu-link">
                                                    <i class="menu-icon tf-icons mdi mdi-chart-box-multiple-outline"></i>
                                                    <div>مولد التقارير</div>
                                                </a>
                                            </li>
                                        @endcan
                                        {{-- مصمم الداشبورد --}}
                                        @can('dashboard-builder-access')
                                            <li class="menu-item {{ request()->is('dashboard-builder*') ? 'active' : '' }}">
                                                <a href="{{ route('dashboard-builder.index') }}" class="menu-link">
                                                    <i class="menu-icon tf-icons mdi mdi-view-dashboard-edit"></i>
                                                    <div>مصمم الداشبورد</div>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endrole
                        </ul>
                    </aside>
                @endrole
            @endif

            <!-- Layout page -->
            <div class="layout-page">

                <!-- BEGIN: Navbar-->
                @if ($isNavbar)
                    @include('layouts/sections/navbar/navbar')
                @endif
                <!-- END: Navbar-->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    @if ($isFlex)
                        <div class="{{ $container }} d-flex align-items-stretch flex-grow-1 p-0">
                        @else
                            <div class="{{ $container }} flex-grow-1 container-p-y">
                    @endif

                    @yield('content')

                    <!-- pricingModal -->
                    @if ($pricingModal)
                        @include('_partials/_modals/modal-pricing')
                    @endif
                    <!--/ pricingModal -->

                </div>
                <!-- / Content -->

                <!-- Footer -->
                @if ($isFooter)
                    @include('layouts/sections/footer/footer')
                @endif
                <!-- / Footer -->
                <div class="content-backdrop fade"></div>
            </div>
            <!--/ Content wrapper -->
        </div>
        <!-- / Layout page -->
    </div>

    @if ($isMenu)
        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    @endif
    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->
@endsection
