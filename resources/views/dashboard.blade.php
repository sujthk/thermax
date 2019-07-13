@extends('layouts.app') 

@section('styles')	
	
@endsection

@section('content')
	<div class="main-body">
	    <div class="page-wrapper">
	        <div class="page-header">
	            <div class="page-header-title">
	                <h4>Analytic Dashboard</h4>
	            </div>
	            <div class="page-header-breadcrumb">
	                <ul class="breadcrumb-title">
	                    <li class="breadcrumb-item">
	                        <a href="index.html">
	                            <i class="icofont icofont-home"></i>
	                        </a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Dasboard</a>
	                    </li>
	                    <li class="breadcrumb-item"><a href="#!">Analytic Dashboard</a>
	                    </li>
	                </ul>
	            </div>
	        </div>
	        <div class="page-body">
	            <div class="col-md-12 col-xl-12">
	                <div class="card">
	                    <div class="card-header">
	                        <h5>LINE CHART</h5>
	                        <span>Lorem ipsum dolor sit amet, consectetur adipisicing elit</span>
	                    </div>
	                    <div class="card-block">
	                        <div id="linechart" class="nvd-chart"></div>
	                    </div>
	                </div>
	            </div>

	            <!-- Live-chart end -->
	            <!-- Last activity start -->
	            <div class="col-xl-12">
	                <div class="card">
	                    <div class="card-header">
	                        <h5>Last Activity</h5>
	                        <div class="f-right">
	                            <label class="label label-success">Today</label>
	                            <label class="label label-danger">Month</label>
	                        </div>
	                    </div>
	                    <div class="card-block table-border-style">
	                        <div class="table-responsive analytic-table">
	                            <table class="table">
	                                <tbody>
	                                    <tr>
	                                        <td>
	                                            <span class="count text-primary">2567</span>
	                                            <span class="table-msg">Total Message Sent</span>
	                                        </td>
	                                        <td>34%</td>
	                                    </tr>
	                                    <tr>
	                                        <td>
	                                            <span class="count text-success">3058</span>
	                                            <span class="table-msg">Last Activity</span>
	                                        </td>
	                                        <td>56%</td>
	                                    </tr>
	                                    <tr>
	                                        <td>
	                                            <span class="count text-inverse">6451</span>
	                                            <span class="table-msg">Total Message Received</span>
	                                        </td>
	                                        <td>84%</td>
	                                    </tr>
	                                    <tr>
	                                        <td>
	                                            <span class="count text-warning">9512</span>
	                                            <span class="table-msg">Monthly Income</span>
	                                        </td>
	                                        <td>79%</td>
	                                    </tr>
	                                    <tr>
	                                        <td>
	                                            <span class="count text-info">9874</span>
	                                            <span class="table-msg">Total Transfer</span>
	                                        </td>
	                                        <td>81%</td>
	                                    </tr>
	                                </tbody>
	                            </table>
	                        </div>
	                    </div>
	                </div>
	            </div>
	            <!-- Last activity end -->
	        </div>
	    </div>
	</div>
@endsection
	
@section('scripts')	
	<script type="text/javascript" src="{{asset('dark-assets/assets/pages/dashboard/analytic-dashboard.js')}}"></script>

@endsection