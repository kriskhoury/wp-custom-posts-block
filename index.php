<?php
class BlockNewsList {
    public $block_name;
    public $block_slug;
    public $block_description;
    public $block_svg;

    public function __construct() {
        $this->block_name = "Posts List";
        $this->block_slug = "posts-list";
        $this->block_description = "A custom posts-list block.";
        $this->block_svg = file_get_contents(__DIR__ . '/icon.svg');
    }
    public function initialize() {
        add_action('acf/init', array($this, 'acf_block_init'));
        add_action('rest_api_init', function () {
            register_rest_route( 'v1', '/posts/', array(
                'methods' => 'POST',
                'callback' => array($this, 'posts_endpoint')
            ));
        });
    }
    public function acf_block_init() {
        if( function_exists('acf_register_block_type') ) {        
            acf_register_block_type(
                array(
                    'name'              => $this->block_slug,
                    'title'             => __($this->block_name),
                    'description'       => __($this->block_description),
                    'render_callback'   => array($this, 'acf_block_render_callback'),
                    'category'          => 'bootstrap',
                    'icon'              => $this->block_svg,
                    'keywords'          => array($this->block_slug),
                    'mode'              => 'edit',
                    'supports'          => array( 'mode' => false ),
                    'enqueue_assets'    => array($this, 'load_assets'),
                )
            );   
        }
    }
    function posts_endpoint( $request_data ) {
        $pageNumber =       $request_data['page'];
        $recordsToShow =    $request_data['per_page'];

        $query = new WP_Query(array(
            'posts_per_page'    => $recordsToShow,
            'paged'             => $pageNumber,
        ));

        $results = array(); // The array that gets returned
        $results['total_pages'] = $query->max_num_pages;
        $results['total_records'] = $query->found_posts;

        $posts = array();

        $key = 0;
        while ($query->have_posts()) {
            $query->the_post();
            $posts[$key]->title = get_the_title();
            $posts[$key]->content = get_the_excerpt();
            $posts[$key]->date = get_the_date();
            $posts[$key]->permalink = get_permalink();
            $posts[$key]->featured = get_the_post_thumbnail_url($post->ID, 'large');
            $key++;
        }

        $results['posts'] = $posts;
        return $results;
    }
    function load_assets() {
        $plugin_url =  plugin_dir_url( __FILE__ );
        wp_enqueue_style( 'style', $plugin_url.'styles.css');
        wp_enqueue_script( 'vuejs', 'https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.js', array(),'1.0.0', true);
        if(!is_admin()) {
            wp_enqueue_script( 'script', $plugin_url.'app.js', array('vuejs'), '1.0.0', true );
        }
    }

    function acf_block_render_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
        $id = $this->block_slug.'-'.$block['id'];
        $className = $this->block_slug;

        if( !empty($block['anchor']) ) {
            $id = $block['anchor'];
        }
        if( !empty($block['className']) ) {
            $className .= ' ' . $block['className'];
        }
        if( !empty($block['align']) ) {
            $className .= ' align' . $block['align'];
        }
        ?>
        <section id="<?php echo esc_attr($id); ?>" class="blocks-<?php echo $this->block_slug; ?> <?php echo esc_attr($className); ?>">
            <div id="app">
                <div v-if="loading">
                  loading...
                </div>
                <div v-if="posts.length">
                    <div class="results-list">
                        <div v-for="(post, index) in posts" :key="index">
                            <figure class="news-image" v-if="post.featured" :style="{backgroundImage: 'url(' + post.featured + ')'}"></figure>
                            <aside class="news-content">
                                <div class="news-title"><a :href="post.permalink">{{post.title}}</a></div>
                                <div class="news-date">{{post.date}}</div>
                                <div class="news-excerpt">{{post.content}}</div>
                            </aside>
                        </div>
                    </div>
                    <div class="custom-pagination">
                        <div class="total"><b>{{results.total_records}}</b> posts</div>
                        <ul class="page-numbers">
                            <li>
                                <a class="prev page-numbers" href="#" @click.prevent="prevClick()"></a>
                            </li>
                            <li v-for="page in results.total_pages">
                                <span v-if="page == pageNumber" class="page-numbers current">{{page}}</span>
                                <a v-else class="page-numbers" href="#" @click.prevent="gotoPage(page)">{{page}}</a>
                            </li>
                            <li>
                                <a class="next page-numbers" href="#" @click.prevent="nextClick()"></a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div v-if="!posts.length && !loading">
                    No results!
                </div>
            </div>
        </section>
        <?php 
        // This allows the WP Admin to load the vue object
        if(is_admin()){ ?>
        <script id="script" src="app.js"></script>
        <?php } ?>
    <?php 
    }
}

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( class_exists('ACF') ) { // Load if ACF is enabled
    $BlockNewsList = new BlockNewsList();
    $BlockNewsList->initialize();
}
