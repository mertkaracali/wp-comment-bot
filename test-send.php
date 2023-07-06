<?php
ini_set('max_execution_time', 0);
set_time_limit(0);
$directory = __DIR__;
require($directory . '/wp-class.php');
$array = explode("\n", file_get_contents('backlink.txt'));

$comment = array(
    "comment" => "Your Comment",
    "author" => "Mertcan k.",
    "email" => "test@nosayazilim.com.tr",
    "site_address" => "https://www.nosayazilim.com.tr"
);
foreach ($array as $row){
$sendcomment = wp_comment::sendComment(trim($row), $comment);
print_r($sendcomment);

}
