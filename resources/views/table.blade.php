<!DOCTYPE html>
<html lang="en">

<head>
    <title>Thermax</title>
    <!-- HTML5 Shim and Respond.js IE9 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
      <![endif]-->
    <!-- Meta -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Favicon icon -->
    <link rel="icon" href="{{asset('dark-assets/assets/images/thermax-logo.png')}}" type="image/x-icon">
    <!-- Google font-->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">
    <!-- Required Fremwork -->

    <link rel="stylesheet" type="text/css" href="{{asset('bower_components/bootstrap/dist/css/bootstrap.min.css')}}">
	<!-- themify-icons line icon -->
	<link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/icon/themify-icons/themify-icons.css')}}">
	<!-- ico font -->
	<link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/icon/icofont/css/icofont.css')}}">
    <!-- flag icon framework css -->
    <link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/pages/flag-icon/flag-icon.min.css')}}">
    <!-- Menu-Search css -->
    <link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/pages/menu-search/css/component.css')}}">
    <!-- Nvd3 chart css -->
    <link rel="stylesheet" type="text/css" href="{{asset('bower_components/nvd3/build/nv.d3.css')}}" media="all">
    <!-- Am-chart css -->
    <!-- <link rel="stylesheet" type="text/css" href="assets/dashboard/amchart/css/export.css" media="all" /> -->
    <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" />
    <!-- Style.css -->
    <link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/css/style.css')}}">
    <!--color css-->
    <link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/css/color/color-1.css')}}" id="color"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('styles')

   
</head>

<div class="table-responsive">
<table class="table table-bordered">
  <thead>
    <tr>
      <th scope="col"> Sr.</th>
      <th scope="col"> Description</th>
      <th scope="col"> Unit</th>
      <th scope="col"> Cooling Mode</th>
    </tr>
     <tr>
      <th scope="col"> </th>
      <th scope="col"> Capacity (+/-3%)</th>
      <th scope="col"> TR </th>
      <th scope="col">  114.0 </th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th scope="row">1</th>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
    </tr>
    <tr>
      <th scope="row">2</th>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
    </tr>
    <tr>
      <th scope="row">3</th>
      <td colspan="2">Larry the Bird</td>
      <td>@twitter</td>
    </tr>
  </tbody>
</table>
</div>





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
    <!-- amchart js -->
    <script src="https://www.amcharts.com/lib/3/amcharts.js"></script>
    <script src="https://www.amcharts.com/lib/3/serial.js"></script>
    <script src="https://www.amcharts.com/lib/3/plugins/export/export.min.js"></script>
    <script src="https://www.amcharts.com/lib/3/themes/light.js"></script>
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
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    @yield('scripts')
     <script>

        $( document ).ready(function() {
            @if (session('status'))

                swal({
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