<?php
class TEMPLATE {
	public $site_name = '';
	public $site_tagline = '';
	public $site_description = '';
	public $site_keywords = '';
	public $site_logo = '';
	public $site_url = '';
	public $title = '';
	public $description = '';
	public $keywords = '';
	public $author = '';
	public $text = '';
	public $image = '';
	public $url = '';
	public $article_date = '';
	public $js = '';
	public $css = '';
	public $footer_js = '';
	public $n;

	function __construct() {
		$this->n = "\n";
	}

	function breadcrumb( $pages = array() ){
		$site_name = strip_tags($this->site_name);
		$site_url = strip_tags($this->site_url);
		$type = 1;

		$breadcrumb_1 = '';
		$breadcrumb_2 = '';
		if( is_array($pages) && count($pages) > 0 ){
			foreach( $pages as $k => $v ){
				$title = ( isset($v['title']) ? $v['title'] : '' );
				$url = ( isset($v['url']) ? $v['url'] : '' );
				if( !empty($title) && !empty($url) ){
					$breadcrumb_1 .= '<a class="breadcrumb-item" href="'.$url.'">'.$title.'</a>'.$this->n;
					$breadcrumb_2 .= '<li class="breadcrumb-item"><a href="'.$url.'">'.$title.'</a></li>'.$this->n;
				}
			}
		}

		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$extra_title = '';
		if( $page > 1 ){
			$extra_title .= ' | '.lang('page').' '.$page;
		}

		if( $type == 1 ){
			$breadcrumb = '<nav class="breadcrumb">'.$this->n;
			$breadcrumb .= '<a class="breadcrumb-item" href="'.$site_url.'">'.lang('home').'</a>'.$this->n;
			$breadcrumb .= $breadcrumb_1;
			$breadcrumb .= '<span class="breadcrumb-item active">'.$this->title.$extra_title.'</span>'.$this->n;
			$breadcrumb .= '</nav>'.$this->n;
		}else{
			$breadcrumb = '<nav aria-label="breadcrumb">'.$this->n;
			$breadcrumb .= '<ol class="breadcrumb">'.$this->n;
			$breadcrumb .= '<li class="breadcrumb-item"><a href="'.$site_url.'">'.lang('home').'</a></li>'.$this->n;
			$breadcrumb .= $breadcrumb_2;
			$breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">'.$this->title.$extra_title.'</li>'.$this->n;
			$breadcrumb .= '</ol>'.$this->n;
			$breadcrumb .= '</nav>'.$this->n;
		}

		return $breadcrumb;
	}

	public function tpl_header_home($page_title, $image, $pdf=0){
		$publication = publication();
		$publication_id = ( isset($publication['id']) ? $publication['id'] : 0 );
		$publication_title = ( isset($publication['title']) ? $publication['title'] : '' );
		$publication_description = ( isset($publication['description']) ? $publication['description'] : '' );
		$publication_image = ( isset($publication['image']) ? $publication['image'] : '' );
		$publication_text = ( isset($publication['text']) ? $publication['text'] : '' );
		$publication_cover = ( isset($publication['cover']) ? $publication['cover'] : '' );
		$site_url = strip_tags($this->site_url);
		$today = date("l j M Y", time());

		if( !empty($publication_image) ){
			if( $publication_cover == 1 ){
				$this->css = '<style>.cover{ background: url('.$publication_image.') no-repeat top center #fff !important; }"</style>';
			}
		}

		$tpl = '<header class="cover">'.$this->n;
		$tpl .= '<div class="row">'.$this->n;
		$tpl .= '<div class="logo"><a href="'.$site_url.'"><img src="'.$image.'" alt="'.$page_title.'" title="'.$page_title.'"></a></div>'.$this->n;
		$tpl .= '</div>'.$this->n;
		$tpl .= '<div class="row">'.$this->n;
		$tpl .= '<div class="title">'.$this->n;
		$tpl .= '<h1>'.setting('site_title').'</h1>'.$this->n;
		$tpl .= '<h2>'.setting('site_slogan').'</h2>'.$this->n;
		$tpl .= '</div>'.$this->n;
		$tpl .= '</div>'.$this->n;
		$tpl .= '<div class="row">'.$this->n;
		$tpl .= '<div class="date">'.$this->n;
		if( $pdf == 1 ){
			//$tpl .= '<p>'.sprintf( lang('alnajat_number'), $publication_id, day_name($today) ).'</p>'.$this->n;
			$tpl .= '<p>'.day_name($today).'</p>'.$this->n;
		}else{
			$tpl .= '<p>'.day_name($today).'</p>'.$this->n;
		}
		$tpl .= '</div>'.$this->n;
		$tpl .= '</div>'.$this->n;
		$tpl .= '</header>'.$this->n;
		return $tpl;
	}

