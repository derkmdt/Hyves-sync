<?php
function var_dump_to_string($var){
    $output = "<pre>";
    $output .= print_r($var);
    $output .= "</pre>";
    return $output;
}
?>