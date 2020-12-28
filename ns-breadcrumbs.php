<?php

class NS_Breadcrumbs2 {

    static $strings = array(
        'home' => 'Головна',
        'author' => 'Архив автора %s',
        '404' => 'Страница не существует',
    );

    static $param = array(
        'show_on_front' => false,
        'show_front' => true,
        'show_current' => true,
        'show_pt_in_term' => true,
        'show_pt_in_single' => true,
        'show_term_in_single' => true,
//        'post_types' => array( 'post', 'portfolio' ),
        'post_types' => array( 'portfolio' ),
        'taxonomies' => array( 'category', 'portfolio_category' ),
        'bc_sep' => '<span class="sep">»</span>',
        'bc_before' => '<div class="breadcrumbs" itemscope="" itemtype="http://schema.org/BreadcrumbList">',
        'bc_after' => '</div>',
        'elem_template' => '<span class="breadcrumbs-item" itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem"><a class="breadcrumbs-link" href="%s" itemprop="item"><span class="breadcrumbs-name" itemprop="name">%s</span><meta itemprop="position" content="%s"></a></span>',
        'elem_last' => '<span class="breadcrumbs-name breadcrumbs-last">%s</span><meta itemprop="position" content="%s">',
    );


    public function build() {

        $crumbs = $this->get_crumbs();

        $last_elem = array_pop( $crumbs );

        $output = self::$param['bc_before'];

        foreach ( $crumbs as $elem ) {
            $output .= sprintf( self::$param['elem_template'], $elem['url'], $elem['title'], $elem['position'] );
            $output .= self::$param['bc_sep'];
        }

        $output .= sprintf( self::$param['elem_last'], $last_elem['title'], $last_elem['position'] );

        $output .= self::$param['bc_after'];

        echo $output;

    }

    public function get_crumbs() {

        global $post;

        $siteurl = get_site_url();

        $output_array = array();

        /* add home link  */
        if ( !is_front_page() && self::$param['show_front'] == true ) {
            $output_array[] = array( 'title' => self::$strings['home'], 'url' => $siteurl, 'position' => 0 );
        }

        /* if page and no parents */
        if ( is_page() && !$post->post_parent ) {
            if ( self::$param['show_current'] == true ) {
                $output_array[] = array( 'title' => $post->post_title, 'url' => '', 'position' => 1 );
            }
        }
        /* if page and has parents */
        if ( is_page() && $post->post_parent ) {

            $i = 1;

            $ancestors = get_ancestors( $post->ID, 'page' );
            $ancestors = array_reverse( $ancestors );

            foreach ( $ancestors as $ancestor ) {
                $output_array[] = array( 'title' => get_the_title( $ancestor ), 'url' => get_the_permalink( $ancestor ), 'position' => $i );
                $i++;
            }

            if ( self::$param['show_current'] == true ) {
                $output_array[] = array( 'title' => $post->post_title, 'url' => '', 'position' => $i );
            }

        }

        /* check if archive page */
        if ( is_archive() ) {

            $q = get_queried_object();
            $i = 1;

            /* check if post type */
            if ( is_post_type_archive( self::$param['post_types'] ) && self::$param['show_current'] = true ) {
                $output_array[] = array( 'title' => $q->label, 'url' => '', 'position' => $i );
            }
            /* check if post type */
            if ( is_tax() || is_category() ) {

                /* if need to add post type name to breadcrumbs*/
                if ( self::$param['show_pt_in_term'] == true && is_object_in_taxonomy( self::$param['post_types'], $q->taxonomy ) ) {
                    $tax = get_taxonomy( $q->taxonomy );
                    $post_type = get_post_type_object( $tax->object_type[0] );
                    $output_array[] = array( 'title' => $post_type->label, 'url' => get_post_type_archive_link( $post_type->name ), 'position' => $i );
                    $i++;
                }
                /* if term have parents */
                if ( $q->parent ) {

                    $output_array = array_merge( $output_array, $this->get_terms( $q->term_id, $q->taxonomy, $i ) );

                    if ( self::$param['show_current'] == true ) {
                        $output_array[] = array( 'title' => $q->name, 'url' => '', 'position' => $i );
                    }

                }
                /* if term not have parents */
                if ( !$q->parent && self::$param['show_current'] == true ) {
                    $output_array[] = array( 'title' => $q->name, 'url' => '', 'position' => $i );
                }

            }
            /*if is author archive*/
            if ( is_author() ) {
                $output_array[] = array( 'title' => sprintf( self::$strings['author'], $q->display_name ), 'url' => '', 'position' => $i );
            }

        }

        /*if single page*/
        if ( is_single() ) {

            $i = 1;

            /* if show post type name on single */
            if ( self::$param['show_pt_in_single'] == true && in_array( $post->post_type, self::$param['post_types'] ) ) {

                $post_type = get_post_type_object( $post->post_type );
                $output_array[] = array( 'title' => $post_type->label, 'url' => get_post_type_archive_link( $post_type->name ), 'position' => $i );
                $i++;

            }

            /* if show terms names on single */
            if ( self::$param['show_term_in_single'] == true ) {

                $taxonomies = get_taxonomies();
                $terms = wp_get_post_terms( $post->ID, $taxonomies );

                if ( $terms ) {

                    $term = $terms[0];

                    /* if term has parents */
                    if ($term->parent) {
                        $output_array = array_merge( $output_array, $this->get_terms( $term->term_id, $term->taxonomy, $i ) );
                    }

                    $output_array[] = array( 'title' => $term->name, 'url' => get_term_link($term->term_id), 'position' => $i );
                    $i++;
                }
            }

            if ( self::$param['show_current'] == true ) {
                $output_array[] = array( 'title' => $post->post_title, 'url' => '', 'position' => $i );
            }

        }

        //TODO добавить пагинацию, страницы архивов по датам, вложениям, тегам, поиск+посттипы, 404, пагинация и тд

        return $output_array;
    }

    private function get_terms( $term_id, $taxonomy, &$i ) : array {

        $ancestors = get_ancestors($term_id, $taxonomy);
        $ancestors = array_reverse($ancestors);
        foreach ($ancestors as $ancestor) {
            $s_term = get_term($ancestor, $taxonomy);
            $output_array[] = array( 'title' => $s_term->name, 'url' => get_term_link( $ancestor ), 'position' => $i );
            $i++;
        }

        return $output_array;
    }
}