	public function tpl_header_site($page_title, $image){
		$publication_id = ( isset($_GET['publication_id']) ? intval($_GET['publication_id']) : 0 );
		$publication = publication($publication_id);
		$publication_id = ( isset($publication['id']) ? $publication['id'] : 0 );
		$publication_title = ( isset($publication['title']) ? $publication['title'] : '' );
		$publication_description = ( isset($publication['description']) ? $publication['description'] : '' );
		$publication_image = ( isset($publication['image']) ? $publication['image'] : '' );
		$publication_text = ( isset($publication['text']) ? $publication['text'] : '' );
		$publication_date = ( isset($publication['date']) ? $publication['date'] : '' );
		$site_url = strip_tags($this->site_url);

		//$today = ( empty($publication_date) ? date("l j M Y", time()) : date("l j M Y", $publication_date)  );
		$today = date("l j M Y", time());

		$tpl = '<header class="inner-header">'.$this->n;
		$tpl .= '<div class="row">'.$this->n;
		$tpl .= '<div class="inner-logo"><a href="'.$site_url.'"><img src="'.$image.'" alt="'.$page_title.'" title="'.$page_title.'"></a></div>'.$this->n;
		//$tpl .= '<div class="inner-date"><p>'.sprintf( lang('alnajat_number'), '<a href="'.url( array( 'action' => 'publication', 'publication_id' => $publication_id ) ).'">'.$publication_id.'</a>', day_name($today) ).'</p></div>'.$this->n;
		$tpl .= '<div class="inner-date"><p>'.day_name($today).'</p></div>'.$this->n;
		$tpl .= '</div>'.$this->n;
		$tpl .= '</header>'.$this->n;
		return $tpl;
	}

