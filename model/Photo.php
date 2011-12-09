<?

require_once 'model/API.php';

class Photo
{
    private $external_photo_id;
    private $default_thumb_url;
    private $default_link;
    private $links;
    private $title;
    private $last_updated;
    private $comment_count;
    private $commenting_enabled;
    private $original_filename;
    private $original_height;
    private $original_width;
    private $original_size_bytes;
    private $geo_location;
    private $comments = NULL;

    public function get_default_thumb_url() { return $this->default_thumb_url; }
    public function get_default_link() { return $this->default_link; }
    public function get_links() { return $this->links; }
    public function get_title() { return $this->title; }
    public function get_last_updated() { return $this->last_updated; }
    public function get_comment_count() { return $this->comment_count; }
    public function get_commenting_enabled() { return $this->commenting_enabled; }
    public function get_original_filename() { return $this->original_filename; }
    public function get_original_height() { return $this->original_height; }
    public function get_original_width() { return $this->original_width; }
    public function get_original_size_bytes() { return $this->original_size_bytes; }
    public function get_geo_location() { return $this->geo_location; }

    public function get_upload_description()
    {
        $s = '';
        $s .= 'Uploaded through M Sync. You can see the original photo at ';
        $s .= $this->get_default_link();
        return $s;
    }

    public function get_comments()
    {
        if ($this->comments == NULL)
        {
            if ($this->get_comment_count() == 0) return array();
            $this->comments = $this->external_photo_id->get_details();
        }

        return $this->comments;
    }

    public function __construct(
                        External_Photo_Id $external_photo_id,
                        $default_thumb_url,
                        $default_link,
                        array $links,
                        $title,
                        $last_updated,
                        $comment_count,
                        $commenting_enabled,
                        $original_filename,
                        $original_height,
                        $original_width,
                        $original_size_bytes,
                        $geo_location)
    {
        $this->external_photo_id = $external_photo_id;
        $this->default_thumb_url = $default_thumb_url;
        $this->default_link = $default_link;
        $this->links = $links;
        $this->title = $title;
        $this->last_updated = $last_updated;
        $this->comment_count = $comment_count;
        $this->commenting_enabled = $commenting_enabled;
        $this->original_filename = $original_filename;
        $this->original_height = $original_height;
        $this->original_width = $original_width;
        $this->original_size_bytes = $original_size_bytes;
        $this->geo_location = $geo_location;
    }

    public function dump_html()
    {
        $s = '';
        $s .= 'Photo uploaded at '.$this->get_last_updated();
        $s .= '<a href="'.$this->get_default_link().'"><img src="'.$this->get_default_thumb_url().'"></a>';
        $s .= $this->get_title();
        return $s;
    }
}

?>
