<!-- Menu header start -->
<nav class="navbar header-navbar">
    <div style="    background-color: #eceeef;" class="navbar-wrapper">
        <div class="navbar-logo">
           <div class="logo-img">
			     <img src="{{asset('assets/images/ichill.png')}}" alt="logo.png">
			</div>
            <a class="mobile-menu" id="mobile-collapse" href="#!">
                <i class="ti-menu"></i>
            </a>
            
            <a class="mobile-options">
                <i class="ti-more"></i>
            </a>
        </div>
        <div class="navbar-container container-fluid">
            <div>
                <ul class="nav-left">
                    <li>
                        <a id="collapse-menu" class="menu-hide-click" href="#">
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
                                <a href="{{ url('profile') }}">
                                    <i class="ti-user"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a href="">
                                    <i class=""></i> 
                                </a>
                            </li>
                            <li style="text-align: right;">
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