	public function tpl_header($home=0){
		$site_name = strip_tags($this->site_name);
		$site_url = strip_tags($this->site_url);
		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$extra_title = '';
		if( $page > 1 ){
			$extra_title .= ' | '.lang('page').' '.$page;
		}

		$acton = ( isset($_GET['action']) ? strip_tags($_GET['action']) : 'home' );

		if( $acton == 'home' ){
			$this->title = '';
		}

		if($this->title == ""){
			if($this->site_tagline == ""){
				$page_title = $site_name.$extra_title;
			}else{
				$page_title = $site_name.' - '.$this->site_tagline.$extra_title;
			}
			$breadcrumb_title = $site_name;
		}else{
			if($this->title == $this->site_name){
				$page_title = strip_tags($this->title).$extra_title;
			}else{
				$page_title = strip_tags($this->title).' - '.$site_name.$extra_title;
			}
			$breadcrumb_title = strip_tags($this->title);
		}

		if($this->description == ""){
			if($this->title == ""){
				$page_description = strip_tags($this->site_description).$extra_title;
			}else{
				$page_description = strip_tags($this->site_description).' - '.strip_tags($this->title).$extra_title;
			}
		}else{
			$page_description = strip_tags($this->description).$extra_title;
		}

		if($this->author == ""){
			$author = 'E-Da`wah Committee';
		}else{
			$author = strip_tags($this->author);
		}

		if($this->url == ""){
			$page_url = strip_tags($this->site_url);
		}else{
			$page_url = strip_tags($this->url);
		}

		if($this->image == ""){
			$image = strip_tags($this->site_logo);
		}else{
			$image = strip_tags($this->image);
		}

		if( $home == 1 ){
			$tpl_header = $this->tpl_header_home($page_title, $this->site_logo);
		}else{
			$tpl_header = $this->tpl_header_site($page_title, $this->site_logo);
		}

		if($this->js == ""){
			$js = '';
		}else{
			$js = $this->js;
		}

		if($this->css == ""){
			$css = '';
		}else{
			$css = $this->css;
		}

		$tpl = '<!DOCTYPE html>'.$this->n;
		$tpl .= '<!--[if IE 6]>'.$this->n;
		$tpl .= '<html id="ie6" lang="en-US" prefix="og: http://ogp.me/ns#">'.$this->n;
		$tpl .= '<![endif]-->'.$this->n;
		$tpl .= '<!--[if IE 7]>'.$this->n;
		$tpl .= '<html id="ie7" lang="en-US" prefix="og: http://ogp.me/ns#">'.$this->n;
		$tpl .= '<![endif]-->'.$this->n;
		$tpl .= '<!--[if IE 8]>'.$this->n;
		$tpl .= '<html id="ie8" lang="en-US" prefix="og: http://ogp.me/ns#">'.$this->n;
		$tpl .= '<![endif]-->'.$this->n;
		$tpl .= '<!--[if !(IE 6) & !(IE 7) & !(IE 8)]><!-->'.$this->n;
		$tpl .= '<html lang="en-US" prefix="og: http://ogp.me/ns#">'.$this->n;
		$tpl .= '<!--<![endif]-->'.$this->n;
		$tpl .= '<head>'.$this->n;
		$tpl .= '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">'.$this->n;
		$tpl .= '<meta charset="UTF-8">'.$this->n;
		$tpl .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.$this->n;
		$tpl .= '<title>'.$page_title.'</title>'.$this->n;
		$tpl .= '<meta name="description" content="'.$page_description.'">'.$this->n;
		$tpl .= '<meta name="author" content="'.$author.'">'.$this->n;

		$tpl .= '<link rel="canonical" href="'.$page_url.'">'.$this->n;
		$tpl .= '<meta property="og:locale" content="en_US">'.$this->n;
		$tpl .= '<meta property="og:type" content="article">'.$this->n;
		$tpl .= '<meta property="og:title" content="'.$page_title.'">'.$this->n;
		$tpl .= '<meta property="og:description" content="'.$page_description.'">'.$this->n;
		$tpl .= '<meta property="og:url" content="'.$page_url.'">'.$this->n;
		$tpl .= '<meta property="og:site_name" content="'.$site_name.'">'.$this->n;

		$tpl .= '<meta property="og:image" content="'.$image.'">'.$this->n;
		$tpl .= '<meta property="og:image:width" content="640">'.$this->n;
		$tpl .= '<meta property="og:image:height" content="360">'.$this->n;

		$tpl .= '<link href="'.$site_url.'css/bootstrap/bootstrap.min.css" rel="stylesheet" type="text/css" media="all">'.$this->n;
		$tpl .= '<link href="'.$site_url.'css/fontawesome/all.css" rel="stylesheet" type="text/css" media="all">'.$this->n;
		$tpl .= '<link href="'.$site_url.'css/style.css" rel="stylesheet" type="text/css" media="all">'.$this->n;
		$tpl .= '<script src="'.$site_url.'js/jquery-3.3.1.slim.min.js"></script>'.$this->n;
		$tpl .= '<script src="'.$site_url.'js/custom.js"></script>'.$this->n;
		$tpl .= $js.$this->n;
		$tpl .= $css.$this->n;
		$tpl .= '</head>'.$this->n;

		$tpl .= '<body>'.$this->n;
		$tpl .= '<main class="mob-view col-12">'.$this->n;
		$tpl .= $tpl_header;
		return $tpl;
	}

