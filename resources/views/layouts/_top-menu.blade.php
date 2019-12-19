<!-- Menu header start -->
<nav class="navbar header-navbar">
    <div class="navbar-wrapper">
        <div class="navbar-logo">
            <a class="mobile-menu" id="mobile-collapse" href="#!">
                <i class="ti-menu"></i>
            </a>
            <a href="{{url('/dashboard')}}">
                <h6 class="mega-menu-title media-left" style="font-size: 1.5rem;color:#e10010;"> i-SelecTAC</h6>
            </a>
            <a class="mobile-options">
                <i class="ti-more"></i>
            </a>
        </div>
        <div class="navbar-container container-fluid">
            <div>
                <ul class="nav-left">
                    <li>
                        <a id="collapse-menu" href="#">
                            <i class="ti-menu"></i>
                        </a>
                    </li>
                    <li>
                        <a href="#!" onclick="javascript:toggleFullScreen()">
                            <i class="ti-fullscreen"></i>
                        </a>
                    </li>
                </ul>
                <ul class="nav-right">
                    <li class="user-profile header-notification">
                        <a href="#!">
                            <img src="{{asset('dark-assets/assets/images/user.png')}}" alt="User-Profile-Image">
                            <span>{{ Auth::guard()->user()->name }}</span>
                            <i class="ti-angle-down"></i>
                        </a>
                        <ul class="show-notification profile-notification">
                            <li>
                                <a href="#!">
                                    <i class="ti-settings"></i> Settings
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('profile') }}">
                                    <i class="ti-user"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('logout') }}">
                                    <i class="ti-layout-sidebar-left"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>