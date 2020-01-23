@extends('layouts.app') 

@section('styles')  
    <!-- Data Table Css -->
    <link rel="stylesheet" type="text/css" href="{{asset('bower_components/datatables.net-bs4/css/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('dark-assets/assets/pages/data-table/css/buttons.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('bower_components/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css')}}">

    <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.20/datatables.min.js"></script>
@endsection

@section('content')
<!-- Main body start -->
<div class="main-body user-profile">
<div class="page-wrapper">
<!-- Page-header start -->
<div class="page-header">
<div class="page-header-title">
<h4>User Reports</h4>
</div>
<div class="page-header-breadcrumb">
<ul class="breadcrumb-title">
    <li class="breadcrumb-item">
        <a href="index.html">
            <i class="icofont icofont-home"></i>
        </a>
    </li>
    <li class="breadcrumb-item"><a href="#!">User Reports</a>
    </li>
    <li class="breadcrumb-item"><a href="#!">User Reports</a>
    </li>
</ul>
</div>
</div>
<!-- Page-header end -->
<!-- Page-body start -->
<div class="page-body">
<!--profile cover start-->
<!--profile cover end-->
<div class="row">
<div class="col-lg-12">
    <!-- tab header start -->
    <div class="tab-header">
        <ul class="nav nav-tabs md-tabs tab-timeline" role="tablist" id="mytab">
           
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#binfo" role="tab">User Report</a>
                <div class="slide"></div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#contacts" role="tab">User Trackings</a>
                <div class="slide"></div>
            </li>
            @if($user->user_type !== "ADMIN")
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#unitset" role="tab">Unit Sets</a>
                <div class="slide"></div>
            </li>
            @endif
           
        </ul>
    </div>
    <!-- tab header end -->
    <!-- tab content start -->
    <div class="tab-content">
       
        <!-- tab pane info start -->
        <div class="tab-pane active" id="binfo" role="tabpanel">
            <!-- info card start -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-header-text">User Services</h5>
                    <div class="pull-left" style="float: right;">
                        <a href="{{ url()->previous() }}" class="btn btn-info" title="Back" >Back</a>
                    </div>
                </div>
                <div class="card-block">
                    <div class="row">
                        <div class="card-block contact-details">
                            <div class="data_table_main table-responsive dt-responsive">
                                <table id="simpletable" class="table  table-striped table-bordered nowrap">
                                    <thead>
                                        <tr>
                                            <th>S.No</th>
                                            <th>Customer Name</th>
                                            <th>Project Name</th>
                                            <th>Opportunity Number</th>
                                            <th>Calculator</th>
                                            <th>Region</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php($i=1)
                                        @foreach($user_reports as $user_report)
                                        <tr>
                                            <td>{{$i}}</td>
                                            <td>{{$user_report->name}}</td>
                                            <td>{{$user_report->project}}</td>
                                            <td>
                                               {{$user_report->phone}}
                                            </td> 
                                            <td>{{$user_report->calculator_code}}</td>
                                            <td>
                                            @if($user_report->region_type == 1)
                                                Domestic
                                            @elseif($user_report->region_type == 2)
                                                USA
                                            @elseif($user_report->region_type == 3)
                                                Europe
                                            @elseif($user_report->region_type == 4)
                                                Both
                                            @endif

                                            </td>
                                            <td> <a href="{{ url('user-profile/download',[$user_report->id]) }}" class="btn btn-info btn-sm m-b-0">Download</a></td>
                                        </tr>
                                        @php($i++)
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- tab pane info end -->
        <!-- tab pane contact start -->
        <div class="tab-pane" id="contacts" role="tabpanel">
            <div class="row">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-sm-12">
                            <!-- contact data table card start -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-header-text">User Trackings</h5>
                                    <div class="pull-left" style="float: right;">
                                        <a href="{{ url()->previous() }}" class="btn btn-info" title="Back" >Back</a>
                                    </div>
                                </div>
                                <div class="card-block contact-details">
                                    <div class="data_table_main table-responsive dt-responsive">
                                        <table id="myTable" class="table  table-striped table-bordered nowrap">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>IP Address</th>
                                                    <th>Loged IN</th>
                                                    <th>Loged OUT</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php($i=1)
                                                @foreach($user_trackings as $user_tracking)
                                                <tr>
                                                    <td>{{$i}}</td>
                                                    <td>{{$user_tracking->ip_address}}</td>
                                                    <td>{{date("d-m-Y , H:i A", strtotime($user_tracking->logged_in))}}</td>
                                                    <td>
                                                        @if($user_tracking->logged_out)
                                                        {{date("d-m-Y , H:i A", strtotime($user_tracking->logged_out))}}
                                                        @endif
                                                    </td>
                                                   
                                                </tr>
                                                @php($i++)
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- contact data table card end -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- tab pane contact start -->
        <div class="tab-pane" id="unitset" role="tabpanel">
            <div class="row">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-sm-12">
                            <!-- contact data table card start -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-header-text">Unit Sets</h5>
                                    <div class="pull-left" style="float: right;">
                                        <a href="{{ url()->previous() }}" class="btn btn-info" title="Back" >Back</a>
                                    </div>
                                </div>
                                <div class="card-block contact-details">
                                    <div class="data_table_main table-responsive dt-responsive">
                                        <table id="myTable" class="table  table-striped table-bordered nowrap">
                                            <thead>
                                                <tr>
                                                <th>Name</th>
                                                <th>TemperatureUnit</th>
                                                <th>LengthUnit</th>
                                                <th>WeightUnit</th>
                                                <th>PressureUnit</th>
                                                <th style="width: 8%">Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($unit_sets as $unit_set) 
                                                <tr>
                                                    <td>{{ $unit_set->name }}</td>
                                                    <td>{{ $unit_set->TemperatureUnit }}</td>
                                                    <td>{{ $unit_set->LengthUnit }}</td>    
                                                    <td>{{ $unit_set->WeightUnit }}</td>
                                                    <td>{{ $unit_set->PressureUnit }}</td>
                                                    <td>
                                                        <a href="{{ url('unit-sets/edit',[$unit_set->id]) }}" class="btn btn-primary btn-sm">Edit</a>
                                                    </td> 
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- contact data table card end -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- tab content end -->
</div>
</div>
</div>
<!-- Page-body end -->
</div>
</div>
<!-- Main body end -->

@endsection

@section('scripts') 

    <script src="{{asset('bower_components/datatables.net/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('bower_components/datatables.net-buttons/js/dataTables.buttons.min.js')}}"></script>
    <script src="{{asset('bower_components/datatables.net-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{asset('bower_components/datatables.net-responsive/js/dataTables.responsive.min.js')}}"></script>
    <script src="{{asset('bower_components/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js')}}"></script>
    <script src="{{asset('assets/pages/data-table/js/data-table-custom.js')}}"></script>
    <script type="text/javascript">
        $(document).ready( function () {
            $('#myTable').DataTable();
        } );
    </script>
@endsection