<!DOCTYPE html>
<html lang="en">

<head>
    <title>Email Template</title>
    <style>
        body {
            background-color: #fff;
        }

        @font-face {
            font-family: "f1";
            src: url({{asset('mail/font/nexa/NexaBold.otf')}});
        }

        @font-face {
            font-family: "f2";
            src: url({{asset('mail/font/nunito/Nunito-Regular.ttf')}});
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
            font-size: 20px;
            color: #2f2f2f;
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
    </style>
</head>

<body class="mail-bg">
    <section class="mail-template">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="mail-temp-logo">
                        <img src="{{asset('mail/images/Thermax-logo-fin.png')}}" alt="thermax-logo" class="img-fluid">
                    </div>
                    <div class="mail-top">
                        <h1>Hi Admin,</h1>
                        <!-- <p>Your thermax user account Vennil Yoav is expired.</p> -->
                    </div>
                    <div class="mail-back">
                        <div class="mail-logo">
                            <img src="{{asset('mail/images/expiry.png')}}" alt="email-logo" class="img-fluid">
                        </div>
                        <div class="mail-content">
                            <p>Your iChill account of {{ $user_email }} has expired..</p>
                            <!-- <h5>1994</h5> -->
                        </div>
                    </div>
                    <div class="social-media-mail">
                        <h5>Follow Us</h5>
                        <ul>
                            <li><a href="#"><img src="{{asset('mail/images/facebook.png')}}" alt="facebook-logo"></a></li>
                            <li><a href="#"><img src="{{asset('mail/images/twitter.png')}}" alt="twitter-logo"></a></li>
                            <li><a href="#"><img src="{{asset('mail/images/linkedin.png')}}" alt="linkedin-logo"></a></li>
                            <li><a href="#"><img src="{{asset('mail/images/instagram.png')}}" alt="instagram-logo"></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>