<?php
if( ! function_exists('setting') ){
	return null;
}

function getFile( $name = '' ){
	if( empty($name) ){
		return '';
	}else{
		$site_url = site_url();
		$url = $site_url.$name;
		$text = file_get_contents($url);
		return str_replace('{site_url}', $site_url, $text);
	}
}

define('_MPDF_TTFONTPATH', __DIR__ . '/custom-fonts');

require_once __DIR__ . '/vendor/autoload.php';

$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

function create_pdf_css(){
	if( pdfVersion() == 1 ){
		$css = '<style type="text/css">'.getFile( 'css/pdf-v1.css' ).'</style>';
	}else{
		$css = '<style type="text/css">'.getFile( 'css/pdf-v2.css' ).'</style>';
	}
	return $css;
}

function fonts(){
  return array(
		'tajawal' => 'Tajawal',
		'frutiger' => 'Frutiger',
		'awanzamanth' => 'Awanzamanth',
		'stc' => 'STC',
		'swissracondensed' => 'Swissracondensed',
		'al-jazeera-arabic-regular' => 'Aljazeera',
		'bahij-insan' => 'Bahij',
		'ae-almateen-bold' => 'Almateen',
		'harf-fannan' => 'Fannan'
	);
}

$pdf_font = setting('pdf_font');

$mpdf = new \Mpdf\Mpdf([
	'mode' => 'utf-8',
	'autoArabic' => true,
	//'autoScriptToLang' => true,
	//'autoLangToFont' => true,
	'fontDir' => array_merge($fontDirs, [
		_MPDF_TTFONTPATH,
	]),
	'format' => ( empty(setting('pdf_format')) ? 'A4' : setting('pdf_format') ),
	'margin_left' => ( empty(setting('pdf_margin_left')) ? 0 : setting('pdf_margin_left') ),
	'margin_right' => ( empty(setting('pdf_margin_right')) ? 0 : setting('pdf_margin_right') ),
	'margin_top' => ( empty(setting('pdf_margin_top')) ? 0 : setting('pdf_margin_top') ),
	'margin_bottom' => ( empty(setting('pdf_margin_bottom')) ? 0 : setting('pdf_margin_bottom') ),
	'margin_header' => ( empty(setting('pdf_margin_header')) ? 0 : setting('pdf_margin_header') ),
	'margin_footer' => ( empty(setting('pdf_margin_footer')) ? 0 : setting('pdf_margin_footer') ),
	'orientation' => ( empty(setting('pdf_orientation')) ? 'P' : setting('pdf_orientation') ),
	   'fontDir' => array_merge($fontDirs, [
		    _MPDF_TTFONTPATH,
	   ]),
	   'fontdata' => $fontData + [
		  'tajawal' => [
	  		'R' => 'tajawal-regular.ttf',
	  		'B' => 'tajawal-bold.ttf',
		    'useOTL' => 0xFF,
		    'useKashida' => 75,
	  	],
			'avenir' => [
	  		'R' => 'Avenir.ttc',
	  		'B' => 'Avenir.ttc',
		    'useOTL' => 0xFF,
		    'useKashida' => 75,
	  	],
	    'frutiger' => [
	  		'R' => 'frutiger-lt-arabic-55-roman.ttf',
	  		'B' => 'frutiger-lt-arabic-65-bold.ttf',
		    'useOTL' => 0xFF,
		    'useKashida' => 75,
	  	],
	    'awanzamanth' => [
	  		'R' => 'awanzamanth.ttf',
		    'useOTL' => 0xFF,
		    'useKashida' => 75,
	  	],
	    'al-jazeera-arabic-regular' => [
	      'R' => 'al-jazeera-arabic-regular.ttf',
	      'useOTL' => 0xFF,
		    'useKashida' => 75,
	  	],
	    'bahij-insan' => [
	      'R' => 'bahij-insan.ttf',
	      'useOTL' => 0xFF,
		    'useKashida' => 75,
	  	],
	    'ae-almateen-bold' => [
	      'R' => 'ae-almateen-bold.ttf',
	      'useOTL' => 0xFF,
		    'useKashida' => 75,
	  	],
	    'harf-fannan' => [
	      'R' => 'harf-fannan.ttf',
	      'useOTL' => 0xFF,
		    'useKashida' => 75,
	  	],
	   ],
	   'default_font' => $pdf_font
]);
$mpdf->SetDirectionality('rtl');
$stylesheet = create_pdf_css();
$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
//$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

