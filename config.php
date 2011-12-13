<?
$inc_paths_nico = array(
                        '/home/nico/hackathon/www/',
                        '/home/nico/hackathon/www/ext_libs/',
                    );

$inc_paths_demo = array(
                    '/home/apikooien.nl/www/derk/hyves-sync/',
                    '/home/apikooien.nl/www/derk/hyves-sync/ext_libs/',
                  );

$path = implode($inc_paths_demo, PATH_SEPARATOR);
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
//define("DOMAIN", 'http://localhost/');
define("DOMAIN", 'http://www.apikooien.nl/derk/hyves-sync/');
?>