	public function tpl_footer(){
		$site_name = strip_tags($this->site_name);
		$site_url = strip_tags($this->site_url);

		if($this->footer_js == ""){
			$js = '';
		}else{
			$js = $this->footer_js;
		}

		/*
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#subscribe_emailModal">
		  Launch demo modal
		</button>

		$form .= '<div class="form-group">';
		$form .= '<label for="site_unsubscribe">'.lang('setting_unsubscribe').'</label>';
		$form .= '<input style="direction:ltr;" type="text" class="form-control" id="site_unsubscribe" name="setting[unsubscribe]" value="'.$this->get_setting('unsubscribe').'">';
		$form .= '</div>';
		*/

		$modal = '<div class="modal fade" id="subscribe_emailModal" tabindex="-1" role="dialog" aria-labelledby="subscribe_emailModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="subscribe_emailLabel">'.lang('submit_subscribe').'</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

			<div id="mc_embed_signup">
			<form action="https://alnajat.us4.list-manage.com/subscribe/post?u=e491488d5ec7ea051798a5ec4&amp;id=a5f6d12767" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
				<div class="form-group" id="mc_embed_signup_scroll">
					<label for="site_unsubscribe" class="site_unsubscribe">'.lang('subscribe').'</label>
					<input type="email" class="form-control" id="site_unsubscribe" name="EMAIL" value="" placeholder="'.lang('users_email').'" required>
					<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_e491488d5ec7ea051798a5ec4_a5f6d12767" tabindex="-1" value=""></div>
				 </div>
				 <input type="submit" value="'.lang('submit_subscribe').'" name="subscribe" id="mc-embedded-subscribe" class="btn btn-primary">
			</form>
			</div>

      </div>
    </div>
  </div>
</div>';
	/*
	<form action="https://alnajat.us3.list-manage.com/subscribe/post?u=c4bca2f3d66403dce8ce6a085&amp;id=6ac845ea02" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
		<div class="form-group" id="mc_embed_signup_scroll">
			<label for="site_unsubscribe" class="site_unsubscribe">'.lang('subscribe').'</label>
			<input type="email" class="form-control" id="site_unsubscribe" name="EMAIL" value="" placeholder="'.lang('users_email').'" required>
			<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_c4bca2f3d66403dce8ce6a085_6ac845ea02" tabindex="-1" value=""></div>
		 </div>
		 <input type="submit" value="'.lang('submit_subscribe').'" name="subscribe" id="mc-embedded-subscribe" class="btn btn-primary">
	</form>
	*/
		$tpl = '';
		if( empty(setting('subscribe_whasapp')) && empty(setting('subscribe_email')) ){
			$tpl .= '';
		}else{
			$tpl .= '<div class="container">'.$this->n;
			$tpl .= '<div class="row">'.$this->n;
			$tpl .= '<div class="col-6 whatsapp">'.$this->n;
			$tpl .= '<a href="'.setting('subscribe_whasapp').'"><i class="fab fa-whatsapp"></i><br>'.lang('alnajat_subscribe_whatsapp').'</a>'.$this->n;
			$tpl .= '</div>'.$this->n;
			$tpl .= '<div class="col-6 email">'.$this->n;
			$tpl .= '<a data-toggle="modal" data-target="#subscribe_emailModal" href="'.setting('subscribe_email').'"><i class="fa fa-envelope"></i><br>'.lang('alnajat_subscribe_email').'</a>'.$this->n;
			$tpl .= $modal;
			$tpl .= '</div>'.$this->n;
			$tpl .= '</div>'.$this->n;
			$tpl .= '<p>'.sprintf(lang('alnajat_unsubscribe'), setting('unsubscribe')).'</p>'.$this->n;
			$tpl .= '</div>'.$this->n;
		}

		$tpl .= '</main>'.$this->n;
		$tpl .= '<script src="'.$site_url.'js/popper.min.js"></script>'.$this->n;
		$tpl .= '<script src="'.$site_url.'js/bootstrap.min.js"></script>'.$this->n;
		$tpl .= $js.$this->n;
		$tpl .= '</body>'.$this->n;
		$tpl .= '</html>';
		return $tpl;
	}

