<!DOCTYPE html>
<html lang="en">

<head>
    <title>Thermax iChill</title>
    <!-- HTML5 Shim and Respond.js IE9 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
   
    <!-- Meta -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Favicon icon -->
   <link rel="icon" href="{{asset('assets/images/thermax_fav.png')}}" type="image/x-icon">
    <!-- Google font-->
    <link href="{{asset('assets/css/googleapis.css')}}" rel="stylesheet">
    <!-- Required Fremwork -->

    <link rel="stylesheet" type="text/css" href="{{asset('bower_components/bootstrap/dist/css/bootstrap.min.css')}}">
	<!-- themify-icons line icon -->
	<link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/icon/themify-icons/themify-icons.css')}}">
	<link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/css/all.min.css')}}">
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

<body class="fix-menu dark-layout">