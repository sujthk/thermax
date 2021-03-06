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
        <img class="user-img img-circle" src="{{$user->image_path}}" alt="user-img">
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
                                    <th scope="row">Mobile Number</th>
                                    <td>{{$user->mobile}}</td>
                                </tr>
                                <tr>
                                    <th scope="row">User Type</th>
                                    <td>{{$user->user_type}}</td>
                                </tr>
                                @if($user->unitset_status == 1)
                                <tr>
                                    <th scope="row">Unit Set Create</th>
                                    <td> <a href="{{ url('/unit-sets') }}" class="btn btn-primary btn-sm">Unit Set Create</a></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <!-- end of table col-lg-6 -->
                    <div class="col-lg-12 col-xl-6">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th scope="row">Username</th>
                                    <td><a href="#!">{{$user->username}}</a></td>
                                </tr>
                                <tr>
                                    <th scope="row">Region Type</th>
                                    <td> 
                                        @if($user->region_type == 1)
                                            Domestic
                                        @elseif($user->region_type == 2)
                                            USA
                                        @elseif($user->region_type == 3)
                                            Europe
                                        @elseif($user->region_type == 4)
                                            Both
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Unit Set</th>
                                    <td>{{$user->unitSet->name}}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Language</th>
                                    <td>{{ ucwords($user->language->name) }}</td>
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
                                            <span class="input-group-addon"> Mobile</span>
                                             <input id="mobile" name="mobile" type="text" value="{{ $user->mobile }}" placeholder="Enter your Mobile Number" required class="form-control">
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td>
                                         <div class="input-group">
                                            <span class="input-group-addon"> Profile Picture</span>
                                             <input id="image" name="image" type="file" value="" class="form-control">
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
                                <!-- <tr>
                                    <td>
                                        <div class="input-group">
                                             <span class="input-group-addon"> Username</span>
                                            <input id="email" name="email" type="email" value="{{ $user->username }}" required class="form-control">
                                        </div>
                                    </td>
                                </tr> -->
                                <tr>
                                    <td>
                                         <div class="input-group">
                                            <span class="input-group-addon"> Language</span>
                                             <select name="language_id" id="language" required class="form-control">
                                                @foreach ($languages as $language)
                                                    <option {{ $user->language_id == $language->id ? 'selected' : '' }} value="{{ $language->id }}">{{ $language->name }}</option>
                                                @endforeach
                                             </select>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-addon"> Unit Set</span>
                                            @if($user->unitset_status == 0)
                                                <input id="unit_set_id" name="unit_set_id" type="text" value="{{ $user->unit_set_id }}"  required class="form-control" readonly>
                                            @else
                                                <select name="unit_set_id" id="unit_set_id" required class="form-control">
                                                    @foreach ($unit_sets as $unit_set)
                                                        <option {{ $user->unit_set_id == $unit_set->id ? 'selected' : '' }} value="{{ $unit_set->id }}">{{ $unit_set->name }}</option>
                                                    @endforeach
                                                </select>    
                                            @endif
                                                
                                            
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
                 <form id="password_change_form" method="post" action="{{ url('/password_change') }}" enctype="multipart/form-data">
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
                                            
                                            <input name="password" type="Password" placeholder="New Password" id="password" required onKeyUp="checkPasswordStrength();" class="form-control">
                                        </div>
                                        <div class="input-group">
                                            <input name="confirm_password" type="Password" placeholder="Confirm Password" id="confirm_password" required class="form-control">
                                        </div>
                                    </td>
                                    
                                </tr>
                                <tr>
                                    <td>
                                        <div id="password-strength-status"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- end of table col-lg-6 -->
                </div>
                <!-- end of row -->
                <div class="text-center">
                    <button type="submit" id="submit_button" class="btn btn-primary waves-effect waves-light m-r-20">Save</button>
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
<script type="text/javascript">

    function checkPasswordStrength() {
        var number = /([0-9])/;
        var alphabets = /([a-zA-Z])/;
        var special_characters = /([~,!,@,#,$,%,^,&,*,-,_,+,=,?,>,<])/;
        var status = false;

        if ($('#password').val().length < 6) {
            $('#password-strength-status').removeClass();
            $('#password-strength-status').addClass('weak-password');
            $('#password-strength-status').html("Weak (should be atleast 6 characters.)");
            status = true;
        } else {
            if ($('#password').val().match(number) && $('#password').val().match(alphabets) && $('#password').val().match(special_characters)) {
                $('#password-strength-status').removeClass();
                $('#password-strength-status').addClass('strong-password');
                $('#password-strength-status').html("Strong");
                status = false;
            } else {
                $('#password-strength-status').removeClass();
                $('#password-strength-status').addClass('medium-password');
                $('#password-strength-status').html("Medium (should include alphabets, numbers and special characters.)");
                status = true;
            }
        }

        $("#submit_button").prop('disabled', status);
    }

    $( "#password_change_form" ).submit(function( event ) {
        if($('#password').val() != $('#confirm_password').val()){
            alert("password and confirm password not matching");
            return false;
        }
      
        return true;
    });
</script>
@endsection