if( pdfVersion() == 1 ){
	if( setting('pdf_set_header') == 1 ){
		$mpdf->SetHTMLHeader('<div style="text-align: right; font-weight: bold;">'.setting('site_title').'</div>');
	}

	if( setting('pdf_set_footer') == 1 ){
		$mpdf->SetHTMLFooter('
		<table width="100%">
		   <tr>
				<td width="33%" class="text-center">'.setting('site_title').'</td>
			    <td width="33%" class="text-center">{PAGENO}/{nbpg}</td>
			    <td width="33%" class="text-center">{DATE j-m-Y}</td>
		   </tr>
		</table>');
	}
}

$templateCount = 0;
function pdf_template( $args = array() ){
	global $templateCount, $mpdf;

  $category_id = ( isset($args['category_id']) ? intval($args['category_id']) : 0 );
  $category_name = ( isset($args['category_name']) ? $args['category_name'] : '' );
  $category_url = ( isset($args['category_url']) ? $args['category_url'] : '' );
  $content_class = ( isset($args['content_class']) ? $args['content_class'] : '' );
  $publication_date = ( isset($args['publication_date']) ? $args['publication_date'] : '' );
  $type = ( isset($args['type']) ? intval($args['type']) : 0 );
  $limit = ( isset($args['limit']) ? intval($args['limit']) : 1 );
	$news = ( isset($args['news']) ? $args['news'] : '' );
  $p = ( isset($args['p']) ? $args['p'] : 0 );

  $page_classes = array(
    1 => 'magazine_page',
    2 => 'magazine_page',
    3 => 'social_page',
    4 => 'radio_page',
    5 => 'tv_page',
    6 => 'work_page',
    7 => 'work_in_kuwait_page',
    8 => 'work_in_gulf_page',
    9 => 'article_page',
    10 => 'idea_page',
    11 => 'project_page',
    12 => 'volunteer_page'
  );

  $page_class = ( isset($page_classes[$category_id]) ? $page_classes[$category_id] : '' );

  if( $category_id == 0 ){
    if( is_array($news) ){
      $code = array();
      foreach($news as $key => $value){
        $code[] = $value;
      }
    }else{
      $code = $news;
    }
  }else{
    if( is_array($news) ){
      $code = array();
      if( $category_id == 4 && $type == 3 ){
        if( count($news) == 1 ){
          $args_2 = array(
            'category_id' => $category_id,
            'limit' => $limit,
            'allow_pagination' => 0,
            'type' => 1,
            'publishedDate' => $publication_date,
            'hide_category_title' => 1,
            'by_date' => 1
          );
          $news = get_news_pdf( $args_2 );
        }
        $content_code = '<div class="radio_page">';
        if( !empty($category_name) ){
					$content_code .= '<div class="radio_page_title"><h1>'.$category_url.'</h1></div>';
        }
        foreach($news as $key => $value){
          $content_code .= '<div class="'.$content_class.'">'.$value.'</div>';
        }
        $content_code .= '</div>';
        $code[] = $content_code;
      }else{
        if( $category_id == 4 && count($news) == 1 ){
          $args_2 = array(
            'category_id' => $category_id,
            'limit' => $limit,
            'allow_pagination' => 0,
            'type' => 1,
            'publishedDate' => $publication_date,
            'hide_category_title' => 1,
            'by_date' => 1
          );
          $news = get_news_pdf( $args_2 );
        }
				$z = 0;
        foreach($news as $keye => $valuee){
					$z++;
					$templateCount++;
          $content_code = '<div class="'.$page_class.'">';
          if( !empty($category_name) ){
						$content_code .= '<div class="radio_page_title">';
            $content_code .= '<div class="top_page_container">';
						if( pdfVersion() == 1 ){
							$content_code .= '<h1>'.$category_url.'</h1>';
						}else{
							$content_code .= '<div class="top_page_number">'.( $templateCount < 10 ? '{PAGENO}' : '{PAGENO}' ).'</div>'; //$mpdf->PageNo()
							$content_code .= '<h1>'.$category_url.'</h1>';
						}
						$content_code .= '</div>';
						$content_code .= '</div>';
          }
          $content_code .= '<div class="'.$content_class.'">'.$valuee.'</div>';
          $content_code .= '</div>';
          $code[] = $content_code;
        }
      }
    }else{
      $code = '<div class="'.$page_class.'">';
      if( !empty($category_name) ){
        $code .= '<div class="radio_page_title"><h1>'.$category_url.'</h1></div>';
      }
      $code .= '<div class="'.$content_class.'">'.$news.'</div>';
      $code .= '</div>';
    }
  }

  return $code;
}

function pdf_first_page( $publication = '' ){
	$publication_id = ( isset($publication['id']) ? $publication['id'] : 0 );
  $publication_title = ( isset($publication['title']) ? $publication['title'] : '' );
  $publication_description = ( isset($publication['description']) ? $publication['description'] : '' );
  $publication_image = ( isset($publication['image']) ? $publication['image'] : '' );
  $publication_text = ( isset($publication['text']) ? $publication['text'] : '' );
  $publication_cover = ( isset($publication['cover']) ? $publication['cover'] : '' );
  $publication_date = ( isset($publication['publication_date']) ? $publication['publication_date'] : '' );
  $publication_d = ( isset($publication['date']) ? $publication['date'] : '' );
  $other_file = ( isset($publication['other_file']) ? $publication['other_file'] : '' );

	$get_other_file = ( empty($other_file) ? '' : '<div class="other-file"><a target="_blank" href="'.$other_file.'">'.lang('other_file').'</a></div>' );

	$archive_img = lang('archive_img');

	if( !empty($publication_date) ){
    $d_time = new DateTime( $publication_date );
    $getTimestamp = $d_time->getTimestamp();
    $today = ( empty($getTimestamp) ? date("l j M Y", time()) : date("l j M Y", $getTimestamp) );
  }else{
    $today = ( empty($publication_d) ? date("l j M Y", time()) : date("l j M Y", $publication_d) );
  }

	if( pdfVersion() == 1 ){
		$html = '<div class="first_page">';
		$html .= '<div class="first_page_date"><p>'.day_name($today).'</p></div>';
		$html .= '<div class="first_page_number_3">'.$get_other_file.'</div>';
		$html .= '<div class="whatsapp-number"><a href="https://wa.me/'.setting('site_whatsapp').'">'.lang('whatsapp_img').'</a></div>';
		$html .= '<div class="publications-archive"><a href="'.url( array('action' => 'publications' ) ).'?open=pdf">'.$archive_img.'</a></div>';
		$html .= '</div>';
	}else{
		$html = '<div class="first_page">';
		$html .= '<div class="first_page_date"><p>'.day_name($today).'</p></div>';
		//$html .= '<div class="first_page_number_3">'.$get_other_file.'</div>';
		//$html .= '<div class="whatsapp-number"><a href="https://wa.me/'.setting('site_whatsapp').'">'.lang('whatsapp_img').'</a></div>';
		//$html .= '<div class="publications-archive"><a href="'.url( array('action' => 'publications' ) ).'?open=pdf">'.$archive_img.'</a></div>';
		$html .= '</div>';
	}

	return $html;
}

function pdf_last_page(){
	if( pdfVersion() == 1 ){
		$html = '<div class="last_page"><img src="'.base_url('images/v1/pdf-footer-last-page3.png').'"></div>';
	}else{
		$html = '<div class="last_page"><img src="'.base_url('images/v2/pdf-footer-last-page.png').'"></div>';
	}

	return $html;
}

$yx = 0;
$arrY = array();
function pdf_write_html($text, $title='', $AddPage=false){
	global $mpdf, $yx, $arrY;

	$yx++;
	$arrY[] = '';

	$html = '<!doctype html>';
	$html .= '<html lang="en">';
	$html .= '<head>';
	$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
	//$html .= create_pdf_css();
	$html .= '</head>';
	$html .= '<body>';
	if( !empty($title) ){
		$html .= '<h1>'.$title.'</h1>';
	}
	$html .= $text;
	$html .= '</body>';
	$html .= '</html>';

	if( !empty(trim($text)) ){
		if( $AddPage ){
			$mpdf->AddPage();
		}
		$mpdf->WriteHTML($html);
		if( pdfVersion() == 1 ){
			$mpdf->SetHTMLFooter('<div class="pdf_footer"><div class="pdf_footer_pages">{PAGENO}</div></div>');
		}else{
			if( $yx == 1 ){
				$mpdf->SetHTMLFooter('');
			}else{
				$mpdf->SetHTMLFooter('<div class="pdf_footer"><div class="pdf_footer_pages"></div></div>');
			}
		}
	}
}

function pdf_get_banner( $banner_id ){
	global $DB;
	$query_banner = $DB->N_query("SELECT * FROM banners WHERE id=$banner_id AND active=1 LIMIT 1");
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
		$banner .= '<a href="'.$banner_url.'"><img target="_blank" src="'.$banner_image.'" alt="'.$banner_name.'"></a>';
		if( !empty($banner_description) ){
			$banner .= '<p>'.$banner_description.'</p>';
		}
		if( !empty($banner_text) ){
			$banner .= $banner_text;
		}
		$banner .= '</div>';
	}
	return $banner;
}

