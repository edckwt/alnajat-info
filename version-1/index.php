<?php
include('includes/function.php');

$acton = ( isset($_GET['action']) ? strip_tags($_GET['action']) : 'home' );

if( $acton == 'news' ){
  $panel = $template->tpl_panel(lang('news'), get_news(), 0);
  echo $template->tpl_body($panel);
}elseif( $acton == 'show' ){
  echo $template->tpl_body( news_show() );
}elseif( $acton == 'category' ){
  $category_id = ( isset($_GET['id']) && intval($_GET['id']) != 0 ? intval($_GET['id']) : 0 );
  echo $template->tpl_body( get_news($category_id, 15, 1, 7) );
}elseif( $acton == 'search' ){
  $form = $template->search();
  echo $template->tpl_body( $form.get_news(0, 10, 1, 7) );
}elseif( $acton == 'publication' ){
  echo $template->tpl_body( get_publication() );
}elseif( $acton == 'publications' ){
  echo $template->tpl_body( get_publications() );
}else{
  $all = ( isset($_GET['all']) ? 1 : 0 );
  if( $all == 1 ){
  	$publisheDate = '';
  }else{
  	$get_published_date = ( isset($_GET['date']) ? strip_tags($_GET['date']) : '' );
  	if( !empty($get_published_date) && preg_match("/(\d{4})-(\d{2})-(\d{2})$/", $get_published_date ) ){
  		$publisheDate = $get_published_date;
  	}else{
  		$publisheDate = date("Y-m-d", time());
  	}
  }

  $news = '';
  for ($i=1; $i <= 15; $i++) {
    $box_category_id = intval(setting('box_category_'.$i));
    $box_limit = intval(setting('box_limit_'.$i));
    $box_type = intval(setting('box_type_'.$i));
    $box_banner_id = intval(setting('box_banner_'.$i));
    $box_code = setting('box_code_'.$i);

    $query_banner = $DB->N_query("SELECT * FROM banners WHERE id='".$box_banner_id."' AND active=1 LIMIT 1");
    $counts_banner = $DB->N_num_rows($query_banner);
    if($counts_banner == 0){
      $banner = '';
    }else{
      $row_banner = $DB->N_fetch_array($query_banner);
      $banner_name = text_filter(3, $row_banner['title']);
      $banner_url = text_filter(3, $row_banner['url']);
      $banner_image = text_filter(3, $row_banner['image']);
      $banner_description = text_filter(3, $row_banner['description']);
      $banner_text = text_filter(2, $row_banner['text']);
      $banner = '<div class="banner_content">';
      $banner .= '<a href="'.$banner_url.'"><img target="_blank" src="'.$banner_image.'" alt="'.$banner_name.'" class="w-100 mt-3"></a>';
      if( !empty($banner_description) ){
        $banner .= '<p>'.$banner_description.'</p>';
      }
      if( !empty($banner_text) ){
        $banner .= $banner_text;
      }
      $banner .= '</div>';
    }

    $query_category = $DB->N_query("SELECT id,title FROM category WHERE id='".$box_category_id."' AND active=1 LIMIT 1");
    $counts_category = $DB->N_num_rows($query_category);
    if($counts_category == 0){
      $category_name = '';
      $category_url = '';
    }else{
      $row_category = $DB->N_fetch_array($query_category);
      $category_name = text_filter(3, $row_category['title']);
      $category_url = '<a href="'.url( array('action' => 'category', 'id' => $box_category_id ) ).'">'.$category_name.'</a>';
    }

    if( empty($banner) ){
      $news .= ( empty($box_code) ? get_news( $box_category_id, $box_limit, 0, $box_type, $publisheDate ) : $box_code );
    }else{
      $news .= $banner;
    }
  }
  if( isset($_GET['read']) && $_GET['read'] == 'pdf' ){
    ini_set("max_execution_time", "-1");
    ini_set("memory_limit", "-1");
    ignore_user_abort(true);
    set_time_limit(0);
    create_pdf('PDF', '', 'Alnajat-News', 1);
  }else{
    echo $template->tpl_body( $news, 1 );
  }
}

?>
