<?php
class CP {
	public $site_name;
	public $main_title;
	public $site_url;
	public $page_title;
	public $DB;
	public $js;
	public $footer_js;
	public $welcome;
	public $script_name;
	public $PDF;
	public $breadcrumb_parent;
	public $dir_dest;
	public $dir_pics;
	public $allowfiles;

	function __construct() {
		global $DB, $mpdf;
		$this->DB = $DB;
		$this->site_name = $this->get_setting('site_title');
		$this->PDF = $mpdf;
		$this->script_name = lang('script_name');
		$this->dir_dest = '../upload';
		$this->dir_pics = 'upload';
		$this->allowfiles = array('application/pdf','application/msword', 'image/*', 'audio/*', 'video/*');
	}

	function check_user($user_id=0, $pass=''){
		$query = $this->DB->N_query("SELECT * FROM users WHERE active=1 AND id='".$user_id."' AND password='".$pass."' LIMIT 1");
		$counts = $this->DB->N_num_rows( $query );
		if( $counts == 0 ){
			$info = array( 'msg' => 'error' );
		}else{
			$row = $this->DB->N_fetch_array($query);
			$name = text_filter(3, $row['name']);
			$username = text_filter(3, $row['username']);
			$password = text_filter(3, $row['password']);
			$email = text_filter(3, $row['email']);
			$active = intval($row['active']);
			$group = intval($row['user_group']);
			$info = array( 'msg' => 'ok', 'user_id' => $user_id, 'username' => $username, 'password' => $password, 'email' => $email, 'name' => $name, 'group' => $group );
		}
		return $info;
	}

	function login_form(){
		$this->page_title = 'Login';

		$code = '<div class="form-container">';
		$code .= '<form class="form-signin" action="" method="post">';
		$code .= '<input type="hidden" name="login" value="ok_'.login_salt().'" />';
		$code .= '<input type="hidden" name="token" value="'.generate_form_token().'" />';
		$code .= '<h2 class="form-signin-heading">'.lang('login').'</h2>';

		$code .= '<div class="form-group">';
		$code .= '<label for="username">'.lang('username').'</label>';
		$code .= '<input type="text" id="username" name="username" class="form-control" placeholder="'.lang('username').'" required autofocus>';
		$code .= '</div>';

		$code .= '<div class="form-group">';
		$code .= '<label for="password">'.lang('password').'</label>';
		$code .= '<input type="password" id="password" name="password" class="form-control" placeholder="'.lang('password').'" required>';
		$code .= '</div>';

		$code .= '<button class="btn btn-lg btn-primary btn-block" type="submit">'.lang('login').'</button>';
		$code .= '</form>';
		$code .= '</div>';

		return $code;
	}

	function get_navbar(){
		$navbar = '<nav class="navbar navbar-expand-lg navbar-dark bg-dark">';
		$navbar .= '<div class="container">';
		$navbar .= '<a class="navbar-brand" href="index.php?action=home">'.lang('dashboard_title').'</a>';
		$navbar .= '<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#siteNavbar" aria-controls="siteNavbar" aria-expanded="false" aria-label="Toggle navigation">';
		$navbar .= '<span class="navbar-toggler-icon"></span>';
		$navbar .= '</button>';
		$navbar .= '<div class="collapse navbar-collapse" id="siteNavbar">';
		$navbar .= '<ul class="'.lang('class_navbar_nav').'">';
		$navbar .= '<li class="nav-item active"><a class="nav-link" target="_blank" href="../index.php"><i class="fas fa-home"></i> '.lang('home').' <span class="sr-only">(current)</span></a></li>';
		$navbar .= '<li class="nav-item"><a target="_blank" class="nav-link" href="'.$this->get_setting('site_url').'index.php?read=pdf&online=1"><i class="fas fa-eye"></i> '.lang('show_publication').'</a></li>';

		$navbar .= '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="dropdown_category" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-folder-open"></i> '.lang('categories').' </a>';
		$navbar .= '<div class="dropdown-menu" aria-labelledby="dropdown_category">';
		$navbar .= '<a class="dropdown-item" href="index.php?action=category_add">'.lang('category_add').'</a>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=category_data">'.lang('categories').'</a>';
		$navbar .= '</div>';
		$navbar .= '</li>';

		$navbar .= '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="dropdown_newspaper" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="far fa-newspaper"></i> '.lang('newspapers').' </a>';
		$navbar .= '<div class="dropdown-menu" aria-labelledby="dropdown_newspaper">';
		$navbar .= '<a class="dropdown-item" href="index.php?action=newspaper_add">'.lang('newspaper_add').'</a>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=newspaper_data">'.lang('newspapers').'</a>';
		$navbar .= '</div>';
		$navbar .= '</li>';

		$navbar .= '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="dropdown_news" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="far fa-plus-square"></i> '.lang('news').' </a>';
		$navbar .= '<div class="dropdown-menu" aria-labelledby="dropdown_news">';
		$navbar .= '<a class="dropdown-item" href="index.php?action=news_add">'.lang('news_add').'</a>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=news_data">'.lang('news').'</a>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=news_order">'.lang('order').'</a>';
		$navbar .= '</div>';
		$navbar .= '</li>';

		//$navbar .= '<li class="nav-item"><a class="nav-link" href="index.php?action=publications_data"><i class="fas fa-newspaper"></i> '.lang('publications').'</a></li>';

		/*
		$navbar .= '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="dropdown_news" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-newspaper"></i> '.lang('publications').' </a>';
		$navbar .= '<div class="dropdown-menu" aria-labelledby="dropdown_news">';
		$navbar .= '<a class="dropdown-item" href="index.php?action=publications_add">'.lang('publications_add').'</a>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=publications_data">'.lang('publications').'</a>';
		$navbar .= '</div>';
		$navbar .= '</li>';
		*/

		$navbar .= '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="dropdown_banners" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-bullhorn"></i> '.lang('banners').' </a>';
		$navbar .= '<div class="dropdown-menu" aria-labelledby="dropdown_banners">';
		$navbar .= '<a class="dropdown-item" href="index.php?action=banner_add">'.lang('banners_add').'</a>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=banner_data">'.lang('banners').'</a>';
		$navbar .= '</div>';
		$navbar .= '</li>';
/*
		$navbar .= '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="dropdown_users" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-user"></i> '.lang('users').' </a>';
		$navbar .= '<div class="dropdown-menu" aria-labelledby="dropdown_users">';
		$navbar .= '<a class="dropdown-item" href="index.php?action=user_add">'.lang('user_add').'</a>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=user_data">'.lang('users').'</a>';
		$navbar .= '</div>';
		$navbar .= '</li>';
*/
		$navbar .= '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="dropdown_tools" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cogs"></i> '.lang('tools').' </a>';
		$navbar .= '<div class="dropdown-menu" aria-labelledby="dropdown_tools">';
		$navbar .= '<a class="dropdown-item" href="index.php?action=upload">'.lang('upload').'</a>';
		$navbar .= '<div class="dropdown-divider"></div>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=publications_data">'.lang('publications').'</a>';
		$navbar .= '<div class="dropdown-divider"></div>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=user_add">'.lang('user_add').'</a>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=user_data">'.lang('users').'</a>';
		$navbar .= '<div class="dropdown-divider"></div>';
		$navbar .= '<a class="dropdown-item" href="index.php?action=setting">'.lang('setting').'</a>';
		$navbar .= '<div class="dropdown-divider"></div>';
		$navbar .= '<a class="dropdown-item" target="_blank" href="'.$this->get_setting('site_url').'index.php?read=pdf&online=1">'.lang('show_publication').'</a>';
		$navbar .= '<a class="dropdown-item" target="_blank" href="'.$this->get_setting('site_url').'index.php?read=pdf">'.lang('download_publication').'</a>';
		$navbar .= '</div>';
		$navbar .= '</li>';

		$navbar .= '<li class="nav-item"><a class="nav-link" href="index.php?action=out"><i class="fas fa-sign-out-alt"></i> '.lang('sign_out').'</a></li>';
		$navbar .= '</ul>';
		$navbar .= '</div>';
		$navbar .= '</div>';
		$navbar .= '</nav>';

		return $navbar;
	}

	function get_header($justhead=0){
		$getmaintitle = ( empty($this->main_title) ? '' : ' - '.$this->main_title );
		$getmaintitlebreadcrumb = ( empty($this->main_title) ? '' : ' <strong>'.$this->main_title.'</strong>' );
		$title = ( empty($this->page_title) ? $this->site_name : $this->site_name.' - '.$this->page_title.$getmaintitle);
		$breadcrumb = $this->breadcrumb( $this->page_title.$getmaintitlebreadcrumb, $this->breadcrumb_parent );

		$code = '<!doctype html>';
		$code .= '<html lang="en">';
		$code .= '<head>';
		$code .= '<meta charset="utf-8">';
		$code .= '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
		$code .= '<title>'.$title.'</title>';
		$code .= '<meta name="description" content="'.$title.'">';
		$code .= '<link href="'.base_url('css/bootstrap.min.css').'" rel="stylesheet">';
		$code .= '<link href="'.base_url('css/style.css').'" rel="stylesheet">';
		$code .= '<link href="'.base_url('css/fontawesome-all.css').'" rel="stylesheet">';
		$code .= '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
		$code .= '<script src="https://code.jquery.com/jquery-1.12.4.js"></script>';
		$code .= '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
		$code .= '<script src="'.base_url('js/tinymce/tinymce.min.js').'"></script>';
		$code .= '<script src="'.base_url('js/tinymce/tinymce.custom.js').'"></script>';
		$code .= '</head>';
		$code .= '<body>';
		if( $justhead != 1 ){
			$code .= $this->get_navbar();
		}
		$code .= '<div class="container" role="main">';
		$code .= '<div class="body-container">';

		if( isset($_SESSION['user_id']) ){
			if( $justhead != 1 ){
				$code .= $this->get_alert( lang('welcome').' <strong>'.$this->welcome.'</strong> <span class="new-add-text"><a class="btn btn-primary btn-sm" href="index.php?action=news_add" role="button"><i class="fas fa-plus-square"></i> '.lang('news_add').'</a></span>', 'success');
			}
		}

		$code .= $breadcrumb;
		return $code;
	}

	function get_footer($justhead=0){

		$code = '<div class="mb-3"></div>';
		$code .= '</div>';
		$code .= '</div>';

		if($justhead != 1 ){
			$code .= '<div class="clearfix"></div>';
			$code .= '<footer>';
			$code .= '<div class="container">';
			$code .= '<p>'.sprintf(lang('copyright'), date('Y', time()).' '.$this->site_name).' | '.lang('script_name').'</p>';
			$code .= '</div>';
			$code .= '</footer>';
		}

		$code .= '<script src="'.base_url('js/popper.min.js').'"></script>';
		$code .= '<script src="'.base_url('js/bootstrap.min.js').'"></script>';

		$action = ( isset($_GET['action']) ? $_GET['action'] : '' );

		$code .= '</body>';
		$code .= '</html>';
		return $code;
	}

	function get_panel($title='', $text='', $style=0){

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
			$addclass = 'card bg-light cpanel-menu mb-3';
		}

