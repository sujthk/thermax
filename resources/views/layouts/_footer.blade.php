 <script type="text/javascript" src="{{asset('bower_components/jquery/dist/jquery.min.js')}} "></script>
    <script type="text/javascript" src="{{asset('bower_components/jquery-ui/jquery-ui.min.js')}} "></script>
    <script type="text/javascript" src="{{asset('bower_components/tether/dist/js/tether.min.js')}} "></script>
    <script type="text/javascript" src="{{asset('bower_components/bootstrap/dist/js/bootstrap.min.js')}} "></script>
    <!-- jquery slimscroll js -->
    <script type="text/javascript" src="{{asset('bower_components/jquery-slimscroll/jquery.slimscroll.js')}} "></script>
    <!-- modernizr js -->
    <script type="text/javascript" src="{{asset('bower_components/modernizr/modernizr.js')}} "></script>
    <script type="text/javascript" src="{{asset('bower_components/modernizr/feature-detects/css-scrollbars.js')}} "></script>
    <!-- classie js -->
    <script type="text/javascript" src="{{asset('bower_components/classie/classie.js')}} "></script>
    <!-- NVD3 chart -->
    <script src="{{asset('bower_components/d3/d3.js')}} "></script>
    <script src="{{asset('bower_components/nvd3/build/nv.d3.js')}} "></script>
    <script src="{{asset('dark-assets/assets/pages/chart/nv-chart/js/stream_layers.js')}}"></script>
    
    <!-- High-chart js -->
    <script src="{{asset('dark-assets/assets/pages/dashboard/high-chart/js/high-chart.js')}}"></script>
    <!-- Morris Chart js -->
    <script src="{{asset('bower_components/raphael/raphael.min.js')}} "></script>
    <script src="{{asset('bower_components/morris.js/morris.js')}} "></script>
    <!-- echart js -->
    <script src="{{asset('dark-assets/assets/pages/chart/echarts/js/echarts-all.js')}}" type="text/javascript"></script>
    <!-- i18next.min.js -->
    <script type="text/javascript" src="{{asset('bower_components/i18next/i18next.min.js')}} "></script>
    <script type="text/javascript" src="{{asset('bower_components/i18next-xhr-backend/i18nextXHRBackend.min.js')}} "></script>
    <script type="text/javascript" src="{{asset('bower_components/i18next-browser-languagedetector/i18nextBrowserLanguageDetector.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('bower_components/jquery-i18next/jquery-i18next.min.js')}}"></script>
    <!-- Custom js -->

    <script type="text/javascript" src="{{asset('dark-assets/assets/js/script.js')}}"></script>

    <link rel="stylesheet" href="{{asset('sweetalert2/dist/sweetalert2.min.css')}}">
    <script src="{{asset('sweetalert2/dist/sweetalert2.min.js')}}"></script>
    
    <!-- <script src="{{asset('assets/pages/user-profile.js')}}"></script> -->
    
    @yield('scripts')
     <script>
        $( document ).ready(function() {
            @if (session('status'))
                Swal.fire({
                    title: "{{session('message')}}",
                    icon:"{{session('status')}}",
                    timer: 2000,
                    button:true,
                });
            @endif 
        });
    </script>
</body>

</html>