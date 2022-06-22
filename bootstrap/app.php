<?php

use PHPMini\Application\Application;

$router = require "../routes/api.php";

return new Application($router);