	public function tpl_close(){
		$site_name = strip_tags($this->site_name);
		$site_url = strip_tags($this->site_url);

		$tpl = '<!DOCTYPE html>'.$this->n;
		$tpl .= '<head>'.$this->n;
		$tpl .= '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">'.$this->n;
		$tpl .= '<meta charset="UTF-8">'.$this->n;
		$tpl .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.$this->n;
		$tpl .= '<title>'.$site_name.'</title>'.$this->n;
		$tpl .= '<link href="'.$site_url.'css/bootstrap/bootstrap.min.css" rel="stylesheet" type="text/css" media="all">'.$this->n;
		$tpl .= '<link href="'.$site_url.'css/fontawesome/all.css" rel="stylesheet" type="text/css" media="all">'.$this->n;
		$tpl .= '<link href="'.$site_url.'css/style.css" rel="stylesheet" type="text/css" media="all">'.$this->n;
		$tpl .= '</head>'.$this->n;
		$tpl .= '<body>'.$this->n;
		$tpl .= '<main>'.$this->n;

		$tpl .= '<div class="container mt-5">'.$this->n;
		$tpl .= $this->tpl_panel( lang('is_close'), setting('close_site_cause') ).$this->n;
		$tpl .= '</div>'.$this->n;

		$tpl .= '</main>'.$this->n;
		$tpl .= '</body>'.$this->n;
		$tpl .= '</html>'.$this->n;

		return $tpl;
	}

	function tpl_panel($title='', $text='', $style=0){

		if( $style == 1 ){
			$addclass = 'card text-white bg-primary cpanel-menu';
		}elseif( $style == 2 ){
			$addclass = 'card text-white bg-success cpanel-menu';
		}elseif( $style == 3 ){
			$addclass = 'card text-white bg-info cpanel-menu';
		}elseif( $style == 4 ){
			$addclass = 'card text-white bg-warning cpanel-menu';
		}elseif( $style == 5 ){
			$addclass = 'card text-white bg-danger cpanel-menu';
		}else{
			$addclass = 'card bg-light cpanel-menu';
		}

		$code = '<div class="'.$addclass.'">'.$this->n;
		$code .= '<div class="card-header">'.$this->n;
		$code .= '<h3 class="card-title">'.$title.'</h3>'.$this->n;
		$code .= '</div>'.$this->n;
		$code .= '<div class="card-body">'.$this->n;
		$code .= $text.$this->n;
		$code .= '</div>'.$this->n;
		$code .= '</div>'.$this->n;
		return $code;
	}

	function tpl_alert($text='', $type='', $clear_bottom=0){
		if($type == "success"){
			$classname = 'alert-success';
		}elseif($type == "info"){
			$classname = 'alert-info';
		}elseif($type == "warning"){
			$classname = 'alert-warning';
		}elseif($type == "danger"){
			$classname = 'alert-danger';
		}else{
			$classname = 'alert-info';
		}

		$bottom_class = ( $clear_bottom == 1 ? ' mb-0' : '' );

		return '<div class="alert '.$classname.$bottom_class.'" role="alert">'.$text.'</div>'.$this->n;
	}

	public function tpl_body($content='', $home=0){
		if( setting('close_site') == 1 ){
			$code = $this->tpl_close();
		}else{
			$code = $this->tpl_header($home);
			$code .= $content;
			$code .= $this->tpl_footer();
		}
		return $code;
	}

	function search(){
		$site_name = strip_tags($this->site_name);
		$site_url = strip_tags($this->site_url);
		$code = '<form role="search" method="get" id="searchform" action="'.$site_url.'index.php">';
		//$code .= '<input type="hidden" name="token" value="'.generate_form_token().'">';
		$code .= '<input type="hidden" name="action" value="search">';
		$code .= '<div class="form-group">';
		$code .= '<input type="text" name="s" id="s" class="form-control" placeholder="Search" required>';
		$code .= '</div>';
		$code .= '<button type="submit" class="btn btn-default">Search</button>';
		$code .= '</form>';
		return $code;
	}

}
?>
