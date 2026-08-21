<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROMASY | Project Management System</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;
        }

        body{
            height:100vh;
            background:linear-gradient(135deg,#0f172a,#1e3a8a,#2563eb);
            display:flex;
            justify-content:center;
            align-items:center;
            color:white;
            overflow:hidden;
        }

        .container{
            text-align:center;
            max-width:800px;
            padding:40px;
        }

        h1{
            font-size:70px;
            letter-spacing:2px;
            margin-bottom:20px;
        }

        h2{
            font-size:28px;
            font-weight:400;
            margin-bottom:20px;
            color:#dbeafe;
        }

        p{
            font-size:18px;
            line-height:1.8;
            color:#cbd5e1;
            margin-bottom:45px;
        }

        .btn{
            display:inline-block;
            text-decoration:none;
            background:#fff;
            color:#1e40af;
            padding:18px 45px;
            border-radius:50px;
            font-size:18px;
            font-weight:bold;
            transition:.3s;
            box-shadow:0 10px 25px rgba(0,0,0,.25);
        }

        .btn:hover{
            transform:translateY(-5px);
            background:#2563eb;
            color:white;
        }

        .circle{
            position:absolute;
            border-radius:50%;
            background:rgba(255,255,255,.08);
            animation:float 8s infinite ease-in-out;
        }

        .circle:nth-child(1){
            width:220px;
            height:220px;
            top:-60px;
            left:-60px;
        }

        .circle:nth-child(2){
            width:160px;
            height:160px;
            bottom:50px;
            right:80px;
        }

        .circle:nth-child(3){
            width:100px;
            height:100px;
            bottom:-20px;
            left:20%;
        }

        @keyframes float{
            50%{
                transform:translateY(-20px);
            }
        }
    </style>
</head>

<body>

<div class="circle"></div>
<div class="circle"></div>
<div class="circle"></div>

<div class="container">

    <h1>PROMASY</h1>

    <h2>Project Management System</h2>

    <p>
        Organize projects, assign tasks, monitor progress,
        collaborate with your team, and stay productive through
        one centralized project management platform.
    </p>

    <a href="login.php" class="btn">
        Get Started →
    </a>

</div>

</body>
</html>