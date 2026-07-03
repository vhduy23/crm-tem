<?php
$img = file_get_contents('https://achau1.bzz.vn/uploads/Logo-nen-trang.png');
file_put_contents('logo_b64.txt', 'data:image/png;base64,' . base64_encode($img));