function pdf_get_category( $category_id ){
	global $DB;
	$query_category = $DB->N_query("SELECT id,title FROM category WHERE id=$category_id AND active=1 LIMIT 1");
	$counts_category = $DB->N_num_rows($query_category);
	if($counts_category == 0){
		$category_name = '';
		$url = '';
		$category_url = '';
	}else{
		$row_category = $DB->N_fetch_array($query_category);
		$category_name = text_filter(3, $row_category['title']);
		$url = url( array('action' => 'category', 'id' => $category_id ) ).'?open=pdf';
		$category_url = '<a href="'.$url.'">'.$category_name.'</a>';
	}

	return array(
		'name' => $category_name,
		'url' => $category_url,
		'href' => $url
	);
}

function create_pdf($pdf_title='', $pdf_content='', $file_name='', $hidden_html_body=0){
	global $mpdf, $DB;

	$title = ( empty($pdf_title) ? 'PDF' : $pdf_title );
	$namefile = ( empty($file_name) ? 'publication' : $file_name );
  $publication_id = ( isset($_GET['publication_id']) ? intval($_GET['publication_id']) : 0 );

	$publication = publication($publication_id);
  $publication_id = ( isset($publication['id']) ? $publication['id'] : 0 );
  $publication_title = ( isset($publication['title']) ? $publication['title'] : '' );
  $publication_description = ( isset($publication['description']) ? $publication['description'] : '' );
  $publication_image = ( isset($publication['image']) ? $publication['image'] : '' );
  $publication_text = ( isset($publication['text']) ? $publication['text'] : '' );
  $publication_cover = ( isset($publication['cover']) ? $publication['cover'] : '' );
  $publication_date = ( isset($publication['publication_date']) ? $publication['publication_date'] : '' );
  $publication_d = ( isset($publication['date']) ? $publication['date'] : '' );

	$mpdf->SetTitle( setting('site_title') );
	$mpdf->SetAuthor('Al Najat charity');
	$mpdf->SetCreator('Ahmed Al Enzi');
	$mpdf->SetSubject(setting('site_title').' | '.setting('site_slogan'));
	$mpdf->SetKeywords('Charity, News');

	if( empty($pdf_content) ){
		$text = array();

    $text[] = pdf_first_page( $publication );

		$xx = 0;
	  for ($i=1; $i <= 15; $i++) {
	    if( setting('pdf_box_code_'.$i) == "" ){
				$category_id = intval(setting('pdf_box_category_'.$i));
				$limit = intval(setting('pdf_box_limit_'.$i));
				$type = intval(setting('pdf_box_type_'.$i));
				$banner_id = intval(setting('pdf_box_banner_'.$i));

        $info = array(
          'limit' => $limit,
          'category_id' => $category_id,
          'publishedDate' => $publication_date,
          'type' => $type,
          'hide_category_title' => 1
        );

        $news = get_news_pdf( $info );
				$banner = pdf_get_banner( $banner_id );
				$category = pdf_get_category( $category_id );

				if( empty($banner) ){
					if( !empty($news) && !empty($category['name']) ){
						$xx++;
		        $args = array(
		          'category_id' => $category_id,
		          'category_name' => ( isset($category['name']) ? $category['name'] : '' ),
		          'category_url' => ( isset($category['url']) ? $category['url'] : '' ),
		          'content_class' => 'radio_page_content',
		          'publication_date' => $publication_date,
		          'type' => $type,
		          'limit' => $limit,
		          'news' => $news,
							'p' => $xx
		        );
						$text[] = pdf_template( $args );
					}
				}else{
					$text[] = $banner;
				}
	    }else{
	      $text[] = setting('pdf_box_code_'.$i);
	    }
	  }
		$text[] = pdf_last_page();
	}else{
		$text = $pdf_content;
	}

	if( $hidden_html_body == 1 ){
		if( is_array($text) && count($text) > 0 ){
			if( setting('pdf_pages') == 1 ){
				$get_value = '';
				$x=0;
				foreach ($text as $key => $value) {
					$x++;
					$get_value .= $value;
				}
				pdf_write_html($get_value, '', false);
			}else{
				foreach ($text as $key => $value) {
          if( is_array($value) ){
            foreach($value as $keyr => $valuer){
							pdf_write_html($valuer, '', true);
            }
          }else{
						if( !empty($value) ){
							pdf_write_html($value, '', true);
						}
          }
				}
			}
		}else{
			pdf_write_html($text, $title);
		}
	}else{
		pdf_write_html($text, $title);
	}

	if( isset($_GET['online']) ){
		$mpdf->Output($namefile.'-'.date('j-m-Y', time()).'.pdf', 'I'); // download
	}else{
		$mpdf->Output($namefile.'-'.date('j-m-Y', time()).'.pdf', 'D'); // I, D, F
	}
}
?>
