{{-- Dynamic Menu Items --}}
@php
    $menuItems = config('dynamic-menu.menu_items', []);
@endphp

{{-- النظام الديناميكي للقوائم --}}
@foreach($menuItems as $item)
    {{-- تحقق من أن العنصر غير مخفي أو أن المستخدم لديه صلاحية عرض العناصر المخفية --}}
    @if(!isset($item['hidden']) || !$item['hidden'])
        @if($item['type'] === 'group')
            @can($item['permission'])
                @php
                    $isActiveOpen = false;
                    foreach($item['active_routes'] ?? [] as $route) {
                        if(request()->is($route)) {
                            $isActiveOpen = true;
                            break;
                        }
                    }
                    $activeClass = $isActiveOpen ? 'active open' : '';
                @endphp

                <li class="menu-item {{ $activeClass }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class='menu-icon tf-icons {{ $item['icon'] }}'></i>
                        <span class="menu-title">{{ $item['title'] }}</span>
                    </a>
                    <ul class="menu-sub">
                        @foreach($item['children'] ?? [] as $child)
                            @if($child['type'] === 'item')
                                @if($child['permission'] === null || (auth()->check() && auth()->user()->can($child['permission'])))
                                    @php
                                        $isChildActive = false;
                                        // التحقق من active_route (مفرد) أو active_routes (مصفوفة)
                                        if(isset($child['active_route'])) {
                                            $isChildActive = request()->is($child['active_route']);
                                        } elseif(isset($child['active_routes'])) {
                                            foreach($child['active_routes'] as $route) {
                                                if(request()->is($route)) {
                                                    $isChildActive = true;
                                                    break;
                                                }
                                            }
                                        } elseif(isset($child['route'])) {
                                            // استخدام route كـ fallback
                                            $isChildActive = request()->is($child['route']);
                                        }
                                    @endphp
                                    <li class="menu-item {{ $isChildActive ? 'active' : '' }}">
                                        <a href="{{ Route($child['route']) }}" class="menu-link">
                                            <i class="{{ $child['icon'] }}"></i>
                                            <div>{{ $child['title'] }}</div>
                                        </a>
                                    </li>
                                @endif
                            @endif
                        @endforeach
                    </ul>
                </li>
            @endcan
        @elseif($item['type'] === 'item')
            @if($item['permission'] === null || (auth()->check() && auth()->user()->can($item['permission'])))
                @php
                    $isItemActive = false;
                    // التحقق من active_route (مفرد) أو active_routes (مصفوفة)
                    if(isset($item['active_route'])) {
                        $isItemActive = request()->is($item['active_route']);
                    } elseif(isset($item['active_routes'])) {
                        foreach($item['active_routes'] as $route) {
                            if(request()->is($route)) {
                                $isItemActive = true;
                                break;
                            }
                        }
                    } elseif(isset($item['route'])) {
                        // استخدام route كـ fallback
                        $isItemActive = request()->is($item['route']);
                    }
                @endphp
                <li class="menu-item {{ $isItemActive ? 'active' : '' }}">
                    <a href="{{ Route($item['route']) }}" class="menu-link">
                        <i class="menu-icon tf-icons {{ $item['icon'] }}"></i>
                        <div>{{ $item['title'] }}</div>
                    </a>
                </li>
            @endif
        @endif
    @endif
@endforeach
