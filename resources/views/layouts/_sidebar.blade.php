<!-- Menu header end -->
    <!-- Menu aside start -->
    <div class="main-menu">
       <!--  <div class="main-menu-header">
            <img class="img-40" src="{{asset('dark-assets/assets/images/user.png')}}" alt="User-Profile-Image">
            <div class="user-details">
                <span>John Doe</span>
                <span id="more-details">UX Designer<i class="ti-angle-down"></i></span>
            </div>
        </div> -->
        <div class="main-menu-content">
            <ul class="main-navigation">
<!--
             
                <li class="nav-title" data-i18n="nav.category.navigation">
                    <i class="ti-line-dashed"></i>
                    <span>Navigation</span>
                </li>
-->
                <li data-placement="bottom" title="Dashboard" class="nav-item single-item {{ Nav::isRoute('dashboard','has-class') }}">
                    <a href="{{ url('/dashboard') }}">
                        <i class="ti-home"></i>
                        <span data-i18n="nav.widget.main"> Dashboard</span>
                    </a>
                </li>
                <li data-placement="bottom" title="Users" class="nav-item single-item {{ Nav::isRoute('users','has-class') }}">
                    <a href="{{ url('/users') }}">
                        <i class="ti-user"></i>
                        <span data-i18n="nav.widget.main"> Users</span>
                    </a>
                </li>
                <li data-placement="bottom" title="Metallurgies" class="nav-item single-item {{ Nav::isRoute('metallurgies','has-class') }}">
                    <a href="{{ url('/metallurgies') }}">
                       <i class="ti-dropbox-alt"></i>
                        <span data-i18n="nav.widget.main"> Metallurgies</span>
                    </a>
                </li>
                
                <li data-placement="bottom" title="Metallurgy Calculators" class="nav-item single-item {{ Nav::isRoute('tube-metallurgy/calculators','has-class') }}">
                    <a href="{{ url('/tube-metallurgy/calculators') }}">
                        <i class="ti-dropbox"></i>
                        <span data-i18n="nav.widget.main"> Metallurgy Calculators</span>
                    </a>
                </li>
             <!--    <li class="nav-item single-item {{ Nav::isRoute('default/calculators','has-class') }}" >
                    <a href="{{ url('/default/calculators') }}">
                      <i class="ti-stats-up"></i>
                        <span data-i18n="nav.widget.main"> Default Values Calculators</span>
                    </a>
                </li> -->
                <li data-placement="bottom" title="Calculation Values" class="nav-item single-item {{ Nav::isRoute('chiller/calculation-values','has-class') }}" >
                    <a href="{{ url('/chiller/calculation-values') }}">
                       <i class="ti-pulse"></i>
                        <span data-i18n="nav.widget.main"> Calculation Values</span>
                    </a>
                </li>
                 <li data-placement="bottom" title="Calculation Keys" class="nav-item single-item {{ Nav::isRoute('/calculation-keys','has-class') }}" >
                    <a href="{{ url('/calculation-keys') }}">
                        <i class="ti-panel"></i>
                        <span data-i18n="nav.widget.main"> Calculation Keys</span>
                    </a>
                </li>
                <li data-placement="bottom" title="Notes" class="nav-item single-item {{ Nav::isRoute('error-notes','has-class') }}" >
                    <a href="{{ url('/error-notes') }}">
                        <i class="ti-map"></i>
                        <span data-i18n="nav.widget.main"> Notes</span>
                    </a>
                </li>
                <li data-placement="bottom" title="Unit Sets" class="nav-item single-item {{ Nav::isRoute('unit-sets','has-class') }}" >
                    <a href="{{ url('/unit-sets') }}">
                        <i class="ti-user"></i>
                        <span data-i18n="nav.widget.main"> Unit Sets</span>
                    </a>
                </li>
                <li data-placement="bottom" title="Region" class="nav-item single-item {{ Nav::isRoute('region','has-class') }}" >
                    <a href="{{ url('/region') }}">
                        <i class="ti-user"></i>
                        <span data-i18n="nav.widget.main">Region</span>
                    </a>
                </li>
                <li data-placement="bottom" title="Calculators" class="nav-item {{ Nav::hasSegment('calculators',[1],'has-class') }}">
                    <a href="#!">
                        <i class="ti-joomla"></i>
                        <span data-i18n="nav.dash.main">Calculators</span>
                    </a>
                    <ul class="tree-1 ">
                        <li data-placement="bottom" title="Double Effect S2 Steam" class="{{ Nav::isRoute('calculators/double-effect-s2','has-class') }}">
                            <a href="{{ url('/calculators/double-effect-s2') }}" data-i18n="nav.dash.default"> Double Effect S2 Steam</a>
                        </li>  
                        <li data-placement="bottom" title="Double Effect H2 Steam" class="{{ Nav::isRoute('calculators/double-effect-h2','has-class') }}">
                            <a href="{{ url('/calculators/double-effect-h2') }}" data-i18n="nav.dash.default"> Double Effect H2 Steam </a>
                        </li>
                        <li data-placement="bottom" title="Double Effect G2 Steam" class="{{ Nav::isRoute('calculators/double-effect-g2','has-class') }}">
                            <a href="{{ url('/calculators/double-effect-g2') }}" data-i18n="nav.dash.default"> Double Effect G2 Steam </a>
                        </li>
                        
                       
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <!-- Menu aside end -->
