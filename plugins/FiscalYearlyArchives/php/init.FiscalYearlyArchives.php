<?php

    // Perl version => http://code.google.com/p/ogawa/wiki/FiscalYearlyArchives

    // version 0.4

    require_once( 'MTUtil.php' );

    ArchiverFactory::add_archiver( 'FiscalYearly', 'FiscalYearlyArchiver' );
    ArchiverFactory::add_archiver( 'Category-FiscalYearly', 'CategoryFiscalYearlyArchiver' );
    ArchiverFactory::add_archiver( 'Author-FiscalYearly', 'AuthorFiscalYearlyArchiver' );
    class FiscalYearlyArchiver extends YearlyArchiver {
        public function get_label($args = null) {
            global $app;
            if ( isset( $app ) ) {
                return $app->translate( 'Fiscal Yearly' );
            }
            $mt = MT::get_instance();
            return $mt->translate( 'Fiscal Yearly' );
        }

        public function get_title($args) {
            $mt = MT::get_instance();
            $ctx =& $mt->context();
            $stamp = $ctx->stash('current_timestamp');
            list($start) = start_end_year($stamp, $ctx->stash('blog'));
            $format = $args['format'];
            $blog = $ctx->stash('blog');
            $lang = ($blog && $blog->blog_language ? $blog->blog_language :
                $mt->config('DefaultLanguage'));
                if (strtolower($lang) == 'jp' || strtolower($lang) == 'ja') {
                $format or $format = "%Y&#24180;&#24230;";
            } else {
                $format or $format = "%Y";
            }
            return $ctx->_hdlr_date(array('ts' => $start, 'format' => $format), $ctx);
        }

        public function get_range($period_start) {
            $mt = MT::get_instance();
            $ctx =& $mt->context();
            if (is_array($period_start)) {
                $y = sprintf("%04d", $period_start['y']);
                $m = sprintf("%02d",$period_start['m']);
                $d = sprintf("%02d",$period_start['d']);
                return array("${y}${m}${d}000000", date("YmdHis", strtotime("$y-$m-$d +1 year -1 sec")));
            } else {
                $y = substr($period_start,0,4);
                $m = substr($period_start,4,2);
                $d = substr($period_start,6,2);
                return array($period_start, date("YmdHis", strtotime("$y-$m-$d +1 year -1 sec")));
            }
        }

        protected function get_update_link_args($results) {
            $args = array();
            if (!empty($results)) {
                $count = count($results);
                $y = $results[0]['y'];
                $m = $results[0]['m'];
                $d = $results[0]['d'];
                $hi = date("YmdHis", strtotime("$y-$m-$d +1 year -1 sec"));
                $low = $y . $m . $d . '000000';
                $args['hi'] = $hi;
                $args['low'] = $low;
            }
            return $args;
        }

        public function template_params() {
            $mt = MT::get_instance();
            $ctx =& $mt->context();
            parent::template_params($ctx);
            $vars =& $ctx->__stash['vars'];
            $vars['datebased_only_archive'] = 1;
            $vars['datebased_fiscal_yearly_archive'] = 1;
            $vars['archive_class'] = 'datebased-fiscal-yearly-archive';
            $vars['module_fiscal_yearly_archives'] = 1;
        }

        protected function get_archive_list_data($args) {
            $mt = MT::get_instance();
            $blog_id = $args['blog_id'];
            $at = $args['archive_type'];
            $at = $mt->db()->escape($at);
            $order = $args['sort_order'] == 'ascend' ? 'asc' : 'desc';
            require_once( 'class.mt_fileinfo.php' );
            $_fileinfo = new FileInfo;
            $where = " fileinfo_blog_id = $blog_id and fileinfo_archive_type = '$at'";
            $extra = array( 'order by' => $order );
            if (isset($args['offset']) ) {
                $extra['offset'] = $args['offset'];
            }
            if (isset($args['lastn']) ) {
                $extra['limit'] = $args['lastn'];
            }
            $archive_list = array();
            $fileinfos = $_fileinfo->Find( $where, FALSE, FALSE, $extra );
            $years = array();
            foreach ( $fileinfos as $fileinfo ) {
                $period_start = $fileinfo->startdate;
                $y = substr($period_start,0,4);
                if (in_array( $y, $years )) {
                    continue;
                }
                array_push( $years, $y );
                $m = substr($period_start,4,2);
                $d = substr($period_start,6,2);
                $period_end = date("YmdHis", strtotime("$y-$m-$d +1 year -1 sec"));
                $sql = "select count(*) as entry_count
                          from mt_entry
                         where entry_blog_id = $blog_id
                           and entry_status = 2
                           and entry_class = 'entry'
                           and entry_authored_on >= '$period_start'
                           and entry_authored_on <= '$period_end'";
                $result = $mt->db()->SelectLimit($sql);
                if ($result) {
                    $fields = $result->fields;
                    $entry_count = $fields['entry_count'];
                } else {
                    $entry_count = 0;
                }
                $archive_list_data['entry_count'] = $entry_count;
                $archive_list_data['y'] = $y;
                array_push( $archive_list, $archive_list_data );
            }
            if ( $archive_list ) {
                return $archive_list;
            } else {
                return NULL;
            }
        }

        protected function get_helper() {
            return 'start_end_fiscal_year';
        }
    }

    class CategoryFiscalYearlyArchiver extends FiscalYearlyArchiver {
        public function get_label($args = null) {
            global $app;
            if ( isset( $app ) ) {
                return $app->translate( 'Category Fiscal Yearly' );
            }
            $mt = MT::get_instance();
            return $mt->translate( 'Category Fiscal Yearly' );
        }

        public function get_title($args) {
            $mt = MT::get_instance();
            $ctx =& $mt->context();
            $stamp = $ctx->stash('current_timestamp');
            $category = $ctx->stash('category');
            $category_label = encode_html( strip_tags( $category->label ) );
            list($start) = start_end_year($stamp, $ctx->stash('blog'));
            $format = $args['format'];
            $blog = $ctx->stash('blog');
            $lang = ($blog && $blog->blog_language ? $blog->blog_language :
                $mt->config('DefaultLanguage'));
                if (strtolower($lang) == 'jp' || strtolower($lang) == 'ja') {
                $format or $format = "%Y&#24180;&#24230;";
            } else {
                $format or $format = "%Y";
            }
            return $category_label . ':' . $ctx->_hdlr_date(array('ts' => $start, 'format' => $format), $ctx);
        }

        public function template_params() {
            $mt = MT::get_instance();
            $ctx =& $mt->context();
            parent::template_params($ctx);
            $vars =& $ctx->__stash['vars'];
            $vars['datebased_only_archive']   = 1;
            $vars['datebased_category_fiscal_yearly_archive'] = 1;
            $vars['archive_class'] = 'datebased-category-fiscal-yearly-archive';
            $vars['module_category_fiscal_yearly_archives']   = 1;
        }

        protected function get_archive_list_data($args) {
            $mt = MT::get_instance();
            $ctx =& $mt->context();
            $cat = $ctx->stash('archive_category');
            $cat or $cat = $ctx->stash('category');
            if ( isset( $cat ) ) {
                $cat_filter = " and placement_category_id=" . $cat->category_id;
            }
            $blog_id = $args['blog_id'];
            $at = $args['archive_type'];
            $at = $mt->db()->escape($at);
            $order = $args['sort_order'] == 'ascend' ? 'asc' : 'desc';
            require_once( 'class.mt_fileinfo.php' );
            $_fileinfo = new FileInfo;
            $where = " fileinfo_blog_id = $blog_id and fileinfo_archive_type = '$at'";
            $extra = array( 'order by' => $order );
            if (isset($args['offset']) ) {
                $extra['offset'] = $args['offset'];
            }
            if (isset($args['lastn']) ) {
                $extra['limit'] = $args['lastn'];
            }
            $archive_list = array();
            $fileinfos = $_fileinfo->Find( $where, FALSE, FALSE, $extra );
            $years = array();
            foreach ( $fileinfos as $fileinfo ) {
                $period_start = $fileinfo->startdate;
                $y = substr($period_start,0,4);
                if (in_array( $y, $years )) {
                    continue;
                }
                array_push( $years, $y );
                $m = substr($period_start,4,2);
                $d = substr($period_start,6,2);
                $period_end = date("YmdHis", strtotime("$y-$m-$d +1 year -1 sec"));
                $sql = "select count(*) as entry_count,
                          placement_category_id,
                          category_label
                          from mt_entry join mt_placement on entry_id = placement_entry_id
                          join mt_category on placement_category_id = category_id
                         where entry_blog_id = $blog_id
                           and entry_status = 2
                           and entry_class = 'entry'
                           and entry_authored_on >= '$period_start'
                           and entry_authored_on <= '$period_end'
                           $cat_filter";
                $result = $mt->db()->SelectLimit($sql);
                if ($result) {
                    $fields = $result->fields;
                    $entry_count = $fields['entry_count'];
                } else {
                    $entry_count = 0;
                }
                $archive_list_data['entry_count'] = $entry_count;
                $archive_list_data['y'] = $y;
                array_push( $archive_list, $archive_list_data );
            }
            if ( $archive_list ) {
                return $archive_list;
            } else {
                return NULL;
            }
        }
    }

    class AuthorFiscalYearlyArchiver extends FiscalYearlyArchiver {
        public function get_label($args = null) {
            global $app;
            if ( isset( $app ) ) {
                return $app->translate( 'Author Fiscal Yearly' );
            }
            $mt = MT::get_instance();
            return $mt->translate( 'Author Fiscal Yearly' );
        }

        public function get_title($args) {
            $mt = MT::get_instance();
            $ctx =& $mt->context();
            $stamp = $ctx->stash('current_timestamp');
            $author_name = '';
            $author = $ctx->stash('archive_author');
            if (! isset( $archive_author ) ) {
                $author = $ctx->stash('author');
                if ( isset($author) ) {
                    $author_name = $author->author_nickname;
                    $author_name or $author_name =
                        $mt->translate('(Display Name not set)');
                }
            }
            $author_name = encode_html( strip_tags( $author_name ) );
            list($start) = start_end_year($stamp, $ctx->stash('blog'));
            $format = $args['format'];
            $blog = $ctx->stash('blog');
            $lang = ($blog && $blog->blog_language ? $blog->blog_language :
                $mt->config('DefaultLanguage'));
                if (strtolower($lang) == 'jp' || strtolower($lang) == 'ja') {
                $format or $format = "%Y&#24180;&#24230;";
            } else {
                $format or $format = "%Y";
            }
            return $author_name . ':' . $ctx->_hdlr_date(array('ts' => $start, 'format' => $format), $ctx);
        }

        public function template_params() {
            $mt = MT::get_instance();
            $ctx =& $mt->context();
            parent::template_params($ctx);
            $vars =& $ctx->__stash['vars'];
            $vars['datebased_only_archive'] = 1;
            $vars['datebased_author_fiscal_yearly_archive'] = 1;
            $vars['archive_class'] = 'datebased-author-fiscal-yearly-archive';
            $vars['module_author_fiscal_yearly_archives'] = 1;
        }

        protected function get_archive_list_data($args) {
            $mt = MT::get_instance();
            $blog_id = $args['blog_id'];
            $at = $args['archive_type'];
            $at = $mt->db()->escape($at);
            $order = $args['sort_order'] == 'ascend' ? 'asc' : 'desc';
            require_once( 'class.mt_fileinfo.php' );
            $_fileinfo = new FileInfo;
            $where = " fileinfo_blog_id = $blog_id and fileinfo_archive_type = '$at'";
            $extra = array( 'order by' => $order );
            if (isset($args['offset']) ) {
                $extra['offset'] = $args['offset'];
            }
            if (isset($args['lastn']) ) {
                $extra['limit'] = $args['lastn'];
            }
            $author = $ctx->stash('archive_author');
            if (! isset( $archive_author ) ) {
                $author = $ctx->stash('author');
            }
            $author_id = $author->id;
            $archive_list = array();
            $fileinfos = $_fileinfo->Find( $where, FALSE, FALSE, $extra );
            $years = array();
            foreach ( $fileinfos as $fileinfo ) {
                $period_start = $fileinfo->startdate;
                $y = substr($period_start,0,4);
                if (in_array( $y, $years )) {
                    continue;
                }
                array_push( $years, $y );
                $m = substr($period_start,4,2);
                $d = substr($period_start,6,2);
                $period_end = date("YmdHis", strtotime("$y-$m-$d +1 year -1 sec"));
                $sql = "select count(*) as entry_count
                          from mt_entry
                         where entry_blog_id = $blog_id
                           and entry_status = 2
                           and entry_class = 'entry'
                           and entry_author_id = $author_id
                           and entry_authored_on >= '$period_start'
                           and entry_authored_on <= '$period_end'";
                $result = $mt->db()->SelectLimit($sql);
                if ($result) {
                    $fields = $result->fields;
                    $entry_count = $fields['entry_count'];
                } else {
                    $entry_count = 0;
                }
                $archive_list_data['entry_count'] = $entry_count;
                $archive_list_data['y'] = $y;
                array_push( $archive_list, $archive_list_data );
            }
            if ( $archive_list ) {
                return $archive_list;
            } else {
                return NULL;
            }
        }
    }

    function start_end_fiscal_year ($ts) {
        $mt = MT::get_instance();
        $start_month = 4;
        $settings = $mt->db()->fetch_plugin_data('FiscalYearlyArchives','configuration');
        if ( $settings ) {
            $start_month = $settings['fiscal_start_month'];
        }
        $start_month = sprintf( "%02d",$start_month );
        $y = substr($ts,0,4);
        $m = substr($ts,4,2);
        if ( $m < $start_month ) {
            $y--;
        }
        $period_start = $y . $start_month . '01000000';
        $period_end = date("YmdHis", strtotime("$y-$start_month-01 +1 year -1 sec"));
        return array($period_start,$period_end);
    }

?>