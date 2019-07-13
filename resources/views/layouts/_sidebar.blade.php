<!-- Menu header end -->
    <!-- Menu aside start -->
    <div class="main-menu">
        <div class="main-menu-header">
            <img class="img-40" src="{{asset('dark-assets/assets/images/user.png')}}" alt="User-Profile-Image">
            <div class="user-details">
                <span>John Doe</span>
                <!-- <span id="more-details">UX Designer<i class="ti-angle-down"></i></span> -->
            </div>
        </div>
        <div class="main-menu-content">
            <ul class="main-navigation">
                <li class="nav-title" data-i18n="nav.category.navigation">
                    <i class="ti-line-dashed"></i>
                    <span>Navigation</span>
                </li>
                <li class="nav-item single-item has-class">
                    <a href="{{ url('/dashboard') }}">
                        <i class="ti-home"></i>
                        <span data-i18n="nav.widget.main"> Dashboard</span>
                    </a>
                </li>
                <li class="nav-item single-item">
                    <a href="{{ url('/users') }}">
                        <i class="ti-user"></i>
                        <span data-i18n="nav.widget.main"> Users</span>
                    </a>
                </li>
                <li class="nav-item ">
                    <a href="#!">
                        <i class="ti-home"></i>
                        <span data-i18n="nav.dash.main">Calculators</span>
                    </a>
                    <ul class="tree-1 ">
                        <li>
                            <a href="{{ url('/calculators/double-effect-s2') }}" data-i18n="nav.dash.default"> Double Effect Steam S2 </a></li>
                        <li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <!-- Menu aside end -->
