<!DOCTYPE html>
<html lang="en">

<head>
    <title>iChill Password Reset</title>
</head>

<body class="mail-bg" style="
width: 90%;
margin: 0 auto;">
    <section class="mail-template" style="
     padding: 80px 20px 40px;
     text-align: center;
     background-color: #eaeff2;">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="mail-temp-logo" style="text-align: center;">
                        <img src="{{asset('mail/images/Thermax-logo-fin.png')}}" alt="thermax-logo" class="img-fluid">
                    </div>
                    <div class="mail-top">
                        <h1 style="font-family: 'MyWebFont', Fallback, sans-serif;;font-size: 35px;
                        margin: 20px 0;">Hi {{ $user_name }},</h1>
                        <!-- <p>Your thermax account password successfully reseted. You can login with the credentials below.
                        </p> -->
                    </div>
                    <div class="mail-back" style=" background-color: #fff;
                    padding: 40px 40px 60px 40px;
                    margin: 40px 0 20px 0;
                    border-radius: 10px;">
                        <div class="mail-logo">
                            <img src="{{asset('mail/images/resetpassword.png')}}" style="width: 150px" alt="email-logo" class="img-fluid">
                        </div>
                        <div class="">
                            <p style="font-family: 'MyWebFont', Fallback, sans-serif;
                            line-height: 30px;
                            font-weight: 600;
                            color: #2f2f2f;
                            font-size: 20px;">Your iChill password has been reset. Please login with the following credentials and change the password immediately after logging in.</p>
                            <h5 style="background: #e20010;
                            padding: 10px 10px;
                            border-radius: 5px;
                            margin: 0 auto;
                            display: inline-block;
                            color: #fff;
                            font-size: 15px;
                            margin-top: 15px;
                            font-family: 'MyWebFont', Fallback, sans-serif;
                            ">Username : {{ $user_email }}</h5>
                            <h5 style="background: #e20010;
                            padding: 10px 10px;
                            border-radius: 5px;
                            margin: 0 auto;
                            display: inline-block;
                            color: #fff;
                            font-size: 15px;
                            margin-top: 15px;
                            font-family: 'MyWebFont', Fallback, sans-serif;
                            ">Password : {{ $password }}</h5>
                        </div>
                    </div>
                    <div class="social-media-mail" style="font-family: 'MyWebFont', Fallback, sans-serif;;
                    font-size: 30px;
                    margin: 20px 0;
                    color: #242424;">
                        <h5>Follow Us</h5>
                        <ul style=" padding: 0;">
                            <li style="list-style: none;display: inline-block;"><a href="#"><img style="width: 40px, height: 40px" 
                                        src="{{asset('mail/images/facebook.png')}}" alt="facebook-logo"></a>
                            </li>
                            <li style="list-style: none;
            display: inline-block;"><a href="#"><img style="width: 40px, height: 40px"  src="{{asset('mail/images/twitter.png')}}"
                                        alt="twitter-logo"></a></li>
                            <li style="list-style: none;
            display: inline-block;"><a href="#"><img style="width: 40px, height: 40px"  src="{{asset('mail/images/linkedin.png')}}"
                                        alt="linkedin-logo"></a></li>
                            <li style="list-style: none;
            display: inline-block;"><a href="#"><img style="width: 40px, height: 40px"  src="{{asset('mail/images/instagram.png')}}"
                                        alt="instagram-logo"></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>