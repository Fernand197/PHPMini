<?php

use Router\Router;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>

<body>
    Congratulations !!! You've successfully installed PHPMini. <br>
    Username: <?= $user->username ?><br>
    Email: <?= $user->email ?>
</body>

</html>