<?php
/**
 * WP-JoomSport
 * @author      BearDev
 * @package     JoomSport
 */

class JoomsportShortcodes {
    
    public static function init() {

        add_shortcode( 'jsStandings', array('JoomsportShortcodes','joomsport_standings') );
        add_shortcode( 'jsMatches', array('JoomsportShortcodes','joomsport_matches') );
        

        add_filter("mce_external_plugins", array('JoomsportShortcodes',"enqueue_plugin_scripts"));
        add_filter("mce_buttons", array('JoomsportShortcodes',"register_buttons_editor"));
    }


    public static function joomsport_standings($attr){

        $args = shortcode_atts( array(
            'id' => 0,
            'group_id' => 0,
            'partic_id' => 0,
            'place' => 0,
            'columns' => '',
            
        ), $attr );
        wp_enqueue_style('jscssbtstrp',plugin_dir_url( __FILE__ ).'../sportleague/assets/css/btstrp.css');
             wp_enqueue_style('jscssjoomsport',plugin_dir_url( __FILE__ ).'../sportleague/assets/css/joomsport.css');
        wp_enqueue_style( 'joomsport-moduletable-css', plugins_url('../sportleague/assets/css/mod_js_table.css', __FILE__) );
        ob_start();

        require_once JOOMSPORT_PATH . DIRECTORY_SEPARATOR. 'sportleague' . DIRECTORY_SEPARATOR . 'sportleague.php';
        require_once JOOMSPORT_PATH_OBJECTS . 'class-jsport-season.php';
        $seasObj = new classJsportSeason($args['id']);
        
        if($seasObj->isComplex() == '1'){
            $childrens = $seasObj->getSeasonChildrens();

            if(count($childrens)){
                foreach ($childrens as $ch) {
                    $classChild = new classJsportSeason($ch->ID);
                    $child = $classChild->getChild();
                    $child->calculateTable(true, $args['group_id']);
                    $classChild->getLists();
                    $row = $classChild;
                    $place_display 	= $args['place'];
                    $columns_list = array();
                    if($args['columns']){
                       $columns_list = explode(';', $args['columns']); 
                    }
                    $yteam_id = $args['partic_id'];
                    $s_id = $args['id'];
                    $gr_id = $args['group_id'];
                    $single = $row->getSingle();
                    $row = $row->season;

                    require JOOMSPORT_PATH_VIEWS . 'widgets' . DIRECTORY_SEPARATOR . 'standings.php';
                }
            }    
        
        }else{
            $child = $seasObj->getChild();
            $child->calculateTable(true, $args['group_id']);
            $seasObj->getLists();
            $row = $seasObj;
            $place_display 	= $args['place'];
            $columns_list = array();
            if($args['columns']){
               $columns_list = explode(';', $args['columns']); 
            }
            $yteam_id = $args['partic_id'];
            $s_id = $args['id'];
            $gr_id = $args['group_id'];
            $single = $row->getSingle();
            $row = $row->season;
        
            require JOOMSPORT_PATH_VIEWS . 'widgets' . DIRECTORY_SEPARATOR . 'standings.php';
        }    
	return ob_get_clean();
    }
    
    
    public static function joomsport_matches($attr){

        $args = shortcode_atts( array(
            'id' => 0,
            'group_id' => 0,
            'partic_id' => 0,
            'quantity' => 0,
            'matchtype' => 0,
            'emblems' => 0,
            'venue' => 0,
            'season' => 0,
            'slider' => 0,
            'layout' => 0,
            'groupbymd' => 0,
            'morder' => 0,
            
        ), $attr );

        wp_enqueue_script('jsjoomsport-carousel',plugins_url('../sportleague/assets/js/jquery.jcarousellite.min.js', __FILE__));
        wp_enqueue_style( 'joomsport-modulescrollmatches-css', plugins_url('../sportleague/assets/css/js_scrollmatches.css', __FILE__) );
        ob_start();
        require_once JOOMSPORT_PATH . DIRECTORY_SEPARATOR. 'sportleague' . DIRECTORY_SEPARATOR . 'sportleague.php';

        require_once JOOMSPORT_PATH_CLASSES . 'class-jsport-matches.php';
        require_once JOOMSPORT_PATH_OBJECTS . 'class-jsport-match.php';
        require_once JOOMSPORT_PATH_OBJECTS.'class-jsport-season.php';
        require_once JOOMSPORT_PATH_MODELS . 'model-jsport-season.php';
        
        $options = array();
            $is_single = 0;
            if($args['id']){
                $options["season_id"] = $args['id'];
                $obj = new classJsportSeason($args['id']);

                $is_single = (int)$obj->getSingle();
                
                
                $season_array = array();
                
                if($obj->isComplex() == '1'){
                    $childrens = $obj->getSeasonChildrens();
                    if(count($childrens)){
                        foreach($childrens as $ch){
                            array_push($season_array, $ch->ID);
                        }
                        $options["season_id"] = $season_array;
                    }
                } 
                
            }

            if($args['partic_id']){
                $options["team_id"] = $args['partic_id'];
            }
            
            if($args['quantity']){
                $options["limit"] = $args['quantity'];
            }
            if($args['matchtype'] == '1'){
                $options["played"] = '0';
            }
            if($args['matchtype'] == '2'){
                $options["played"] = '1';
                //$options["ordering"] = 'm.m_date DESC, m.m_time DESC, m.id DESC';
            }
            if($args['morder'] == '1'){
                $options["ordering_dest"] = 'desc';
            }

            $obj = new classJsportMatches($options);
            $rows = $obj->getMatchList($is_single);


            $matches = array();

            if($rows['list']){
                foreach ($rows['list'] as $row) {
                    $match = new classJsportMatch($row->ID, false);
                    $matches[] = $match->getRowSimple();
                }
            }
            $list = $matches;
            if(count($list)){
                /*$document		= JFactory::getDocument();
                $document->addStyleSheet(JURI::root() . 'modules/mod_js_scrollmatches/css/js_scrollmatches.css'); 
                $document->addScript(JURI::root() . 'modules/mod_js_scrollmatches/js/jquery.jcarousellite.min.js');
                $baseurl = JUri::base();*/

                $module_id = rand(0, 2000);
                $enbl_slider = $args['slider'];
                $classname = $enbl_slider ? "jsSliderContainer":"jsDefaultContainer";
                if($enbl_slider){
                    $curpos = 0;
                    $date = date("Y-m-d");
                    for($intA = 0;$intA < count($matches); $intA++){
                        $mdate  = get_post_meta($matches[$intA]->id,'_joomsport_match_date',true);
                        if(isset($options["ordering_dest"]) && $options["ordering_dest"] == 'desc'){
                            if($mdate > $date){
                            
                                $curpos =  $intA;
                            }
                        }else
                        if($mdate < $date){
                            
                            $curpos =  $intA+1;
                        }
                    }

                    //$curpos = $curpos > 1 ? $curpos : 0;
                }
                
                require JOOMSPORT_PATH_VIEWS . 'widgets' . DIRECTORY_SEPARATOR . 'matches.php';

            }
            

	return ob_get_clean();
    }
    
    
    public static function enqueue_plugin_scripts($plugin_array)
    {
        //enqueue TinyMCE plugin script with its ID.
        $plugin_array["joomsport_shortcodes_button"] =  plugin_dir_url(__FILE__) . "../assets/js/shortcodes.js";
        return $plugin_array;
    }
    public static function register_buttons_editor($buttons)
    {
        //register buttons with their id.
        array_push($buttons, "joomsport_shortcodes_button");
        return $buttons;
    }
    
}


JoomsportShortcodes::init();