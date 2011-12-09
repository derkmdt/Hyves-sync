<?

abstract class Media_Fetcher_Wrapper
{
    abstract public function log_in($next_url);
    abstract public function get_user_albums();
}

abstract class Media_Uploader_Wrapper
{
    abstract public function log_in($next_url);
    abstract public function upload(Photo $photo);
}

abstract class External_Album_Id
{
    abstract public function get_details();
}

abstract class External_Photo_Id
{
    abstract public function get_details();
}

?>
