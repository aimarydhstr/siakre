<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="author backend" content="Danu Andrean">
  <meta name="author frontend" content="M N Hikam">
  <meta name="description" content="system data prodi teknik elektro UAD">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="shortcut icon" href="https://uhb.ac.id/wp-content/uploads/2024/03/logo_UHB_r-1.png" type="image/x-icon">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>@yield('title') - {{ config('app.name') }}</title>
  <!-- Bootstrap core CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.3/css/bootstrap.min.css">
  <!-- <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet"> -->

  <!-- chart -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.bundle.min.js"></script>

  <!-- fontawesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" integrity="sha512-+4zCK9k+qNFUR5X+cKL9EIR+ZOhtIloNl9GIKS57V1MyNsYpYcUrUeQc9vNfzsWfV28IaLL3i96P9sdNyeRssA==" crossorigin="anonymous" />
    
    <!-- Custom styles for this template -->
  <link href="{{asset('css/style.css') }}" rel="stylesheet">

  <!-- pdf -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.2.228/pdf.min.js"></script>
  <!-- <script src="//mozilla.github.io/pdf.js/build/pdf.js"></script> -->

  <!-- Bootstrap core JavaScript -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.3/js/bootstrap.bundle.min.js"></script>

  <!-- datepicker -->
  <!-- Special version of Bootstrap that is isolated to content wrapped in .bootstrap-iso -->
  <link rel="stylesheet" href="https://formden.com/static/cdn/bootstrap-iso.css" />
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.min.css" integrity="sha512-rxThY3LYIfYsVCWPCW9dB0k+e3RZB39f23ylUYTEuZMDrN/vRqLdaCBo/FbvVT6uC2r0ObfPzotsfKF9Qc5W5g==" crossorigin="anonymous" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js" integrity="sha512-T/tUfKSV1bihCnd+MxKD0Hm1uBBroVYBOYSk1knyvQ9VyZJpc/ALb4P0r6ubwVPSGB2GvjeoMAJJImBG12TiaQ==" crossorigin="anonymous"></script>
  

</head>
<body>

  @yield('nav')


  <!-- Menu Toggle Script -->
  @yield('js')
  <script>
    $("#menu-toggle").click(function(e) {
      e.preventDefault();
      $("#wrapper").toggleClass("toggled");
      $("#navbarSupportedContent").toggleClass("swipe");
    });

    $('#nav-icon1').click(function(){
      $(this).toggleClass('open');
    });

    $(window).on('resize', function() {
      if ( $("#wrapper.toggled").length != null ) {
        $("#wrapper").removeClass("toggled");
        $("#nav-icon1").removeClass("open");
        $("#navbarSupportedContent").removeClass("swipe");
      };
    });
  </script>

</body>

</html>
