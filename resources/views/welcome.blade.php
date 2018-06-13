<!DOCTYPE html>
<html>
    <head>
        <!--meta-->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Smart Car</title>

        <link href="{{asset('/web-css/welcome_note.css')}}" rel="stylesheet" type="text/css" />
        <link href="{{asset('/web-css/bootstrap.min.css')}}"" rel="stylesheet" type="text/css" />
       <!--  <link href="{{asset('/admin-css/bootstrap/css/owl.carousel.css')}}"" rel="stylesheet" type="text/css" />
        <link href="{{asset('/admin-css/bootstrap/css/owl.theme.css')}}"" rel="stylesheet" type="text/css" /> -->
        <link href="{{asset('/web-css/slider.css')}}"" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
        <link href="http://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet"/>
        <style>
              html, body
            {
                height: 100%;
               font-family: 'Ubuntu', sans-serif;
             
            }
           /* .welcome_outer #owl-demo .item img{
                display: inline-block;
                width: 275px;
                height: auto;
            }*/
            .carousel-indicators
            {
                display: none;
            }
        </style>
    </head>
    <body>
    <!--welcome-outer-->
    <div class="welcome_outer">
        <!--main_bg_section-->
        <nav class="navbar navbar-default navbar-fixed-top">
  <div class="container">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#">Smart Car</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
       
      </ul>
      
      <ul class="nav navbar-nav navbar-right">
         <li><a href="#download">Download</a></li>
        <li><a href="#features">Features</a></li>
       <!--  <li><a href="#signin">Log In</a></li> -->
        <li><a href="#contact">Contact</a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>
        <div id="bootstrap-touch-slider" class="carousel bs-slider fade  control-round indicators-line" data-ride="carousel" data-pause="hover" data-interval="2500" >

            <!-- Indicators -->
            <ol class="carousel-indicators">
                <li data-target="#bootstrap-touch-slider" data-slide-to="0" class="active"></li>
                <li data-target="#bootstrap-touch-slider" data-slide-to="1"></li>
                <li data-target="#bootstrap-touch-slider" data-slide-to="2"></li>
            </ol>

            <!-- Wrapper For Slides -->
            <div class="carousel-inner" role="listbox">

                <!-- Third Slide -->
                <div class="item active">

                    <!-- Slide Background -->
                    <img src="{{asset('/images/4.jpg')}}" alt="Bootstrap Touch Slider"  class="slide-image"/>
                    <div class="bs-slider-overlay"></div>

                    <div class="container">
                        <div class="row">
                    
                            <!-- Slide Text Layer -->
                            <div class="slide-text slide_style_right" id="first_slide">
                                <h1> SMARTEST CAR APP<br/>  on the Block</h1>
                                <p class="lead">
                               Start your UBER Like Taxi service instantly. Try out now.
                            </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Slide -->

                <!-- Second Slide -->
                <div class="item">

                    <!-- Slide Background -->
                    <img src="{{asset('/images/5.jpg')}}" alt="Bootstrap Touch Slider"  class="slide-image"/>
                    <div class="bs-slider-overlay"></div>
                    <!-- Slide Text Layer -->
                    <div class="slide-text slide_style_center hero_two_main">
                        <h1>Advanced Control over your business</h1>
                        <p class="hero_two"style="max-width:700px;margin: auto;line-height: 30px;">Your driver partners and Customers both matter, manage everything, right from the driver feedback, customer ratings and payout all from a single place.</p>
                      
                    </div>
                </div>
                <!-- End of Slide -->

                <!-- Third Slide -->
                <div class="item">

                    <!-- Slide Background -->
                    <img src="{{asset('/images/3.jpg.jpg')}}" alt="Bootstrap Touch Slider"  class="slide-image"/>
                    <div class="bs-slider-overlay"></div>
                    <!-- Slide Text Layer -->
                    <div class="slide-text slide_style_right">
                        <h1>SIMPLE AND POWERFUL</h1>
                        <p>Works like no other, Ready to GO. Smart Car LITE App is business ready.</p>
                      <!--   <a href="http://bootstrapthemes.co/" target="_blank" class="btn btn-default" data-animation="animated fadeInLeft">select one</a>
                        <a href="http://bootstrapthemes.co/" target="_blank" class="btn btn-primary" data-animation="animated fadeInRight">select two</a> -->
                    </div>
                </div>
                <!-- End of Slide -->

            </div><!-- End of Wrapper For Slides -->

            <!-- Left Control -->
            <a class="left carousel-control" href="#bootstrap-touch-slider" role="button" data-slide="prev">
                <span class="fa fa-angle-left" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>

            <!-- Right Control -->
            <a class="right carousel-control" href="#bootstrap-touch-slider" role="button" data-slide="next">
                <span class="fa fa-angle-right" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>

        </div> <!-- End  bootstrap-touch-slider Slider -->
       
        <!--features-section-->
        <section class="features" id="features">
            <div class="features_heading text-center">
                FEATURES
            </div>
            <div class="features_heading_content text-center">
            <p>Explore the latest trends with Taxi Anytime Features.</p>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6  col-xs-12">
                        <div class="featuers_main text-center">
                            <i class="fa fa-sign-in fa-3x"></i>
                            <h4>SOCIAL LOGIN</h4>
                            <p>Leverage the ease of social signing in to on board your customers instantly.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6  col-xs-12">
                        <div class="featuers_main text-center">
                            <i class="fa fa-cab fa-3x"></i>
                            <h4>CARS AROUND ME</h4>
                            <p>Display cars which are nearby to your customers and vice versa, so they can book one, immediately.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-xs-12">
                        <div class="featuers_main text-center">
                            <i class="fa fa-history fa-3x"></i>
                            <h4>ETA</h4>
                            <p>Time is Key, Estimated Time of Arrival informs your customers about the car's arrival time, so they'll know it's time to put on their shoes and step out for the ride.</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4 col-md-6  col-xs-12">
                        <div class="featuers_main text-center">
                            <i class="fa fa-share-alt fa-3x"></i>
                            <h4>FARE CALCULATOR</h4>
                            <p>Fares are calculated instantly upon completion of the ride, The calculator also provides an estimated price, before your customer takes their ride.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6  col-xs-12">
                        <div class="featuers_main text-center">
                            <i class="fa fa-flag fa-3x"></i>
                            <h4>LANGUAGE TRANSLATION</h4>
                            <p>Hablas español? Oui. Expand your taxi business Globally, one by one or all at the same time, you can customize the app to add any number of languages. Subarashi</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6  col-xs-12">
                        <div class="featuers_main text-center">
                            <i class="fa fa-tags fa-3x"></i>
                            <h4>Promotion Code / REFERENCE</h4>
                            <p>Promotional codes are a great way to reward your customers, Offer your customers promo discounts on their rides easily. A truly rewarding experience !</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

         <!--view-demo-section-->
        <section class="view_demo" id="download">
            <div class="view_demo_overlay"></div>
            <div class="container text-center">
                <div class="features_heading">
                    DOWNLOAD
                </div>
                <div class="features_heading_content">
                    <p>Start your own business </p>
                </div>
                <p>Do you want to take a look at our product? Click the following link</p>
                <div class="login_btn">
                        <a href="#"><img src="{{asset('/images/istore.png')}}" alt="istore" /></a>
                         <a href="#"><img src="{{asset('/images/google_play.png')}}" alt="play_store"/></a>
                    </div>
            </div>
        </section>

        <!--mobile_features_section-->
        <section class="mobile_features_section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 col-md-6 col-xs-12">
                        <ul class="list-unstyled">
                            <li>
                                <div class="media">
                                  <div class="media-left">
                                    <i class="fa fa-user fa-3x"></i>
                                  </div>
                                  <div class="media-body">
                                    <h4 class="media-heading">ADMIN PANEL</h4>
                                    <p>Intuitive admin panel gives granular control over various aspects of the apps and the behind the scenes. Puts you at the control</p>
                                  </div>
                                </div>
                            </li>
                             <li>
                                <div class="media">
                                  <div class="media-left">
                                    <i class="fa fa-credit-card fa-3x"></i>
                                  </div>
                                  <div class="media-body">
                                    <h4 class="media-heading">PAYOUT MANAGEMENT</h4>
                                    <p>Pay your drivers to the cent, precise calculators working in the background make sure, you don't have to struggle paying out your driver partners.</p>
                                  </div>
                                </div>
                            </li>
                             <li>
                                <div class="media">
                                  <div class="media-left">
                                    <i class="fa fa-signal fa-3x"></i>
                                  </div>
                                  <div class="media-body">
                                    <h4 class="media-heading">ANALYTICS</h4>
                                    <p>All the data you need to take business decisions and plan your next quarter efficiently. Meet our Integrated Analytics system.</p>
                                  </div>
                                </div>
                            </li>
                             <li>
                                <div class="media">
                                  <div class="media-left">
                                    <i class="fa fa-plane fa-3x"></i>
                                  </div>
                                  <div class="media-body">
                                    <h4 class="media-heading">TRIP MANAGEMENT</h4>
                                   <p>Right from Invoices to Driver Feedback management, view and control using powerful tools from the admin panel.</p>
                                  </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="col-lg-4 col-md-6 col-xs-12">
                        <div class="text-center">
                            <img src="{{asset('/images/phone-img.gif')}}" alt="phone-screen" width="275"/>
                        </div>
                       <!--  <div id="owl-demo" class="owl-carousel owl-theme">
                          <div class="item text-center">
                              <img src="http://xuber.appoets.co/assets/landing/img/iphone-2.png" width="300" alt="app_screen" >
                            </div>
                            <div class="item text-center">
                                <img src="http://xuber.appoets.co/assets/landing/img/iphone-2.png" alt="app_screen" >
                            </div>
                         
                        </div> -->
                    </div>
                </div>
        </section>

        <section class="signin" id="signin">
            <div class="signin_overlay"></div>
            <div class="container">
                <div class="col-lg-6">
                    <p class="lead">Time & Tide wait for none, When you're in the business that grows quickly, speed and execution are key, Smart Car Lite has the right tools to be your partner to . Push Through to the top in an already dominated market. Talk to us to know more or check the demo below. It's time to Do it your way! 
                    </p>
                    <!-- <div class="login_btn">
                        <a href="#" class="btn">USER LOGIN</a>
                         <a href="#" class="btn">PROVIDER LOGIN</a>
                    </div> -->
                </div>
            </div>
        <!--carousel-->  
        </section>
        
        <!--footer-section-->
        <footer id="contact">
            <div class="container">
                <div class="col-lg-4 col-md-4 col-xs-12">
                    <div class="about_us">
                        <h3 class="section_title">About Us</h3>
                        <p>
                           Advanced car rental software that has all the features of UBER, Has fleet management, Vehicle hire, multiple payment gateways. Comes with Mobile apps for IOS and Android.
                        </p>
                        <h3 class="section_title"></h3>
                        <p class="copy_right">
                            All rights reserved Copyright © 2016 <br/> Smart Car: <a href="http://smart-car.tech/car-rental-software/" target="_blank" >Car Rental Software</a>
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-xs-12">
                    <div class="contact_address">
                        <h3 class="section_title">Our Address</h3>
                        <ul class="contact_info list-unstyled">
                            <li>
                                <i class="fa fa-location-arrow"></i>2nd Floor, No:36, 80 Feet Main Road,
                                14th Main Road, Sector- 7,HSR Layout,
                                Bangalore-560102
                            </li>
                            <li>
                                <i class="fa fa-phone"></i>
                                080- 65653510, 65655615
                            </li>
                            <li>
                                <i class="fa fa-envelope"></i>
                                info@smart-car.tech
                            </li>
                        </ul>
                        <h3 class="section_title">Connect with us</h3>
                        <ul class="social_media list-unstyled">
                            <li>
                                <a href="javascript:void(0);">
                                    <i class="fa fa-facebook"></i>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);">
                                    <i class="fa fa-twitter"></i>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);">
                                    <i class="fa fa-dribbble"></i>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);">
                                    <i class="fa fa-github-alt"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-xs-12">
                    <div class="comment_section">
                        <h3 class="section_title">Drop us a line</h3>
                        <div class="form-group">
                            <input type="text" class="form-control" placeholder="Name" />
                        </div>
                         <div class="form-group">
                            <input type="email" class="form-control" placeholder="Email" />
                        </div>
                         <div class="form-group">
                            <textarea class="form-control" rows="6" placeholder="Message"></textarea>
                        </div>
                        <div class="form-group clearfix">
                            <button type="sumbit" class="btn pull-right">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
      <a href="javascript:void(0);" id="scroll" title="Scroll to Top" style="display: none;">Top<span></span></a>
    </div>

    </body>
    <!--js-->
    <script src="{{asset('/admin-css/bootstrap/js/jquery.min.js')}}"></script>
    <script src="{{asset('/admin-css/bootstrap/js/bootstrap.min.js')}}"></script>
    <script src="{{asset('/admin-css/bootstrap/js/owl.carousel.min.js')}}"></script>
   
    <!--owl-carousel-script-->
  <!--    <script>
        $(document).ready(function() {
         
          $("#owl-demo").owlCarousel({
         
              navigation : false, // Show next and prev buttons
              slideSpeed : 300,
               pagination: true,
              singleItem:true,
                autoPlay: 3000
          });
         
        });
     </script> -->
     <!--scrolltop & navbar-->
     <script type='text/javascript'>
    $(document).ready(function(){ 
        $(window).scroll(function(){ 
            if ($(this).scrollTop() > 100) { 
                $('#scroll').fadeIn(); 
                $('.navbar-default').css("background-color" , "#fff");
                $('.navbar-default').css("box-shadow", "rgba(0, 0, 0, 0.4) 0px 2px 1px 0px");
                $('.navbar-default').css("border-bottom", "1px solid rgb(221, 221, 221");
                $('.navbar-brand').css("color","#000");
                $('.navbar-nav>li>a').css("color","#000");
                $('.welcome_outer .navbar-default .navbar-brand:hover').css("color","#000");
            } else { 
                $('#scroll').fadeOut(); 
                $('.navbar-default').css("background-color", "transparent");
                $('.navbar-brand').css("color","#fff");
                $('.navbar-nav>li>a').css("color","#fff");
                $('.navbar-default').css("box-shadow", "none");
                $('.navbar-default').css("border-bottom", "none");
                $('.welcome_outer .navbar-default .navbar-brand:hover').css("color","#fff");
            } 
        }); 
        $('#scroll').click(function(){ 
            $("html, body").animate({ scrollTop: 0 }, 1000); 
            return false; 
        }); 
    });
    </script>
<script>
$(document).ready(function(){

  $("a").on('click', function(event) {
    if (this.hash !== "") {
      event.preventDefault();
      var hash = this.hash;
      $('html, body').animate({
        scrollTop: $(hash).offset().top
      }, 800, function(){
   
        window.location.hash = hash;
      });
    }
  });
});
</script>

</html>
