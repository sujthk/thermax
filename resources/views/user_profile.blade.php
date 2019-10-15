@extends('layouts.app') 

@section('content')
<!-- Main-body start -->
<div class="main-body">
<div class="page-wrapper">
<!-- Page-header start -->
<div class="page-header">
<div class="page-header-title">
<h4>User Profile</h4>
</div>
<div class="page-header-breadcrumb">
<ul class="breadcrumb-title">
<li class="breadcrumb-item">
<a href="index.html">
<i class="icofont icofont-home"></i>
</a>
</li>                       
<li class="breadcrumb-item"><a href="#!">User Profile</a>
</li>
</ul>
</div>
</div>
<div class="page-body">
<div class="card">
<div class="pull-right">

</div>
<!-- Main body start -->
<div class="main-body user-profile">


<!-- Page-header end -->
<!-- Page-body start -->
<div class="page-body">
<!--profile cover start-->
<div class="row">
<div class="col-lg-12">
<div class="cover-profile">
<div class="profile-bg-img">
<img class="profile-bg-img img-fluid" src="{{asset('assets/images/user-profile/bg-img1.jpg')}}" alt="bg-img">
<div class="card-block user-info">
<div class="col-md-12">
<div class="media-left">
    <a href="#" class="profile-image">
        <img class="user-img img-circle" src="{{asset('assets/images/avatar-1.png')}}" alt="user-img">
    </a>
</div>
<div class="media-body row">
    <div class="col-lg-12">
        <div class="user-title">
            <h2>{{$user->name}}</h2>
            <span class="text-white">{{$user->user_type}}</span>
        </div>
    </div>
    
</div>
</div>
</div>
</div>
</div>
</div>
</div>
<!--profile cover end-->
<div class="row">
<div class="col-lg-12">

