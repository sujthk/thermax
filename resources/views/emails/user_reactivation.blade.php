<!DOCTYPE html>
<html lang="en">

<head>
    <title>iChill Account Re-Activated</title>
    <!-- <style>
        body {
            background-color: #fff;
        }

        @font-face {
            font-family: "f1";
            src: url(../termax-mail/font/nexa/NexaBold.otf);
        }

        @font-face {
            font-family: "f2";
            src: url(../termax-mail/font/nunito/Nunito-Regular.ttf);
        }

        .mail-bg {
            width: 50%;
            margin: 0 auto;
        }

        .mail-template {
            padding: 80px 20px 40px;
            text-align: center;
            background-color: #eaeff2;
        }

        .mail-temp-logo {
            text-align: center;
        }

        .mail-top h1 {
            font-family: "f1";
            font-size: 35px;
            margin: 20px 0;
        }

        .mail-top p {
            font-family: "f2";
            font-size: 32px;
            font-weight: 600;
            color: #2f2f2f;
            margin: 0 auto;
        }

        .mail-logo {
            padding: 30px 0;
        }

        .mail-logo img {
            width: 50%;
        }

        .mail-content p {
            font-family: "f2";
            line-height: 30px;
            font-weight: 600;
            color: #2f2f2f;
            font-size: 20px;
        }

        .mail-back {
            background-color: #fff;
            padding: 40px 40px 60px 40px;
            margin: 40px 0 20px 0;
            border-radius: 10px;
        }

        .mail-content h5 {
            background: #e20010;
            padding: 10px 50px;
            border-radius: 5px;
            margin: 0 auto;
            display: inline-block;
            color: #fff;
            font-size: 19px;
            margin-top: 15px;
            font-family: "f1";
            letter-spacing: 5px;
        }

        .social-media-mail ul li {
            list-style: none;
            display: inline-block;
        }

        .social-media-mail ul li img {
            width: 80%;
        }

        .social-media-mail ul {
            padding: 0;
        }

        .social-media-mail h5 {
            font-family: "f1";
            font-size: 30px;
            margin: 20px 0;
            color: #242424;
        }
    </style> -->
</head>

<body class="mail-bg" style="
width: 50%;
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
                        <!-- <p>Your thermax account is re-activated successfully. You can login with your old credentials.
                        </p> -->
                    </div>
                    <div class="mail-back" style=" background-color: #fff;
                    padding: 40px 40px 60px 40px;
                    margin: 40px 0 20px 0;
                    border-radius: 10px;">
                        <div class="mail-logo" style="padding: 30px 0;">
                            <img style="width: 150px" src="{{asset('mail/images/email.png')}}" alt="email-logo" class="img-fluid">
                        </div>
                        <div class="mail-content">
                            <p style="font-family: 'MyWebFont', Fallback, sans-serif;
                            line-height: 30px;
                            font-weight: 600;
                            color: #2f2f2f;
                            font-size: 20px;">Your iChill account has been re-activated, please login with the existing credentials.</p>
                        </div>
                    </div>
                    <div class="social-media-mail" style="font-family: 'MyWebFont', Fallback, sans-serif;;
                    font-size: 30px;
                    margin: 20px 0;
                    color: #242424;">
                        <h5>Follow Us</h5>
                        <ul style=" padding: 0;">
                            <li style="list-style: none;display: inline-block;"><a href="#"><img
                                        src="{{asset('mail/images/facebook.png')}}" alt="facebook-logo"></a>
                            </li>
                            <li style="list-style: none;
            display: inline-block;"><a href="#"><img style="width: 80%;" src="{{asset('mail/images/twitter.png')}}"
                                        alt="twitter-logo"></a></li>
                            <li style="list-style: none;
            display: inline-block;"><a href="#"><img style="width: 80%;" src="{{asset('mail/images/linkedin.png')}}"
                                        alt="linkedin-logo"></a></li>
                            <li style="list-style: none;
            display: inline-block;"><a href="#"><img style="width: 80%;" src="{{asset('mail/images/instagram.png')}}"
                                        alt="instagram-logo"></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>