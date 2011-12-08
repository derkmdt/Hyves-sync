<?

require_once 'model/API.php';

class Album
{
    private $title = NULL;
    private $date_published = NULL;
    private $summary = NULL;
    private $comment_count = 0;
    private $commenting_enabled = false;
    private $author_name = NULL;
    private $categories = array();
    private $default_link = NULL;
    private $links = array();
    private $external_id = NULL;
    private $photos = NULL;

    public function get_external_id() { return $this->external_id; }
    public function get_title() { return $this->title; }
    public function get_date_published() { return $this->date_published; }
    public function get_summary() { return $this->summary; }
    public function get_comment_count() { return $this->comment_count; }
    public function get_commenting_enabled() { return $this->commenting_enabled; }
    public function get_author_name() { return $this->author_name; }
    public function get_categories() { return $this->categories; }
    public function get_default_link() { return $this->default_link; }
    public function get_links() { return $this->links; }

    /**
     * Lazy-loading for the photos
     */
    public function get_photos()
    {
        if ($this->photos == NULL)
            $this->photos = $this->external_id->get_details();

        return $this->photos;
    }

    public function dump_html()
    {
        $s = '<div class="album_box">';
        $s .= '<h2><a href="'.$this->get_default_link().'">'.$this->get_title().'</a></h2>';
        $s .= '<blockquote>'.$this->get_summary().'</blockquote>';
        $s .= ' by '.$this->get_author_name();
        $s .= '</div>';
        return $s;
    }

    public function __construct(
                        External_Album_Id $external_id,
                        /* string */ $title,
                        /* string */ $date_published,
                        /* string */ $summary,
                        /* int */ $comment_count,
                        /* bool */ $commenting_enabled,
                        /* string */ $author_name,
                        array $categories,
                        /* string */ $default_link,
                        array $links)
    {
        $this->external_id = $external_id;
        $this->title = $title;
        $this->date_published = $date_published;
        $this->summary = $summary;
        $this->comment_count = $comment_count;
        $this->commenting_enabled = $commenting_enabled;
        $this->author_name = $author_name;
        $this->categories = $categories;
        $this->default_link = $default_link;
        $this->links = $links;
    }
}

?>