<!-- tab header end -->
<!-- tab content start -->
<div class="tab-content">
<!-- tab panel personal start -->
<div class="tab-pane active" id="personal" role="tabpanel">
<!-- personal card start -->
<div class="card">
<div class="card-header">
<h5 class="card-header-text">About Me</h5>
<button id="edit-btn" type="button" class="btn btn-sm btn-primary waves-effect waves-light f-right">
<i class="icofont icofont-edit"></i>
</button>
<button id="change-password"  type="button" class="btn btn-sm btn-primary waves-effect waves-light ">
Change Password
</button>
</div>
<div class="card-block">
<div class="view-info" id="user-list">
    <div class="row">
        <div class="col-lg-12">
            <div class="general-info">
                <div class="row">
                    <div class="col-lg-12 col-xl-6">
                        <table class="table m-0">
                            <tbody>
                                <tr>
                                    <th scope="row">Full Name</th>
                                    <td>{{$user->name}}</td>
                                </tr>
                                <tr>
                                    <th scope="row">User Type</th>
                                    <td>{{$user->user_type}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- end of table col-lg-6 -->
                    <div class="col-lg-12 col-xl-6">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th scope="row">Email</th>
                                    <td><a href="#!">{{$user->email}}</a></td>
                                </tr>
                                <tr>
                                    <th scope="row">Unit Set</th>
                                    <td>{{$user->unitSet->name}}</td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>
                    <!-- end of table col-lg-6 -->
                </div>
                <!-- end of row -->
            </div>
            <!-- end of general info -->
        </div>
        <!-- end of col-lg-12 -->
    </div>
    <!-- end of row -->
</div>
  <!-- end of view-info -->
<div class="edit-info" style="display: none;">
    <div class="row">
        <div class="col-lg-12">

            <div class="general-info">
                 <form id="add_user" method="post" action="{{ url('user_profile/edit',[$user->id]) }}" enctype="multipart/form-data">
                                {{ csrf_field() }}
                <div class="row">
                    <div class="col-lg-6">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-addon"> Name</span>
                                             <input id="name" name="name" type="text" value="{{ $user->name }}" required class="form-control">
                                        </div>
                                    </td>
                                </tr>
                               
                                <tr>
                                    <td>
                                         <div class="input-group">
                                         <span class="input-group-addon">User Type </span>
                                        <select name="user_type" id="user_type" required class="form-control">
                                                <option {{ $user->user_type == 'ADMIN' ? 'selected' : '' }} value="ADMIN">ADMIN</option>
                                                <option {{ $user->user_type == 'THERMAX_USER' ? 'selected' : '' }} value="THERMAX_USER">Thermax User</option>
                                                <option {{ $user->user_type == 'NON_THERMAX_USER' ? 'selected' : '' }} value="NON_THERMAX_USER">Non Thermax User</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>
                    <!-- end of table col-lg-6 -->
                    <div class="col-lg-6">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="input-group">
                                             <span class="input-group-addon"> Email</span>
                                            <input id="email" name="email" type="email" value="{{ $user->email }}" required class="form-control">
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-addon"> Unit Set</span>
                                            <select name="unit_set_id" id="unit_set_id" required class="form-control">
                                                @foreach ($unit_sets as $unit_set)
                                                    <option {{ $user->unit_set_id == $unit_set->id ? 'selected' : '' }} value="{{ $unit_set->id }}">{{ $unit_set->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- end of table col-lg-6 -->
                </div>
                <!-- end of row -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary waves-effect waves-light m-r-20">Save</button>
                    <a href="#!" id="edit-cancel" class="btn btn-danger waves-effect">Cancel</a>
                </div>
            </form>
            </div>
            <!-- end of edit info -->
        </div>
        <!-- end of col-lg-12 -->
    </div>
    <!-- end of row -->
</div>
                                <!-- end of edit-info -->

                                  <!-- end of view-info -->
<div class="edit-password" style="display: none;">
    <div class="row">
        <div class="col-lg-12">
            <div class="general-info">
                 <form id="add_user" method="post" action="{{ url('/password_change') }}" enctype="multipart/form-data">
                                {{ csrf_field() }}
                <div class="row">
                    <div class="col-lg-6">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="input-group">
                                             <input id="old_password" name="old_password" type="password" placeholder="Old Password" required class="form-control">
                                        </div>
                                    </td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </div>
                    <!-- end of table col-lg-6 -->
                    <div class="col-lg-6">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="input-group">
                                            
                                            <input name="password" type="Password" placeholder="New Password" required class="form-control">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- end of table col-lg-6 -->
                </div>
                <!-- end of row -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary waves-effect waves-light m-r-20">Save</button>
                    <a href="#!" id="cancel-password" class="btn btn-danger waves-effect">Cancel</a>
                </div>
            </form>
            </div>
            <!-- end of edit info -->
        </div>
        <!-- end of col-lg-12 -->
    </div>
    <!-- end of row -->
</div>
</div>
<!-- end of card-block -->
</div>
<div class="row">
<div class="col-lg-12">
<div class="card">
    <div class="card-header">
        <h5 class="card-header-text">Description About Me</h5>
       
    </div>
    <div class="card-block user-desc">
        <div class="view-desc">
            <p></p>
        </div>
        
    </div>
</div>
</div>
</div>
<!-- personal card end-->
</div>
<!-- tab pane personal end -->

</div>
<!-- tab content end -->
</div>
</div>
</div>
<!-- Page-body end -->

</div>
<!-- Main body end -->

</div>
<!-- /.box -->
</div>
<!-- /.box -->
</div>
</div>

@endsection

@section('scripts') 
<script>
$(document).ready(function(){
  $("#edit-btn").click(function(){
    $("#user-list").hide();
    $(".edit-password").hide();
    $(".edit-info").show();
  });
  $("#edit-cancel").click(function(){
    $(".edit-info").hide();
    $(".edit-password").hide();
     $("#user-list").show();
  });

$("#change-password").click(function(){
    $("#user-list").hide();
     $(".edit-info").hide();
    $(".edit-password").show();
  });
  $("#cancel-password").click(function(){
     $("#user-list").show();
     $(".edit-info").hide();
    $(".edit-password").hide();
  });
});
</script>
@endsection