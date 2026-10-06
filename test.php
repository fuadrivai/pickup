<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ramadan Carousel</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body {
            background: url('https://source.unsplash.com/1600x900/?ramadan,mosque,green') no-repeat center center fixed;
            background-size: cover;
        }
        .carousel-inner {
            background: rgba(0, 50, 0, 0.8);
            padding: 20px;
            border-radius: 15px;
            border: 2px solid gold;
        }
        .table-dark {
            background: rgba(0, 100, 0, 0.9) !important;
            color: gold;
            font-size: 24px;
            border-radius: 10px;
        }
        .ramadan-title {
            font-family: 'Arabic Calligraphy', sans-serif;
            font-size: 40px;
            color: gold;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 2px 2px 8px gold;
        }
        .crescent {
            width: 60px;
            height: 60px;
            filter: drop-shadow(0 0 10px gold);
        }
    </style>
</head>
<body>

<div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel" data-interval="15000">
    <ol class="carousel-indicators">
        <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
        <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
    </ol>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <div class="container-fluid">
                <div class="row">
                    <div class="col text-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/1624/1624740.png" class="crescent" alt="Crescent Moon">
                        <h2 class="ramadan-title">Ramadan Mubarak</h2>
                        <div class="table-responsive">
                            <table class="table table-striped table-dark" border='0' width="100%">
                                <tbody>
                                    <tr>
                                        <td>Suhur Time</td>
                                        <td>04:30 AM</td>
                                    </tr>
                                    <tr>
                                        <td>Iftar Time</td>
                                        <td>06:45 PM</td>
                                    </tr>
                                    <tr>
                                        <td>Islamic Quote</td>
                                        <td>"Fasting is a shield with which a servant protects himself from the Fire." - Prophet Muhammad (PBUH)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>