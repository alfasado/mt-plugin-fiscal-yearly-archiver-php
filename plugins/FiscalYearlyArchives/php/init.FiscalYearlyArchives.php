<?php

    // Perl version => http://code.google.com/p/ogawa/wiki/FiscalYearlyArchives

    ArchiverFactory::add_archiver( 'FiscalYearly', 'FiscalYearlyArchiver' );
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
            if (is_array($period_start))
            $period_start = sprintf("%04d", $period_start['y']);
            $y = substr($period_start,0,4);
            $m = substr($period_start,4,2);
            $d = substr($period_start,6,2);
            return array($period_start, date("YmdHis", strtotime("$y-$m-$d +1 year -1 sec")));
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
            $vars['datebased_only_archive']   = 1;
            $vars['datebased_fiscal_yearly_archive'] = 1;
            $vars['archive_class'] = 'datebased-fiscal-yearly-archive';
            $vars['module_fiscal_yearly_archives']   = 1;
        }

        protected function get_archive_list_data($args) {
            $mt = MT::get_instance();
            $blog_id = $args['blog_id'];
            $at = $args['archive_type'];
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
                $archive_list['entry_count'] = $entry_count;
                $archive_list['y'] = $y;
            }
            if ( $archive_list ) {
                return array($archive_list);
            } else {
                return NULL;
            }
        }

        protected function get_helper() {
            return 'start_end_fiscal_year';
        }
    }

    function start_end_fiscal_year ($ts) {
        $mt = MT::get_instance();
        $settings = $mt->db()->fetch_plugin_data('FiscalYearlyArchives','configuration');
        $start_month = $settings['fiscal_start_month'];
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