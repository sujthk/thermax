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

             @if(Auth::guard()->user()->user_type == 'ADMIN')      
                <li data-placement="bottom" title="Users" class="nav-item single-item {{ Nav::isRoute('users','has-class') }}">
                    <a href="{{ url('/users') }}">
                        <i class="ti-user"></i>
                        <span data-i18n="nav.widget.main"> Users</span>
                    </a>
                </li>
                 <li data-placement="bottom" title="Group Calculator" class="nav-item single-item {{ Nav::isRoute('group-calcluation','has-class') }}">
                    <a href="{{ url('/group-calcluation') }}">
                        
                        <i class="ti-harddrives"></i>
                        <span data-i18n="nav.widget.main"> Group Calculator</span>
                    </a>
                </li>
                <li class="nav-item single-item {{ Nav::isRoute('time-line','has-class') }}" >
                    <a href="{{ url('/time-line') }}">
                      <i class="ti-stats-up"></i>
                        <span data-i18n="nav.widget.main">Time Line</span>
                    </a>
                </li>
                <!--    <li class="nav-item single-item {{ Nav::isRoute('default/calculators','has-class') }}" >
                    <a href="{{ url('/default/calculators') }}">
                      <i class="ti-stats-up"></i>
                        <span data-i18n="nav.widget.main"> Default Values Calculators</span>
                    </a>
                </li> -->
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
                       <i class="ti-id-badge"></i>
                        <span data-i18n="nav.widget.main"> Unit Sets</span>
                    </a>
                </li>
                <li data-placement="bottom" title="Region" class="nav-item single-item {{ Nav::isRoute('region','has-class') }}" >
                    <a href="{{ url('/region') }}">
                        <i class="ti-world"></i>
                        <span data-i18n="nav.widget.main">Region</span>
                    </a>
                </li>
                 <li data-placement="bottom" title="Double Effect S2 Steam" class="nav-item single-item {{ Nav::isRoute('calculators/double-effect-s2','has-class') }}" >
                    <a href="{{ url('/calculators/double-effect-s2') }}">
                        <i class="ti-package"></i>
                        <span data-i18n="nav.widget.main">Double Effect S2 Steam</span>
                    </a>
                </li>
                <li data-placement="bottom" title="Double Effect H2 Steam" class="nav-item single-item {{ Nav::isRoute('calculators/double-effect-h2','has-class') }}" >
                    <a href="{{ url('/calculators/double-effect-h2') }}">
                        <i class="ti-package"></i>
                        <span data-i18n="nav.widget.main">Double Effect H2 Hot Water </span>
                    </a>
                </li>
                <li data-placement="bottom" title="Double Effect G2 Steam" class="nav-item single-item {{ Nav::isRoute('calculators/double-effect-g2','has-class') }}" >
                    <a href="{{ url('/calculators/double-effect-g2') }}">
                       <i class="ti-package"></i>
                        <span data-i18n="nav.widget.main">Double Effect G2 Steam</span>
                    </a>
                </li>
                @else
                @foreach(Auth::guard()->user()->userCalculators as $userCalculator)
                    <li data-placement="bottom" title="Double Effect S2 Steam" class="nav-item single-item {{ Nav::isRoute( route($userCalculator->calculator->route),'has-class') }}" >
                        <a href="{{ route($userCalculator->calculator->route)}}">
                            <i class="ti-package"></i>
                            <span data-i18n="nav.widget.main">{{$userCalculator->calculator->name}}</span>
                        </a>
                    </li> 
                @endforeach  
                @endif
            </ul>
        </div>
    </div>
    <!-- Menu aside end -->
