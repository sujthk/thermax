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
        <img class="user-img img-circle" src="{{Auth::guard()->user()->image_path}}" alt="user-img">
    </a>
</div>
<div class="media-body row">
    <div class="col-lg-12">
        <div class="user-title">
            <h2>{{Auth::guard()->user()->name}}</h2>
            <span class="text-white">{{Auth::guard()->user()->user_type}}</span>
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
<h5 class="card-header-text">Change password</h5>
</div>
<div class="card-block">
<div class="edit-password">
    <div class="row">
        <div class="col-lg-12">
            <div class="general-info">
                 <form id="password_change_form" method="post" action="{{ url('/user-password-change') }}" enctype="multipart/form-data">
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