		$code = '<div class="'.$addclass.'">';
		$code .= '<div class="card-header">';
		$code .= '<h3 class="card-title">'.$title.'</h3>';
		$code .= '</div>';
		$code .= '<div class="card-body">';
		$code .= $text;
		$code .= '</div>';
		$code .= '</div>';
		return $code;
	}

	function get_alert($text='', $type='', $clear_bottom=0){
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

		return '<div class="alert '.$classname.$bottom_class.'" role="alert">'.$text.'</div>';
	}

	function breadcrumb( $current_page_title, $pages = array() ){
		if( isset($_SESSION['user_id']) AND isset($_SESSION['pass']) AND $_SESSION['timeout'] > time() ){
			$get_password = str_replace(get_start_password(), "", strip_tags($_SESSION['pass']));

			$check_username = $this->check_user( intval($_SESSION['user_id']), $get_password );
			$msg = ( isset($check_username['msg']) ? $check_username['msg'] : 'error' );
			$acton = ( isset($_GET['action']) ? strip_tags($_GET['action']) : 'home' );

			if( $msg == 'ok' && $acton != 'home' ){
				$breadcrumb = '<nav aria-label="breadcrumb">';
				$breadcrumb .= '<ol class="breadcrumb">';
				$breadcrumb .= '<li class="breadcrumb-item"><a href="index.php">'.lang('home').'</a></li>';
				if( is_array($pages) && count($pages) > 0 ){
					foreach( $pages as $k => $v ){
						$title = ( isset($v['title']) ? $v['title'] : '' );
						$url = ( isset($v['url']) ? $v['url'] : '' );
						if( !empty($title) && !empty($url) ){
							$breadcrumb .= '<li class="breadcrumb-item"><a href="'.$url.'">'.$title.'</a></li>';
						}
					}
				}
				$breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">'.$current_page_title.'</li>';
				$breadcrumb .= '</ol>';
				$breadcrumb .= '</nav>';
			}else{
				$breadcrumb = '';
			}
		}else{
			$breadcrumb = '';
		}

		$code = '<div class="row">';
		$code .= '<div class="col-md-8">';
		$code .= $breadcrumb;
		$code .= '</div>';
		$code .= '<div class="col-md-4">';
		$code .= '<a href="#" class="btn btn-primary active" role="button" aria-pressed="true"><i class="fas fa-folder-open"></i> الأقسام </a>';
		$code .= '</div>';
		$code .= '</div>';

		return $breadcrumb;
	}

	function newspaper_add( $update=0 ){
		$id = ( isset($_GET['id']) ? intval($_GET['id']) : 0 );
		$name = '';
		$logo = '';
		$url = '';
		$country = '';
		$description = '';
		$text = '';
		$active = 1;
		$user_id = 0;
		$type = 0;

		$form_action = 'index.php?action=newspaper_insert';
		$input_hidden = '<input type="hidden" name="token" value="'.generate_form_token('newspaper_add').'">';
		$submit_name = lang('submit');

		$code = '';
		$allow_form = 1;

		if( $update == 1 ){
			if( empty($id) ){
				$code .= $this->get_alert(sprintf(lang('empty_id'), $id), 'danger', 1);
				$allow_form = 0;
			}else{
				$query = $this->DB->N_query("SELECT * FROM newspaper WHERE id='".$id."' LIMIT 1");
				$counts = $this->DB->N_num_rows( $query );
				if( $counts == 0 ){
					$code .= $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger', 1);
					$allow_form = 0;
				}else{
					$row = $this->DB->N_fetch_array($query);
					$name = text_filter(3, $row['name']);
					$logo = text_filter(3, $row['logo']);
					$url = text_filter(3, $row['url']);
					$country = text_filter(3, $row['country']);
					$description = text_filter(3, $row['description']);
					$text = text_filter(3, $row['text']);
					$active = intval($row['active']);
					$user_id = text_filter(3, $row['user_id']);
					$type = intval($row['type']);

					$this->main_title = $name;

					$form_action = 'index.php?action=newspaper_update';
					$input_hidden = '<input type="hidden" name="id" value="'.$id.'">';
					$input_hidden .= '<input type="hidden" name="token" value="'.generate_form_token('newspaper_update_'.$id).'">';
					$submit_name = lang('update');
				}
			}
		}

		$newspaper_type = array( 0 => lang('newspaper_type_paper'), 1 => lang('newspaper_type_web') );

		$form = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
		$form .= $input_hidden;

		$form .= '<div class="form-group">';
		$form .= '<label for="name">'.lang('name').'</label>';
		$form .= '<input type="text" class="form-control" id="name" name="name" value="'.$name.'">';
		$form .= '</div>';

		$form .= '<div class="form-group">';
		$form .= '<label for="logo">'.lang('logo').'</label>';
		$form .= '<input type="text" class="form-control" id="logo" name="logo" value="'.$logo.'">';
		$form .= '<input type="file" class="form-control" id="upload_image" name="upload_image">';
		$form .= '</div>';

		$form .= '<div class="form-group">';
		$form .= '<label for="url">'.lang('url').'</label>';
		$form .= '<input type="text" class="form-control" id="url" name="url" value="'.$url.'">';
		$form .= '</div>';

		$form .= '<div class="form-group">';
		$form .= '<label for="country">'.lang('country').'</label>';
		$form .= '<select class="form-control" id="country" name="country">';
		$form .= '<option value="">- - -</option>';
		foreach( countries() as $k => $v ){
			if( $country == $k ){
				$form .= '<option value="'.$k.'" selected>'.$v.'</option>';
			}else{
				$form .= '<option value="'.$k.'">'.$v.'</option>';
			}
		}
		$form .= '</select>';
		$form .= '</div>';

		$form .= '<div class="form-group">';
		$form .= '<label for="type">'.lang('newspaper_type').'</label>';
		$form .= '<select class="form-control" id="type" name="type">';
		foreach( $newspaper_type as $kt => $vt ){
			if( $type == $kt ){
				$form .= '<option value="'.$kt.'" selected>'.$vt.'</option>';
			}else{
				$form .= '<option value="'.$kt.'">'.$vt.'</option>';
			}
		}
		$form .= '</select>';
		$form .= '</div>';

		$form .= '<div class="form-group">';
		$form .= '<label for="description">'.lang('description').'</label>';
		$form .= '<textarea class="form-control" id="description" rows="3" name="description">'.$description.'</textarea>';
		$form .= '</div>';

		$form .= '<div class="form-group">';
		$form .= '<label for="text">'.lang('notice').'</label>';
		$form .= '<textarea class="form-control" id="text" rows="3" name="text">'.$text.'</textarea>';
		$form .= '</div>';

		$form .= '<button type="submit" class="btn btn-primary">'.$submit_name.'</button>';
		$form .= '</form>';

		$this->breadcrumb_parent = array( array('title' => lang('newspapers'), 'url' => 'index.php?action=newspaper_data') );

		if($allow_form == 1){
			return $form;
		}else{
			return $code;
		}
	}

	function newspaper_edit(){
		return $this->newspaper_add(1);
	}

	function newspaper_insert(){
		$code = '';

		if( verify_form_token('newspaper_add') == false ){
			$code .= '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST) ){
				$name = ( isset($_POST['name']) ? $this->DB->N_escape_string($_POST['name']) : '' );
				$logo = ( isset($_POST['logo']) ? $this->DB->N_escape_string($_POST['logo']) : '' );
				$url = ( isset($_POST['url']) ? $this->DB->N_escape_string($_POST['url']) : '' );
				$country = ( isset($_POST['country']) ? $this->DB->N_escape_string($_POST['country']) : '' );
				$description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
				$text = ( isset($_POST['text']) ? $this->DB->N_escape_string($_POST['text']) : '' );
				$type = ( isset($_POST['type']) ? intval($_POST['type']) : 0 );
				$active = 1;

				if( isset($_SESSION['user_id']) ){
					$user_id = intval($_SESSION['user_id']);
				}else{
					$user_id = 0;
				}
				$date = time();

				$err = array();

				if( empty($name) ){
					$err[] = '<p class="mb-0">'.lang('validate_name').'</p>';
				}

				if( count($err) > 0 ){
					foreach ($err as $key => $value) {
						$code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
					}
				}else{
					$upload = upload_files('upload_image', 'newspaper');
					$get_image = ( empty($upload) ? $logo : $upload );

					$query = $this->DB->N_query("INSERT INTO newspaper (`name`, `logo`, `url`, `country`, `description`, `text`, `user_id`, `date`, `active`, `type`) VALUES ('".$name."', '".$get_image."', '".$url."', '".$country."', '".$description."', '".$text."', '".$user_id."', '".$date."', '".$active."', '".$type."' )");
					if( $query ){
						$insert_id = ( $this->DB->N_insert_id() == 0 ? $this->DB->last_N_insert_id('newspaper', 'id') : $this->DB->N_insert_id() );
						$code .= '<div class="alert alert-success" role="alert">'.lang('added').'</div>';
						$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=newspaper_edit&id='.$insert_id.'" />';
					}else{
						$code .= '<div class="alert alert-danger" role="alert">'.lang('not_added').'</div>';
					}
				}
			}else{
				$code .= '<div class="alert alert-danger" role="alert">'.lang('empty_value').'</div>';
			}
		}

		return $code;
	}

	function newspaper_update(){
		if( verify_form_token('newspaper_update_'.$_POST['id']) == false ){
			$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST['id']) && intval($_POST['id']) != 0 ){
				$id = intval($_POST['id']);

				if( isset($_SESSION['user_id']) ){
					$user_id = intval($_SESSION['user_id']);
				}else{
					$user_id = 0;
				}

				$date = time();

				$name = ( isset($_POST['name']) ? $this->DB->N_escape_string($_POST['name']) : '' );
				$logo = ( isset($_POST['logo']) ? $this->DB->N_escape_string($_POST['logo']) : '' );
				$url = ( isset($_POST['url']) ? $this->DB->N_escape_string($_POST['url']) : '' );
				$country = ( isset($_POST['country']) ? $this->DB->N_escape_string($_POST['country']) : '' );
				$description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
				$text = ( isset($_POST['text']) ? $this->DB->N_escape_string($_POST['text']) : '' );
				$type = ( isset($_POST['type']) ? intval($_POST['type']) : '' );
				$active = 1;

				$err = array();

				if( empty($name) ){
					$err[] = '<p class="mb-0">'.lang('validate_name').'</p>';
				}

				$code = '';
				if( count($err) > 0 ){
					foreach ($err as $key => $value) {
						$code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
					}
				}else{
					$upload = upload_files('upload_image', 'newspaper');
					$get_image = ( empty($upload) ? $logo : $upload );

					$query = $this->DB->N_query("UPDATE newspaper SET name='".$name."', logo='".$get_image."', url='".$url."', country='".$country."', description='".$description."', text='".$text."', update_user_id='".$user_id."', update_date='".$date."', type='".$type."' WHERE id='".$id."' LIMIT 1");
					if( $query ){
						$code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
						$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=newspaper_data" />';
					}else{
						$code = $this->get_alert('Error', 'danger');
					}
				}
			}else{
				$code = $this->get_alert(lang('empty_id'), 'danger');
			}
		}

		return $code;
	}

	function newspaper_status(){
		if( isset($_GET['id']) && intval($_GET['id']) != 0 ){
			$id = intval($_GET['id']);
			$status = ( isset($_GET['act']) ? intval($_GET['act']) : 0 );
			$query = $this->DB->N_query("UPDATE newspaper SET active='".$status."' WHERE id='".$id."' LIMIT 1");
			if( $query ){
				$code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
				$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=newspaper_data" />';
			}else{
				$code = $this->get_alert(lang('error'), 'danger');
			}
		}else{
			$code = $this->get_alert(lang('empty_id'), 'danger');
		}

		return $code;
	}

	function newspaper_delete(){
		if(isset($_GET['id']) && intval($_GET['id']) != 0){
			$id = intval($_GET['id']);

			$query = $this->DB->N_query("SELECT * FROM newspaper WHERE id='".$id."' LIMIT 1");
			$counts = $this->DB->N_num_rows($query);
			if($counts == 0){
				$code = $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger');
			}else{
				$row = $this->DB->N_fetch_array($query);
				$name = text_filter(3, $row['name']);
				$this->main_title = $name;

				$this->breadcrumb_parent = array( array('title' => lang('newspapers'), 'url' => 'index.php?action=newspaper_data') );

				$code = '<form name="delete" method="post" action="index.php?action=newspaper_delete">';
				$code .= '<input type="hidden" name="post_id" value="'.$id.'" />';
				$code .= '<input type="hidden" name="delete_post" value="yes" />';
				$code .= '<input type="hidden" name="token" value="'.generate_form_token('newspaper_delete_'.$id).'">';
				$code .= '<input type="submit" value="'.sprintf(lang('delete_sure'), $name).'" name="delete" />';
				$code .= '</form>';
			}
		}else{
			$post_id = ( isset($_POST['post_id']) ? intval($_POST['post_id']) : 0 );
			if( verify_form_token('newspaper_delete_'.$post_id) == false ){
				$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
			}else{
				if( isset($_POST['delete_post']) && $_POST['delete_post'] == "yes" && isset($_POST['post_id']) && intval($_POST['post_id']) != 0 ){
					$post_id = intval($_POST['post_id']);
					$query =  $this->DB->N_query("DELETE FROM newspaper WHERE id='".$post_id."' LIMIT 1");
					if( $query ){
						$code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
						$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=newspaper_data" />';
					}else{
						$code = $this->get_alert(lang('error'), 'danger');
					}
				}else{
					$code = $this->get_alert(lang('empty_id'), 'danger');
				}
			}
		}

		return $code;
	}

	function newspaper_data($home=0){
		$counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM newspaper") );

		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$page = ($page == 0 ? 1 : $page);
		$perpage = 30;
		$startpoint = ($page * $perpage) - $perpage;

		$query_d = $this->DB->N_query("SELECT * FROM newspaper order by id desc LIMIT $startpoint,$perpage");
		$data_count = $this->DB->N_num_rows($query_d);

		$text = '';

		if($data_count == 0){
			$text .= lang('not_found');
		}else{
			$countries = countries();

			$text .= '<table class="table table-hover">';
			$text .= '<thead>';
			$text .= '<tr>';
			$text .= '<th scope="col">'.lang('name').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('news').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('country').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('status').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('edit').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('delete').'</th>';
			$text .= '</tr>';
			$text .= '</thead>';
			$text .= '<tbody>';
			$i=0;
			$arr_money = array();
			while ($row = $this->DB->N_fetch_array($query_d)){
				$name = text_filter(3, $row['name']);
				$logo = text_filter(3, $row['logo']);
				$url = text_filter(3, $row['url']);
				$country = text_filter(3, $row['country']);
				$description = text_filter(3, $row['description']);
				$notice = text_filter(3, $row['text']);
				$active = intval($row['active']);
				$user_id = text_filter(3, $row['user_id']);

				$added = date("j/n/Y", $row['date']);

				$country_name = ( array_key_exists($country, $countries) ? $countries[$country] : 'None' );
				$get_logo = ( empty($logo) ? '' : '<img src="'.$logo.'" alt="'.$name.'" class="w-100"> ' );

				if( $active == 1 ){
					$get_active = '<a href="index.php?action=newspaper_status&id='.$row['id'].'&act=0"><i class="fas fa-eye text-success"></i></a>';
				}else{
					$get_active = '<a href="index.php?action=newspaper_status&id='.$row['id'].'&act=1"><i class="fas fa-eye-slash text-danger"></i></a>';
				}

				if( empty($get_logo) ){
					$modal = '';
				}else{
					$modal = '<span data-toggle="modal" data-target=".newspaper-modal-'.$row['id'].'"><i class="far fa-image"></i></span> ';
					$modal .= '<div class="modal fade newspaper-modal-'.$row['id'].'" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">';
					$modal .= '<div class="modal-dialog modal-'.$row['id'].'">';
					$modal .= '<div class="modal-content">'.$get_logo.'</div>';
					$modal .= '</div>';
					$modal .= '</div>';
				}

				$news_count = $this->DB->N_num_rows($this->DB->N_query("SELECT * FROM news WHERE newspaper_id=".$row['id'].""));

				++$i;

				$text .= '<tr>';
				$text .= '<td>'.$modal.'<a href="index.php?action=newspaper_edit&id='.$row['id'].'">'.$name.'</a></td>';
				$text .= '<td class="text-center"><a href="index.php?action=news_newspaper&newspaper_id='.$row['id'].'">'.$news_count.'</a></td>';
				$text .= '<td class="text-center">'.$country_name.'</td>';
				$text .= '<td class="text-center">'.$get_active.'</td>';
				$text .= '<td class="text-center"><a href="index.php?action=newspaper_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a></td>';
				$text .= '<td class="text-center"><a href="index.php?action=newspaper_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a></td>';
				$text .= '</tr>';
			}
			$text .= '</tbody>';
			$text .= '</table>';
			if( $home == 1 ){
				$text .= '<div class="mt-3"><a href="index.php?action=newspaper_add">More</a></div>';
			}else{
				$text .= pagination($counts, $perpage, $page, 'index.php?action=newspaper_add&');
			}

		}

		return $text;
	}

	function categories_id_by_parent( $type = 0 ){
		if( $type == 1 ){
			$categories = array( 12, 10, 9, 7, 6, 1 );
		}elseif( $type == 2 ){
			$categories = array( 4 );
		}elseif( $type == 3 ){
			$categories = array( 5 );
		}elseif( $type == 4 ){
			$categories = array( 3 );
		}elseif( $type == 5 ){
			$categories = array( 11 );
		}else{
			$categories = array( 12, 11, 10, 9, 7, 6, 5, 4, 3, 1 );
		}
		return $categories;
	}

	function news_input( $info, $key_name = '' ){
		$title = ( isset($info['title']) ? $info['title'] : '' );
		$image = ( isset($info['image']) ? $info['image'] : '' );
		$url = ( isset($info['url']) ? $info['url'] : '' );
		$description = ( isset($info['description']) ? $info['description'] : '' );
		$text = ( isset($info['text']) ? $info['text'] : '' );
		$newspaper_id = ( isset($info['newspaper_id']) ? $info['newspaper_id'] : '' );
		$newspaper_number = ( isset($info['newspaper_number']) ? $info['newspaper_number'] : '' );
		$published_date = ( isset($info['published_date']) ? $info['published_date'] : '' );
		$active = ( isset($info['active']) ? $info['active'] : '' );
		$user_id = ( isset($info['user_id']) ? $info['user_id'] : '' );
		$tweet_url = ( isset($info['tweet_url']) ? $info['tweet_url'] : '' );
		$sound_url = ( isset($info['sound_url']) ? $info['sound_url'] : '' );
		$video_url = ( isset($info['video_url']) ? $info['video_url'] : '' );
		$type = ( isset($info['type']) ? $info['type'] : '' );
		$original_image = ( isset($info['original_image']) ? $info['original_image'] : '' );
		$hide_title = ( isset($info['hide_title']) ? $info['hide_title'] : '' );
		$hide_description = ( isset($info['hide_description']) ? $info['hide_description'] : '' );
		$hide_more = ( isset($info['hide_more']) ? $info['hide_more'] : '' );
		$post_categories = ( isset($info['post_categories']) ? $info['post_categories'] : '' );
		$submit_name = ( isset($info['submit_name']) ? $info['submit_name'] : '' );
		$data_type = ( isset($info['data_type']) ? $info['data_type'] : 0 );
		$hide_in_pdf = ( isset($info['hide_in_pdf']) ? $info['hide_in_pdf'] : '' );

		$categories_id_by_parent = $this->categories_id_by_parent( $data_type );
		$categories_id_by_parent_coun = count($categories_id_by_parent);

		$hide_title_checked = ( $hide_title == 1 ? ' checked' : '' );
		$hide_description_checked = ( $hide_description == 1 ? ' checked' : '' );
		$hide_more_checked = ( $hide_more == 1 ? ' checked' : '' );
		$hide_in_pdf_checked = ( $hide_in_pdf == 1 ? ' checked' : '' );

		$prefix_id = '_'.$data_type;

		$input_newspaper_id = '<div class="form-group">';
		$input_newspaper_id .= '<label for="newspaper_id'.$prefix_id.'">'.lang('newspapers').'</label>';
		$input_newspaper_id .= '<div class="row">';
		$input_newspaper_id .= '<div class="col-6 col-md-6">';
		$input_newspaper_id .= '<p>'.lang('newspaper_type_paper').'</p>';
		$input_newspaper_id .= '<select class="form-control" id="newspaper_id'.$prefix_id.'" name="newspaper_id_0">';
		$input_newspaper_id .= '<option value="0">- - -</option>';
		$query_d = $this->DB->N_query("SELECT * FROM newspaper WHERE type=0 ORDER BY id DESC");
		$data_count = $this->DB->N_num_rows($query_d);
		if($data_count > 0){
			while ($rowx = $this->DB->N_fetch_array($query_d)){
				$newspaper_name = text_filter(3, $rowx['name']);
				if( $newspaper_id == $rowx['id'] ){
					$input_newspaper_id .= '<option value="'.$rowx['id'].'" selected>'.$newspaper_name.'</option>';
				}else{
					$input_newspaper_id .= '<option value="'.$rowx['id'].'">'.$newspaper_name.'</option>';
				}
			}
		}
		$input_newspaper_id .= '</select>';
		$input_newspaper_id .= '</div>';
		$input_newspaper_id .= '<div class="col-6 col-md-6">';
		$input_newspaper_id .= '<p>'.lang('newspaper_type_web').'</p>';
		$input_newspaper_id .= '<select class="form-control" id="newspaper_id2'.$prefix_id.'" name="newspaper_id_1">';
		$input_newspaper_id .= '<option value="0">- - -</option>';
		$query_d2 = $this->DB->N_query("SELECT * FROM newspaper WHERE type=1 ORDER BY id DESC");
		$data_count2 = $this->DB->N_num_rows($query_d2);
		if($data_count2 > 0){
			while ($rowx2 = $this->DB->N_fetch_array($query_d2)){
				$newspaper_name2 = text_filter(3, $rowx2['name']);
				if( $newspaper_id == $rowx2['id'] ){
					$input_newspaper_id .= '<option value="'.$rowx2['id'].'" selected>'.$newspaper_name2.'</option>';
				}else{
					$input_newspaper_id .= '<option value="'.$rowx2['id'].'">'.$newspaper_name2.'</option>';
				}
			}
		}
		$input_newspaper_id .= '</select>';
		$input_newspaper_id .= '</div>';
		$input_newspaper_id .= '</div>';
		/*
		$input_newspaper_id .= '<select class="form-control" id="newspaper_id'.$prefix_id.'" name="newspaper_id">';
		$input_newspaper_id .= '<option value="0">- - -</option>';
		$query_d = $this->DB->N_query("SELECT * FROM newspaper ORDER BY id DESC");
		$data_count = $this->DB->N_num_rows($query_d);
		if($data_count > 0){
			while ($rowx = $this->DB->N_fetch_array($query_d)){
				$newspaper_name = text_filter(3, $rowx['name']);
				if( $newspaper_id == $rowx['id'] ){
					$input_newspaper_id .= '<option value="'.$rowx['id'].'" selected>'.$newspaper_name.'</option>';
				}else{
					$input_newspaper_id .= '<option value="'.$rowx['id'].'">'.$newspaper_name.'</option>';
				}
			}
		}
		$input_newspaper_id .= '</select>';
		*/
		$input_newspaper_id .= '</div>';

		$input_categories = '<div class="form-group">';
		$input_categories .= '<label for="category">'.lang('categories').'</label>';
		$input_categories .= '<div class="row">';
		$query_d = $this->DB->N_query("SELECT id,title FROM category WHERE active=1");
		if($this->DB->N_num_rows($query_d) > 0){
			while($rowx = $this->DB->N_fetch_array($query_d)){
				$category_title = text_filter(3, $rowx['title']);
				$checked = ( in_array($rowx['id'], $post_categories) ? ' checked' : '' );
				if( in_array( $rowx['id'], $categories_id_by_parent) ){
					$input_categories .= '<div class="col-4 col-md-3"><input type="checkbox" name="category_id[]" id="cat-'.$rowx['id'].$prefix_id.'" value="'.$rowx['id'].'"'.$checked.'> <label for="cat-'.$rowx['id'].$prefix_id.'">'.$category_title.'</label></div>';
				}
			}
		}
		$input_categories .= '</div>';
		$input_categories .= '</div>';

		$input_list = array();
		$input_list['title'] = '<div class="form-group"><label for="title'.$prefix_id.'">'.lang('title').'</label><input type="text" class="form-control" id="title'.$prefix_id.'" name="title" value="'.$title.'"></div>';
		$input_list['image'] = '<div class="form-group"><label for="image'.$prefix_id.'">'.lang('image').'</label><input type="text" class="form-control" id="image'.$prefix_id.'" name="image" value="'.$image.'"><input type="file" class="form-control" id="upload_image" name="upload_image"></div>';
		$input_list['url'] = '<div class="form-group"><label for="url'.$prefix_id.'">'.lang('source').'</label><input type="text" class="form-control" id="url'.$prefix_id.'" name="url" value="'.$url.'"></div>';
		$input_list['url2'] = '<div class="form-group"><label for="url'.$prefix_id.'">'.lang('donation_url').'</label><input type="text" class="form-control" id="url'.$prefix_id.'" name="url" value="'.$url.'"></div>';
		$input_list['newspaper_id'] = $input_newspaper_id;
		$input_list['newspaper_number'] = '<div class="form-group"><label for="newspaper_number'.$prefix_id.'">'.lang('newspaper_number').'</label><input type="number" class="form-control" id="newspaper_number'.$prefix_id.'" name="newspaper_number" value="'.$newspaper_number.'"></div>';
		$input_list['published_date'] = '<div class="form-group"><label for="published_date'.$prefix_id.'">'.lang('published_date').'</label><input type="date" class="form-control" id="published_date'.$prefix_id.'" name="published_date" value="'.$published_date.'"></div>';
		$input_list['description'] = '<div class="form-group"><label for="description'.$prefix_id.'">'.lang('description').'</label><textarea class="form-control" id="description'.$prefix_id.'" rows="3" name="description">'.$description.'</textarea></div>';
		$input_list['description2'] = '<div class="form-group"><label for="description'.$prefix_id.'">'.lang('text_tweet').'</label><textarea class="form-control" id="description'.$prefix_id.'" rows="3" name="description">'.$description.'</textarea></div>';
		$input_list['text'] = '<div class="form-group"><label for="tinymce-editor'.$prefix_id.'">'.lang('details').'</label><textarea class="form-control tinymce-editor" rows="3" name="text">'.$text.'</textarea></div>';
		if( $categories_id_by_parent_coun == 1 ){
			if( is_array($post_categories) && count($post_categories) > 0 ){
				$inputs = '';
				foreach($post_categories as $key_pp => $value_pp ){
					$inputs .= '<input type="hidden" name="category_id[]" value="'.$value_pp.'">';
				}
				$input_list['categories'] = $inputs;
			}else{
				$input_list['categories'] = '<input type="hidden" name="category_id[]" value="'.$categories_id_by_parent[0].'">';
			}
		}else{
			$input_list['categories'] = $input_categories;
		}
		$input_list['tweet_url'] = '<div class="form-group"><label for="tweet_url'.$prefix_id.'">'.lang('tweet_link').'</label><input type="text" class="form-control" id="tweet_url'.$prefix_id.'" name="tweet_url" value="'.$tweet_url.'"></div>';
		$input_list['sound_url'] = '<div class="form-group"><label for="sound_url'.$prefix_id.'">'.lang('sound_link').'</label><input type="text" class="form-control" id="sound_url'.$prefix_id.'" name="sound_url" value="'.$sound_url.'"></div>';
		$input_list['video_url'] = '<div class="form-group"><label for="video_url'.$prefix_id.'">'.lang('video_link').'</label><input type="text" class="form-control" id="video_url'.$prefix_id.'" name="video_url" value="'.$video_url.'"></div>';
		$input_list['hide_title'] = '<div class="form-group"><input type="checkbox" id="hide_title_switch'.$prefix_id.'" name="hide_title" value="1"'.$hide_title_checked.'> <label class="custom-control-label" for="hide_title_switch'.$prefix_id.'">'.lang('hide_title').'</label></div>';
		$input_list['hide_in_pdf'] = '<div class="form-group"><input type="checkbox" id="hide_in_pdf_switch'.$prefix_id.'" name="hide_in_pdf" value="1"'.$hide_in_pdf_checked.'> <label class="custom-control-label" for="hide_in_pdf_switch'.$prefix_id.'">'.lang('hide_in_pdf').'</label></div>';
		$input_list['hide_description'] = '<div class="form-group"><input type="checkbox" id="hide_description_switch'.$prefix_id.'" name="hide_description" value="1"'.$hide_description_checked.'> <label class="custom-control-label" for="hide_description_switch'.$prefix_id.'">'.lang('hide_description').'</label></div>';
		$input_list['hide_more'] = '<div class="form-group"><input type="checkbox" id="hide_more_switch'.$prefix_id.'" name="hide_more" value="1"'.$hide_more_checked.'> <label for="hide_more_switch'.$prefix_id.'">'.lang('hide_more').'</label></div>';
		$input_list['submit'] = '<button type="submit" class="btn btn-primary">'.$submit_name.'</button>';

		return $input_list;
	}

	function news_duplicate(){
		$output = $this->news_add( 2 );

		return $output;
	}

	function news_add( $update=0 ){
		$id = ( isset($_GET['id']) ? intval($_GET['id']) : 0 );
		$title = '';
		$image = '';
		$url = '';
		$description = '';
		$text = '';
		$newspaper_id = 0;
		$newspaper_number = 0;
		$published_date = date("Y-m-d", time());
		$active = 1;
		$user_id = 0;
		$post_categories = array();
		$tweet_url = '';
		$sound_url = '';
		$video_url = '';
		$type = 0;
		$original_image = 0;
		$hide_title = 0;
		$hide_in_pdf = 0;
		$hide_description = 0;
		$hide_more = 0;

		$form_action = 'index.php?action=news_insert';
		$input_hidden = '<input type="hidden" name="token" value="'.generate_form_token('news_add').'">';
		$submit_name = lang('submit');

		$code = '';
		$allow_form = 1;

		if( $update == 1 || $update == 2 ){
			if( empty($id) ){
				$code .= $this->get_alert(sprintf(lang('empty_id'), $id), 'danger', 1);
				$allow_form = 0;
			}else{
				$query = $this->DB->N_query("SELECT * FROM news WHERE id='".$id."' LIMIT 1");
				$counts = $this->DB->N_num_rows( $query );
				if( $counts == 0 ){
					$code .= $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger', 1);
					$allow_form = 0;
				}else{
					$row = $this->DB->N_fetch_array($query);
					$title = text_filter(3, $row['title']);
					$image = text_filter(3, $row['image']);
					$url = text_filter(3, $row['url']);
					$description = text_filter(3, $row['description']);
					$text = text_filter(3, $row['text']);
					$newspaper_id = intval($row['newspaper_id']);
					$newspaper_number = intval($row['newspaper_number']);
					$published_date = ( $update == 2 ? $published_date : text_filter(3, $row['published_date']) );
					$active = intval($row['active']);
					$user_id = text_filter(3, $row['user_id']);
					$tweet_url = text_filter(3, $row['tweet_url']);
					$sound_url = text_filter(3, $row['sound_url']);
					$video_url = text_filter(3, $row['video_url']);
					$type = intval($row['type']);
					$original_image = intval($row['original_image']);

					$hide_title = intval($row['hide_title']);
					$hide_in_pdf = intval($row['hide_in_pdf']);
					$hide_description = intval($row['hide_description']);
					$hide_more = intval($row['hide_more']);

					$query_c = $this->DB->N_query("SELECT * FROM news_meta WHERE meta_key='category_id' AND news_id=".$row['id']."");
				  if($this->DB->N_num_rows($query_c) > 0){
				    while($rowc = $this->DB->N_fetch_array($query_c)){
					    $categoryID = intval($rowc['meta_value']);
							$post_categories[] = $categoryID;
						}
				  }

					$this->main_title = $title;

					if( $update == 2 ){
						$form_action = 'index.php?action=news_insert';
						//$input_hidden = '<input type="hidden" name="id" value="'.$id.'">';
						$input_hidden .= '<input type="hidden" name="token" value="'.generate_form_token('news_add').'">';
						$submit_name = lang('add_duplicate');
					}else{
						$form_action = 'index.php?action=news_update';
						$input_hidden = '<input type="hidden" name="id" value="'.$id.'">';
						$input_hidden .= '<input type="hidden" name="token" value="'.generate_form_token('news_update_'.$id).'">';
						$submit_name = lang('update');
					}
				}
			}
		}

		$info = array(
			'title' => $title,
			'image' => $image,
			'url' => $url,
			'description' => $description,
			'text' => $text,
			'newspaper_id' => $newspaper_id,
			'newspaper_number' => $newspaper_number,
			'published_date' => $published_date,
			'active' => $active,
			'user_id' => $user_id,
			'tweet_url' => $tweet_url,
			'sound_url' => $sound_url,
			'video_url' => $video_url,
			'type' => $type,
			'original_image' => $original_image,
			'hide_title' => $hide_title,
			'hide_in_pdf' => $hide_in_pdf,
			'hide_description' => $hide_description,
			'hide_more' => $hide_more,
			'post_categories' => $post_categories,
			'submit_name' => $submit_name,
			'data_type' => 1
		);

		$input_list = $this->news_input( $info );

		$form_news = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
		$form_news .= $input_hidden;
		$form_news .= '<input type="hidden" name="type" value="1">';
		$form_news .= $input_list['title'];
		$form_news .= $input_list['image'];
		$form_news .= $input_list['url'];
		$form_news .= $input_list['newspaper_id'];
		$form_news .= $input_list['newspaper_number'];
		$form_news .= $input_list['published_date'];
		$form_news .= $input_list['description'];
		$form_news .= $input_list['text'];
		$form_news .= $input_list['categories'];
		$form_news .= $input_list['hide_title'];
		$form_news .= $input_list['hide_description'];
		$form_news .= $input_list['hide_more'];
		$form_news .= $input_list['hide_in_pdf'];
		/*
		$form_news .= $input_list['tweet_url'];
		$form_news .= $input_list['sound_url'];
		$form_news .= $input_list['video_url'];
		*/
		$form_news .= $input_list['submit'];
		$form_news .= '</form>';

		unset($info['data_type']);
		$info['data_type'] = 2;
		$input_list2 = $this->news_input( $info );

		$form_sound = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
		$form_sound .= $input_hidden;
		$form_sound .= '<input type="hidden" name="type" value="2">';
		$form_sound .= $input_list2['title'];
		$form_sound .= $input_list2['description'];
		$form_sound .= $input_list2['sound_url'];
		$form_sound .= $input_list2['image'];
		$form_sound .= $input_list2['text'];
		$form_sound .= $input_list2['published_date'];
		$form_sound .= $input_list2['categories'];
		$form_sound .= $input_list2['submit'];
		$form_sound .= '</form>';

		unset($info['data_type']);
		$info['data_type'] = 3;
		$input_list3 = $this->news_input( $info );

		$form_video = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
		$form_video .= $input_hidden;
		$form_video .= '<input type="hidden" name="type" value="3">';
		$form_video .= $input_list3['title'];
		$form_video .= $input_list3['description'];
		$form_video .= $input_list3['video_url'];
		$form_video .= $input_list3['image'];
		$form_video .= $input_list3['text'];
		$form_video .= $input_list3['categories'];
		$form_video .= $input_list3['published_date'];
		$form_video .= $input_list3['submit'];
		$form_video .= '</form>';

		unset($info['data_type']);
		$info['data_type'] = 4;
		$input_list4 = $this->news_input( $info );

		$form_tweet = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
		$form_tweet .= $input_hidden;
		$form_tweet .= '<input type="hidden" name="type" value="4">';
		$form_tweet .= $input_list4['title'];
		$form_tweet .= $input_list4['description2'];
		$form_tweet .= $input_list4['tweet_url'];
		$form_tweet .= $input_list4['image'];
		$form_tweet .= $input_list4['categories'];
		$form_tweet .= $input_list4['published_date'];
		$form_tweet .= $input_list4['hide_title'];
		$form_tweet .= $input_list4['hide_description'];
		$form_tweet .= $input_list4['hide_more'];
		$form_tweet .= $input_list4['hide_in_pdf'];
		$form_tweet .= $input_list4['submit'];
		$form_tweet .= '</form>';

		unset($info['data_type']);
		$info['data_type'] = 5;
		$input_list5 = $this->news_input( $info );

		$form_project = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
		$form_project .= $input_hidden;
		$form_project .= '<input type="hidden" name="type" value="5">';
		$form_project .= $input_list5['title'];
		$form_project .= $input_list5['description'];
		$form_project .= $input_list5['url2'];
		$form_project .= $input_list5['image'];
		$form_project .= $input_list5['categories'];
		$form_project .= $input_list5['published_date'];
		$form_project .= $input_list5['hide_title'];
		$form_project .= $input_list5['hide_description'];
		$form_project .= $input_list5['hide_more'];
		$form_project .= $input_list5['hide_in_pdf'];
		$form_project .= $input_list5['submit'];
		$form_project .= '</form>';

		$tabs_list = '<ul class="nav nav-tabs justify-content-center pr-0" id="newsTab" role="tablist">';
		$tabs_list .= '<li class="nav-item news-item"><a class="nav-link active" id="news-tab" data-toggle="tab" href="#news" role="tab" aria-controls="news" aria-selected="true"><i class="far fa-newspaper"></i> '.lang('news_text').'</a></li>';
		$tabs_list .= '<li class="nav-item news-item"><a class="nav-link" id="sound-tab" data-toggle="tab" href="#sound" role="tab" aria-controls="sound" aria-selected="false"><i class="fas fa-microphone"></i> '.lang('news_sound').'</a></li>';
		$tabs_list .= '<li class="nav-item news-item"><a class="nav-link" id="video-tab" data-toggle="tab" href="#video" role="tab" aria-controls="video" aria-selected="false"><i class="fas fa-video"></i> '.lang('news_video').'</a></li>';
		$tabs_list .= '<li class="nav-item news-item"><a class="nav-link" id="tweet-tab" data-toggle="tab" href="#tweet" role="tab" aria-controls="tweet" aria-selected="false"><i class="fas fa-retweet"></i> '.lang('news_tweet').'</a></li>';
		$tabs_list .= '<li class="nav-item news-item"><a class="nav-link" id="project-tab" data-toggle="tab" href="#project" role="tab" aria-controls="project" aria-selected="false"><i class="fas fa-umbrella"></i> '.lang('news_project').'</a></li>';
		$tabs_list .= '</ul>';

		$tabs_content = '<div class="tab-content p-3 bg-white" id="newsTabContent">';
		$tabs_content .= '<div class="tab-pane fade show active" id="news" role="tabpanel" aria-labelledby="news-tab">'.$form_news.'</div>';
		$tabs_content .= '<div class="tab-pane fade" id="sound" role="tabpanel" aria-labelledby="sound-tab">'.$form_sound.'</div>';
		$tabs_content .= '<div class="tab-pane fade" id="video" role="tabpanel" aria-labelledby="video-tab">'.$form_video.'</div>';
		$tabs_content .= '<div class="tab-pane fade" id="tweet" role="tabpanel" aria-labelledby="tweet-tab">'.$form_tweet.'</div>';
		$tabs_content .= '<div class="tab-pane fade" id="project" role="tabpanel" aria-labelledby="project-tab">'.$form_project.'</div>';
		$tabs_content .= '</div>';

		if( $update == 1 || $update == 2 ){
			if( $type == 2 ){
				$form = '<h3 class="mb-3"><i class="fas fa-microphone"></i> '.lang('news_sound').'</h3>';
				$form .= $form_sound;
			}elseif( $type == 3 ){
				$form = '<h3 class="mb-3"><i class="fas fa-video"></i> '.lang('news_video').'</h3>';
				$form .= $form_video;
			}elseif( $type == 4 ){
				$form = '<h3 class="mb-3"><i class="fas fa-retweet"></i> '.lang('news_tweet').'</h3>';
				$form .= $form_tweet;
			}elseif( $type == 5 ){
				$form = '<h3 class="mb-3"><i class="fas fa-umbrella"></i> '.lang('news_project').'</h3>';
				$form .= $form_project;
			}else{
				$form = '<h3 class="mb-3"><i class="fas fa-newspaper"></i> '.lang('news_text').'</h3>';
				$form .= $form_news;
			}
		}else{
			$form = $tabs_list.$tabs_content;
		}

		$this->breadcrumb_parent = array( array('title' => lang('news'), 'url' => 'index.php?action=news_data') );

		if($allow_form == 1){
			return $form;
		}else{
			return $code;
		}
	}

	function news_edit(){
		return $this->news_add(1);
	}

	function publications_create( $info ){
		if( is_array($info) ){
			$publication_date = ( isset($info['publication_date']) ? $this->DB->N_escape_string($info['publication_date']) : '' );
	    $title = ( isset($info['title']) ? $this->DB->N_escape_string($info['title']) : $publication_date );
			$description = ( isset($info['description']) ? $this->DB->N_escape_string($info['description']) : '' );
	    $url = ( isset($info['url']) ? $this->DB->N_escape_string($info['url']) : '' );
			$image = ( isset($info['image']) ? $this->DB->N_escape_string($info['image']) : '' );
	    $text = ( isset($info['text']) ? $this->DB->N_escape_string($info['text']) : '' );
			$cover = ( isset($info['cover']) ? $this->DB->N_escape_string($info['cover']) : 0 );
			$active = ( isset($info['active']) ? $this->DB->N_escape_string($info['active']) : 0 );
			$user_id = ( isset($info['user_id']) ? $this->DB->N_escape_string($info['user_id']) : 0 );
			$date = ( isset($info['date']) ? $this->DB->N_escape_string($info['date']) : time() );

			if( $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM publications WHERE publication_date='".$publication_date."'") ) == 0 && !empty($publication_date) ){
				//$query = $this->DB->N_query("INSERT INTO publications (`title`, `user_id`, `date`, `active`, `publication_date`) VALUES ('".$title."', '".$user_id."', '".$date."', '".$active."', '".$publication_date."' )");
				$query = $this->DB->N_query("INSERT INTO publications (`title`, `description`, `url`, `image`, `text`, `user_id`, `date`, `active`, `cover`, `publication_date`, `update_user_id`) VALUES ('".$title."', '".$description."', '".$url."', '".$image."', '".$text."', '".$user_id."', '".$date."', '".$active."', '".$cover."', '".$publication_date."', 0 )");
	      if( $query ){
	        $insert_id = ( $this->DB->N_insert_id() == 0 ? $this->DB->last_N_insert_id('publications', 'id') : $this->DB->N_insert_id() );
	        $code = '<div class="alert alert-success" role="alert">'.lang('publications_created').'</div>';
	      }else{
	        $code = '<div class="alert alert-danger" role="alert">'.lang('not_added').'</div>';
	      }
			}else{
				$code = '';
			}

    }

	  return $code;
	}

	function news_insert(){
		$code = '';
		if( verify_form_token('news_add') == false ){
			$code .= '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST) ){
				$title = ( isset($_POST['title']) ? $this->DB->N_escape_string($_POST['title']) : '' );
				$image = ( isset($_POST['image']) ? $this->DB->N_escape_string($_POST['image']) : '' );
				$url = ( isset($_POST['url']) ? $this->DB->N_escape_string($_POST['url']) : '' );
				$description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
				$text = ( isset($_POST['text']) ? $this->DB->N_escape_string($_POST['text']) : '' );
				//$newspaper_id = ( isset($_POST['newspaper_id']) ? $this->DB->N_escape_string($_POST['newspaper_id']) : 0 );
				$newspaper_number = ( isset($_POST['newspaper_number']) ? $this->DB->N_escape_string($_POST['newspaper_number']) : 0 );
				$published_date = ( isset($_POST['published_date']) ? $this->DB->N_escape_string($_POST['published_date']) : '' );
				$tweet_url = ( isset($_POST['tweet_url']) ? $this->DB->N_escape_string($_POST['tweet_url']) : '' );
				$sound_url = ( isset($_POST['sound_url']) ? $this->DB->N_escape_string($_POST['sound_url']) : '' );
				$video_url = ( isset($_POST['video_url']) ? $this->DB->N_escape_string($_POST['video_url']) : '' );
				$type = ( isset($_POST['type']) ? $this->DB->N_escape_string($_POST['type']) : 0 );
				$original_image = ( isset($_POST['original_image']) ? $this->DB->N_escape_string($_POST['original_image']) : 0 );

				$hide_title = ( isset($_POST['hide_title']) ? $this->DB->N_escape_string($_POST['hide_title']) : 0 );
				$hide_description = ( isset($_POST['hide_description']) ? $this->DB->N_escape_string($_POST['hide_description']) : 0 );
				$hide_more = ( isset($_POST['hide_more']) ? $this->DB->N_escape_string($_POST['hide_more']) : 0 );
				$hide_in_pdf = ( isset($_POST['hide_in_pdf']) ? $this->DB->N_escape_string($_POST['hide_in_pdf']) : 0 );

				$newspaper_id_0 = ( isset($_POST['newspaper_id_0']) ? $this->DB->N_escape_string($_POST['newspaper_id_0']) : 0 );
				$newspaper_id_1 = ( isset($_POST['newspaper_id_1']) ? $this->DB->N_escape_string($_POST['newspaper_id_1']) : 0 );

				$newspaper_id = ( $newspaper_id_1 == 0 ? $newspaper_id_0 : $newspaper_id_1 );

				$active = 1;

				if( isset($_SESSION['user_id']) ){
					$user_id = intval($_SESSION['user_id']);
				}else{
					$user_id = 0;
				}
				$date = time();

				$err = array();

				if( empty($title) ){
					$err[] = '<p class="mb-0">'.lang('validate_title').'</p>';
				}

				if( count($err) > 0 ){
					foreach ($err as $key => $value) {
						$code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
					}
				}else{
					$upload = upload_files('upload_image', 'news');
					$get_image = ( empty($upload) ? $image : $upload );

					$query = $this->DB->N_query("INSERT INTO news (`title`, `image`, `url`, `newspaper_id`, `description`, `text`, `user_id`, `date`, `active`, `newspaper_number`, `published_date`, `tweet_url`, `sound_url`, `video_url`, `type`, `visit`, `original_image`, `hide_title`, `hide_in_pdf`, `hide_description`, `hide_more`) VALUES ('".$title."', '".$get_image."', '".$url."', '".$newspaper_id."', '".$description."', '".$text."', '".$user_id."', '".$date."', '".$active."', '".$newspaper_number."', '".$published_date."', '".$tweet_url."', '".$sound_url."', '".$video_url."', ".$type.", '0', '".$original_image."', '".$hide_title."', '".$hide_in_pdf."', '".$hide_description."', '".$hide_more."' )");
					if( $query ){
						$insert_id = ( $this->DB->N_insert_id() == 0 ? $this->DB->last_N_insert_id('news', 'id') : $this->DB->N_insert_id() );

						if( isset($_POST['category_id']) && count($_POST['category_id']) > 0 ){
							foreach ($_POST['category_id'] as $nkey => $nvalue) {
								$this->DB->N_query("INSERT INTO news_meta (`meta_key`, `meta_value`, `news_id`) VALUES ('category_id', '".$nvalue."', '".$insert_id."' )");
							}
						}

						$code .= $this->publications_create( array( 'publication_date' => $published_date, 'user_id' => $user_id, 'date' => $date, 'active' => 1 ) );

						$code .= '<div class="alert alert-success" role="alert">'.lang('added').'</div>';
						//$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=news_edit&id='.$insert_id.'" />';
						$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=news_add" />';
					}else{
						$code .= '<div class="alert alert-danger" role="alert">'.lang('not_added').'</div>';
					}
				}
			}else{
				$code .= '<div class="alert alert-danger" role="alert">'.lang('empty_value').'</div>';
			}
		}

		return $code;
	}

	function news_update(){
		$post_id = ( isset($_POST['id']) ? intval($_POST['id']) : 0 );
		if( verify_form_token('news_update_'.$post_id) == false ){
			$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST['id']) && intval($_POST['id']) != 0 ){
				$id = intval($_POST['id']);

				if( isset($_SESSION['user_id']) ){
					$user_id = intval($_SESSION['user_id']);
				}else{
					$user_id = 0;
				}

				$date = time();

				$title = ( isset($_POST['title']) ? $this->DB->N_escape_string($_POST['title']) : '' );
				$image = ( isset($_POST['image']) ? $this->DB->N_escape_string($_POST['image']) : '' );
				$url = ( isset($_POST['url']) ? $this->DB->N_escape_string($_POST['url']) : '' );
				$description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
				$text = ( isset($_POST['text']) ? $this->DB->N_escape_string($_POST['text']) : '' );
				$newspaper_id_0 = ( isset($_POST['newspaper_id_0']) ? $this->DB->N_escape_string($_POST['newspaper_id_0']) : 0 );
				$newspaper_id_1 = ( isset($_POST['newspaper_id_1']) ? $this->DB->N_escape_string($_POST['newspaper_id_1']) : 0 );
				$newspaper_id = ( $newspaper_id_1 == 0 ? $newspaper_id_0 : $newspaper_id_1 );
				//$newspaper_id = ( isset($_POST['newspaper_id']) ? $this->DB->N_escape_string($_POST['newspaper_id']) : 0 );
				$newspaper_number = ( isset($_POST['newspaper_number']) ? $this->DB->N_escape_string($_POST['newspaper_number']) : 0 );
				$published_date = ( isset($_POST['published_date']) ? $this->DB->N_escape_string($_POST['published_date']) : '' );
				$tweet_url = ( isset($_POST['tweet_url']) ? $this->DB->N_escape_string($_POST['tweet_url']) : '' );
				$sound_url = ( isset($_POST['sound_url']) ? $this->DB->N_escape_string($_POST['sound_url']) : '' );
				$video_url = ( isset($_POST['video_url']) ? $this->DB->N_escape_string($_POST['video_url']) : '' );
				$active = 1;

				$original_image = ( isset($_POST['original_image']) ? $this->DB->N_escape_string($_POST['original_image']) : 0 );
				$hide_title = ( isset($_POST['hide_title']) ? $this->DB->N_escape_string($_POST['hide_title']) : 0 );
				$hide_description = ( isset($_POST['hide_description']) ? $this->DB->N_escape_string($_POST['hide_description']) : 0 );
				$hide_more = ( isset($_POST['hide_more']) ? $this->DB->N_escape_string($_POST['hide_more']) : 0 );
				$hide_in_pdf = ( isset($_POST['hide_in_pdf']) ? $this->DB->N_escape_string($_POST['hide_in_pdf']) : 0 );

				$err = array();

				if( empty($title) ){
					$err[] = '<p class="mb-0">'.lang('validate_title').'</p>';
				}

				$code = '';

				if( count($err) > 0 ){
					foreach ($err as $key => $value) {
						$code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
					}
				}else{
					$upload = upload_files('upload_image', 'news');
					$get_image = ( empty($upload) ? $image : $upload );

					$query = $this->DB->N_query("UPDATE news SET title='".$title."', image='".$get_image."', url='".$url."', description='".$description."', text='".$text."', newspaper_id='".$newspaper_id."', newspaper_number='".$newspaper_number."', published_date='".$published_date."', update_user_id='".$user_id."', update_date='".$date."', tweet_url='".$tweet_url."', sound_url='".$sound_url."', video_url='".$video_url."', original_image='".$original_image."', hide_in_pdf='".$hide_in_pdf."', hide_title='".$hide_title."', hide_description='".$hide_description."', hide_more='".$hide_more."' WHERE id='".$id."' LIMIT 1");
					if( $query ){

						if( isset($_POST['category_id']) && count($_POST['category_id']) > 0 ){
							$queryc = $this->DB->N_query("SELECT * FROM news_meta WHERE news_id='".$id."' AND meta_key='category_id'");
							$countsc = $this->DB->N_num_rows($queryc);
							if($countsc > 0){
								while( $rowc = $this->DB->N_fetch_array($queryc) ){
									$meta_value = $rowc['meta_value'];
									if( !in_array( $meta_value, $_POST['category_id'] ) ){
										$this->DB->N_query("DELETE FROM news_meta WHERE meta_value='".$meta_value."' AND meta_key='category_id' AND news_id='".$id."'");
									}
								}
							}

							foreach ($_POST['category_id'] as $nkey => $nvalue) {
								$countsx = $this->DB->N_num_rows( $this->DB->N_query("SELECT * FROM news_meta WHERE meta_key='category_id' AND meta_value='".$nvalue."' AND news_id='".$id."'") );
								if( $countsx == 0 ){
									$this->DB->N_query("INSERT INTO news_meta (`meta_key`, `meta_value`, `news_id`) VALUES ('category_id', '".$nvalue."', '".$id."' )");
								}
							}
						}else{
							$this->DB->N_query("DELETE FROM news_meta WHERE meta_key='category_id' AND news_id='".$id."'");
						}

						$code .= $this->publications_create( array( 'publication_date' => $published_date, 'user_id' => $user_id, 'date' => $date, 'active' => 1 ) );

						$code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
						$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=news_data" />';
					}else{
						$code = $this->get_alert('Error', 'danger');
					}
				}
			}else{
				$code = $this->get_alert(lang('empty_id'), 'danger');
			}
		}

		return $code;
	}

	function news_status(){
		if( isset($_GET['id']) && intval($_GET['id']) != 0 ){
			$id = intval($_GET['id']);
			$status = ( isset($_GET['act']) ? intval($_GET['act']) : 0 );
			$query = $this->DB->N_query("UPDATE news SET active='".$status."' WHERE id='".$id."' LIMIT 1");
			if( $query ){
				$code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
				$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=news_data" />';
			}else{
				$code = $this->get_alert(lang('error'), 'danger');
			}
		}else{
			$code = $this->get_alert(lang('empty_id'), 'danger');
		}

		return $code;
	}

	function news_delete(){
		if(isset($_GET['id']) && intval($_GET['id']) != 0){
			$id = intval($_GET['id']);

			$query = $this->DB->N_query("SELECT * FROM news WHERE id='".$id."' LIMIT 1");
			$counts = $this->DB->N_num_rows($query);
			if($counts == 0){
				$code = $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger');
			}else{
				$row = $this->DB->N_fetch_array($query);
				$title = text_filter(3, $row['title']);
				$this->main_title = $title;

				$this->breadcrumb_parent = array( array('title' => lang('news'), 'url' => 'index.php?action=news_data') );

				$code = '<form name="delete" method="post" action="index.php?action=news_delete">';
				$code .= '<input type="hidden" name="post_id" value="'.$id.'" />';
				$code .= '<input type="hidden" name="delete_post" value="yes" />';
				$code .= '<input type="hidden" name="token" value="'.generate_form_token('news_delete_'.$id).'">';
				$code .= '<input type="submit" value="'.sprintf(lang('delete_sure'), $title).'" name="delete" />';
				$code .= '</form>';
			}
		}else{
			$post_id = ( isset($_POST['post_id']) ? intval($_POST['post_id']) : 0 );
			if( verify_form_token('news_delete_'.$post_id) == false ){
				$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
			}else{
				if(isset($_POST['delete_post']) && $_POST['delete_post'] == "yes" && isset($_POST['post_id']) && intval($_POST['post_id']) != 0 ){
					$post_id = intval($_POST['post_id']);
					$query =  $this->DB->N_query("DELETE FROM news WHERE id='".$post_id."' LIMIT 1");
					if( $query ){
						$this->DB->N_query("DELETE FROM news_meta WHERE meta_key='category_id' AND news_id='".$post_id."'");
						$code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
						$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=news_data" />';
					}else{
						$code = $this->get_alert(lang('error'), 'danger');
					}
				}else{
					$code = $this->get_alert(lang('empty_id'), 'danger');
				}
			}
		}

		return $code;
	}

	function news_data($limit = 30, $home = 0){
		$counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM news") );

		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$page = ($page == 0 ? 1 : $page);
		$perpage = $limit;
		$startpoint = ($page * $perpage) - $perpage;

		$query_d = $this->DB->N_query("SELECT * FROM news ORDER BY id DESC LIMIT $startpoint,$perpage");
		$data_count = $this->DB->N_num_rows($query_d);

		$text = '';
		$data = array();

		if($data_count == 0){
			$text .= lang('not_found');
		}else{
			$text .= '<table class="table table-hover">';
			$text .= '<thead>';
			$text .= '<tr>';
			$text .= '<th scope="col" class="text-center">'.lang('image').'</th>';
			$text .= '<th scope="col">'.lang('title').'</th>';
			//$text .= '<th scope="col" class="text-center">'.lang('description').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('newspaper').'</th>';
			//$text .= '<th scope="col" class="text-center">'.lang('visits').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('date').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('setting_box_category').'</th>';
			//$text .= '<th scope="col" class="text-center">'.lang('status').'</th>';
			//$text .= '<th scope="col" class="text-center">'.lang('edit').'</th>';
			//$text .= '<th scope="col" class="text-center">'.lang('delete').'</th>';
			$text .= '</tr>';
			$text .= '</thead>';
			$text .= '<tbody>';
			$i=0;
			$arr_money = array();
			while ($row = $this->DB->N_fetch_array($query_d)){
				$title = text_filter(3, $row['title']);
				$image = text_filter(3, $row['image']);
				$url = text_filter(3, $row['url']);
				$description = text_filter(3, $row['description']);
				$notice = text_filter(3, $row['text']);
				$active = intval($row['active']);
				$user_id = intval($row['user_id']);
				$newspaper_number = intval($row['newspaper_number']);
				$newspaper_id = intval($row['newspaper_id']);
				$published_date = text_filter(3, $row['published_date']);
				$visit = intval($row['visit']);
				$visit_pdf = intval($row['visit_pdf']);

				$get_large_image = get_image($image, 'large', 1);
				$get_xsmall_image = get_image($image, 'xsmall', 1);
				$get_small_image = get_image($image, 'small', 1);
				$get_medium_image = get_image($image, 'medium', 1);

				$view_image = ( empty($image) ? '- - -' : '<img alt="'.$title.'" src="'.$get_xsmall_image.'" class="w-100" style="max-width: 100px;">' );

				$query_newspaper = $this->DB->N_query("SELECT id,name FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
				$counts_newspaper = $this->DB->N_num_rows($query_newspaper);
				if($counts_newspaper == 0){
					$get_newspaper = '- - -';
				}else{
					$row_newspaper = $this->DB->N_fetch_array($query_newspaper);
					$newspaper_name = text_filter(3, $row_newspaper['name']);
					$get_newspaper = '<a href="index.php?action=news_newspaper&newspaper_id='.$newspaper_id.'">'.$newspaper_name.'</a>';
				}

				$added = date("j/n/Y", $row['date']);

				$get_image = ( empty($image) ? '' : '<img src="'.$image.'" alt="'.$title.'" class="w-100"> ' );

				if( $active == 1 ){
					$get_active = '<a href="index.php?action=news_status&id='.$row['id'].'&act=0"><i class="fas fa-eye text-success"></i></a>';
				}else{
					$get_active = '<a href="index.php?action=news_status&id='.$row['id'].'&act=1"><i class="fas fa-eye-slash text-danger"></i></a>';
				}

				if( empty($get_image) ){
					$modal = '- - -';
				}else{
					$modal = '<span data-toggle="modal" data-target=".newspaper-modal-'.$row['id'].'">'.$view_image.'</span>';
					$modal .= '<div class="modal fade newspaper-modal-'.$row['id'].'" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">';
					$modal .= '<div class="modal-dialog modal-'.$row['id'].'">';
					$modal .= '<div class="modal-content">'.$get_image.'</div>';
					$modal .= '</div>';
					$modal .= '</div>';
				}

				$shortcode_text = '<pre><kbd>[news]'.$row['id'].'[/news]</kbd></pre>';

				$query_c = $this->DB->N_query("SELECT * FROM news_meta WHERE meta_key='category_id' AND news_id=".$row['id']."");
				$post_categories = '';
				if($this->DB->N_num_rows($query_c) > 0){
					while($rowc = $this->DB->N_fetch_array($query_c)){
						$categoryID = intval($rowc['meta_value']);
						$query_cats = $this->DB->N_query("SELECT id,title FROM category WHERE id='".$categoryID."' LIMIT 1");
						$counts_cats = $this->DB->N_num_rows($query_cats);
						if($counts_cats > 0){
							$row_cats = $this->DB->N_fetch_array($query_cats);
							$category_title = text_filter(3, $row_cats['title']);
							$post_categories .= '<a href="index.php?action=news_category&category_id='.$categoryID.'">'.$category_title.'</a>, ';
						}
					}
				}

				$list_inline = '<ul class="list-inline">';
				$list_inline .= '<li class="list-inline-item"><a title="'.lang('duplicate').'" href="index.php?action=news_duplicate&id='.$row['id'].'"><i class="fas fa-copy"></i></a></li>';
				$list_inline .= '<li class="list-inline-item">'.$get_active.'</li>';
				$list_inline .= '<li class="list-inline-item"><a href="index.php?action=news_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a></li>';
				$list_inline .= '<li class="list-inline-item"><a href="index.php?action=news_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a></li>';
				$list_inline .= '<li title="'.lang('pdf_visits').' '.$visit_pdf.'" class="list-inline-item"><i class="fas fa-eye"></i> '.$visit.'</li>';
				$list_inline .= '</ul>';

				++$i;

				$text .= '<tr>';
				$text .= '<td class="text-center" style="width: 10%">'.$modal.'</td>';
				$text .= '<td style="width: 40%">';
				$text .= '<p><a href="index.php?action=news_edit&id='.$row['id'].'">'.$title.'</a></p>';
				if( !empty($description) ){
					$text .= '<p>'.$description.'</p>';
				}
				$text .= $list_inline;
				$text .= '</td>';
				//$text .= '<td>'.$description.'</td>';
				$text .= '<td class="text-center" style="width: 20%">'.$get_newspaper.'</td>';
				//$text .= '<td class="text-center" title="'.lang('pdf_visits').' '.$visit_pdf.'" style="width: 10%">'.$visit.'</td>';
				$text .= '<td class="text-center" style="width: 10%">'.$published_date.'</td>';
				$text .= '<td class="text-center" style="width: 20%">'.rtrim($post_categories, ', ').'</td>';
				//$text .= '<td class="text-center">'.$get_active.'</td>';
				//$text .= '<td class="text-center"><a href="index.php?action=news_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a></td>';
				//$text .= '<td class="text-center"><a href="index.php?action=news_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a></td>';
				$text .= '</tr>';

				$data[] = array(
					'id' => $row['id'],
					'title' => $title,
					'image' => $get_image,
					'url' => $url,
					'description' => $description,
					'notice' => $notice,
					'user_id' => $user_id,
					'newspaper_number' => $newspaper_number,
					'newspaper_id' => $newspaper_id,
					'newspaper_name' => $get_newspaper,
					'published_date' => $added,
					'active' => $get_active,
					'edit' => '<a href="index.php?action=news_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a>',
					'delete' => '<a href="index.php?action=news_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a>',
					'shortcode' => $shortcode_text
					);
			}
			$text .= '</tbody>';
			$text .= '</table>';
			if( $home == 1 ){
				$text .= '<div class="mt-3"><a href="index.php?action=news_add">More</a></div>';
			}else{
				$text .= pagination($counts, $perpage, $page, 'index.php?action=news_data&');
			}

		}

		if( $home == 1 ){
			return $data;
		}else{
			return $text;
		}
	}

	function news_data_by_category($limit = 30){
		$category_id = ( isset($_GET['category_id']) ? intval($_GET['category_id']) : 0 );
		$query_cats = $this->DB->N_query("SELECT id,title FROM category WHERE id='".$category_id."' LIMIT 1");
		$counts_cats = $this->DB->N_num_rows($query_cats);
		if($counts_cats == 0){
			$post_categories = '';
		}else{
			$row_cats = $this->DB->N_fetch_array($query_cats);
			$category_title = text_filter(3, $row_cats['title']);
			$post_categories = $category_title;
			//$this->main_title = $post_categories;
			$this->breadcrumb_parent = array( array('title' => lang('categories'), 'url' => 'index.php?action=category_data') );
		}

		$this->page_title = ( empty($post_categories) ? lang('news') : $post_categories );

		$counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM news_meta WHERE meta_key='category_id' AND meta_value=".$category_id."") );
		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$page = ($page == 0 ? 1 : $page);
		$perpage = $limit;
		$startpoint = ($page * $perpage) - $perpage;

		$i=0;

		$query_c = $this->DB->N_query("SELECT * FROM news_meta WHERE meta_key='category_id' AND meta_value=".$category_id." ORDER BY news_id DESC LIMIT $startpoint,$perpage");
		$td = '';
		if($this->DB->N_num_rows($query_c) > 0){
			while($rowc = $this->DB->N_fetch_array($query_c)){
				$news_id = intval($rowc['news_id']);
				$query_d = $this->DB->N_query("SELECT * FROM news WHERE id='".$news_id."' LIMIT 1");
				$data_count = $this->DB->N_num_rows($query_d);
				if($data_count > 0){
					++$i;
					$row = $this->DB->N_fetch_array($query_d);
					$title = text_filter(3, $row['title']);
					$image = text_filter(3, $row['image']);
					$url = text_filter(3, $row['url']);
					$description = text_filter(3, $row['description']);
					$notice = text_filter(3, $row['text']);
					$active = intval($row['active']);
					$user_id = intval($row['user_id']);
					$newspaper_number = intval($row['newspaper_number']);
					$newspaper_id = intval($row['newspaper_id']);
					$published_date = text_filter(3, $row['published_date']);
					$visit = intval($row['visit']);
					$visit_pdf = intval($row['visit_pdf']);

					$get_large_image = get_image($image, 'large', 1);
					$get_xsmall_image = get_image($image, 'xsmall', 1);
					$get_small_image = get_image($image, 'small', 1);
					$get_medium_image = get_image($image, 'medium', 1);

					$view_image = ( empty($image) ? '- - -' : '<img alt="'.$title.'" src="'.$get_xsmall_image.'" class="w-100" style="max-width: 100px;">' );

					$query_newspaper = $this->DB->N_query("SELECT id,name FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
					$counts_newspaper = $this->DB->N_num_rows($query_newspaper);
					if($counts_newspaper == 0){
						$get_newspaper = '- - -';
					}else{
						$row_newspaper = $this->DB->N_fetch_array($query_newspaper);
						$newspaper_name = text_filter(3, $row_newspaper['name']);
						$get_newspaper = $newspaper_name;
					}

					$added = date("j/n/Y", $row['date']);

					$get_image = ( empty($image) ? '' : '<img src="'.$image.'" alt="'.$title.'" class="w-100"> ' );

					if( $active == 1 ){
						$get_active = '<a href="index.php?action=news_status&id='.$row['id'].'&act=0"><i class="fas fa-eye text-success"></i></a>';
					}else{
						$get_active = '<a href="index.php?action=news_status&id='.$row['id'].'&act=1"><i class="fas fa-eye-slash text-danger"></i></a>';
					}

					if( empty($get_image) ){
						$modal = '- - -';
					}else{
						$modal = '<span data-toggle="modal" data-target=".newspaper-modal-'.$row['id'].'-modal-xl">'.$view_image.'</span>';
						$modal .= '<div class="modal fade newspaper-modal-'.$row['id'].'-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">';
						$modal .= '<div class="modal-dialog modal-'.$row['id'].' modal-xl">';
						$modal .= '<div class="modal-content">'.$get_image.'</div>';
						$modal .= '</div>';
						$modal .= '</div>';
					}

					$list_inline = '<ul class="list-inline">';
					$list_inline .= '<li class="list-inline-item">'.$get_active.'</li>';
					$list_inline .= '<li class="list-inline-item"><a href="index.php?action=news_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a></li>';
					$list_inline .= '<li class="list-inline-item"><a href="index.php?action=news_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a></li>';
					$list_inline .= '<li title="'.lang('pdf_visits').' '.$visit_pdf.'" class="list-inline-item"><i class="fas fa-eye"></i> '.$visit.'</li>';
					$list_inline .= '</ul>';

					$td .= '<tr>';
					$td .= '<td class="text-center" style="width: 10%">'.$modal.'</td>';
					$td .= '<td style="width: 60%">';
					$td .= '<p><a href="index.php?action=news_edit&id='.$row['id'].'">'.$title.'</a></p>';
					if( !empty($description) ){
						$td .= '<p>'.$description.'</p>';
					}
					$td .= $list_inline;
					$td .= '</td>';
					$td .= '<td class="text-center" style="width: 20%">'.$get_newspaper.'</td>';
					$td .= '<td class="text-center" style="width: 10%">'.$published_date.'</td>';
					$td .= '</tr>';
				}

			}

			if( empty($td) ){
				$text = lang('not_found');
			}else{
				$text = '<table class="table table-hover">';
				$text .= '<thead>';
				$text .= '<tr>';
				$text .= '<th scope="col" class="text-center">'.lang('image').'</th>';
				$text .= '<th scope="col">'.lang('title').'</th>';
				$text .= '<th scope="col" class="text-center">'.lang('newspaper').'</th>';
				$text .= '<th scope="col" class="text-center">'.lang('date').'</th>';
				$text .= '</tr>';
				$text .= '</thead>';
				$text .= '<tbody>';
				$text .= $td;
				$text .= '</tbody>';
				$text .= '</table>';
				$text .= pagination($counts, $perpage, $page, 'index.php?action=news_category&category_id='.$category_id.'&');
			}
		}else{
			$text = lang('not_found');
		}

		return $text;
	}

	function news_data_by_newspaper( $limit = 20 ){
		$newspaper_id = ( isset($_GET['newspaper_id']) ? intval($_GET['newspaper_id']) : 0 );
		$query_newspaper = $this->DB->N_query("SELECT id,name FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
		$counts_newspaper = $this->DB->N_num_rows($query_newspaper);
		if($counts_newspaper == 0){
			$post_newspaper = '';
		}else{
			$row_newspaper = $this->DB->N_fetch_array($query_newspaper);
			$newspaper_title = text_filter(3, $row_newspaper['name']);
			$post_newspaper = $newspaper_title;
			$this->breadcrumb_parent = array( array('title' => lang('newspapers'), 'url' => 'index.php?action=newspaper_data') );
		}

		$this->page_title = ( empty($post_newspaper) ? lang('newspaper') : $post_newspaper );

		$counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM news WHERE newspaper_id='".$newspaper_id."'") );
		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$page = ($page == 0 ? 1 : $page);
		$perpage = $limit;
		$startpoint = ($page * $perpage) - $perpage;

		$td = '';

		$i=0;

		$query_d = $this->DB->N_query("SELECT * FROM news WHERE newspaper_id='".$newspaper_id."' ORDER BY id DESC LIMIT $startpoint,$perpage");
		$data_count = $this->DB->N_num_rows($query_d);
		if($data_count == 0){
			$text = lang('not_found');
		}else{
			++$i;
			while($row = $this->DB->N_fetch_array($query_d)){
				$title = text_filter(3, $row['title']);
				$image = text_filter(3, $row['image']);
				$url = text_filter(3, $row['url']);
				$description = text_filter(3, $row['description']);
				$notice = text_filter(3, $row['text']);
				$active = intval($row['active']);
				$user_id = intval($row['user_id']);
				$newspaper_number = intval($row['newspaper_number']);
				$newspaper_id = intval($row['newspaper_id']);
				$published_date = text_filter(3, $row['published_date']);
				$visit = intval($row['visit']);
				$visit_pdf = intval($row['visit_pdf']);

				$get_large_image = get_image($image, 'large', 1);
				$get_xsmall_image = get_image($image, 'xsmall', 1);
				$get_small_image = get_image($image, 'small', 1);
				$get_medium_image = get_image($image, 'medium', 1);

				$view_image = ( empty($image) ? '- - -' : '<img alt="'.$title.'" src="'.$get_xsmall_image.'" class="w-100" style="max-width: 100px;">' );

				$query_newspaper = $this->DB->N_query("SELECT id,name FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
				$counts_newspaper = $this->DB->N_num_rows($query_newspaper);
				if($counts_newspaper == 0){
					$get_newspaper = '- - -';
				}else{
					$row_newspaper = $this->DB->N_fetch_array($query_newspaper);
					$newspaper_name = text_filter(3, $row_newspaper['name']);
					$get_newspaper = $newspaper_name;
				}

				$added = date("j/n/Y", $row['date']);

				$get_image = ( empty($image) ? '' : '<img src="'.$image.'" alt="'.$title.'" class="w-100"> ' );

				if( $active == 1 ){
					$get_active = '<a href="index.php?action=news_status&id='.$row['id'].'&act=0"><i class="fas fa-eye text-success"></i></a>';
				}else{
					$get_active = '<a href="index.php?action=news_status&id='.$row['id'].'&act=1"><i class="fas fa-eye-slash text-danger"></i></a>';
				}

				$query_c = $this->DB->N_query("SELECT * FROM news_meta WHERE meta_key='category_id' AND news_id=".$row['id']."");
				$post_categories = '';
				if($this->DB->N_num_rows($query_c) > 0){
					while($rowc = $this->DB->N_fetch_array($query_c)){
						$categoryID = intval($rowc['meta_value']);
						$query_cats = $this->DB->N_query("SELECT id,title FROM category WHERE id='".$categoryID."' LIMIT 1");
						$counts_cats = $this->DB->N_num_rows($query_cats);
						if($counts_cats > 0){
							$row_cats = $this->DB->N_fetch_array($query_cats);
							$category_title = text_filter(3, $row_cats['title']);
							$post_categories .= '<a href="index.php?action=news_category&category_id='.$categoryID.'">'.$category_title.'</a>, ';
						}
					}
				}

				if( empty($get_image) ){
					$modal = '- - -';
				}else{
					$modal = '<span data-toggle="modal" data-target=".newspaper-modal-'.$row['id'].'">'.$view_image.'</span>';
					$modal .= '<div class="modal fade newspaper-modal-'.$row['id'].'" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">';
					$modal .= '<div class="modal-dialog modal-'.$row['id'].'">';
					$modal .= '<div class="modal-content">'.$get_image.'</div>';
					$modal .= '</div>';
					$modal .= '</div>';
				}

				$list_inline = '<ul class="list-inline">';
				$list_inline .= '<li class="list-inline-item">'.$get_active.'</li>';
				$list_inline .= '<li class="list-inline-item"><a href="index.php?action=news_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a></li>';
				$list_inline .= '<li class="list-inline-item"><a href="index.php?action=news_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a></li>';
				$list_inline .= '<li title="'.lang('pdf_visits').' '.$visit_pdf.'" class="list-inline-item"><i class="fas fa-eye"></i> '.$visit.'</li>';
				$list_inline .= '</ul>';

				$td .= '<tr>';
				$td .= '<td class="text-center" style="width: 10%">'.$modal.'</td>';
				$td .= '<td style="width: 60%">';
				$td .= '<p><a href="index.php?action=news_edit&id='.$row['id'].'">'.$title.'</a></p>';
				if( !empty($description) ){
					$td .= '<p>'.$description.'</p>';
				}
				$td .= $list_inline;
				$td .= '</td>';
				$td .= '<td class="text-center" style="width: 20%">'.rtrim($post_categories, ', ').'</td>';
				$td .= '<td class="text-center" style="width: 10%">'.$published_date.'</td>';
				$td .= '</tr>';
			}

			if( empty($td) ){
				$text = lang('not_found');
			}else{
				$text = '<table class="table table-hover">';
				$text .= '<thead>';
				$text .= '<tr>';
				$text .= '<th scope="col" class="text-center">'.lang('image').'</th>';
				$text .= '<th scope="col">'.lang('title').'</th>';
				$text .= '<th scope="col" class="text-center">'.lang('setting_box_category').'</th>';
				$text .= '<th scope="col" class="text-center">'.lang('date').'</th>';
				$text .= '</tr>';
				$text .= '</thead>';
				$text .= '<tbody>';
				$text .= $td;
				$text .= '</tbody>';
				$text .= '</table>';
				$text .= pagination($counts, $perpage, $page, 'index.php?action=news_newspaper&newspaper_id='.$newspaper_id.'&');
			}

		}

		return $text;
	}

	function news_order($limit = 50){
		$today_date = date("Y-m-d", time());
		if( isset($_GET['date']) ){
			$today_date = ( !empty(strip_tags($_GET['date'])) && preg_match("/(\d{4})-(\d{2})-(\d{2})$/", strip_tags($_GET['date']) ) ? strip_tags($_GET['date']) : $today_date );
			$query_date = " WHERE published_date='".$today_date."'";
			$var_date = "&date=".$today_date;

			$this->page_title = $today_date;
			$this->breadcrumb_parent = array( array('title' => lang('order'), 'url' => 'index.php?action=news_order') );

		}else{
			$this->page_title = lang('order');
			$query_date = '';
			$var_date = '';
		}


		$date_form = '<div class="bg-info text-center mb-3 p-3 text-white">';
		$date_form .= '<form name="add" method="GET" action="index.php">';
		$date_form .= '<input type="hidden" name="action" value="news_order">';
		$date_form .= '<div class="form-group">';
		$date_form .= '<h3><label for="date">'.lang('select_date').'</label></h3>';
		$date_form .= '<input type="date" class="form-control" id="date" name="date" value="'.$today_date.'">';
		$date_form .= '</div>';
		$date_form .= '<button type="submit" class="btn btn-primary">'.lang('update').'</button>';
		$date_form .= '</form>';
		$date_form .= '</div>';

		$counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM news".$query_date) );

		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$page = ($page == 0 ? 1 : $page);
		$perpage = $limit;
		$startpoint = ($page * $perpage) - $perpage;

		$query_d = $this->DB->N_query("SELECT * FROM news ".$query_date." ORDER BY id DESC LIMIT $startpoint,$perpage");
		$data_count = $this->DB->N_num_rows($query_d);

		$text = '';
		$data = array();

		if($data_count == 0){
			$text .= $date_form;
			$text .= lang('not_found');
		}else{
			$text .= $date_form;
			$text .= '<form method="post" action="index.php?action=news_order_update">';
			$i=0;
			$data_array = array();
			while ($row = $this->DB->N_fetch_array($query_d)){
				$id = intval($row['id']);
				$title = text_filter(3, $row['title']);
				$image = text_filter(3, $row['image']);
				$url = text_filter(3, $row['url']);
				$description = text_filter(3, $row['description']);
				$notice = text_filter(3, $row['text']);
				$active = intval($row['active']);
				$user_id = intval($row['user_id']);
				$newspaper_number = intval($row['newspaper_number']);
				$newspaper_id = intval($row['newspaper_id']);
				$published_date = text_filter(3, $row['published_date']);
				$orders = intval($row['orders']);
				$type = intval($row['type']);
				$added = date("j/n/Y", $row['date']);
				$visit = intval($row['visit']);
				$visit_pdf = intval($row['visit_pdf']);

				$query_newspaper = $this->DB->N_query("SELECT id,name FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
				$counts_newspaper = $this->DB->N_num_rows($query_newspaper);
				if($counts_newspaper == 0){
					$get_newspaper = '- - -';
				}else{
					$row_newspaper = $this->DB->N_fetch_array($query_newspaper);
					$newspaper_name = text_filter(3, $row_newspaper['name']);
					$get_newspaper = $newspaper_name;
				}

				$query_news_meta = $this->DB->N_query("SELECT * FROM news_meta WHERE news_id='".$id."' AND meta_key='category_id' LIMIT 1");
				$counts_news_meta = $this->DB->N_num_rows($query_news_meta);
				if($counts_news_meta == 0){
					$news_meta_value = 0;
				}else{
					$row_news_meta = $this->DB->N_fetch_array($query_news_meta);
					$news_meta_value = intval($row_news_meta['meta_value']);
				}

				++$i;

				$data_array[$news_meta_value][] = array(
					'id' => $id,
					'title' => $title,
					'order' => $orders,
					'image' => $image,
					'newspaper' => $get_newspaper,
					'published_date' => $published_date
				);

			}

			$tr_array = array();
			$query_categories = $this->DB->N_query("SELECT * FROM category");
			$counts_categories = $this->DB->N_num_rows($query_categories);
			if($counts_categories == 0){
				$news_meta_value = 0;
			}else{
				while( $row_categories = $this->DB->N_fetch_array($query_categories) ){
					$category_id = intval($row_categories['id']);
					$category_title = text_filter(3, $row_categories['title']);
					$category_data = ( isset($data_array[$category_id]) ? $data_array[$category_id] : '' );
					$tr_array[] = array( 'title' => $category_title, 'text' => $category_data );
				}
			}

			/*
			if( isset($data_array[1]) && count($data_array[1]) > 0 ){
				$tr_array[] = array( 'title' => '<i class="far fa-newspaper"></i> '.lang('news_text'), 'text' => $data_array[1] );
			}
			if( isset($data_array[2]) && count($data_array[2]) > 0 ){
				$tr_array[] = array( 'title' => '<i class="fas fa-microphone"></i> '.lang('news_sound'), 'text' => $data_array[2] );
			}
			if( isset($data_array[3]) && count($data_array[3]) > 0 ){
				$tr_array[] = array( 'title' => '<i class="fas fa-video"></i> '.lang('news_video'), 'text' => $data_array[3] );
			}
			if( isset($data_array[4]) && count($data_array[4]) > 0 ){
				$tr_array[] = array( 'title' => '<i class="fas fa-retweet"></i> '.lang('news_tweet'), 'text' => $data_array[4] );
			}
			if( isset($data_array[5]) && count($data_array[5]) > 0 ){
				$tr_array[] = array( 'title' => '<i class="fas fa-umbrella"></i> '.lang('news_project'), 'text' => $data_array[5] );
			}
			*/

			$text .= '<table class="table table-hover">';
			$text .= '<thead>';
			$text .= '<tr>';
			$text .= '<th scope="col" class="text-center">'.lang('image').'</th>';
			$text .= '<th scope="col">'.lang('title').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('date').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('newspaper').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('news_order').'</th>';
			//$text .= '<th scope="col" class="text-center">'.lang('edit').'</th>';
			//$text .= '<th scope="col" class="text-center">'.lang('delete').'</th>';
			$text .= '</tr>';
			$text .= '</thead>';
			$text .= '<tbody>';

			if( is_array($tr_array) ){
				$i = 0;
				foreach( $tr_array as $key => $value ){
					$title_head = ( isset($value['title']) ? $value['title'] : '' );
					$content = ( isset($value['text']) ? $value['text'] : '' );
					++$i;

					if( !empty($content) ){
						$text .= '<tr>';
						$text .= '<td colspan="5"><h4>'.$title_head.'</h4></td>';
						$text .= '</tr>';
					}

					if( is_array($content) && count($content) > 0 ){
						foreach( $content as $key2 => $value2 ){
							$get_id = ( isset($value2['id']) ? $value2['id'] : 0 );
							$get_title = ( isset($value2['title']) ? $value2['title'] : '' );
							$get_order = ( isset($value2['order']) ? $value2['order'] : 0 );
							$get_newspaper_name = ( isset($value2['newspaper']) ? $value2['newspaper'] : 0 );
							$get_image = ( isset($value2['image']) ? $value2['image'] : '' );
							$get_published_date = ( isset($value2['published_date']) ? $value2['published_date'] : '' );

							$get_large_image = get_image($get_image, 'large', 1);
							$get_xsmall_image = get_image($get_image, 'xsmall', 1);
							$get_small_image = get_image($get_image, 'small', 1);
							$get_medium_image = get_image($get_image, 'medium', 1);

							$view_image = ( empty($get_image) ? '- - -' : '<a target="_blank" href="'.$get_image.'"><img alt="'.$get_title.'" src="'.$get_xsmall_image.'" class="w-100" style="max-width: 100px;"></a>' );

							$text .= '<tr>';
							$text .= '<td class="text-center">'.$view_image.'</td>';
							$text .= '<td><input type="hidden" name="id[]" value="'.$get_id.'"><a href="index.php?action=news_edit&id='.$get_id.'">'.$get_title.'</a></td>';
							$text .= '<td class="text-center">'.$get_published_date.'</td>';
							$text .= '<td class="text-center">'.$get_newspaper_name.'</td>';
							$text .= '<td class="text-center"><input type="number" class="form-control text-center" name="order[]" value="'.$get_order.'"></td>';
							//$text .= '<td class="text-center"><a href="index.php?action=news_edit&id='.$get_id.'"><i class="fas fa-edit"></i></a></td>';
							//$text .= '<td class="text-center"><a href="index.php?action=news_delete&id='.$get_order.'"><i class="fas fa-trash-alt"></i></a></td>';
							$text .= '</tr>';
						}
					}

				}
			}

			$text .= '</tbody>';
			$text .= '</table>';
			$text .= '<button type="submit" class="btn btn-primary">'.lang('news_order').'</button>';
			$text .= '</form>';

			$text .= pagination($counts, $perpage, $page, 'index.php?action=news_order'.$var_date.'&');

		}

		return $text;
	}

	function news_order_update(){
		$error = array();
		$go = 1;
		$code = '';
		if( isset($_POST['order']) && is_array($_POST['order']) && count($_POST['order']) > 0 ){
			foreach( $_POST['order'] as $key => $value ){
				$id = ( isset($_POST['id'][$key]) ? intval($_POST['id'][$key]) : 0 );
				$query = $this->DB->N_query("UPDATE news SET orders='".$value."' WHERE id='".$id."' LIMIT 1");
				if( $query ){
		      $code .= '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
		    }else{
					$go = 0;
		      $code .= $this->get_alert('Not update ID '.$id, 'danger');
		    }
			}

			if( $go == 1 ){
				$output = $code;
				$output .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=news_order" />';
			}else{
				$output = $code;
			}
		}

	  return $output;
	}

	function publications_add( $update=0 ){
	  $id = ( isset($_GET['id']) ? intval($_GET['id']) : 0 );
	  $title = '';
		$description = '';
	  $url = '';
		$image = '';
		$publication_date = '';
	  $text = '';
	  $active = 1;
	  $user_id = 0;
		$cover = 0;
		$other_file = '';

	  $form_action = 'index.php?action=publications_insert';
		$input_hidden = '<input type="hidden" name="token" value="'.generate_form_token('publications_add').'">';
	  $submit_name = lang('submit');
		$get_news = '';

	  $code = '';
	  $allow_form = 1;

	  if( $update == 1 ){
	    if( empty($id) ){
	      $code .= $this->get_alert(sprintf(lang('empty_id'), $id), 'danger', 1);
	      $allow_form = 0;
	    }else{
	      $query = $this->DB->N_query("SELECT * FROM publications WHERE id='".$id."' LIMIT 1");
	      $counts = $this->DB->N_num_rows( $query );
	      if( $counts == 0 ){
	        $code .= $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger', 1);
	        $allow_form = 0;
	      }else{
	        $row = $this->DB->N_fetch_array($query);
	        $title = text_filter(3, $row['title']);
					$description = text_filter(3, $row['description']);
					$url = text_filter(3, $row['url']);
	        $image = text_filter(3, $row['image']);
	        $text = text_filter(3, $row['text']);
					$publication_date = text_filter(3, $row['publication_date']);
	        $active = intval($row['active']);
	        $user_id = intval($row['user_id']);
					$cover = intval($row['cover']);
					$other_file = text_filter(3, $row['other_file']);

	        $this->main_title = $title;

	        $form_action = 'index.php?action=publications_update';
	        $input_hidden = '<input type="hidden" name="id" value="'.$id.'">';
					$input_hidden .= '<input type="hidden" name="token" value="'.generate_form_token('publications_update_'.$id).'">';
	        $submit_name = lang('update');
	      }
	    }
	  }

		$shortcode = '';
		$news_ids = '';

	  $form = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
	  $form .= $input_hidden;

		$form .= '<div class="form-group">';
	  $form .= '<label for="publication_date">'.lang('publication_date').'</label>';
	  $form .= '<input type="text" class="form-control" id="publication_date" name="publication_date" value="'.$publication_date.'" disabled>';
	  $form .= '</div>';

	  $form .= '<div class="form-group">';
	  $form .= '<label for="title">'.lang('title').'</label>';
	  $form .= '<input type="text" class="form-control" id="title" name="title" value="'.$title.'">';
	  $form .= '</div>';

		$form .= '<div class="form-group">';
	  $form .= '<label for="description">'.lang('description').'</label>';
	  $form .= '<textarea class="form-control" id="description" rows="3" name="description">'.$description.'</textarea>';
	  $form .= '</div>';

	  $form .= '<div class="form-group">';
	  $form .= '<label for="image">'.lang('image').'</label>';
	  $form .= '<input type="text" class="form-control" id="image" name="image" value="'.$image.'">';
	  $form .= '<input type="file" class="form-control" id="upload_image" name="upload_image">';
		$is_cover = ( $cover == 1 ? ' checked' : '' );
		$form .= '<input type="checkbox" value="1" id="cover" name="cover"'.$is_cover.'> <label class="form-check-label" for="cover">'.lang('cover').'</label>';
	  $form .= '</div>';

	  $form .= '<div class="form-group">';
	  $form .= '<label for="url">'.lang('url').'</label>';
	  $form .= '<input type="text" class="form-control" id="url" name="url" value="'.$url.'">';
	  $form .= '</div>';

		$form .= '<div class="form-group">';
	  $form .= '<label for="other_file">'.lang('other_file').'</label>';
	  $form .= '<input type="text" class="form-control" id="other_file" name="other_file" value="'.$other_file.'">';
	  $form .= '<input type="file" class="form-control" id="upload_other_file" name="upload_other_file">';
	  $form .= '</div>';

		if( isset($_POST['news']) && count($_POST['news']) > 0 ){
			$form .= '<div class="form-group">';
		  $form .= '<label for="news_id">'.lang('include_news').'</label>';
		  $form .= '<ul>';
			$shortcode = '';
			foreach ($_POST['news'] as $key => $value) {
				$query_d = $this->DB->N_query("SELECT id,title FROM news WHERE id='".$value."' LIMIT 1");
			  if($this->DB->N_num_rows($query_d) > 0){
			    $rowx = $this->DB->N_fetch_array($query_d);
			    $news_title = text_filter(3, $rowx['title']);
					$shortcode_text = '<code><kbd>[news]'.$rowx['id'].'[/news]</kbd></code>';
		      $form .= '<li>'.$news_title.' '.$shortcode_text.'</li>';
					//$shortcode .= '<p>'.$news_title.'</p>'."\n";
					$shortcode .= '<p>[news]'.$value.'[/news]</p>'."\n";
					$news_ids .= '<input type="hidden" name="news_id[]" value="'.$rowx['id'].'">'."\n";
			  }
			}
		  $form .= '</ul>';
			$form .= $news_ids;
		  $form .= '</div>';
		}else{
			$form .= $get_news;
		}

	  $form .= '<div class="form-group">';
	  $form .= '<label for="tinymce-editor">'.lang('details').'</label>';
		//shortcode($shortcode)
	  $form .= '<textarea id="tinymce-editor" class="form-control" rows="3" name="text">'.$shortcode.$text.'</textarea>';
	  $form .= '</div>';

	  $form .= '<button type="submit" class="btn btn-primary">'.$submit_name.'</button>';
	  $form .= '</form>';

	  $this->breadcrumb_parent = array( array('title' => lang('publications'), 'url' => 'index.php?action=publications_data') );

	  if($allow_form == 1){
	    return $form;
	  }else{
	    return $code;
	  }
	}

	function publications_edit(){
	  return $this->publications_add(1);
	}

	function publications_insert(){
	  $code = '';
		if( verify_form_token('publications_add') == false ){
			$code .= '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST) ){
		    $title = ( isset($_POST['title']) ? $this->DB->N_escape_string($_POST['title']) : '' );
				$description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
		    $url = ( isset($_POST['url']) ? $this->DB->N_escape_string($_POST['url']) : '' );
				$image = ( isset($_POST['image']) ? $this->DB->N_escape_string($_POST['image']) : '' );
		    $text = ( isset($_POST['text']) ? $this->DB->N_escape_string($_POST['text']) : '' );
				$cover = ( isset($_POST['cover']) ? $this->DB->N_escape_string($_POST['cover']) : 0 );
				$other_file = ( isset($_POST['other_file']) ? $this->DB->N_escape_string($_POST['other_file']) : '' );

		    $active = 1;

		    if( isset($_SESSION['user_id']) ){
		      $user_id = intval($_SESSION['user_id']);
		    }else{
		      $user_id = 0;
		    }
		    $date = time();

		    $err = array();

		    if( empty($title) ){
		      $err[] = '<p class="mb-0">'.lang('validate_title').'</p>';
		    }

		    if( count($err) > 0 ){
		      foreach ($err as $key => $value) {
		        $code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
		      }
		    }else{
		      $upload = upload_files('upload_image', 'publications');
		      $get_image = ( empty($upload) ? $image : $upload );

					$upload_other_file = upload_files('upload_other_file', 'publications');
		      $get_other_file = ( empty($upload_other_file) ? $other_file : $upload_other_file );

		      $query = $this->DB->N_query("INSERT INTO publications (`title`, `description`, `url`, `image`, `text`, `user_id`, `date`, `active`, `cover`, `other_file`) VALUES ('".$title."', '".$description."', '".$url."', '".$get_image."', '".$text."', '".$user_id."', '".$date."', '".$active."', '".$cover."', '".$get_other_file."' )");
		      if( $query ){
		        $insert_id = ( $this->DB->N_insert_id() == 0 ? $this->DB->last_N_insert_id('publications', 'id') : $this->DB->N_insert_id() );

		        $code .= '<div class="alert alert-success" role="alert">'.lang('added').'</div>';
		        $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=publications_edit&id='.$insert_id.'" />';
		      }else{
		        $code .= '<div class="alert alert-danger" role="alert">'.lang('not_added').'</div>';
		      }
		    }
		  }else{
		    $code .= '<div class="alert alert-danger" role="alert">'.lang('empty_value').'</div>';
		  }
		}

	  return $code;
	}

	function publications_update(){
		$post_id = ( isset($_POST['id']) ? intval($_POST['id']) : 0 );
		if( verify_form_token('publications_update_'.$post_id) == false ){
			$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST['id']) && intval($_POST['id']) != 0 ){
		    $id = intval($_POST['id']);

		    if( isset($_SESSION['user_id']) ){
		      $user_id = intval($_SESSION['user_id']);
		    }else{
		      $user_id = 0;
		    }

		    $date = time();

				$title = ( isset($_POST['title']) ? $this->DB->N_escape_string($_POST['title']) : '' );
				$description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
		    $url = ( isset($_POST['url']) ? $this->DB->N_escape_string($_POST['url']) : '' );
				$image = ( isset($_POST['image']) ? $this->DB->N_escape_string($_POST['image']) : '' );
		    $text = ( isset($_POST['text']) ? $this->DB->N_escape_string($_POST['text']) : '' );
				$cover = ( isset($_POST['cover']) ? $this->DB->N_escape_string($_POST['cover']) : 0 );
				$other_file = ( isset($_POST['other_file']) ? $this->DB->N_escape_string($_POST['other_file']) : '' );

		    $err = array();

		    if( empty($title) ){
		      $err[] = '<p class="mb-0">'.lang('validate_title').'</p>';
		    }

				$code = '';
		    if( count($err) > 0 ){
		      foreach ($err as $key => $value) {
		        $code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
		      }
		    }else{
		      $upload = upload_files('upload_image', 'publications');
		      $get_image = ( empty($upload) ? $image : $upload );

					$upload_other_file = upload_files('upload_other_file', 'publications');
		      $get_other_file = ( empty($upload_other_file) ? $other_file : $upload_other_file );

		      $query = $this->DB->N_query("UPDATE publications SET title='".$title."', description='".$description."', url='".$url."', image='".$get_image."', text='".$text."', update_user_id='".$user_id."', update_date='".$date."', cover='".$cover."', other_file='".$get_other_file."' WHERE id='".$id."' LIMIT 1");
		      if( $query ){
		        $code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
		        $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=publications_data" />';
		      }else{
		        $code = $this->get_alert('Error', 'danger');
		      }
		    }
		  }else{
		    $code = $this->get_alert(lang('empty_id'), 'danger');
		  }
		}

	  return $code;
	}

	function publications_status(){
	  if( isset($_GET['id']) && intval($_GET['id']) != 0 ){
	    $id = intval($_GET['id']);
	    $status = ( isset($_GET['act']) ? intval($_GET['act']) : 0 );
	    $query = $this->DB->N_query("UPDATE publications SET active='".$status."' WHERE id='".$id."' LIMIT 1");
	    if( $query ){
	      $code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
	      $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=publications_data" />';
	    }else{
	      $code = $this->get_alert(lang('error'), 'danger');
	    }
	  }else{
	    $code = $this->get_alert(lang('empty_id'), 'danger');
	  }

	  return $code;
	}

	function publications_delete(){
	  if(isset($_GET['id']) && intval($_GET['id']) != 0){
	    $id = intval($_GET['id']);

	    $query = $this->DB->N_query("SELECT * FROM publications WHERE id='".$id."' LIMIT 1");
	    $counts = $this->DB->N_num_rows($query);
	    if($counts == 0){
	      $code = $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger');
	    }else{
	      $row = $this->DB->N_fetch_array($query);
	      $title = text_filter(3, $row['title']);
	      $this->main_title = $title;

	      $this->breadcrumb_parent = array( array('title' => lang('publications'), 'url' => 'index.php?action=publications_data') );

	      $code = '<form name="delete" method="post" action="index.php?action=publications_delete">';
	      $code .= '<input type="hidden" name="post_id" value="'.$id.'" />';
	      $code .= '<input type="hidden" name="delete_post" value="yes" />';
				$code .= '<input type="hidden" name="token" value="'.generate_form_token('publications_delete_'.$id).'">';
	      $code .= '<input type="submit" value="'.sprintf(lang('delete_sure'), $title).'" name="delete" />';
	      $code .= '</form>';
	    }
	  }else{
			$post_id = ( isset($_POST['post_id']) ? intval($_POST['post_id']) : 0 );
			if( verify_form_token('publications_delete_'.$post_id) == false ){
				$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
			}else{
				if( isset($_POST['delete_post']) && $_POST['delete_post'] == "yes" && isset($_POST['post_id']) && intval($_POST['post_id']) != 0 ){
		      $post_id = intval($_POST['post_id']);
		      $query =  $this->DB->N_query("DELETE FROM publications WHERE id='".$post_id."' LIMIT 1");
		      if( $query ){
		        $code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
		        $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=publications_data" />';
		      }else{
		        $code = $this->get_alert(lang('error'), 'danger');
		      }
		    }else{
		      $code = $this->get_alert(lang('empty_id'), 'danger');
		    }
			}
	  }

	  return $code;
	}

	function publications_data($home=0){
	  $counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM publications") );

	  $page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
	  $page = ($page == 0 ? 1 : $page);
	  $perpage = 30;
	  $startpoint = ($page * $perpage) - $perpage;

	  $query_d = $this->DB->N_query("SELECT * FROM publications order by id desc LIMIT $startpoint,$perpage");
	  $data_count = $this->DB->N_num_rows($query_d);

	  $text = '';

	  if($data_count == 0){
	    $text .= lang('not_found');
	  }else{
	    $text .= '<table class="table table-hover">';
	    $text .= '<thead>';
	    $text .= '<tr>';
	    $text .= '<th scope="col">'.lang('title').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('publication_date').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('show_publication').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('status').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('edit').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('delete').'</th>';
	    $text .= '</tr>';
	    $text .= '</thead>';
	    $text .= '<tbody>';
			$url_site = str_replace('/cp', '', base_url());
	    $i=0;
	    $arr_money = array();
	    while ($row = $this->DB->N_fetch_array($query_d)){
	      $title = text_filter(3, $row['title']);
				$description = text_filter(3, $row['description']);
				$publication_date = text_filter(3, $row['publication_date']);
				$url = text_filter(3, $row['url']);
	      $image = text_filter(3, $row['image']);
	      $notice = text_filter(3, $row['text']);
	      $active = intval($row['active']);
	      $user_id = intval($row['user_id']);
				$added = date("j/n/Y", $row['date']);

	      $get_image = ( empty($image) ? '' : '<img src="'.$image.'" alt="'.$title.'" class="w-100"> ' );

	      if( $active == 1 ){
	        $get_active = '<a href="index.php?action=publications_status&id='.$row['id'].'&act=0"><i class="fas fa-eye text-success"></i></a>';
	      }else{
	        $get_active = '<a href="index.php?action=publications_status&id='.$row['id'].'&act=1"><i class="fas fa-eye-slash text-danger"></i></a>';
	      }

	      if( empty($get_image) ){
	        $modal = '';
	      }else{
	        $modal = '<span data-toggle="modal" data-target=".publicationspaper-modal-'.$row['id'].'"><i class="far fa-image"></i></span> ';
	        $modal .= '<div class="modal fade publicationspaper-modal-'.$row['id'].'" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">';
	        $modal .= '<div class="modal-dialog modal-'.$row['id'].'">';
	        $modal .= '<div class="modal-content">'.$get_image.'</div>';
	        $modal .= '</div>';
	        $modal .= '</div>';
	      }

	      ++$i;

	      $text .= '<tr>';
	      $text .= '<td>'.$modal.'<a href="index.php?action=publications_edit&id='.$row['id'].'">'.$title.'</a></td>';
	      $text .= '<td class="text-center">'.$publication_date.'</td>';
				$text .= '<td class="text-center"><a target="_blank" href="'.$url_site.'/index.php?read=pdf&online=1&publication_id='.$row['id'].'"><i class="fas fa-file-pdf"></i></a></td>';
	      $text .= '<td class="text-center">'.$get_active.'</td>';
	      $text .= '<td class="text-center"><a href="index.php?action=publications_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a></td>';
	      $text .= '<td class="text-center"><a href="index.php?action=publications_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a></td>';
	      $text .= '</tr>';
	    }
	    $text .= '</tbody>';
	    $text .= '</table>';
	    if( $home == 1 ){
	      $text .= '<div class="mt-3"><a href="index.php?action=publications_add">More</a></div>';
	    }else{
	      $text .= pagination($counts, $perpage, $page, 'index.php?action=publications_data&');
	    }

	  }

	  return $text;
	}

	function category_add( $update=0 ){
		$id = ( isset($_GET['id']) ? intval($_GET['id']) : 0 );
		$title = '';
		$description = '';
		$active = 1;
		$user_id = 0;

		$form_action = 'index.php?action=category_insert';
		$input_hidden = '<input type="hidden" name="token" value="'.generate_form_token('category_add').'">';
		$submit_name = lang('submit');

		$code = '';
		$allow_form = 1;

		if( $update == 1 ){
			if( empty($id) ){
				$code .= $this->get_alert(sprintf(lang('empty_id'), $id), 'danger', 1);
				$allow_form = 0;
			}else{
				$query = $this->DB->N_query("SELECT * FROM category WHERE id='".$id."' LIMIT 1");
				$counts = $this->DB->N_num_rows( $query );
				if( $counts == 0 ){
					$code .= $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger', 1);
					$allow_form = 0;
				}else{
					$row = $this->DB->N_fetch_array($query);
					$title = text_filter(3, $row['title']);
					$description = text_filter(3, $row['description']);
					$active = intval($row['active']);
					$user_id = text_filter(3, $row['user_id']);

					$this->main_title = $title;

					$form_action = 'index.php?action=category_update';
					$input_hidden = '<input type="hidden" name="id" value="'.$id.'">';
					$input_hidden .= '<input type="hidden" name="token" value="'.generate_form_token('category_update_'.$id).'">';
					$submit_name = lang('update');
				}
			}
		}

		$form = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
		$form .= $input_hidden;

		$form .= '<div class="form-group">';
		$form .= '<label for="title">'.lang('title').'</label>';
		$form .= '<input type="text" class="form-control" id="title" name="title" value="'.$title.'">';
		$form .= '</div>';

		$form .= '<div class="form-group">';
		$form .= '<label for="description">'.lang('description').'</label>';
		$form .= '<textarea class="form-control" id="description" rows="3" name="description">'.$description.'</textarea>';
		$form .= '</div>';

		$form .= '<button type="submit" class="btn btn-primary">'.$submit_name.'</button>';
		$form .= '</form>';

		$this->breadcrumb_parent = array( array('title' => lang('categories'), 'url' => 'index.php?action=category_data') );

		if($allow_form == 1){
			return $form;
		}else{
			return $code;
		}
	}

	function category_edit(){
		return $this->category_add(1);
	}

	function category_insert(){
		$code = '';
		if( verify_form_token('category_add') == false ){
			$code .= '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST) ){
				$title = ( isset($_POST['title']) ? $this->DB->N_escape_string($_POST['title']) : '' );
				$description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
				$active = 1;

				if( isset($_SESSION['user_id']) ){
					$user_id = intval($_SESSION['user_id']);
				}else{
					$user_id = 0;
				}
				$date = time();

				$err = array();

				if( empty($title) ){
					$err[] = '<p class="mb-0">'.lang('validate_title').'</p>';
				}

				if( count($err) > 0 ){
					foreach ($err as $key => $value) {
						$code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
					}
				}else{
					$query = $this->DB->N_query("INSERT INTO category (`title`, `description`, `user_id`, `date`, `active`) VALUES ('".$title."', '".$description."', '".$user_id."', '".$date."', '".$active."')");
					if( $query ){
						$insert_id = ( $this->DB->N_insert_id() == 0 ? $this->DB->last_N_insert_id('category', 'id') : $this->DB->N_insert_id() );
						$code .= '<div class="alert alert-success" role="alert">'.lang('added').'</div>';
						$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=category_edit&id='.$insert_id.'" />';
					}else{
						$code .= '<div class="alert alert-danger" role="alert">'.lang('not_added').'</div>';
					}
				}
			}else{
				$code .= '<div class="alert alert-danger" role="alert">'.lang('empty_value').'</div>';
			}
		}

		return $code;
	}

	function category_update(){
		$post_id = ( isset($_POST['id']) ? intval($_POST['id']) : 0 );
		if( verify_form_token('category_update_'.$post_id) == false ){
			$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST['id']) && intval($_POST['id']) != 0 ){
				$id = intval($_POST['id']);

				if( isset($_SESSION['user_id']) ){
					$user_id = intval($_SESSION['user_id']);
				}else{
					$user_id = 0;
				}

				$date = time();

				$title = ( isset($_POST['title']) ? $this->DB->N_escape_string($_POST['title']) : '' );
				$description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
				$active = 1;

				$err = array();

				if( empty($title) ){
					$err[] = '<p class="mb-0">'.lang('validate_title').'</p>';
				}

				$code = '';
				if( count($err) > 0 ){
					foreach ($err as $key => $value) {
						$code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
					}
				}else{
					$query = $this->DB->N_query("UPDATE category SET title='".$title."', description='".$description."', update_user_id='".$user_id."', update_date='".$date."' WHERE id='".$id."' LIMIT 1");
					if( $query ){
						$code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
						$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=category_data" />';
					}else{
						$code = $this->get_alert('Error', 'danger');
					}
				}
			}else{
				$code = $this->get_alert(lang('empty_id'), 'danger');
			}
		}

		return $code;
	}

	function category_status(){
		if( isset($_GET['id']) && intval($_GET['id']) != 0 ){
			$id = intval($_GET['id']);
			$status = ( isset($_GET['act']) ? intval($_GET['act']) : 0 );
			$query = $this->DB->N_query("UPDATE category SET active='".$status."' WHERE id='".$id."' LIMIT 1");
			if( $query ){
				$code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
				$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=category_data" />';
			}else{
				$code = $this->get_alert(lang('error'), 'danger');
			}
		}else{
			$code = $this->get_alert(lang('empty_id'), 'danger');
		}

		return $code;
	}

	function category_delete(){
		if(isset($_GET['id']) && intval($_GET['id']) != 0){
			$id = intval($_GET['id']);

			$query = $this->DB->N_query("SELECT * FROM category WHERE id='".$id."' LIMIT 1");
			$counts = $this->DB->N_num_rows($query);
			if($counts == 0){
				$code = $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger');
			}else{
				$row = $this->DB->N_fetch_array($query);
				$title = text_filter(3, $row['title']);
				$this->main_title = $title;

				$this->breadcrumb_parent = array( array('title' => lang('categories'), 'url' => 'index.php?action=category_data') );

				$code = '<form name="delete" method="post" action="index.php?action=category_delete">';
				$code .= '<input type="hidden" name="post_id" value="'.$id.'" />';
				$code .= '<input type="hidden" name="delete_post" value="yes" />';
				$code .= '<input type="hidden" name="token" value="'.generate_form_token('category_delete_'.$id).'">';
				$code .= '<input type="submit" value="'.sprintf(lang('delete_sure'), $title).'" name="delete" />';
				$code .= '</form>';
			}
		}else{
			$post_id = ( isset($_POST['post_id']) ? intval($_POST['post_id']) : 0 );
			if( verify_form_token('category_delete_'.$post_id) == false ){
				$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
			}else{
				if( isset($_POST['delete_post']) && $_POST['delete_post'] == "yes" && isset($_POST['post_id']) && intval($_POST['post_id']) != 0 ){
					$post_id = intval($_POST['post_id']);
					$query =  $this->DB->N_query("DELETE FROM category WHERE id='".$post_id."' LIMIT 1");
					if( $query ){
						$code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
						$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=category_data" />';
					}else{
						$code = $this->get_alert(lang('error'), 'danger');
					}
				}else{
					$code = $this->get_alert(lang('empty_id'), 'danger');
				}
			}
			}

		return $code;
	}

	function category_data(){
		$counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM category") );

		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$page = ($page == 0 ? 1 : $page);
		$perpage = 30;
		$startpoint = ($page * $perpage) - $perpage;

		$query_d = $this->DB->N_query("SELECT * FROM category order by id desc LIMIT $startpoint,$perpage");
		$data_count = $this->DB->N_num_rows($query_d);

		$text = '';

		if($data_count == 0){
			$text .= lang('not_found');
		}else{
			$text .= '<table class="table table-hover">';
			$text .= '<thead>';
			$text .= '<tr>';
			$text .= '<th scope="col">'.lang('title').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('description').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('news').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('status').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('edit').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('delete').'</th>';
			$text .= '</tr>';
			$text .= '</thead>';
			$text .= '<tbody>';
			$i=0;
			$arr_money = array();
			while ($row = $this->DB->N_fetch_array($query_d)){
				$title = text_filter(3, $row['title']);
				$description = text_filter(3, $row['description']);
				$active = intval($row['active']);
				$user_id = intval($row['user_id']);

				if( $active == 1 ){
					$get_active = '<a href="index.php?action=category_status&id='.$row['id'].'&act=0"><i class="fas fa-eye text-success"></i></a>';
				}else{
					$get_active = '<a href="index.php?action=category_status&id='.$row['id'].'&act=1"><i class="fas fa-eye-slash text-danger"></i></a>';
				}
				++$i;

				$news_count = $this->DB->N_num_rows($this->DB->N_query("SELECT * FROM news_meta WHERE meta_key='category_id' AND meta_value=".$row['id'].""));

				$text .= '<tr>';
				$text .= '<td><a href="index.php?action=category_edit&id='.$row['id'].'">'.$title.'</a></td>';
				$text .= '<td>'.$description.'</td>';
				$text .= '<td class="text-center"><a href="index.php?action=news_category&category_id='.$row['id'].'">'.$news_count.'</a></td>';
				$text .= '<td class="text-center">'.$get_active.'</td>';
				$text .= '<td class="text-center"><a href="index.php?action=category_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a></td>';
				$text .= '<td class="text-center"><a href="index.php?action=category_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a></td>';
				$text .= '</tr>';
			}
			$text .= '</tbody>';
			$text .= '</table>';
			$text .= pagination($counts, $perpage, $page, 'index.php?action=category_data&');
		}

		return $text;
	}

	function user_add( $update=0 ){
	  $id = ( isset($_GET['id']) ? intval($_GET['id']) : 0 );
	  $name = '';
		$username = '';
	  $password = '';
		$email = '';
	  $active = 1;
		$group = 0;

	  $form_action = 'index.php?action=user_insert';
	  $input_hidden = '<input type="hidden" name="token" value="'.generate_form_token('user_add').'">';
	  $submit_name = lang('submit');
		$get_news = '';

	  $code = '';
	  $allow_form = 1;

	  if( $update == 1 ){
	    if( empty($id) ){
	      $code .= $this->get_alert(sprintf(lang('empty_id'), $id), 'danger', 1);
	      $allow_form = 0;
	    }else{
	      $query = $this->DB->N_query("SELECT * FROM users WHERE id='".$id."' LIMIT 1");
	      $counts = $this->DB->N_num_rows( $query );
	      if( $counts == 0 ){
	        $code .= $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger', 1);
	        $allow_form = 0;
	      }else{
	        $row = $this->DB->N_fetch_array($query);
	        $name = text_filter(3, $row['name']);
					$username = text_filter(3, $row['username']);
					$password = text_filter(3, $row['password']);
	        $email = text_filter(3, $row['email']);
	        $active = intval($row['active']);
					$group = intval($row['user_group']);

	        $this->main_title = $name;

	        $form_action = 'index.php?action=user_update';
	        $input_hidden = '<input type="hidden" name="id" value="'.$id.'">';
					$input_hidden .= '<input type="hidden" name="current_username" value="'.$username.'">';
					$input_hidden .= '<input type="hidden" name="current_email" value="'.$email.'">';
					$input_hidden .= '<input type="hidden" name="token" value="'.generate_form_token('user_update_'.$id).'">';
	        $submit_name = lang('update');
	      }
	    }
	  }

	  $form = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
	  $form .= $input_hidden;

	  $form .= '<div class="form-group">';
	  $form .= '<label for="name">'.lang('users_name').'</label>';
	  $form .= '<input type="text" class="form-control" id="name" name="name" value="'.$name.'">';
	  $form .= '</div>';

		$form .= '<div class="form-group">';
	  $form .= '<label for="username">'.lang('users_username').'</label>';
		$form .= '<input type="username" class="form-control" id="username" name="username" value="'.$username.'">';
	  $form .= '</div>';

	  $form .= '<div class="form-group">';
	  $form .= '<label for="image">'.lang('users_password').'</label>';
	  $form .= '<input type="password" class="form-control" id="password" name="password" value="">';
	  $form .= '</div>';

	  $form .= '<div class="form-group">';
	  $form .= '<label for="email">'.lang('users_email').'</label>';
	  $form .= '<input type="email" class="form-control" id="email" name="email" value="'.$email.'">';
	  $form .= '</div>';

	  $form .= '<button type="submit" class="btn btn-primary">'.$submit_name.'</button>';
	  $form .= '</form>';

	  $this->breadcrumb_parent = array( array('title' => lang('users'), 'url' => 'index.php?action=user_data') );

	  if($allow_form == 1){
	    return $form;
	  }else{
	    return $code;
	  }
	}

	function user_edit(){
	  return $this->user_add(1);
	}

	function user_insert(){
	  $code = '';
		if( verify_form_token('user_add') == false ){
			$code .= '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST) ){
		    $name = ( isset($_POST['name']) ? $this->DB->N_escape_string($_POST['name']) : '' );
				$username = ( isset($_POST['username']) ? $this->DB->N_escape_string($_POST['username']) : '' );
		    $password = ( isset($_POST['password']) ? $this->DB->N_escape_string($_POST['password']) : '' );
				$email = ( isset($_POST['email']) ? $this->DB->N_escape_string($_POST['email']) : '' );
		    $group = ( isset($_POST['group']) ? $this->DB->N_escape_string($_POST['group']) : 0 );
		    $active = 1;

		    $date = time();

				$check_username = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM users WHERE username='".$username."'") );
				$check_email = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM users WHERE email='".$email."'") );

		    $err = array();

		    if( empty($name) ){
		      $err[] = '<p class="mb-0">'.lang('users_empty_name').'</p>';
		    }

				if( empty($password) ){
		      $err[] = '<p class="mb-0">'.lang('users_empty_password').'</p>';
		    }

				if( $check_username > 0 ){
					$err[] = '<p class="mb-0">'.lang('users_username_already').'</p>';
				}

				if( $check_email > 0 ){
					$err[] = '<p class="mb-0">'.lang('users_email_already').'</p>';
				}

		    if( count($err) > 0 ){
		      foreach ($err as $key => $value) {
		        $code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
		      }
		    }else{
		      $query = $this->DB->N_query("INSERT INTO users (`name`, `username`, `password`, `email`, `user_group`, `date`, `active`) VALUES ('".$name."', '".$username."', '".md5($password)."', '".$email."', '".$group."', '".$date."', '".$active."' )");
		      if( $query ){
		        $insert_id = ( $this->DB->N_insert_id() == 0 ? $this->DB->last_N_insert_id('users', 'id') : $this->DB->N_insert_id() );

		        $code .= '<div class="alert alert-success" role="alert">'.lang('added').'</div>';
		        $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=user_edit&id='.$insert_id.'" />';
		      }else{
		        $code .= '<div class="alert alert-danger" role="alert">'.lang('not_added').'</div>';
		      }
		    }
		  }else{
		    $code .= '<div class="alert alert-danger" role="alert">'.lang('empty_value').'</div>';
		  }
		}

	  return $code;
	}

	function user_update(){
		$post_id = ( isset($_POST['id']) ? intval($_POST['id']) : 0 );
		if( verify_form_token('user_update_'.$post_id) == false ){
			$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
		}else{
			if( isset($_POST['id']) && intval($_POST['id']) != 0 ){
		    $id = intval($_POST['id']);

		    $date = time();

				$name = ( isset($_POST['name']) ? $this->DB->N_escape_string($_POST['name']) : '' );
				$username = ( isset($_POST['username']) ? $this->DB->N_escape_string($_POST['username']) : '' );
		    $password = ( isset($_POST['password']) ? $this->DB->N_escape_string($_POST['password']) : '' );
				$email = ( isset($_POST['email']) ? $this->DB->N_escape_string($_POST['email']) : '' );
		    $group = ( isset($_POST['group']) ? $this->DB->N_escape_string($_POST['group']) : 0 );

				$current_username = ( isset($_POST['current_username']) ? $this->DB->N_escape_string($_POST['current_username']) : '' );
				$current_email = ( isset($_POST['current_email']) ? $this->DB->N_escape_string($_POST['current_email']) : '' );

				$check_username = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM users WHERE username='".$username."'") );
				$check_email = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM users WHERE email='".$email."'") );

		    $err = array();

		    if( empty($name) ){
		      $err[] = '<p class="mb-0">'.lang('users_empty_name').'</p>';
		    }

				if( $current_username != $username ){
					if( $check_username > 0 ){
						$err[] = '<p class="mb-0">'.lang('users_username_already').'</p>';
					}
				}
				if( $current_email != $email ){
					if( $check_email > 0 ){
						$err[] = '<p class="mb-0">'.lang('users_email_already').'</p>';
					}
				}

				$code = '';
		    if( count($err) > 0 ){
		      foreach ($err as $key => $value) {
		        $code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
		      }
		    }else{
		      $query = $this->DB->N_query("UPDATE users SET name='".$name."', username='".$username."', email='".$email."', user_group='".$group."' WHERE id='".$id."' LIMIT 1");
		      if( $query ){
						if( !empty($password) ){
							$this->DB->N_query("UPDATE users SET password='".md5($password)."' WHERE id='".$id."' LIMIT 1");
							$current_password = get_start_password().md5($password);
							if( isset($_SESSION["user_id"]) && $_SESSION["user_id"] == $id ){
								$_SESSION["pass"] = $current_password;
							}
						}
		        $code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
		        $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=user_data" />';
		      }else{
		        $code = $this->get_alert('Error', 'danger');
		      }
		    }
		  }else{
		    $code = $this->get_alert(lang('empty_id'), 'danger');
		  }
		}

	  return $code;
	}

	function user_status(){
		if( isset($_SESSION['user_id']) ){
			$user_id = intval($_SESSION['user_id']);
		}else{
			$user_id = 0;
		}

	  if( isset($_GET['id']) && intval($_GET['id']) != 0 ){
	    $id = intval($_GET['id']);
			$status = ( isset($_GET['act']) ? intval($_GET['act']) : 0 );
			if( $user_id == $id ){
				$code = $this->get_alert(lang('users_not_allow'), 'danger');
			}else{
				$query = $this->DB->N_query("UPDATE users SET active='".$status."' WHERE id='".$id."' LIMIT 1");
		    if( $query ){
		      $code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
		      $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=user_data" />';
		    }else{
		      $code = $this->get_alert(lang('error'), 'danger');
		    }
			}
	  }else{
	    $code = $this->get_alert(lang('empty_id'), 'danger');
	  }

	  return $code;
	}

	function user_delete(){
		if( isset($_SESSION['user_id']) ){
			$user_id = intval($_SESSION['user_id']);
		}else{
			$user_id = 0;
		}

	  if(isset($_GET['id']) && intval($_GET['id']) != 0){
	    $id = intval($_GET['id']);

			if( $user_id == $id ){
				$code = $this->get_alert(lang('users_not_allow'), 'danger');
			}else{
				$query = $this->DB->N_query("SELECT * FROM users WHERE id='".$id."' LIMIT 1");
		    $counts = $this->DB->N_num_rows($query);
		    if($counts == 0){
		      $code = $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger');
		    }else{
		      $row = $this->DB->N_fetch_array($query);
		      $name = text_filter(3, $row['name']);
		      $this->main_title = $name;

		      $this->breadcrumb_parent = array( array('title' => lang('users'), 'url' => 'index.php?action=user_data') );

		      $code = '<form name="delete" method="post" action="index.php?action=user_delete">';
		      $code .= '<input type="hidden" name="post_id" value="'.$id.'" />';
		      $code .= '<input type="hidden" name="delete_post" value="yes" />';
					$code .= '<input type="hidden" name="token" value="'.generate_form_token('user_delete_'.$id).'">';
		      $code .= '<input type="submit" value="'.sprintf(lang('delete_sure'), $name).'" name="delete" />';
		      $code .= '</form>';
		    }
			}
	  }else{
			$post_id = ( isset($_POST['post_id']) ? intval($_POST['post_id']) : 0 );
			if( verify_form_token('user_delete_'.$post_id) == false ){
				$code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
			}else{
				if( isset($_POST['delete_post']) && $_POST['delete_post'] == "yes" && isset($_POST['post_id']) && intval($_POST['post_id']) != 0 ){
		      $post_id = intval($_POST['post_id']);
					if( $user_id == $post_id ){
						$code = $this->get_alert(lang('users_not_allow'), 'danger');
					}else{
			      $query =  $this->DB->N_query("DELETE FROM users WHERE id='".$post_id."' LIMIT 1");
			      if( $query ){
			        $code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
			        $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=user_data" />';
			      }else{
			        $code = $this->get_alert(lang('error'), 'danger');
			      }
					}
		    }else{
		      $code = $this->get_alert(lang('empty_id'), 'danger');
		    }
			}
	  }

	  return $code;
	}

	function user_data($home=0){
	  $counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM users") );

	  $page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
	  $page = ($page == 0 ? 1 : $page);
	  $perpage = 30;
	  $startpoint = ($page * $perpage) - $perpage;

	  $query_d = $this->DB->N_query("SELECT * FROM users order by id desc LIMIT $startpoint,$perpage");
	  $data_count = $this->DB->N_num_rows($query_d);

	  $text = '';

	  if($data_count == 0){
	    $text .= lang('not_found');
	  }else{
	    $text .= '<table class="table table-hover">';
	    $text .= '<thead>';
	    $text .= '<tr>';
	    $text .= '<th scope="col">'.lang('users_username').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('users_email').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('status').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('edit').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('delete').'</th>';
	    $text .= '</tr>';
	    $text .= '</thead>';
	    $text .= '<tbody>';
	    $i=0;
	    $arr_money = array();
	    while ($row = $this->DB->N_fetch_array($query_d)){
				$name = text_filter(3, $row['name']);
				$username = text_filter(3, $row['username']);
				$password = text_filter(3, $row['password']);
				$email = text_filter(3, $row['email']);
				$active = intval($row['active']);
				$group = intval($row['user_group']);
				$added = date("j/n/Y", $row['date']);

	      if( $active == 1 ){
	        $get_active = '<a href="index.php?action=user_status&id='.$row['id'].'&act=0"><i class="fas fa-eye text-success"></i></a>';
	      }else{
	        $get_active = '<a href="index.php?action=user_status&id='.$row['id'].'&act=1"><i class="fas fa-eye-slash text-danger"></i></a>';
	      }

	      ++$i;

	      $text .= '<tr>';
	      $text .= '<td><a href="index.php?action=user_edit&id='.$row['id'].'">'.$username.'</a></td>';
	      $text .= '<td>'.$email.'</td>';
	      $text .= '<td class="text-center">'.$get_active.'</td>';
	      $text .= '<td class="text-center"><a href="index.php?action=user_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a></td>';
	      $text .= '<td class="text-center"><a href="index.php?action=user_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a></td>';
	      $text .= '</tr>';
	    }
	    $text .= '</tbody>';
	    $text .= '</table>';
	    if( $home == 1 ){
	      $text .= '<div class="mt-3"><a href="index.php?action=user_add">More</a></div>';
	    }else{
	      $text .= pagination($counts, $perpage, $page, 'index.php?action=user_data&');
	    }

	  }

	  return $text;
	}

	function banner_add( $update=0 ){
	  $id = ( isset($_GET['id']) ? intval($_GET['id']) : 0 );
	  $title = '';
	  $image = '';
	  $url = '';
	  $description = '';
	  $text = '';
	  $active = 1;
	  $user_id = 0;

	  $form_action = 'index.php?action=banner_insert';
	  $input_hidden = '<input type="hidden" name="token" value="'.generate_form_token('banner_add').'">';
	  $submit_name = lang('submit');

	  $code = '';
	  $allow_form = 1;

	  if( $update == 1 ){
	    if( empty($id) ){
	      $code .= $this->get_alert(sprintf(lang('empty_id'), $id), 'danger', 1);
	      $allow_form = 0;
	    }else{
	      $query = $this->DB->N_query("SELECT * FROM banners WHERE id='".$id."' LIMIT 1");
	      $counts = $this->DB->N_num_rows( $query );
	      if( $counts == 0 ){
	        $code .= $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger', 1);
	        $allow_form = 0;
	      }else{
	        $row = $this->DB->N_fetch_array($query);
	        $title = text_filter(3, $row['title']);
	        $image = text_filter(3, $row['image']);
	        $url = text_filter(3, $row['url']);
	        $description = text_filter(3, $row['description']);
	        $text = text_filter(3, $row['text']);
	        $active = intval($row['active']);
	        $user_id = text_filter(3, $row['user_id']);

	        $this->main_title = $title;

	        $form_action = 'index.php?action=banner_update';
	        $input_hidden = '<input type="hidden" name="id" value="'.$id.'">';
	        $input_hidden .= '<input type="hidden" name="token" value="'.generate_form_token('banner_update_'.$id).'">';
	        $submit_name = lang('update');
	      }
	    }
	  }

	  $form = '<form name="add" method="post" action="'.$form_action.'" enctype="multipart/form-data">';
	  $form .= $input_hidden;

	  $form .= '<div class="form-group">';
	  $form .= '<label for="title">'.lang('title').'</label>';
	  $form .= '<input type="text" class="form-control" id="title" name="title" value="'.$title.'">';
	  $form .= '</div>';

		$form .= '<div class="form-group">';
	  $form .= '<label for="description">'.lang('description').'</label>';
		$form .= '<input type="text" class="form-control" id="description" name="description" value="'.$description.'">';
	  $form .= '</div>';

		$form .= '<div class="form-group">';
	  $form .= '<label for="url">'.lang('url').'</label>';
	  $form .= '<input type="text" class="form-control" id="url" name="url" value="'.$url.'">';
	  $form .= '</div>';

	  $form .= '<div class="form-group">';
	  $form .= '<label for="image">'.lang('image').'</label>';
	  $form .= '<input type="text" class="form-control" id="image" name="image" value="'.$image.'">';
	  $form .= '<input type="file" class="form-control" id="upload_image" name="upload_image">';
	  $form .= '</div>';

	  $form .= '<div class="form-group">';
	  $form .= '<label for="tinymce-editorx">'.lang('html_code').'</label>';
	  $form .= '<textarea id="tinymce-editor" class="form-control text-left" dir="ltr" rows="5" name="text">'.$text.'</textarea>';
	  $form .= '</div>';

	  $form .= '<button type="submit" class="btn btn-primary">'.$submit_name.'</button>';
	  $form .= '</form>';

	  $this->breadcrumb_parent = array( array('title' => lang('banners'), 'url' => 'index.php?action=banner_data') );

	  if($allow_form == 1){
	    return $form;
	  }else{
	    return $code;
	  }
	}

	function banner_edit(){
	  return $this->banner_add(1);
	}

	function banner_insert(){
	  $code = '';
	  if( verify_form_token('banner_add') == false ){
	    $code .= '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
	  }else{
	    if( isset($_POST) ){
	      $title = ( isset($_POST['title']) ? $this->DB->N_escape_string($_POST['title']) : '' );
	      $image = ( isset($_POST['image']) ? $this->DB->N_escape_string($_POST['image']) : '' );
	      $url = ( isset($_POST['url']) ? $this->DB->N_escape_string($_POST['url']) : '' );
	      $description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
	      $text = ( isset($_POST['text']) ? $this->DB->N_escape_string($_POST['text']) : '' );
	      $active = 1;

	      if( isset($_SESSION['user_id']) ){
	        $user_id = intval($_SESSION['user_id']);
	      }else{
	        $user_id = 0;
	      }
	      $date = time();

	      $err = array();

	      if( empty($title) ){
	        $err[] = '<p class="mb-0">'.lang('validate_title').'</p>';
	      }

	      if( count($err) > 0 ){
	        foreach ($err as $key => $value) {
	          $code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
	        }
	      }else{
	        $upload = upload_files('upload_image', 'banners');
	        $get_image = ( empty($upload) ? $image : $upload );

	        $query = $this->DB->N_query("INSERT INTO banners (`title`, `image`, `url`, `description`, `text`, `user_id`, `date`, `active`) VALUES ('".$title."', '".$get_image."', '".$url."', '".$description."', '".$text."', '".$user_id."', '".$date."', '".$active."' )");
	        if( $query ){
	          $insert_id = ( $this->DB->N_insert_id() == 0 ? $this->DB->last_N_insert_id('banners', 'id') : $this->DB->N_insert_id() );

	          $code .= '<div class="alert alert-success" role="alert">'.lang('added').'</div>';
	          $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=banner_edit&id='.$insert_id.'" />';
	        }else{
	          $code .= '<div class="alert alert-danger" role="alert">'.lang('not_added').'</div>';
	        }
	      }
	    }else{
	      $code .= '<div class="alert alert-danger" role="alert">'.lang('empty_value').'</div>';
	    }
	  }

	  return $code;
	}

	function banner_update(){
	  $post_id = ( isset($_POST['id']) ? intval($_POST['id']) : 0 );
	  if( verify_form_token('banner_update_'.$post_id) == false ){
	    $code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
	  }else{
	    if( isset($_POST['id']) && intval($_POST['id']) != 0 ){
	      $id = intval($_POST['id']);

	      if( isset($_SESSION['user_id']) ){
	        $user_id = intval($_SESSION['user_id']);
	      }else{
	        $user_id = 0;
	      }

	      $date = time();

	      $title = ( isset($_POST['title']) ? $this->DB->N_escape_string($_POST['title']) : '' );
	      $image = ( isset($_POST['image']) ? $this->DB->N_escape_string($_POST['image']) : '' );
	      $url = ( isset($_POST['url']) ? $this->DB->N_escape_string($_POST['url']) : '' );
	      $description = ( isset($_POST['description']) ? $this->DB->N_escape_string($_POST['description']) : '' );
	      $text = ( isset($_POST['text']) ? $this->DB->N_escape_string($_POST['text']) : '' );
	      $active = 1;

	      $err = array();

	      if( empty($title) ){
	        $err[] = '<p class="mb-0">'.lang('validate_title').'</p>';
	      }

	      $code = '';

	      if( count($err) > 0 ){
	        foreach ($err as $key => $value) {
	          $code .= '<div class="alert alert-danger" role="alert">'.$value.'</div>';
	        }
	      }else{
	        $upload = upload_files('upload_image', 'banners');
	        $get_image = ( empty($upload) ? $image : $upload );

	        $query = $this->DB->N_query("UPDATE banners SET title='".$title."', image='".$get_image."', url='".$url."', description='".$description."', text='".$text."', update_user_id='".$user_id."', update_date='".$date."' WHERE id='".$id."' LIMIT 1");
	        if( $query ){
	          $code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
	          $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=banner_data" />';
	        }else{
	          $code = $this->get_alert('Error', 'danger');
	        }
	      }
	    }else{
	      $code = $this->get_alert(lang('empty_id'), 'danger');
	    }
	  }

	  return $code;
	}

	function banner_status(){
	  if( isset($_GET['id']) && intval($_GET['id']) != 0 ){
	    $id = intval($_GET['id']);
	    $status = ( isset($_GET['act']) ? intval($_GET['act']) : 0 );
	    $query = $this->DB->N_query("UPDATE banners SET active='".$status."' WHERE id='".$id."' LIMIT 1");
	    if( $query ){
	      $code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
	      $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=banner_data" />';
	    }else{
	      $code = $this->get_alert(lang('error'), 'danger');
	    }
	  }else{
	    $code = $this->get_alert(lang('empty_id'), 'danger');
	  }

	  return $code;
	}

	function banner_delete(){
	  if(isset($_GET['id']) && intval($_GET['id']) != 0){
	    $id = intval($_GET['id']);

	    $query = $this->DB->N_query("SELECT * FROM banners WHERE id='".$id."' LIMIT 1");
	    $counts = $this->DB->N_num_rows($query);
	    if($counts == 0){
	      $code = $this->get_alert(sprintf(lang('not_fount_id'), $id), 'danger');
	    }else{
	      $row = $this->DB->N_fetch_array($query);
	      $title = text_filter(3, $row['title']);
	      $this->main_title = $title;

	      $this->breadcrumb_parent = array( array('title' => lang('banners'), 'url' => 'index.php?action=banner_data') );

	      $code = '<form name="delete" method="post" action="index.php?action=banner_delete">';
	      $code .= '<input type="hidden" name="post_id" value="'.$id.'" />';
	      $code .= '<input type="hidden" name="delete_post" value="yes" />';
	      $code .= '<input type="hidden" name="token" value="'.generate_form_token('banner_delete_'.$id).'">';
	      $code .= '<input type="submit" value="'.sprintf(lang('delete_sure'), $title).'" name="delete" />';
	      $code .= '</form>';
	    }
	  }else{
	    $post_id = ( isset($_POST['post_id']) ? intval($_POST['post_id']) : 0 );
	    if( verify_form_token('banner_delete_'.$post_id) == false ){
	      $code = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
	    }else{
	      if(isset($_POST['delete_post']) && $_POST['delete_post'] == "yes" && isset($_POST['post_id']) && intval($_POST['post_id']) != 0 ){
	        $post_id = intval($_POST['post_id']);
	        $query =  $this->DB->N_query("DELETE FROM banners WHERE id='".$post_id."' LIMIT 1");
	        if( $query ){
	          $this->DB->N_query("DELETE FROM banner_meta WHERE meta_key='category_id' AND banner_id='".$post_id."'");
	          $code = '<div class="alert alert-success" role="alert">'.lang('success').'</div>';
	          $code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=banner_data" />';
	        }else{
	          $code = $this->get_alert(lang('error'), 'danger');
	        }
	      }else{
	        $code = $this->get_alert(lang('empty_id'), 'danger');
	      }
	    }
	  }

	  return $code;
	}

	function banner_data($limit = 30, $home = 0){
	  $counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM banners") );

	  $page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
	  $page = ($page == 0 ? 1 : $page);
	  $perpage = $limit;
	  $startpoint = ($page * $perpage) - $perpage;

	  $query_d = $this->DB->N_query("SELECT * FROM banners order by id desc LIMIT $startpoint,$perpage");
	  $data_count = $this->DB->N_num_rows($query_d);

	  $text = '';
	  $data = array();

	  if($data_count == 0){
	    $text .= lang('not_found');
	  }else{
	    $text .= '<table class="table table-hover">';
	    $text .= '<thead>';
	    $text .= '<tr>';
	    $text .= '<th scope="col">'.lang('title').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('date').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('status').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('edit').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('delete').'</th>';
	    $text .= '</tr>';
	    $text .= '</thead>';
	    $text .= '<tbody>';
	    $i=0;
	    $arr_money = array();
	    while ($row = $this->DB->N_fetch_array($query_d)){
	      $title = text_filter(3, $row['title']);
	      $image = text_filter(3, $row['image']);
	      $url = text_filter(3, $row['url']);
	      $description = text_filter(3, $row['description']);
	      $notice = text_filter(3, $row['text']);
	      $active = intval($row['active']);
	      $user_id = intval($row['user_id']);

	      $added = date("j/n/Y", $row['date']);

	      $get_image = ( empty($image) ? '' : '<img src="'.$image.'" alt="'.$title.'" class="w-100"> ' );

	      if( $active == 1 ){
	        $get_active = '<a href="index.php?action=banner_status&id='.$row['id'].'&act=0"><i class="fas fa-eye text-success"></i></a>';
	      }else{
	        $get_active = '<a href="index.php?action=banner_status&id='.$row['id'].'&act=1"><i class="fas fa-eye-slash text-danger"></i></a>';
	      }

	      if( empty($get_image) ){
	        $modal = '';
	      }else{
	        $modal = '<span data-toggle="modal" data-target=".banners-modal-'.$row['id'].'"><i class="far fa-image"></i></span> ';
	        $modal .= '<div class="modal fade banners-modal-'.$row['id'].'" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">';
	        $modal .= '<div class="modal-dialog modal-'.$row['id'].'">';
	        $modal .= '<div class="modal-content">'.$get_image.'</div>';
	        $modal .= '</div>';
	        $modal .= '</div>';
	      }

	      ++$i;

	      $text .= '<tr>';
	      $text .= '<td>'.$modal.'<a href="index.php?action=banner_edit&id='.$row['id'].'">'.$title.'</a></td>';
	      $text .= '<td>'.$description.'</td>';
	      $text .= '<td class="text-center">'.$added.'</td>';
	      $text .= '<td class="text-center">'.$get_active.'</td>';
	      $text .= '<td class="text-center"><a href="index.php?action=banner_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a></td>';
	      $text .= '<td class="text-center"><a href="index.php?action=banner_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a></td>';
	      $text .= '</tr>';

	      $data[] = array(
	        'id' => $row['id'],
	        'title' => $title,
	        'image' => $get_image,
	        'url' => $url,
	        'description' => $description,
	        'notice' => $notice,
	        'user_id' => $user_id,
	        'published_date' => $added,
	        'active' => $get_active,
	        'edit' => '<a href="index.php?action=banner_edit&id='.$row['id'].'"><i class="fas fa-edit"></i></a>',
	        'delete' => '<a href="index.php?action=banner_delete&id='.$row['id'].'"><i class="fas fa-trash-alt"></i></a>'
	        );
	    }
	    $text .= '</tbody>';
	    $text .= '</table>';
	    if( $home == 1 ){
	      $text .= '<div class="mt-3"><a href="index.php?action=banner_add">More</a></div>';
	    }else{
	      $text .= pagination($counts, $perpage, $page, 'index.php?action=banner_data&');
	    }

	  }

	  if( $home == 1 ){
	    return $data;
	  }else{
	    return $text;
	  }
	}

	function get_setting($meta_key){
		$query = $this->DB->N_query("SELECT * FROM setting WHERE meta_key='".$meta_key."' LIMIT 1");
		$counts = $this->DB->N_num_rows( $query );
		if( $counts == 0 ){
			$code = '';
		}else{
			$row = $this->DB->N_fetch_array($query);
			$code = text_filter(3, $row['meta_value']);
		}
		return $code;
	}

	function accordion( $data = array() ){
		$accordion_name = ( isset($data['name']) ? $data['name'] : 'accordionExample' );
		if( is_array($data['data']) ){
			$code = '<div class="accordion" id="'.$accordion_name.'">';
			$i=0;
			foreach($data['data'] as $key => $value) {
				$title = ( isset($value['title']) ? $value['title'] : 'None' );
				$text = ( isset($value['text']) ? $value['text'] : 'None' );
				++$i;

				$show = ( $i == 1 ? ' show' : '' );
				$aria = ( $i == 1 ? 'true' : 'false' );

				$code .= '<div class="card">';
				$code .= '<div class="card-header" id="heading_'.$key.'">';
				$code .= '<h4 class="mb-0" data-toggle="collapse" data-target="#collapse_'.$key.'" aria-expanded="'.$aria.'" aria-controls="collapse_'.$key.'">'.$title.'</h4>';
				$code .= '</div>';
				$code .= '<div id="collapse_'.$key.'" class="collapse'.$show.'" aria-labelledby="heading_'.$key.'" data-parent="#'.$accordion_name.'">';
				$code .= '<div class="card-body">'.$text.'</div>';
				$code .= '</div>';
				$code .= '</div>';
			}
			$code .= '</div>';
		}else{
			$code = 'Not Array';
		}
		return $code;
	}

	function setting(){
		$query_c = $this->DB->N_query("SELECT id,title FROM category WHERE active=1 ORDER BY id DESC");
		$post_categories = array();
	  if($this->DB->N_num_rows($query_c) > 0){
	    while($rowc = $this->DB->N_fetch_array($query_c)){
		    $categoryID = intval($rowc['id']);
				$post_categories[$categoryID] = text_filter(3, $rowc['title']);
			}
	  }

		$query_b = $this->DB->N_query("SELECT id,title FROM banners WHERE active=1 ORDER BY id DESC");
		$post_banners = array();
	  if($this->DB->N_num_rows($query_b) > 0){
	    while($rowb = $this->DB->N_fetch_array($query_b)){
		    $bannerID = intval($rowb['id']);
				$post_banners[$bannerID] = text_filter(3, $rowb['title']);
			}
	  }

		$post_types = array( '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10 );
		$switch = array( 0 => lang('no'), 1 => lang('yes') );
		$pdf_format = array( 'A4', 'Letter', 'Legal', 'Executive', 'Folio', 'Demy', 'Royal', 'A', 'B' );
		$pdf_orientation = array( 'P', 'L' );
		$pdf_pages = array( lang('setting_pdf_pages_page'), lang('setting_pdf_pages_text') );
		$pdf_fonts = fonts();

		$general_setting = '<div class="form-group">';
		$general_setting .= '<label for="site_title">'.lang('setting_site_name').'</label>';
		$general_setting .= '<input type="text" class="form-control" id="site_title" name="setting[site_title]" value="'.$this->get_setting('site_title').'">';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="site_slogan">'.lang('setting_site_slogan').'</label>';
		$general_setting .= '<input type="text" class="form-control" id="site_slogan" name="setting[site_slogan]" value="'.$this->get_setting('site_slogan').'">';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="site_description">'.lang('setting_site_description').'</label>';
		$general_setting .= '<input type="text" class="form-control" id="site_description" name="setting[site_description]" value="'.$this->get_setting('site_description').'">';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="site_logo">'.lang('setting_site_logo').'</label>';
		$general_setting .= '<input type="text" style="direction:ltr;" class="form-control" id="site_logo" name="setting[site_logo]" value="'.$this->get_setting('site_logo').'">';
		$general_setting .= '<input type="file" class="form-control" id="upload_image" name="upload_image">';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="site_url">'.lang('setting_site_url').'</label>';
		$general_setting .= '<input type="text" style="direction:ltr;" class="form-control" id="site_url" name="setting[site_url]" value="'.$this->get_setting('site_url').'">';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="site_subscribe_email">'.lang('setting_subscribe_email').'</label>';
		$general_setting .= '<input style="direction:ltr;" type="text" class="form-control" id="site_subscribe_email" name="setting[subscribe_email]" value="'.$this->get_setting('subscribe_email').'">';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="site_subscribe_whasapp">'.lang('setting_subscribe_whasapp').'</label>';
		$general_setting .= '<input style="direction:ltr;" type="text" class="form-control" id="site_subscribe_whasapp" name="setting[subscribe_whasapp]" value="'.$this->get_setting('subscribe_whasapp').'">';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="site_unsubscribe">'.lang('setting_unsubscribe').'</label>';
		$general_setting .= '<input style="direction:ltr;" type="text" class="form-control" id="site_unsubscribe" name="setting[unsubscribe]" value="'.$this->get_setting('unsubscribe').'">';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="site_whatsapp">'.lang('setting_whatsapp_number').'</label>';
		$general_setting .= '<input style="direction:ltr;" type="text" class="form-control" id="site_whatsapp" name="setting[site_whatsapp]" value="'.$this->get_setting('site_whatsapp').'">';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="box_header_code">'.lang('setting_header_code').'</label>';
		$general_setting .= '<textarea style="direction:ltr;" class="form-control" id="box_header_code" rows="3" name="setting[header_code]">'.$this->get_setting('header_code').'</textarea>';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="box_footer_code">'.lang('setting_footer_code').'</label>';
		$general_setting .= '<textarea style="direction:ltr;" class="form-control" id="box_footer_code" rows="3" name="setting[footer_code]">'.$this->get_setting('footer_code').'</textarea>';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="newspaper_name">'.lang('setting_newspaper_name').'</label>';
		$general_setting .= '<select class="form-control" id="newspaper_name" name="setting[newspaper_name]">';
		$general_setting .= '<option value="">- - -</option>';
		foreach ($switch as $key_2 => $value_2) {
			if( $this->get_setting('newspaper_name') == $key_2 ){
				$general_setting .= '<option value="'.$key_2.'" selected>'.$value_2.'</option>';
			}else{
				$general_setting .= '<option value="'.$key_2.'">'.$value_2.'</option>';
			}
		}
		$general_setting .= '</select>';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="close_site">'.lang('setting_close_site').'</label>';
		$general_setting .= '<select class="form-control" id="close_site" name="setting[close_site]">';
		$general_setting .= '<option value="">- - -</option>';
		foreach ($switch as $key_1 => $value_1) {
			if( $this->get_setting('close_site') == $key_1 ){
				$general_setting .= '<option value="'.$key_1.'" selected>'.$value_1.'</option>';
			}else{
				$general_setting .= '<option value="'.$key_1.'">'.$value_1.'</option>';
			}
		}
		$general_setting .= '</select>';
		$general_setting .= '</div>';

		$general_setting .= '<div class="form-group">';
		$general_setting .= '<label for="close_site_cause">'.lang('setting_close_site_cause').'</label>';
		$general_setting .= '<textarea style="direction:ltr;" class="form-control" id="close_site_cause" rows="3" name="setting[close_site_cause]">'.$this->get_setting('close_site_cause').'</textarea>';
		$general_setting .= '</div>';

		$pdf_setting = '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_set_header">'.lang('setting_pdf_set_header').'</label>';
		$pdf_setting .= '<select class="form-control" id="pdf_set_header" name="setting[pdf_set_header]">';
		$pdf_setting .= '<option value="">- - -</option>';
		foreach ($switch as $key_3 => $value_3) {
			if( $this->get_setting('pdf_set_header') == $key_3 ){
				$pdf_setting .= '<option value="'.$key_3.'" selected>'.$value_3.'</option>';
			}else{
				$pdf_setting .= '<option value="'.$key_3.'">'.$value_3.'</option>';
			}
		}
		$pdf_setting .= '</select>';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_set_footer">'.lang('setting_pdf_set_footer').'</label>';
		$pdf_setting .= '<select class="form-control" id="pdf_set_footer" name="setting[pdf_set_footer]">';
		$pdf_setting .= '<option value="">- - -</option>';
		foreach ($switch as $key_4 => $value_4) {
			if( $this->get_setting('pdf_set_footer') == $key_4 ){
				$pdf_setting .= '<option value="'.$key_4.'" selected>'.$value_4.'</option>';
			}else{
				$pdf_setting .= '<option value="'.$key_4.'">'.$value_4.'</option>';
			}
		}
		$pdf_setting .= '</select>';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_format">'.lang('setting_pdf_format').'</label>';
		$pdf_setting .= '<select class="form-control" id="pdf_format" name="setting[pdf_format]">';
		$pdf_setting .= '<option value="">- - -</option>';
		foreach ($pdf_format as $key_5 => $value_5) {
			if( $this->get_setting('pdf_format') == $value_5 ){
				$pdf_setting .= '<option value="'.$value_5.'" selected>'.$value_5.'</option>';
			}else{
				$pdf_setting .= '<option value="'.$value_5.'">'.$value_5.'</option>';
			}
		}
		$pdf_setting .= '</select>';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_margin_left">'.lang('setting_pdf_margin_left').'</label>';
		$pdf_setting .= '<input type="number" class="form-control" id="pdf_margin_left" name="setting[pdf_margin_left]" value="'.$this->get_setting('pdf_margin_left').'">';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_margin_right">'.lang('setting_pdf_margin_right').'</label>';
		$pdf_setting .= '<input type="number" class="form-control" id="pdf_margin_right" name="setting[pdf_margin_right]" value="'.$this->get_setting('pdf_margin_right').'">';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_margin_top">'.lang('setting_pdf_margin_top').'</label>';
		$pdf_setting .= '<input type="number" class="form-control" id="pdf_margin_top" name="setting[pdf_margin_top]" value="'.$this->get_setting('pdf_margin_top').'">';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_margin_bottom">'.lang('setting_pdf_margin_bottom').'</label>';
		$pdf_setting .= '<input type="number" class="form-control" id="pdf_margin_bottom" name="setting[pdf_margin_bottom]" value="'.$this->get_setting('pdf_margin_bottom').'">';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_margin_header">'.lang('setting_pdf_margin_header').'</label>';
		$pdf_setting .= '<input type="number" class="form-control" id="pdf_margin_header" name="setting[pdf_margin_header]" value="'.$this->get_setting('pdf_margin_header').'">';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_margin_footer">'.lang('setting_pdf_margin_footer').'</label>';
		$pdf_setting .= '<input type="number" class="form-control" id="pdf_margin_footer" name="setting[pdf_margin_footer]" value="'.$this->get_setting('pdf_margin_footer').'">';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_orientation">'.lang('setting_pdf_orientation').'</label>';
		$pdf_setting .= '<select class="form-control" id="pdf_orientation" name="setting[pdf_orientation]">';
		$pdf_setting .= '<option value="">- - -</option>';
		foreach ($pdf_orientation as $key_6 => $value_6) {
			if( $this->get_setting('pdf_orientation') == $value_6 ){
				$pdf_setting .= '<option value="'.$value_6.'" selected>'.$value_6.'</option>';
			}else{
				$pdf_setting .= '<option value="'.$value_6.'">'.$value_6.'</option>';
			}
		}
		$pdf_setting .= '</select>';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_pages">'.lang('setting_pdf_pages').'</label>';
		$pdf_setting .= '<select class="form-control" id="pdf_pages" name="setting[pdf_pages]">';
		$pdf_setting .= '<option value="">- - -</option>';
		foreach ($pdf_pages as $key_7 => $value_7) {
			if( $this->get_setting('pdf_pages') == $key_7 ){
				$pdf_setting .= '<option value="'.$key_7.'" selected>'.$value_7.'</option>';
			}else{
				$pdf_setting .= '<option value="'.$key_7.'">'.$value_7.'</option>';
			}
		}
		$pdf_setting .= '</select>';
		$pdf_setting .= '</div>';

		$pdf_setting .= '<div class="form-group">';
		$pdf_setting .= '<label for="pdf_font">'.lang('setting_pdf_font').'</label>';
		$pdf_setting .= '<select class="form-control" id="pdf_font" name="setting[pdf_font]">';
		$pdf_setting .= '<option value="">- - -</option>';
		foreach ($pdf_fonts as $key_8 => $value_8) {
			if( $this->get_setting('pdf_font') == $key_8 ){
				$pdf_setting .= '<option value="'.$key_8.'" selected>'.$value_8.'</option>';
			}else{
				$pdf_setting .= '<option value="'.$key_8.'">'.$value_8.'</option>';
			}
		}
		$pdf_setting .= '</select>';
		$pdf_setting .= '</div>';

		$pdf_box_setting = '';
		for ($pi=1; $pi <= 15; $pi++) {
			$pdf_boxes = '<div class="form-group">';
			$pdf_boxes .= '<label for="pdf_box_category_'.$pi.'">'.lang('setting_box_category').'</label>';
			$pdf_boxes .= '<select class="form-control" id="pdf_box_category_'.$pi.'" name="setting[pdf_box_category_'.$pi.']">';
			$pdf_boxes .= '<option value="">- - -</option>';
			foreach ($post_categories as $key_pdf => $value_pdf) {
				if( $this->get_setting('pdf_box_category_'.$pi) == $key_pdf ){
					$pdf_boxes .= '<option value="'.$key_pdf.'" selected>'.$value_pdf.'</option>';
				}else{
					$pdf_boxes .= '<option value="'.$key_pdf.'">'.$value_pdf.'</option>';
				}
			}
			$pdf_boxes .= '</select>';
			$pdf_boxes .= '</div>';

			$pdf_boxes .= '<div class="form-group">';
			$pdf_boxes .= '<label for="pdf_box_banner_'.$pi.'">'.lang('setting_box_banner').'</label>';
			$pdf_boxes .= '<select class="form-control" id="pdf_box_banner_'.$pi.'" name="setting[pdf_box_banner_'.$pi.']">';
			$pdf_boxes .= '<option value="">- - -</option>';
			foreach ($post_banners as $keybb_pdf => $valuebb_pdf) {
				if( $this->get_setting('pdf_box_banner_'.$pi) == $keybb_pdf ){
					$pdf_boxes .= '<option value="'.$keybb_pdf.'" selected>'.$valuebb_pdf.'</option>';
				}else{
					$pdf_boxes .= '<option value="'.$keybb_pdf.'">'.$valuebb_pdf.'</option>';
				}
			}
			$pdf_boxes .= '</select>';
			$pdf_boxes .= '</div>';

			$pdf_boxes .= '<div class="form-group">';
			$pdf_boxes .= '<label for="pdf_box_limit_'.$pi.'">'.lang('setting_box_limit').'</label>';
			$pdf_boxes .= '<input type="number" class="form-control" id="pdf_box_limit_'.$pi.'" name="setting[pdf_box_limit_'.$pi.']" value="'.$this->get_setting('pdf_box_limit_'.$pi).'">';
			$pdf_boxes .= '</div>';

			$pdf_boxes .= '<div class="form-group">';
			$pdf_boxes .= '<label for="pdf_box_type_'.$pi.'">'.lang('setting_box_type').'</label>';
			$pdf_boxes .= '<select class="form-control" id="pdf_box_type_'.$pi.'" name="setting[pdf_box_type_'.$pi.']">';
			$pdf_boxes .= '<option value="">- - -</option>';
			foreach ($post_types as $keyt_pdf => $valuet_pdf) {
				if( $this->get_setting('pdf_box_type_'.$pi) == $keyt_pdf ){
					$pdf_boxes .= '<option value="'.$keyt_pdf.'" selected>'.$valuet_pdf.'</option>';
				}else{
					$pdf_boxes .= '<option value="'.$keyt_pdf.'">'.$valuet_pdf.'</option>';
				}
			}
			$pdf_boxes .= '</select>';
			$pdf_boxes .= '</div>';

			$pdf_boxes .= '<div class="form-group">';
			$pdf_boxes .= '<label for="pdf_box_code_'.$pi.'">'.lang('setting_box_code').'</label>';
			$pdf_boxes .= '<textarea style="direction:ltr;" class="form-control" id="pdf_box_code_'.$pi.'" rows="3" name="setting[pdf_box_code_'.$pi.']">'.$this->get_setting('pdf_box_code_'.$pi).'</textarea>';
			$pdf_boxes .= '</div>';

			$pdf_box_setting .= $this->get_panel( lang('setting_box').' #'.$pi, $pdf_boxes );
		}

		$home_boxes = '';
		for ($i=1; $i <= 15; $i++) {
			$form_box = '<div class="form-group">';
			$form_box .= '<label for="box_category_'.$i.'">'.lang('setting_box_category').'</label>';
			$form_box .= '<select class="form-control" id="box_category_'.$i.'" name="setting[box_category_'.$i.']">';
			$form_box .= '<option value="">- - -</option>';
			foreach ($post_categories as $key => $value) {
				if( $this->get_setting('box_category_'.$i) == $key ){
					$form_box .= '<option value="'.$key.'" selected>'.$value.'</option>';
				}else{
					$form_box .= '<option value="'.$key.'">'.$value.'</option>';
				}
			}
			$form_box .= '</select>';
			$form_box .= '</div>';

			$form_box .= '<div class="form-group">';
			$form_box .= '<label for="box_banner_'.$i.'">'.lang('setting_box_banner').'</label>';
			$form_box .= '<select class="form-control" id="box_banner_'.$i.'" name="setting[box_banner_'.$i.']">';
			$form_box .= '<option value="">- - -</option>';
			foreach ($post_banners as $keybb => $valuebb) {
				if( $this->get_setting('box_banner_'.$i) == $keybb ){
					$form_box .= '<option value="'.$keybb.'" selected>'.$valuebb.'</option>';
				}else{
					$form_box .= '<option value="'.$keybb.'">'.$valuebb.'</option>';
				}
			}
			$form_box .= '</select>';
			$form_box .= '</div>';

			$form_box .= '<div class="form-group">';
			$form_box .= '<label for="box_limit_'.$i.'">'.lang('setting_box_limit').'</label>';
			$form_box .= '<input type="number" class="form-control" id="box_limit_'.$i.'" name="setting[box_limit_'.$i.']" value="'.$this->get_setting('box_limit_'.$i).'">';
			$form_box .= '</div>';

			$form_box .= '<div class="form-group">';
			$form_box .= '<label for="box_type_'.$i.'">'.lang('setting_box_type').'</label>';
			$form_box .= '<select class="form-control" id="box_type_'.$i.'" name="setting[box_type_'.$i.']">';
			$form_box .= '<option value="">- - -</option>';
			foreach ($post_types as $keyt => $valuet) {
				if( $this->get_setting('box_type_'.$i) == $keyt ){
					$form_box .= '<option value="'.$keyt.'" selected>'.$valuet.'</option>';
				}else{
					$form_box .= '<option value="'.$keyt.'">'.$valuet.'</option>';
				}
			}
			$form_box .= '</select>';
			$form_box .= '</div>';

			$form_box .= '<div class="form-group">';
			$form_box .= '<label for="box_code_'.$i.'">'.lang('setting_box_code').'</label>';
			$form_box .= '<textarea style="direction:ltr;" class="form-control" id="box_code_'.$i.'" rows="3" name="setting[box_code_'.$i.']">'.$this->get_setting('box_code_'.$i).'</textarea>';
			$form_box .= '</div>';

			$home_boxes .= $this->get_panel( lang('setting_box').' #'.$i, $form_box );
		}

		$accordion = array(
			'name' => 'accordion_1',
			'data' => array(
				array( 'title' => '<i class="fas fa-cogs"></i> '.lang('setting_general'), 'text' => $general_setting ),
				array( 'title' => '<i class="fas fa-home"></i> '.lang('setting_home_title'), 'text' => $home_boxes ),
				array( 'title' => '<i class="fas fa-file-pdf"></i> '.lang('setting_pdf'), 'text' => $pdf_setting ),
				array( 'title' => '<i class="fas fa-boxes"></i> '.lang('setting_pdf_home_title'), 'text' => $pdf_box_setting )
			)
		);

		$form = '<form name="add" method="post" action="index.php?action=setting_update" enctype="multipart/form-data">';
		$form .= '<input type="hidden" name="token" value="'.generate_form_token('setting').'">';
		$form .= $this->accordion($accordion);
		$form .= '<button type="submit" class="btn btn-primary mt-3">'.lang('update').'</button>';
		$form .= '</form>';

		return $form;

	}

	function setting_update(){
		$error = array();
		if( isset($_POST['setting']) && count($_POST['setting']) > 0 ){
			if( verify_form_token('setting') == false ){
				$error[] = '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
			}else{
				$upload = upload_files('upload_image', 'logo');

				foreach ($_POST['setting'] as $key => $value) {
					$check = $this->DB->N_num_rows($this->DB->N_query("SELECT * FROM setting WHERE meta_key='".$key."'"));
					if( $key == 'site_logo' && !empty($upload) ){
						$value = $upload;
					}
				  if($check == 0){
						$query = $this->DB->N_query("INSERT INTO setting (`meta_key`, `meta_value`) VALUES ('".$this->DB->N_escape_string($key)."', '".$this->DB->N_escape_string($value)."' )");
						if( !$query ){
							$error[] = '<div class="alert alert-danger" role="alert"><strong>'.strip_tags($key).'</strong> not inserted</div>';
						}
				  }else{
						$query = $this->DB->N_query("UPDATE setting SET meta_value='".$this->DB->N_escape_string($value)."' WHERE meta_key='".$this->DB->N_escape_string($key)."' LIMIT 1");
						if( !$query ){
							$error[] = '<div class="alert alert-danger" role="alert"><strong>'.strip_tags($key).'</strong> not updated</div>';
						}
					}
				}
			}
		}else{
			$error[] = '<div class="alert alert-danger" role="alert">not found elements</div>';
		}

		if($_SERVER['REQUEST_METHOD'] === 'POST'){
			if( is_array($error) && count($error) > 0 ){
				$code = '';
				foreach ($error as $key2 => $value2) {
					$code .= $value2;
				}
			}else{
				$code = '<div class="alert alert-success mb-0" role="alert">'.lang('success').'</div>';
				$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=setting" />';
			}
		}else{
			$code = '';
		}

		return $code;
	}

	function upload_data($limit = 30){
	  $counts = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM upload") );

	  $page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
	  $page = ($page == 0 ? 1 : $page);
	  $perpage = $limit;
	  $startpoint = ($page * $perpage) - $perpage;

	  $query_d = $this->DB->N_query("SELECT * FROM upload order by id desc LIMIT $startpoint,$perpage");
	  $data_count = $this->DB->N_num_rows($query_d);

	  $text = '';

	  if($data_count == 0){
	    $text .= lang('not_found');
	  }else{
	    $text .= '<table class="table table-hover mt-3">';
	    $text .= '<thead>';
	    $text .= '<tr>';
	    $text .= '<th scope="col">'.lang('title').'</th>';
	    $text .= '<th scope="col" class="text-center">'.lang('date').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('size').'</th>';
			$text .= '<th scope="col" class="text-center">'.lang('mime').'</th>';
	    $text .= '</tr>';
	    $text .= '</thead>';
	    $text .= '<tbody>';
	    $i=0;
	    while ($row = $this->DB->N_fetch_array($query_d)){
	      $title = text_filter(3, $row['title']);
	      $url = text_filter(3, $row['url']);
	      $size = text_filter(3, $row['size']);
	      $type = text_filter(3, $row['type']);
				$mime = text_filter(3, $row['mime']);
	      $user_id = intval($row['user_id']);
	      $added = date("j/n/Y", $row['date']);

	      ++$i;

	      $text .= '<tr>';
	      $text .= '<td><a target="_blank" href="'.$url.'">'.$title.'</a></td>';
	      $text .= '<td class="text-center">'.$added.'</td>';
	      $text .= '<td class="text-center">'.$size.'</td>';
	      $text .= '<td class="text-center">'.$mime.'</td>';
	      $text .= '</tr>';

	    }
	    $text .= '</tbody>';
	    $text .= '</table>';
			$text .= pagination($counts, $perpage, $page, 'index.php?action=upload&');
	  }

    return $text;
	}

	function upload(){

		$dir_dest = $this->dir_dest;
		$dir_pics = $this->dir_pics;
		$allowfiles = $this->allowfiles;

		if(isset($_GET['type']) && $_GET['type'] == 1){
		$form = '<form name="form1" enctype="multipart/form-data" method="post" action="index.php?action=uploading">
		<div class="form-group">
	<input type="file" class="form-control-file" id="FormControlFile" name="upload_file">
</div>
		<button class="btn btn-secondary" type="submit">'.lang('submit_upload').'</button>
			</form>';
		}elseif(isset($_GET['type']) && $_GET['type'] == 2){
		$form = '<form name="form5" enctype="multipart/form-data" method="post" action="index.php?action=uploading" />
			<div class="inputs">
				<p><input type="file" size="32" name="upload_file" value="" id="dnd_field" /></p>
		    </div>

			<div id="dnd_drag">... '.lang('upload').' ...</div>
			<div id="dnd_status"></div>

			<div class="submitform">
				<p><input type="submit" name="Submit" value="'.lang('submit_upload').'" id="dnd_upload"/></p>
		    </div>
		</form>
		<div id="dnd_result"></div>';
		}elseif(isset($_GET['type']) && $_GET['type'] == 3){
		$form = '<form name="form3" enctype="multipart/form-data" method="post" action="index.php?action=multiple">
			<div class="inputs">
				<p><input type="file" size="32" name="upload_file[]" value="" /></p>
				<p><input type="file" size="32" name="upload_file[]" value="" /></p>
				<p><input type="file" size="32" name="upload_file[]" value="" /></p>
				<p><input type="file" size="32" name="upload_file[]" value="" /></p>
				<p><input type="file" size="32" name="upload_file[]" value="" /></p>
			</div>

			<div class="submitform">
				<p><input type="submit" name="Submit" value="'.lang('submit_upload').'" /></p>
			</div>
		        </form>';
		}else{
			$form = '<form name="form1" enctype="multipart/form-data" method="post" action="index.php?action=uploading">
			<div class="form-group">
		<input type="file" class="form-control-file" id="FormControlFile" name="upload_file">
	</div>
			<button class="btn btn-secondary" type="submit">'.lang('submit_upload').'</button>
				</form>';
		}

		$code = $form;
/*
		$get_allowfiles = '<ul>';
		foreach($allowfiles as $k){
			$get_allowfiles .= '<li>'.$k.'</li>';
		}
		$get_allowfiles .= '</ul>';
		//$code .= '<p>Folder: '.$dir_dest.'</p>';
		$code .= '<p class="mt-3">'.lang('allowed').':'.$get_allowfiles.'</p>';
*/
		$code .= $this->upload_data(30);

		return $code;
	}

	function uploading(){
		$dir_dest = $this->dir_dest;
		$dir_pics = $this->dir_pics;
		$allowfiles = $this->allowfiles;
		$url_site = str_replace('/cp', '', base_url());
		$rename_file = 'upload_'.time().'_'.rand_str(15);
		$input_name = 'upload_file';
		if( isset($_SESSION['user_id']) ){
			$user_id = intval($_SESSION['user_id']);
		}else{
			$user_id = 0;
		}
		$date = time();

		if( isset( $_FILES[$input_name] ) && isset($_FILES[$input_name]['name']) && !empty($_FILES[$input_name]['name']) ){
			$handle = new \Verot\Upload\Upload($_FILES[$input_name]);
			$handle->allowed = $allowfiles;
			$handle->file_new_name_body = $rename_file;
			$handle->Process($dir_dest);

			if ($handle->processed) {
				$info = getimagesize($handle->file_dst_pathname);

				$file_src_name = ( isset($handle->file_src_name) ? $handle->file_src_name : 'None' ); //pdf-section-articles.jpg
				$file_src_name_body = ( isset($handle->file_src_name_body) ? $handle->file_src_name_body : 'None' ); //pdf-section-articles
				$file_src_name_ext = ( isset($handle->file_src_name_ext) ? $handle->file_src_name_ext : 'None' ); //jpg
				$file_src_mime = ( isset($handle->file_src_mime) ? $handle->file_src_mime : 'None' ); //image/jpeg
				$file_src_size = ( isset($handle->file_src_size) ? $handle->file_src_size : 'None' ); //10935
				$file_dst_name = ( isset($handle->file_dst_name) ? $handle->file_dst_name : 'None' ); //upload_1566288509_PWrslbGhHOYBL2o.jpg
				$file_dst_pathname = ( isset($handle->file_dst_pathname) ? $handle->file_dst_pathname : 'None' ); //../upload\upload_1566288509_PWrslbGhHOYBL2o.jpg

				$full_path = $url_site.'/'.$dir_pics.'/'.$file_dst_name;

				$query = $this->DB->N_query("INSERT INTO upload (`title`, `url`, `size`, `type`, `mime`, `user_id`, `date`) VALUES ('".$this->DB->N_escape_string($file_src_name_body)."', '".$this->DB->N_escape_string($full_path)."', '".$this->DB->N_escape_string($file_src_size)."', '".$this->DB->N_escape_string($file_src_name_ext)."', '".$file_src_mime."', '".$user_id."', '".$date."' )");
				if( $query ){
					$insert_id = ( $this->DB->N_insert_id() == 0 ? $this->DB->last_N_insert_id('upload', 'id') : $this->DB->N_insert_id() );

					$code = '<div class="alert alert-success" role="alert">'.lang('added').'</div>';
					$code .= '<div class="form-group row">';
					$code .= '<label for="staticEmail" class="col-sm-2 col-form-label">Link</label>';
					$code .= '<div class="col-sm-10">';
					$code .= '<input type="text" readonly class="form-control-plaintext" id="staticEmail" value="'.$full_path.'">';
					$code .= '</div>';
					$code .= '</div>';

					$code .= '<div class="form-group row">';
					$code .= '<label for="staticEmail" class="col-sm-2 col-form-label">Mime</label>';
					$code .= '<div class="col-sm-10">';
					$code .= '<input type="text" readonly class="form-control-plaintext" id="staticEmail" value="'.$file_src_mime.'">';
					$code .= '</div>';
					$code .= '</div>';

					$code .= '<div class="form-group row">';
					$code .= '<label for="staticEmail" class="col-sm-2 col-form-label">Size</label>';
					$code .= '<div class="col-sm-10">';
					$code .= '<input type="text" readonly class="form-control-plaintext" id="staticEmail" value="'.$file_src_size.'">';
					$code .= '</div>';
					$code .= '</div>';

					$code .= '<a class="btn btn-primary" href="index.php?action=upload" role="button">'.lang('new_upload').'</a>';

					//$code .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php?action=upload" />';
				}else{
					$code .= '<div class="alert alert-danger" role="alert">'.lang('not_added').'</div>';
				}

			}else{
				$code = '<div>';
				$code .= '<p>Error: '.$handle->error.'</p>';
				$code .= '</div>';
			}
		}else{
			$code = '<p>Error</p>';
		}

		return $code;
	}

	function output(){
		$output = '';
		if( isset($_SESSION['user_id']) AND isset($_SESSION['pass']) AND $_SESSION['timeout'] > time() ){
			$get_password = str_replace(get_start_password(), "", strip_tags($_SESSION['pass']));

			$check_username = $this->check_user( intval($_SESSION['user_id']), $get_password );
			$msg = ( isset($check_username['msg']) ? $check_username['msg'] : 'error' );
			$user_id = ( isset($check_username['user_id']) ? $check_username['user_id'] : 'error' );
			$get_username = ( isset($check_username['username']) ? $check_username['username'] : 'error' );
			$get_pass = ( isset($check_username['password']) ? $check_username['password'] : 'error' );
			$get_email = ( isset($check_username['email']) ? $check_username['email'] : 'error' );
			$get_name = ( isset($check_username['name']) ? $check_username['name'] : $get_username );

			if( $user_id == $_SESSION['user_id'] && $get_pass == $get_password ){
				$this->welcome = $get_name;

				$action = ( isset($_GET['action']) ? strip_tags($_GET['action']) : 'home' );
				$mainTitle = ( empty($this->main_title) ? '' : ' ['.$this->main_title.']' );

				if( $action == 'add' ){
					$this->page_title = lang('add');
					$output .= $this->get_panel( $this->page_title, $this->add() );
				}elseif( $action == 'edit' ){
					$this->page_title = lang('edit');
					$output .= $this->get_panel( $this->page_title, $this->add() );
				}elseif( $action == 'insert' ){
					$this->page_title = lang('insert');
					$output .= $this->get_panel( $this->page_title, $this->insert() );
				}elseif( $action == 'update' ){
					$this->page_title = lang('update');
					$output .= $this->get_panel( $this->page_title, $this->update() );
				}elseif( $action == 'data' ){
					$this->page_title = lang('data');
					$output .= $this->get_panel( $this->page_title, $this->loop() );
				}elseif( $action == 'delete' ){
					$this->page_title = 'Delete';
					$output .= $this->get_panel( 'Delete', $this->delete() );
				}elseif( $action == 'newspaper_add' ){
						$this->page_title = lang('newspaper_add');
						$output .= $this->get_panel( $this->page_title, $this->newspaper_add() );
				}elseif( $action == 'newspaper_edit' ){
					$edit_form = $this->newspaper_edit();
					$this->page_title = lang('edit');
					$output .= $this->get_panel( $this->page_title.$mainTitle, $edit_form );
				}elseif( $action == 'newspaper_insert' ){
					$this->page_title = lang('insert');
					$output .= $this->get_panel( $this->page_title, $this->newspaper_insert() );
				}elseif( $action == 'newspaper_update' ){
					$this->page_title = lang('update');
					$output .= $this->get_panel( $this->page_title, $this->newspaper_update() );
				}elseif( $action == 'newspaper_status' ){
					$this->page_title = lang('status');
					$output .= $this->get_panel( $this->page_title, $this->newspaper_status() );
				}elseif( $action == 'newspaper_delete' ){
					$delete_check = $this->newspaper_delete();
					$this->page_title = lang('delete');
					$output .= $this->get_panel( $this->page_title.$mainTitle, $delete_check );
				}elseif( $action == 'newspaper_data' ){
					$this->page_title = lang('newspapers');
					$output .= $this->get_panel( $this->page_title.'<div class="new-add-text"><a class="btn btn-primary btn-sm" href="index.php?action=newspaper_add" role="button"><i class="fas fa-plus-square"></i> '.lang('newspaper_add').'</a></div>', $this->newspaper_data() );
				}elseif( $action == 'news_add' ){
					$this->page_title = lang('news_add');
					$output .= $this->get_panel( $this->page_title, $this->news_add() );
				}elseif( $action == 'news_duplicate' ){
					$this->page_title = lang('add_duplicate');
					$output .= $this->get_panel( $this->page_title, $this->news_duplicate() );
				}elseif( $action == 'news_edit' ){
					$edit_form = $this->news_edit();
					$this->page_title = lang('edit');
					$output .= $this->get_panel( $this->page_title.$mainTitle, $edit_form );
				}elseif( $action == 'news_insert' ){
					$this->page_title = lang('insert');
					$output .= $this->get_panel( $this->page_title, $this->news_insert() );
				}elseif( $action == 'news_update' ){
					$this->page_title = lang('update');
					$output .= $this->get_panel( $this->page_title, $this->news_update() );
				}elseif( $action == 'news_status' ){
					$this->page_title = lang('status');
					$output .= $this->get_panel( $this->page_title, $this->news_status() );
				}elseif( $action == 'news_delete' ){
					$delete_check = $this->news_delete();
					$this->page_title = lang('delete');
					$output .= $this->get_panel( $this->page_title.$mainTitle, $delete_check );
				}elseif( $action == 'news_data' ){
					$this->page_title = lang('news');
					$output .= $this->get_panel( $this->page_title.'<div class="new-add-text"><a class="btn btn-primary btn-sm" href="index.php?action=news_add" role="button"><i class="fas fa-plus-square"></i> '.lang('news_add').'</a></div>', $this->news_data() );
				}elseif( $action == 'news_category' ){
					$news_data_by_category = $this->news_data_by_category();
					$output .= $this->get_panel( $this->page_title, $news_data_by_category );
				}elseif( $action == 'news_newspaper' ){
					$news_data_by_newspaper = $this->news_data_by_newspaper();
					$output .= $this->get_panel( $this->page_title, $news_data_by_newspaper );
				}elseif( $action == 'news_order' ){
					$output .= $this->get_panel( $this->page_title, $this->news_order() );
				}elseif( $action == 'news_order_update' ){
				  $this->page_title = lang('update');
				  $output .= $this->get_panel( $this->page_title, $this->news_order_update() );

				}elseif( $action == 'banner_add' ){
				  $this->page_title = lang('banners_add');
				  $output .= $this->get_panel( $this->page_title, $this->banner_add() );
				}elseif( $action == 'banner_edit' ){
				  $edit_form = $this->banner_edit();
				  $this->page_title = lang('edit');
				  $output .= $this->get_panel( $this->page_title.$mainTitle, $edit_form );
				}elseif( $action == 'banner_insert' ){
				  $this->page_title = lang('insert');
				  $output .= $this->get_panel( $this->page_title, $this->banner_insert() );
				}elseif( $action == 'banner_update' ){
				  $this->page_title = lang('update');
				  $output .= $this->get_panel( $this->page_title, $this->banner_update() );
				}elseif( $action == 'banner_status' ){
				  $this->page_title = lang('status');
				  $output .= $this->get_panel( $this->page_title, $this->banner_status() );
				}elseif( $action == 'banner_delete' ){
				  $delete_check = $this->banner_delete();
				  $this->page_title = lang('delete');
				  $output .= $this->get_panel( $this->page_title.$mainTitle, $delete_check );
				}elseif( $action == 'banner_data' ){
				  $this->page_title = lang('banners');
				  $output .= $this->get_panel( $this->page_title.'<div class="new-add-text"><a class="btn btn-primary btn-sm" href="index.php?action=banner_add" role="button"><i class="fas fa-plus-square"></i> '.lang('banners_add').'</a></div>', $this->banner_data() );

				}elseif( $action == 'publications_add' ){
				  $this->page_title = lang('publications_add');
				  $output .= $this->get_panel( $this->page_title, $this->publications_add() );
				}elseif( $action == 'publications_edit' ){
				  $edit_form = $this->publications_edit();
				  $this->page_title = lang('edit');
				  $output .= $this->get_panel( $this->page_title.$mainTitle, $edit_form );
				}elseif( $action == 'publications_insert' ){
				  $this->page_title = lang('insert');
				  $output .= $this->get_panel( $this->page_title, $this->publications_insert() );
				}elseif( $action == 'publications_update' ){
				  $this->page_title = lang('update');
				  $output .= $this->get_panel( $this->page_title, $this->publications_update() );
				}elseif( $action == 'publications_status' ){
				  $this->page_title = lang('status');
				  $output .= $this->get_panel( $this->page_title, $this->publications_status() );
				}elseif( $action == 'publications_delete' ){
				  $delete_check = $this->publications_delete();
				  $this->page_title = lang('delete');
				  $output .= $this->get_panel( $this->page_title.$mainTitle, $delete_check );
				}elseif( $action == 'publications_data' ){
				  $this->page_title = lang('publications');
				  $output .= $this->get_panel( $this->page_title, $this->publications_data() );

				}elseif( $action == 'category_add' ){
				  $this->page_title = lang('category_add');
				  $output .= $this->get_panel( $this->page_title, $this->category_add() );
				}elseif( $action == 'category_edit' ){
				  $edit_form = $this->category_edit();
				  $this->page_title = lang('edit');
				  $output .= $this->get_panel( $this->page_title.$mainTitle, $edit_form );
				}elseif( $action == 'category_insert' ){
				  $this->page_title = lang('insert');
				  $output .= $this->get_panel( $this->page_title, $this->category_insert() );
				}elseif( $action == 'category_update' ){
				  $this->page_title = lang('update');
				  $output .= $this->get_panel( $this->page_title, $this->category_update() );
				}elseif( $action == 'category_status' ){
				  $this->page_title = lang('status');
				  $output .= $this->get_panel( $this->page_title, $this->category_status() );
				}elseif( $action == 'category_delete' ){
				  $delete_check = $this->category_delete();
				  $this->page_title = lang('delete');
				  $output .= $this->get_panel( $this->page_title.' ['.$this->main_title.']', $delete_check );
				}elseif( $action == 'category_data' ){
				  $this->page_title = lang('categories');
				  $output .= $this->get_panel( $this->page_title.'<div class="new-add-text"><a class="btn btn-primary btn-sm" href="index.php?action=category_add" role="button"><i class="fas fa-plus-square"></i> '.lang('category_add').'</a></div>', $this->category_data() );

				}elseif( $action == 'user_add' ){
				  $this->page_title = lang('user_add');
				  $output .= $this->get_panel( $this->page_title, $this->user_add() );
				}elseif( $action == 'user_edit' ){
				  $edit_form = $this->user_edit();
				  $this->page_title = lang('edit');
				  $output .= $this->get_panel( $this->page_title.$mainTitle, $edit_form );
				}elseif( $action == 'user_insert' ){
				  $this->page_title = lang('insert');
				  $output .= $this->get_panel( $this->page_title, $this->user_insert() );
				}elseif( $action == 'user_update' ){
				  $this->page_title = lang('update');
				  $output .= $this->get_panel( $this->page_title, $this->user_update() );
				}elseif( $action == 'user_status' ){
				  $this->page_title = lang('status');
				  $output .= $this->get_panel( $this->page_title, $this->user_status() );
				}elseif( $action == 'user_delete' ){
				  $delete_check = $this->user_delete();
				  $this->page_title = lang('delete');
				  $output .= $this->get_panel( $this->page_title.$mainTitle, $delete_check );
				}elseif( $action == 'user_data' ){
				  $this->page_title = lang('users');
				  $output .= $this->get_panel( $this->page_title.'<div class="new-add-text"><a class="btn btn-primary btn-sm" href="index.php?action=user_add" role="button"><i class="fas fa-plus-square"></i> '.lang('user_add').'</a></div>', $this->user_data() );

				}elseif( $action == 'setting' ){
				  $this->page_title = lang('setting');
				  $output .= $this->get_panel( $this->page_title, $this->setting() );
				}elseif( $action == 'setting_update' ){
				  $this->page_title = lang('setting');
				  $output .= $this->get_panel( $this->page_title, $this->setting_update() );

				}elseif( $action == 'upload' ){
				  $this->page_title = lang('upload');
				  $output .= $this->get_panel( $this->page_title, $this->upload() );
				}elseif( $action == 'uploading' ){
				  $this->page_title = lang('upload');
				  $output .= $this->get_panel( $this->page_title, $this->uploading() );

				}elseif( $action == 'out' ){
					//$_SESSION = array();
					unset($_SESSION['user_id']);
					unset($_SESSION['user']);
					unset($_SESSION['pass']);
					unset($_SESSION['timeout']);
					session_destroy();
					$output .= '<div class="alert alert-success" role="alert">'.lang('sign_out').'</div>';
					$output .= '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=index.php" />';
				}else{
					$this->page_title = lang('summary');

					$count_category = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM category") );
					$count_newspaper = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM newspaper") );
					$count_news = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM news") );

					$limit_news = 10;
					$arr_news = $this->news_data($limit_news, 1);
					if( is_array($arr_news) ){
						$news = '<table class="table table-hover my-4">';
						$news .= '<thead>';
						$news .= '<tr>';
						$news .= '<th scope="col" colspan="6" class="text-center">'.sprintf( lang('last_news'), $limit_news ).'</th>';
						$news .= '</tr>';
						$news .= '</thead>';
						$news .= '<thead>';
						$news .= '<tr>';
						$news .= '<th scope="col">'.lang('title').'</th>';
						$news .= '<th scope="col" class="text-center">'.lang('newspaper').'</th>';
						$news .= '<th scope="col" class="text-center">'.lang('date').'</th>';
						$news .= '<th scope="col" class="text-center">'.lang('status').'</th>';
						$news .= '<th scope="col" class="text-center">'.lang('edit').'</th>';
						$news .= '<th scope="col" class="text-center">'.lang('delete').'</th>';
						$news .= '</tr>';
						$news .= '</thead>';
						$news .= '<tbody>';
						foreach ( $arr_news as $key => $value ) {
							$news .= '<tr>';
							$news .= '<td><a href="index.php?action=news_edit&id='.$value['id'].'">'.$value['title'].'</a></td>';
							$news .= '<td class="text-center">'.$value['newspaper_name'].'</td>';
							$news .= '<td class="text-center">'.$value['published_date'].'</td>';
							$news .= '<td class="text-center">'.$value['active'].'</td>';
							$news .= '<td class="text-center">'.$value['edit'].'</td>';
							$news .= '<td class="text-center">'.$value['delete'].'</td>';
							$news .= '</tr>';
						}
						$news .= '</tbody>';
						$news .= '</table>';
					}else{
						$news = '';
					}

					$code = '<p class="text-center"><a class="btn btn-secondary" href="'.$this->get_setting('site_url').'index.php?read=pdf"><i class="fas fa-file-pdf"></i> '.lang('download_publication').'</a> <a class="btn btn-secondary" href="'.$this->get_setting('site_url').'index.php?read=pdf&online=1"><i class="fas fa-eye"></i> '.lang('show_publication').'</a></p>';
					$code .= $news;

					$code .= '<div class="row text-center mt-3">';
					$code .= '<div class="col-12 col-md-4">';
					$code .= '<h2><a href="index.php?action=category_data">'.lang('categories').'</a></h2>';
					$code .= '<p>'.$count_category.'</p>';
					$code .= '<p><a class="btn btn-secondary" href="index.php?action=category_add" role="button">'.lang('category_add').'</a></p>';
					$code .= '</div>';
					$code .= '<div class="col-12 col-md-4">';
					$code .= '<h2><a href="index.php?action=newspaper_data">'.lang('newspapers').'</a></h2>';
					$code .= '<p>'.$count_newspaper.'</p>';
					$code .= '<p><a class="btn btn-secondary" href="index.php?action=newspaper_add" role="button">'.lang('newspaper_add').'</a></p>';
					$code .= '</div>';
					$code .= '<div class="col-12 col-md-4">';
					$code .= '<h2><a href="index.php?action=news_data">'.lang('news').'</a></h2>';
					$code .= '<p>'.$count_news.'</p>';
					$code .= '<p><a class="btn btn-secondary" href="index.php?action=news_add" role="button">'.lang('news_add').'</a></p>';
					$code .= '</div>';
					$code .= '</div>';


					$output .= $this->get_panel($this->page_title, $code);
				}
				echo $this->get_header();
				echo $output;
				echo $this->get_footer();
			}else{
				echo $this->get_header(1);
				echo '<p>'.lang('not_login').'</p>';
				unset($_SESSION['user_id']);
				unset($_SESSION['user']);
				unset($_SESSION['pass']);
				unset($_SESSION['timeout']);
				session_destroy();
				echo $this->login_form();
				echo $this->get_footer(1);
			}
		}else{
			echo $this->get_header(1);

			$login_report = '';
			$username = '';
			$password = '';
			$password_md5 = '';
			$current_password = '';
			$timeout = 86400; // one day

			$errors = array();

			if( isset($_POST['login']) && $_POST['login'] == 'ok_'.login_salt() ){

				if( verify_form_token() == false ){
					echo '<div class="alert alert-danger" role="alert">'.lang('token_not_verify').'</div>';
				}else{
					if( isset($_POST['username']) && isset($_POST['password']) ){
						$username = text_filter(1, $_POST['username']);
						$password = text_filter(1, $_POST['password']);
						$password_md5 = md5($password);
						$current_password = get_start_password().$password_md5;
						$check_username = $this->DB->N_num_rows( $this->DB->N_query("SELECT id FROM users WHERE (username='".$username."' AND password='".$password_md5."' AND active=1) OR (email='".$username."' AND password='".$password_md5."' AND active=1)") );

						if( $check_username == 0 ){
							$errors[] = '<li>'.lang('validate_username_valid').'</li>';
						}

						if( trim($username) == "" ){
							$errors[] = '<li>'.lang('validate_username').'</li>';
						}

						if( trim($password) == "" ){
							$errors[] = '<li>'.lang('validate_password').'</li>';
						}
					}else{
						$errors[] = '<li>'.lang('validate_not_found').'</li>';
					}

					if( count($errors) > 0 ){
						echo '<div class="alert alert-danger" role="alert">';
						echo '<ul>';
						foreach($errors as $val){
							echo $val;
						}
						echo '</ul>';
						echo '</div>';
						echo $this->login_form();
					}else{
						$query = $this->DB->N_query("SELECT id,name,username,email,user_group FROM users WHERE (username='".$username."' AND password='".$password_md5."' AND active=1) OR (email='".$username."' AND password='".$password_md5."' AND active=1) LIMIT 1");
						$counts = $this->DB->N_num_rows( $query );
						if( $counts == 0 ){
							echo '<div class="alert alert-danger" role="alert">'.lang('validate_not_found').'</div>';
						}else{
							$row = $this->DB->N_fetch_array($query);
							$user_id = intval($row['id']);
							$name = text_filter(3, $row['name']);
							$username = text_filter(3, $row['username']);
							$email = text_filter(3, $row['email']);
							$group = intval($row['user_group']);
							$session_ids = session_id();
							//$_SESSION["user"] = $username;
							$_SESSION["user_id"] = $user_id;
							$_SESSION["pass"] = $current_password;
							$_SESSION['timeout'] = time()+$timeout;
							echo '<div class="alert alert-success" role="alert">'.lang('validate_success').'</div>';
							echo "<META HTTP-EQUIV='refresh' CONTENT='0; URL=index.php?action=home'>";
						}
					}
				}
			}else{
				echo $this->login_form();
			}

			echo $this->get_footer(1);
		}
	}
}
?>
