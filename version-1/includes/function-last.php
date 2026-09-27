<?php
error_reporting(E_ALL);
session_start();
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Kuwait');
ini_set('memory_limit', '1024M');
$path = dirname(__FILE__);

include($path.'/config.php');
include($path.'/class_upload.php');

include($path.'/class-template.php');
$template = new TEMPLATE();

define('_MPDF_TTFONTPATH', __DIR__ . '/custom-fonts');

require_once __DIR__ . '/vendor/autoload.php';

$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

class CustomLanguageToFontImplementation extends \Mpdf\Language\LanguageToFont
{

    public function getLanguageOptions($llcc, $adobeCJK)
    {
        if ($llcc === 'ar') {
            return [false, 'tajawal']; // for thai language, font is not core suitable and the font is Frutiger
        }

        return parent::getLanguageOptions($llcc, $adobeCJK);
    }

}

function fonts(){
  $pdf_fonts = array( 'tajawal' => 'Tajawal', 'frutiger' => 'Frutiger', 'awanzamanth' => 'Awanzamanth', 'stc' => 'STC', 'swissracondensed' => 'Swissracondensed', 'al-jazeera-arabic-regular' => 'Aljazeera', 'bahij-insan' => 'Bahij', 'ae-almateen-bold' => 'Almateen', 'harf-fannan' => 'Fannan' );

  return $pdf_fonts;
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
		//'languageToFont' => new CustomLanguageToFontImplementation(),
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
        /*
        'stc' => [
	  			'R' => 'stc-regular.ttf',
	  			'B' => 'stc-light.ttf',
	        'useOTL' => 0xFF,
	        'useKashida' => 75,
	  		],
        'swissracondensed' => [
	  			'R' => 'swissracondensed-normal.ttf',
	  			'B' => 'swissracondensed-bold.ttf',
	        'useOTL' => 0xFF,
	        'useKashida' => 75,
	  		],
        */
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
/*
print_r($fontData);
print_r($fontDirs);
echo _MPDF_TTFONTPATH;
exit;
*/

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

$bg_with_text = 0;

function create_pdf_css(){
	global $bg_with_text;
/*
	if( $bg_with_text == 1 ){
		$bg = array( 'images/bg-with-text/pdf-home-795.jpg', 'images/bg-with-text/pdf-footer-bg.png', 'images/bg-with-text/pdf-section-radio.jpg', 'images/bg-with-text/pdf-section-articles.jpg', 'images/bg-with-text/pdf-section-magazine.jpg', 'images/bg-with-text/pdf-section-social-media.jpg', 'images/bg-with-text/pdf-section-tv.jpg', 'images/bg-with-text/pdf-section-work.jpg', 'images/bg-with-text/pdf-section-work-in-kuwait.jpg', 'images/bg-with-text/pdf-section-work-in-gulf.jpg', 'images/pdf-section-project.jpg', 'images/pdf-section-volunteer.jpg' );
	}else{
		$bg = array( 'images/pdf-home-795.jpg', 'images/pdf-footer-bg.png', 'images/pdf-section-radio.jpg', 'images/pdf-section-articles.jpg', 'images/pdf-section-magazine.jpg', 'images/pdf-section-social-media.jpg', 'images/pdf-section-tv.jpg', 'images/pdf-section-work.jpg', 'images/pdf-section-work-in-kuwait.jpg', 'images/pdf-section-work-in-gulf.jpg', 'images/pdf-section-project.jpg', 'images/pdf-section-volunteer.jpg' );
	}
  */
  $bg = array( 'images/pdf-home-795-7.jpg', 'images/pdf-footer-bg.png', 'images/pdf-section-radio.jpg', 'images/pdf-section-articles.jpg', 'images/pdf-section-magazine.jpg', 'images/pdf-section-social-media.jpg', 'images/pdf-section-tv.jpg', 'images/pdf-section-work.jpg', 'images/pdf-section-work-in-kuwait.jpg', 'images/pdf-section-work-in-gulf.jpg', 'images/pdf-section-project.jpg', 'images/pdf-section-volunteer.jpg', 'images/pdf-section-idea.jpg' );

	$css = '<style type="text/css">
	@page {
	   margin: 0mm 0mm 0mm 0mm;
	}
	body{
	  margin: auto;
	  max-width:980px;
	  direction: rtl;
	  line-height: 1.6;
	  color: #000000;
	  text-align: right;
	  background-color: #fff;
		font-size: 18px;
		font-weight: normal;
	}
	h1,
	h2,
	h3,
	h4{
    font-weight: bold;
	}
	h1{
    font-size: 26px;
	}
  .largeimage {
    text-align: center;
  }
  .magazineLogo{
    background-color: #ffffff;
    border: 1px solid #dddddd;
    padding: 0 20px;
    border-radius: 25%;
    margin: 15px 0;
  }
  .magazineLogo img {
    width: 70%;

  }
	.first_page {
		height:1117px;
		background: #eeeeee url('.base_url($bg[0]).') no-repeat top center !important;
	}
	.first_page_title {
		padding-top: 75px;
		padding-right: 160px;
		width: 300px;
	}
	.first_page_title h1 {
		font-weight: bold;
		font-size: 40px;
		color: #3a6ab9;
		padding: 0;
		margin: 0;
		padding-bottom: 20px;
	}
	.first_page_title h5 {
		font-size: 22px;
		font-weight: bold;
		color: #000;
		padding: 0;
		margin: 0;
	}
	.first_page_number {
		padding-top: 320px;
		padding-right: 420px;
	}
  .first_page_number_3 {
		padding-top: 300px;
		padding-right: 210px;
	}
	.first_page_number2 {
		padding-top: 530px;
		padding-right: 450px;
	}
	.first_page_number p, .first_page_number2 p, .first_page_number_3 p {
		font-size: 30px;
		font-weight: normal;
		color: #fff;
		padding: 0;
		margin: 0;
		text-align: center;
	}
	.first_page_date {
		padding-top: 275px;
		padding-right: 155px;
		color: #000;
		font-size: 25px;
		font-weight: normal;
	}
  .publications-archive {
    margin-top: 20px;
    padding-right: 150px;
  }
  .whatsapp-number {
    margin-top: 250px;
    padding-right: 130px;
  }
	.radio_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[2]).') no-repeat top center;
	}
	.article_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[3]).') no-repeat top center;
	}
	.magazine_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[4]).') no-repeat top center;
	}
	.social_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[5]).') no-repeat top center;
	}
	.tv_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[6]).') no-repeat top center;
	}
	.work_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[7]).') no-repeat top center;
	}
	.work_in_kuwait_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[8]).') no-repeat top center;
	}
	.work_in_gulf_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[9]).') no-repeat top center;
	}
  .project_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[10]).') no-repeat top center;
	}
  .volunteer_page {
		height:1117px;
		background: #ffffff url('.base_url($bg[11]).') no-repeat top center;
	}
  .idea_page {
    height:1117px;
		background: #ffffff url('.base_url($bg[12]).') no-repeat top center;
  }
	.last_page {
		height:1117px;
		background: #ffffff;
		text-align: center;
	}
	.pdf_footer {
		height: '.( $bg_with_text == 1 ? '80px' : '80px' ).';
		background: url('.base_url($bg[1]).') no-repeat top center;
	}
	.pdf_footer_pages {
		padding-right: '.( $bg_with_text == 1 ? '104px' : '104px' ).';
		padding-top: '.( $bg_with_text == 1 ? '9px' : '9px' ).';
		font-size: '.( $bg_with_text == 1 ? '20px' : '20px' ).';
		font-weight: bold;
		color: #000;
	}
	.radio_page_title {
		padding-top: 35px;
		padding-right: 5px;
		width: 290px;
		text-align: center;
	}
	.radio_page_title h1 {
		font-weight: normal;
		font-size: 22px;
		color: #fff;
		padding: 0;
		margin: 0;
	}
	.radio_page_title h1 a {
		color: #fff;
		text-decoration: none;
	}
  .source {
    text-align: left;
    margin: 0;
  }
  .source a {
    color: #666;
		text-decoration: none;
  }
  .donation_url, .show_tweet {
    padding: 0;
    border: 1px solid #dddddd;
    background-color: #3e68ba;
    width: 300px;
    margin: 0 auto;
    color: #f0b617;
    text-align: center;
    margin-top: 30px;
    border-radius: 10px;
  }
  .show_sound {
    padding: 0;
    border: 1px solid #dddddd;
    background-color: #ffba00;
    width: 300px;
    margin: 0 auto;
    color: #fff;
    text-align: center;
    margin-top: 30px;
    border-radius: 10px;
  }
  .show_tv {
    padding: 0;
    border: 1px solid #dddddd;
    background-color: #e92424;
    width: 300px;
    margin: 0 auto;
    color: #fff;
    text-align: center;
    margin-top: 30px;
    border-radius: 10px;
  }
  .donation_url a, .show_tweet a, .show_sound a, .show_tv a {
    color: #f0b617;
    text-decoration: none;
    display: block;
  }
  .donation_url p, .show_tweet p, .show_sound p, .show_tv p {
    padding: 10px 0 10px 0;
    margin: 0;
  }
	.radio_page_content{
		padding-top: 70px;
		padding-right: 40px;
		padding-left: 40px;
    font-size: 22px;
	}
	.radio_page_content2{
		padding-top: 250px;
		padding-right: 30px;
		padding-left: 30px;
	}
	.banner_content {
		padding: 30px;
	}
  .work_in_kuwait_page .radio_page_title h1 , .work_in_gulf_page .radio_page_title h1 {
		font-weight: bold;
		font-size: 22px;
		color: #fff;
		padding: 0;
		margin: 0;
	}
  .work_in_kuwait_page .radio_page_title, .work_in_gulf_page .radio_page_title {
		padding-top: 35px !important;
	}
  .work_in_kuwait_page .radio_page_content, .work_in_gulf_page .radio_page_content {
		padding-top: 70px !important;
	}
	.cover{
	    background:url('.site_url().'images/Header-bg.jpg) no-repeat top center #fff;
	    text-align: center;
	    min-height: 750px;
	}
	.logo{
	    padding-top: 15px;
	}
	.cover .title h1 {
	    font-weight: normal;
	}
	.title{
	    display: block;
	    margin: 120px auto;
	    color: #fff;
	}
	.date{
	    margin: auto;
	    display: block;
	    color: #fff;
	}
	.w-100 {
		width: 100%;
		max-height: 600px;
	}
	.just-image {
		text-align: center;
	}
	.pdf-img-tpl-5 {
		width: 350px !important;
		padding-right: 15px;
	}
	.date span{
	    padding: 18px;
	}
	.date a{
	    color: #fff;
	}
	.the-title{
	    margin: 30px auto 10px;
	    display: block;
	    width: 100%;
	    text-align: center
	}
	.the-title span{
	    margin: auto;
	    display: block;
	    text-align:center;
	}
	.the-title a{
	   color: #212529;
	}
	.news-title{
	    margin: 15px 15px 0 15px;
			line-height: 1.8em;
      text-align: center;
	}
	.news-title h2 {
	    margin: 0;
			padding: 0;
      color: #3a6ab9;
      font-size: 28px;
      font-weight: bold;
	}
	.news-excerpet p{
	    text-align: justify;
	    text-align-last: right;
	    padding: 0 15px;
      color: #000000;
	}
	.news-excerpet p a{
	    color: #ae42bc;
	}
	.news-excerpet p a:hover{
	    color: #1975b8;
	}
	.row {
	    width: 100%;
			margin-bottom: 5px;
	}
	.col-md-3, .col-3, .col-md-6, .col-6 {
	    float: right;
	}
	.col-md-3, .col-3 {
	    width: 25%;
	}
	.col-md-6, .col-6 {
	    width: 49%;
	}
	.col-md-9, .col-9 {
	    float: left;
	    width: 72%;
			pading-right: 15px;
	}
	.row h5 {
	    padding: 0;
			margin-bottom: 15px;
			margin-top: 0;
	}
	.social{
		background-color: #fff;
		pading-right: 15px;
	}
	.circle-div{
		padding: 0 15px;
	}
	.circle-div img {
		width: 350px !important;
		height: 155px;
	}
	.tweet-text{
		padding: 0 15px;
		text-align: justify;
	}
	.read-more-button{
		background: #1da1f2;
		display: block;
		margin: auto;
		width: 50%;
		line-height: 45px;
		font-weight: 500;
		color: #fff;
	}
	.banner-ads{
		margin: auto;
	}
	.banner-ads img{
		width: 100%;
	}
	.radio{
		padding: 0 15px;
	}
	.radio-desc{
		margin: 30px 0 0 0;
		text-align: justify;
	}
	.social-contaner{
		background-color: #008ee0;
		color: #ffffff;
		width: 200px;
		text-align: center;
		display: block;
		padding: 15px;
		margin-top: 5px;
		margin: 0 auto;
		margin-bottom: 35px;
		border-radius: 5px;
	}
	.social-contaner a{
		display: block;
		line-height: 45px;
		color: #ffffff;
		font-weight: 700;
		text-decoration: none;
	}
	.radio-listen-contaner{
		background-color: #ffd600;
		color: #212121;
		width: 200px;
		text-align: center;
		display: block;
		padding: 15px;
		margin-top: 15px;
		border-radius: 5px;
	}
	.radio-listen{
		display: block;
		line-height: 45px;
		color: #212121;
		font-weight: 700;
		text-decoration: none;
	}
	.row-devider{
		border-bottom: 1px solid #dedede;
		padding: 15px 0
	}
	.najat-tv{
		padding: 30px 0;
		padding: 0 15px;
	}
	.najat-tv .row {
		margin-bottom: 30px;
	}
	.najat-tv h3 {
		font-size: 22px;
		font-weight: bold;
		color: #222222;
		padding: 0;
		margin: 0;
	}
	.najat-tv h3 a {
		color: #222222;
		text-decoration: none;
	}
	.text{
		line-height: 1.8em;
		padding: 15px 0 0 15px;
		text-align: justify;
	}
	.watch-contaner {
		background-color: #e92424;
		color: #fff;
		width: 200px;
		text-align: center;
		display: block;
		padding: 15px;
		margin-top: 15px;
		border-radius: 5px;
	}
	.watch{
		color: #fff;
	}
	.news{
		border-right: 5px solid #ae42bc;
	  border-bottom: 1px dotted #ae42bc;
		padding: 15px;
	}
	.news a.btn {
		color: #fff !important;
	}
	.table {
			width: 100%;
			max-width: 100%;
			margin-bottom: 16px;
			border-collapse: collapse;
	}
	thead {
			display: table-header-group;
			vertical-align: middle;
			border-color: inherit;
	}
	.table .thead-dark th {
			color: #fff;
			background-color: #212529;
			border-color: #32383e;
	}

	.table thead th {
			vertical-align: bottom;
			border-bottom: 2px solid #dee2e6;
	}
	.table td, .table th {
			padding: 15px;
			vertical-align: top;
			border-top: 1px solid #dee2e6;
	}
	tbody {
			display: table-row-group;
			vertical-align: middle;
	}
	tr {
			display: table-row;
	}
	.text-center {
			text-align: center !important;
	}
	.text-right {
			text-align: right !important;
	}
  .other-file {
			padding: 10px 320px 0 0;
	}
  .other-file a {
			color: #ffba00;
      display: block;
      font-size: 40px;
      text-decoration: none;
	}
	</style>';
	return $css;
}

function pdf_template( $args = array() ){
  $category_id = ( isset($args['category_id']) ? intval($args['category_id']) : 0 );
  $category_name = ( isset($args['category_name']) ? $args['category_name'] : '' );
  $category_url = ( isset($args['category_url']) ? $args['category_url'] : '' );
  $bg_with_text = ( isset($args['bg_with_text']) ? $args['bg_with_text'] : '' );
  $content_class = ( isset($args['content_class']) ? $args['content_class'] : '' );
  $publication_date = ( isset($args['publication_date']) ? $args['publication_date'] : '' );
  $type = ( isset($args['type']) ? intval($args['type']) : 0 );
  $limit = ( isset($args['limit']) ? intval($args['limit']) : 1 );
  $news = ( isset($args['news']) ? $args['news'] : '' );

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

  $title_classes = array(
    1 => 'radio_page_title'
  );

  $page_class = ( isset($page_classes[$category_id]) ? $page_classes[$category_id] : '' );
  $title_class = ( isset($title_classes[$category_id]) ? $title_classes[$category_id] : 'radio_page_title' );

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
        if( !empty($category_name) && $bg_with_text != 1 ){
          $content_code .= '<div class="'.$title_class.'"><h1>'.$category_url.'</h1></div>';
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
        foreach($news as $keye => $valuee){
          $content_code = '<div class="'.$page_class.'">';
          if( !empty($category_name) && $bg_with_text != 1 ){
            $content_code .= '<div class="'.$title_class.'"><h1>'.$category_url.'</h1></div>';
          }
          $content_code .= '<div class="'.$content_class.'">'.$valuee.'</div>';
          $content_code .= '</div>';
          $code[] = $content_code;
        }
      }
    }else{
      $code = '<div class="'.$page_class.'">';
      if( !empty($category_name) && $bg_with_text != 1 ){
        $code .= '<div class="'.$title_class.'"><h1>'.$category_url.'</h1></div>';
      }
      $code .= '<div class="'.$content_class.'">'.$news.'</div>';
      $code .= '</div>';
    }
  }

  return $code;
}

function create_pdf($pdf_title='', $pdf_content='', $file_name='', $hidden_html_body=0){
	global $mpdf, $DB, $bg_with_text;

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
  $other_file = ( isset($publication['other_file']) ? $publication['other_file'] : '' );

  $get_other_file = ( empty($other_file) ? '' : '<div class="other-file"><a target="_blank" href="'.$other_file.'">'.lang('other_file').'</a></div>' );

	$mpdf->SetTitle( setting('site_title') );
	$mpdf->SetAuthor('Al Najat charity');
	$mpdf->SetCreator('Ahmed Al Enzi');
	$mpdf->SetSubject(setting('site_title').' | '.setting('site_slogan'));
	$mpdf->SetKeywords('Charity, News');

  if( !empty($publication_date) ){
    $d_time = new DateTime( $publication_date );
    $getTimestamp = $d_time->getTimestamp();
    $today = ( empty($getTimestamp) ? date("l j M Y", time()) : date("l j M Y", $getTimestamp) );
  }else{
    $today = ( empty($publication_d) ? date("l j M Y", time()) : date("l j M Y", $publication_d) );
  }

  $archive_img = lang('archive_img');

	if( empty($pdf_content) ){
		//if( setting('pdf_set_footer') == 1 ){
		/*
		<table width="100%">
				<tr>
						<td width="33%" class="text-center">'.setting('site_title').'</td>
						<td width="33%" class="text-center">{PAGENO}/{nbpg}</td>
						<td width="33%" class="text-center">{DATE j-m-Y}</td>
				</tr>
		</table>
		*/
			$mpdf->SetHTMLFooter('
			<div class="pdf_footer"><div class="pdf_footer_pages">{PAGENO}</div></div>');
		//}
		$text = array();
		if( $bg_with_text == 1 ){
			$text[] = '<div class="first_page">
		  <div class="first_page_number2">
		  <p>'.day_name($today).'</p>
      '.$get_other_file.'
		  </div>
		  </div>';
      //sprintf( lang('alnajat_number'), $publication_id.'<br />', day_name($today) )
			$content_class = 'radio_page_content2';
		}else{
      $text[] = '<div class="first_page">
      	  <div class="first_page_date"><p>'.day_name($today).'</p></div>
		  <div class="first_page_number_3">
      '.$get_other_file.'
		  </div>
      <div class="whatsapp-number"><a href="https://wa.me/'.setting('site_whatsapp').'">'.lang('whatsapp_img').'</a></div>
      <div class="publications-archive"><a href="'.url( array('action' => 'publications' ) ).'?open=pdf">'.$archive_img.'</a></div>
		  </div>';
      //https://api.whatsapp.com/send?phone='.setting('site_whatsapp').'
      //https://wa.me/15551234567?text=I'm%20interested%20in%20your%20car%20for%20sale
      //<h5>'.setting('site_slogan').'</h5>
			$content_class = 'radio_page_content';
		}

	  for ($i=1; $i <= 15; $i++) {
	    if( setting('pdf_box_code_'.$i) == "" ){
				$category_id = intval(setting('pdf_box_category_'.$i));
				$limit = intval(setting('pdf_box_limit_'.$i));
				$type = intval(setting('pdf_box_type_'.$i));
				$banner_id = intval(setting('pdf_box_banner_'.$i));
				//$news = get_news( $category_id, $limit, 0, $type, $publication_date, 1, 1 );
        $info = array(
          'limit' => $limit,
          'category_id' => $category_id,
          'publishedDate' => $publication_date,
          'type' => $type,
          'hide_category_title' => 1
        );
        //$news = news_by_category_and_date( $info );
        $news = get_news_pdf( $info );

				$query_banner = $DB->N_query("SELECT * FROM banners WHERE id='".$banner_id."' AND active=1 LIMIT 1");
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

				$query_category = $DB->N_query("SELECT id,title FROM category WHERE id='".$category_id."' AND active=1 LIMIT 1");
				$counts_category = $DB->N_num_rows($query_category);
				if($counts_category == 0){
					$category_name = '';
					$category_url = '';
				}else{
					$row_category = $DB->N_fetch_array($query_category);
					$category_name = text_filter(3, $row_category['title']);
					$category_url = '<a href="'.url( array('action' => 'category', 'id' => $category_id ) ).'?open=pdf">'.$category_name.'</a>';
				}

        $args = array(
          'category_id' => $category_id,
          'category_name' => $category_name,
          'category_url' => $category_url,
          'bg_with_text' => $bg_with_text,
          'content_class' => $content_class,
          'publication_date' => $publication_date,
          'type' => $type,
          'limit' => $limit,
          'news' => $news
        );

				if( empty($banner) ){
          $code = pdf_template( $args );
				}else{
					$code = $banner;
				}

				if( !empty($category_name) || !empty($banner) ){
          if( !empty($news) ){
            $text[] = $code;
          }
				}
	    }else{
	      $text[] = setting('pdf_box_code_'.$i);
	    }
	  }
		$text[] = '<div class="last_page"><img src="'.( $bg_with_text == 1 ? base_url('images/bg-with-text/pdf-footer-last-page.png') : base_url('images/pdf-footer-last-page3.png') ).'"></div>';
	}else{
		$text = $pdf_content;
	}

	if( $hidden_html_body == 1 ){
		if( is_array($text) && count($text) > 0 ){
			if( setting('pdf_pages') == 1 ){
				$get_value = '';
				foreach ($text as $key => $value) {
					$get_value .= $value;
				}
				$html = '<!doctype html>';
				$html .= '<html lang="en">';
				$html .= '<head>';
				$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
				$html .= create_pdf_css();
				$html .= '</head>';
				$html .= '<body>';
        //$html .= '<div class="radio_page_title">'.$category_id.'</div>';
				$html .= $get_value;
				$html .= '</body>';
				$html .= '</html>';
				$mpdf->WriteHTML($html);
			}else{
				$x = 0;
				foreach ($text as $key => $value) {
					++$x;

          if( is_array($value) ){
            foreach($value as $keyr => $valuer){
              $html = '<!doctype html>';
    					$html .= '<html lang="en">';
    					$html .= '<head>';
    					$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    					$html .= create_pdf_css();
    					$html .= '</head>';
    					$html .= '<body>';
    					$html .= $valuer;
    					$html .= '</body>';
    					$html .= '</html>';
              if( !empty($valuer) ){
                $mpdf->AddPage();
      					$mpdf->WriteHTML($html);
              }
            }
          }else{
            $html = '<!doctype html>';
  					$html .= '<html lang="en">';
  					$html .= '<head>';
  					$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
  					$html .= create_pdf_css();
  					$html .= '</head>';
  					$html .= '<body>';
  					$html .= $value;
  					$html .= '</body>';
  					$html .= '</html>';
            if( !empty($value) ){
              $mpdf->AddPage();
    					$mpdf->WriteHTML($html);
            }
          }

				}
			}
		}else{
			$html = '<!doctype html>';
			$html .= '<html lang="en">';
			$html .= '<head>';
			$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
			$html .= create_pdf_css();
			$html .= '</head>';
			$html .= '<body>';
			$html .= '<h1>'.$title.'</h1>';
			$html .= $text;
			$html .= '</body>';
			$html .= '</html>';
			$mpdf->WriteHTML($html);
		}
	}else{
		$html = '<!doctype html>';
		$html .= '<html lang="en">';
		$html .= '<head>';
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
		$html .= create_pdf_css();
		$html .= '</head>';
		$html .= '<body>';
		$html .= '<h1>'.$title.'</h1>';
		$html .= $text;
		$html .= '</body>';
		$html .= '</html>';
		$mpdf->WriteHTML($html);
	}
	if( isset($_GET['online']) ){
		$mpdf->Output($namefile.'-'.date('j-m-Y', time()).'.pdf', 'I'); // download
	}else{
		$mpdf->Output($namefile.'-'.date('j-m-Y', time()).'.pdf', 'D'); // I, D, F
	}
	//$mpdf->Output('EDC.pdf', 'F'); // save on server
	//$mpdf->Output(); // online
}

function base_url($url=''){
	if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$https = 'https://';
	}else{
		$https = 'http://';
	}
	$HTTP_HOST = str_replace($https, '', strtolower($_SERVER['HTTP_HOST']));
	$_url = ( empty($url) ? $https.$HTTP_HOST.''.dirname($_SERVER['SCRIPT_NAME']) : $https.$HTTP_HOST.''.dirname($_SERVER['SCRIPT_NAME']).'/'.$url );
	return $_url;
}

function text_filter($type, $text){
	if($type==1){
		$text = addslashes($text);
		$text = trim($text);
	}elseif($type==2){
		$text = trim($text);
		$text = stripslashes($text);
	}elseif($type==3){
		$text = stripslashes($text);
		$text = htmlspecialchars($text);
	}else{
		$text = $text;
	}
	return $text;
}

function get_start_password(){
	return 'hpl]hgadjd';
}

function login_salt(){
	$salt = date("Ymd");
	return $salt;
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
		$addclass = 'card bg-light cpanel-menu';
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

	if($clear_bottom == 1){ $style=' style="margin-bottom:0px;"'; }else{ $style=''; }
	$code = '<div class="alert '.$classname.'" role="alert"'.$style.'>'.$text.'</div>';
	return $code;
}

function lang($key){
	$lang = array();
	if( isset($_GET['l']) && $_GET['l'] == 'en' ){
		$lang['site_title'] = 'News';
		$lang['name'] = 'Name';
		$lang['newspaper_add'] = 'Add newspaper';
		$lang['newspapers'] = 'Newspapers';
		$lang['country'] = 'Country';
		$lang['logo'] = 'Logo';
		$lang['description'] =  'Description';
		$lang['url'] = 'URL';
		$lang['date'] = 'Date';
		$lang['notice'] = 'Notice';
		$lang['welcome'] = 'Welcome';
		$lang['add'] = 'Add';
		$lang['dashboard_title'] = 'Dashboard';
		$lang['home'] = 'Home';
		$lang['sign_out'] = 'Sign out';
		$lang['class_navbar_nav'] = 'navbar-nav ml-auto';
		$lang['submit'] = 'Submit';
		$lang['added'] = 'Added';
		$lang['not_added'] = 'Not added!';
		$lang['empty_value'] = 'Empty value!';
		$lang['login'] = 'Login';
		$lang['username'] = 'Username';
		$lang['password'] = 'Password';
		$lang['empty_id'] = 'Empty ID!';
		$lang['not_found'] = 'Not Found';
		$lang['category_not_found'] = 'Category not found';
		$lang['edit'] = 'Edit';
		$lang['data'] = 'Data';
		$lang['insert'] = 'Insert';
		$lang['delete'] = 'Delete';
		$lang['not_login'] = 'You are not login';
		$lang['validate_username'] = 'Username is empty';
		$lang['validate_password'] = 'Password is empty';
		$lang['validate_username_info'] = 'Username info Error';
		$lang['validate_username_valid'] = 'Username is invalid';
		$lang['validate_password_valid'] = 'Password is invalid';
		$lang['validate_not_found'] = 'Not found username/password';
		$lang['validate_success'] = 'Success';
		$lang['update'] = 'Update';
		$lang['success'] = 'Success';
		$lang['projects'] = 'Projects';
		$lang['total'] = 'Total';
		$lang['newspaper'] = 'Newspaper';
		$lang['newspaper_number'] = 'Newspaper number';
		$lang['published_date'] = 'Published date';
		$lang['details'] = 'Details';
		$lang['publications'] = 'Publications';
		$lang['publications_add'] = 'Add new publications';
		$lang['include_news'] = 'Include news';
		$lang['publications_created'] = 'Publication is created, if you want edit cover click <a href="%s">here</a>';
		$lang['publication_date'] = 'Publication date';
		$lang['date'] = 'Date';
		$lang['categories'] = 'Categories';
		$lang['category_add'] = 'Add new category';
		$lang['query_error'] = 'Error';
		$lang['alnajat_title'] = 'Alnajat Charity News';
		$lang['alnajat_description'] = 'Published by Alnajat Charity';
		$lang['alnajat_number'] = 'Number <span>%s</span> %s';
		$lang['alnajat_subscribe_whatsapp'] = 'Subscribe whatsapp click here';
		$lang['alnajat_subscribe_email'] = 'Subscribe email click here';
		$lang['alnajat_unsubscribe'] = 'Unsubscribe <a href="%s">Click here</a>';
		$lang['subscribe'] = 'Subscribe';
		$lang['submit_subscribe'] = 'Subscribe';
		$lang['saturday'] = 'Saturday';
		$lang['sunday'] = 'Sunday';
		$lang['monday'] = 'Monday';
		$lang['tuesday'] = 'Tuesday';
		$lang['wednesday'] = 'Wednesday';
		$lang['thursday'] = 'Thursday';
		$lang['friday'] = 'Friday';
		$lang['january'] = 'January';
		$lang['february'] = 'February';
		$lang['march'] = 'March';
		$lang['april'] = 'April';
		$lang['may'] = 'May';
		$lang['june'] = 'June';
		$lang['july'] = 'July';
		$lang['august'] = 'August';
		$lang['september'] = 'September';
		$lang['october'] = 'October';
		$lang['november'] = 'November';
		$lang['december'] = 'December';
		$lang['jan'] = 'Jan';
		$lang['feb'] = 'Feb';
		$lang['mar'] = 'Mar';
		$lang['apr'] = 'Apr';
		$lang['may'] = 'May';
		$lang['jun'] = 'Jun';
		$lang['jul'] = 'Jul';
		$lang['aug'] = 'Aug';
		$lang['sep'] = 'Sep';
		$lang['oct'] = 'Oct';
		$lang['nov'] = 'Nov';
		$lang['dec'] = 'Dec';
		$lang['read_more_img'] = '<img alt="Read more" src="'.base_url('images/read-more-en.png').'">';
    $lang['read_more'] = 'Read more';
		$lang['page_link'] = 'Page Link';
		$lang['tweet_link'] = 'Tweet Link';
		$lang['sound_link'] = 'Sound Link';
    $lang['listen_now_img'] = '<img alt="Listen now" src="'.base_url('images/listen-now-en.png').'">';
		$lang['listen_now'] = 'Listen now';
		$lang['video_link'] = 'Video Link';
		$lang['browse_post_img'] = '<img alt="View post" src="'.base_url('images/browse-post-en.png').'">';
    $lang['browse_post'] = 'View post';
		$lang['listen'] = 'Listen';
    $lang['watch_img'] = '<img alt="Watch now" src="'.base_url('images/watch-now-en.png').'">';
		$lang['watch'] = 'Watch now';
		$lang['setting'] = 'Setting';
		$lang['setting_site_name'] = 'Site name';
		$lang['setting_site_logo'] = 'Logo';
		$lang['setting_site_url'] = 'Site URL';
		$lang['setting_box_category'] = 'Category';
		$lang['setting_box_banner'] = 'Banner';
		$lang['setting_box'] = 'Box';
		$lang['setting_box_limit'] = 'Posts';
		$lang['setting_box_type'] = 'Template';
		$lang['setting_box_code'] = 'Code';
		$lang['setting_site_description'] = 'Description';
		$lang['setting_site_slogan'] = 'Tagline';
		$lang['setting_subscribe_email'] = 'Subscribe link for email';
		$lang['setting_subscribe_whasapp'] = 'Subscribe link for whasapp';
		$lang['setting_unsubscribe'] = 'Unsubscribe link';
    $lang['setting_whatsapp_number'] = 'Whatsapp Number';
		$lang['setting_header_code'] = 'Header code';
		$lang['setting_footer_code'] = 'Footer code';
		$lang['setting_close_site'] = 'Close site';
		$lang['setting_close_site_cause'] = 'Close site cause';
		$lang['setting_home_title'] = 'Home page setting';
		$lang['setting_pdf_home_title'] = 'PDF Sections setting';
		$lang['setting_newspaper_name'] = 'Show newspaper name';
		$lang['yes'] = 'Yes';
		$lang['no'] = 'No';
		$lang['is_close'] = 'Site is close';
		$lang['summary'] = 'Summary';
		$lang['pdf'] = 'PDF';
		$lang['source'] = 'Source';
		$lang['news_source'] = 'News source';
		$lang['page'] = 'Page';
		$lang['cover'] = 'Use image to cover';
		$lang['search_result'] = 'Search result';
		$lang['users'] = 'Users';
		$lang['user_add'] = 'Add new user';
		$lang['users_name'] = 'Name';
		$lang['users_username'] = 'Username';
		$lang['users_password'] = 'Password';
		$lang['users_email'] = 'Email';
		$lang['users_group'] = 'Group';
		$lang['users_username_already'] = 'Username already exists';
		$lang['users_email_already'] = 'Email already exists';
		$lang['users_empty_password'] = 'Password is empty';
		$lang['users_not_allow'] = 'You are not allow open this page';
		$lang['token_not_verify'] = 'Could not verify token';
		$lang['share'] = 'Share';
		$lang['setting_pdf'] = 'PDF Setting';
		$lang['setting_pdf_set_header'] = 'PDF set header';
		$lang['setting_pdf_set_footer'] = 'PDF set footer';
		$lang['setting_pdf_format'] = 'PDF format';
		$lang['setting_pdf_margin_left'] = 'PDF margin left';
		$lang['setting_pdf_margin_right'] = 'PDF margin right';
		$lang['setting_pdf_margin_top'] = 'PDF margin top';
		$lang['setting_pdf_margin_bottom'] = 'PDF margin bottom';
		$lang['setting_pdf_margin_header'] = 'PDF margin header';
		$lang['setting_pdf_margin_footer'] = 'PDF margin footer';
		$lang['setting_pdf_orientation'] = 'PDF orientation';
		$lang['setting_pdf_pages'] = 'PDF pages';
		$lang['setting_pdf_pages_page'] = 'Pages';
    $lang['setting_pdf_font'] = 'Fonts';
		$lang['setting_pdf_pages_text'] = 'Text';
    $lang['setting_general'] = 'General Setting';
		$lang['download_publication'] = 'Download publication by PDF';
    $lang['show_publication'] = 'Show publication';
		$lang['last_news'] = 'Last %d news';
		$lang['script_name'] = 'News & Publications';
		$lang['copyright'] = 'Copyright %s';
		$lang['banners'] = 'Banners';
		$lang['banners_add'] = 'Add banners';
		$lang['html_code'] = 'HTML Code';
    $lang['news_text'] = 'News';
    $lang['news_sound'] = 'Sound';
    $lang['news_video'] = 'Video';
    $lang['news_tweet'] = 'Tweet';
    $lang['news_project'] = 'Project';
    $lang['donation_url'] = 'Donation URL';
    $lang['donation_now_img'] = '<img alt="Donate Now" src="'.base_url('images/donate-en.png').'">';
    $lang['donation_now'] = 'Donate Now';
    $lang['text_tweet'] = 'Tweet';
    $lang['upload'] = 'Upload files';
    $lang['allowed'] = 'Allowed files';
    $lang['submit_upload'] = 'Upload';
    $lang['mime'] = 'Mime';
    $lang['size'] = 'Size';
    $lang['tools'] = 'Tools';
    $lang['new_upload'] = 'New Upload';
    $lang['read_from_source_img'] = '<img alt="Read from source" src="'.base_url('images/source-en.png').'">';
    $lang['read_from_source_img_white'] = '<img alt="Read from source" src="'.base_url('images/source-white-en.png').'">';
    $lang['archive_img'] = '<img alt="Archive" src="'.base_url('images/archive-en.png').'">';
    $lang['whatsapp_img'] = '<img alt="Whatsapp" src="'.base_url('images/whatsapp.png').'">';
    $lang['read_from_source'] = 'Read from source';
    $lang['hide_title'] = 'Hide title';
    $lang['hide_in_pdf'] = 'Hide post from PDF file';
    $lang['hide_description'] = 'Hide description';
    $lang['hide_more'] = 'Hide more';
    $lang['news_order'] = 'Order';
    $lang['order'] = 'Order news PDF';
    $lang['publications_archive'] = 'Publications Archive';
    $lang['visits'] = 'Visits';
    $lang['pdf_visits'] = 'Visits from PDF file';
    $lang['select_date'] = 'Select date';
    $lang['duplicate'] = 'Duplicate';
    $lang['add_duplicate'] = 'Add duplicate';
    $lang['newspaper_type'] = 'Type';
    $lang['newspaper_type_paper'] = 'Paper';
    $lang['newspaper_type_web'] = 'Website';
    $lang['other_file'] = 'Other file';
	}else{
		$lang['site_title'] = 'لجنة الدعوة الإلكترونية';
		$lang['name'] = 'الاسم';
		$lang['newspaper_add'] = 'إضافة صحيفة';
		$lang['newspapers'] = 'الصحف والجرائد';
		$lang['country'] = 'الدولة';
		$lang['logo'] = 'الشعار';
		$lang['description'] =  'الوصف';
		$lang['url'] = 'الرابط';
		$lang['notice'] = 'ملاحظات';
		$lang['welcome'] = 'أهلا بك';
		$lang['validate_name'] = 'الاسم فارغ';
		$lang['dashboard_title'] = 'لوحة التحكم';
		$lang['home'] = 'الرئيسية';
		$lang['sign_out'] = 'خروج';
		$lang['class_navbar_nav'] = 'navbar-nav mr-auto';
		$lang['submit'] = 'أضف';
		$lang['added'] = 'تمت الإضافة بنجاح';
		$lang['not_added'] = 'لم يتم إضافة البيانات';
		$lang['empty_value'] = 'البيانات فارغة';
		$lang['login'] = 'تسجيل الدخول';
		$lang['username'] = 'اسم المستخدم';
		$lang['password'] = 'الرقم السري';
		$lang['empty_id'] =  'رقم الآي دي فارغ';
		$lang['not_found'] = 'لا يوجد بيانات';
		$lang['category_not_found'] = 'القسم غير موجود';
		$lang['edit'] = 'تحرير';
		$lang['data'] = 'عرض البيانات';
		$lang['insert'] = 'إدراج البيانات';
		$lang['delete'] = 'حذف';
		$lang['not_login'] = 'لم تقم بتسجيل الدخول';
		$lang['validate_username'] = 'اسم المستخدم فارغ';
		$lang['validate_password'] = 'الرقم السري فارغ';
		$lang['validate_username_info'] = 'خطأ في معلومات اسم المستخدم';
		$lang['validate_username_valid'] = 'اسم المستخدم غير صحيح';
		$lang['validate_password_valid'] = 'الرقم السري غير مطابق';
		$lang['validate_not_found'] = 'لا يوجد اسم مستخدم/رقم سري';
		$lang['validate_success'] = 'عملية الدخول ناجحة';
		$lang['update'] = 'تحديث';
		$lang['success'] =  'تم تحديث البيانات بنجاح';
		$lang['error'] = 'هناك خطأ ما لم يتم تحديث البيانات';
		$lang['not_fount_id'] = 'رقم الآي دي %d غير مموجود في قاعدة البيانات';
		$lang['status'] = 'الحالة';
		$lang['delete'] = 'حذف';
		$lang['delete_sure'] = 'هل أنت متأكد من حذف [%s]';
		$lang['title'] = 'العنوان';
		$lang['validate_title'] = 'العنوان فارغ';
		$lang['news'] = 'الأخبار';
		$lang['image'] = 'الصورة';
		$lang['news_add'] = 'إضافة خبر جديد';
		$lang['newspaper'] = 'الصحيفة';
		$lang['newspaper_number'] = 'غدد الصحيفة';
		$lang['published_date'] = 'تاريخ النشر';
		$lang['details'] = 'التفاصيل';
		$lang['publications'] = 'النشرات';
		$lang['publications_add'] = 'إضافة نشرة جديدة';
		$lang['publications_created'] = 'تم إنشاء نشرة جديدة, إذا أردت تحرير غلاف النشرة إضغط <a href="%s">هنا</a>';
		$lang['publication_date'] = 'تاريخ النشرة';
		$lang['include_news'] = 'الأخبار التي يتم تضمينها في النشرة';
		$lang['date'] = 'التاريخ';
		$lang['categories'] = 'الأقسام';
		$lang['category_add'] = 'إضافة قسم جديد';
		$lang['query_error'] = 'خطأ';
		$lang['alnajat_title'] = 'نشرة أخبار العمل الخيري';
		$lang['alnajat_description'] = 'تصدر عن جمعية النجاة الخيرية';
		$lang['alnajat_number'] = 'العدد <span>%s</span> %s';
		$lang['alnajat_subscribe_whatsapp'] = 'للاشتراك في النشرة  الإخبارية		عبر واتساب اضغط هنا';
		$lang['alnajat_subscribe_email'] = 'للاشتراك في النشرة  الإخبارية		عبر البريد اضغط هنا';
		$lang['alnajat_unsubscribe'] = 'في حال عدم رغبتكم في تلقي النشرة الإخبارية عبر الواتساب او البريد الإلكتروني يرجى <a href="%s">الضغط هنا</a> لإلغاء الإشتراك';
		$lang['subscribe'] = 'الاشتراك بالنشرة البريدية';
		$lang['submit_subscribe'] = 'اشتراك';
		$lang['saturday'] = 'السبت';
		$lang['sunday'] = 'الأحد';
		$lang['monday'] = 'الإثنين';
		$lang['tuesday'] = 'الثلاثاء';
		$lang['wednesday'] = 'الأربعاء';
		$lang['thursday'] = 'الخميس';
		$lang['friday'] = 'الجمعة';
		$lang['january'] = 'يناير';
		$lang['february'] = 'فبراير';
		$lang['march'] = 'مارس';
		$lang['april'] = 'أبريل';
		$lang['may'] = 'مايو';
		$lang['june'] = 'يونيو';
		$lang['july'] = 'يوليو';
		$lang['august'] = 'أغسطس';
		$lang['september'] = 'سبتمبر';
		$lang['october'] = 'أكتوبر';
		$lang['november'] = 'نوفمبر';
		$lang['december'] = 'ديسمبر';
		$lang['jan'] = 'يناير';
		$lang['feb'] = 'فبراير';
		$lang['mar'] = 'مارس';
		$lang['apr'] = 'أبريل';
		$lang['may'] = 'مايو';
		$lang['jun'] = 'يونيو';
		$lang['jul'] = 'يوليو';
		$lang['aug'] = 'أغسطس';
		$lang['sep'] = 'سبتمبر';
		$lang['oct'] = 'أكتوبر';
		$lang['nov'] = 'نوفمبر';
		$lang['dec'] = 'ديسمبر';
		$lang['read_more_img'] = '<img alt="أكمل القراءة" src="'.base_url('images/read-more-ar.png').'">';
    $lang['read_more'] = 'أكمل القراءة';
		$lang['page_link'] = 'رابط الصفحة';
		$lang['tweet_link'] = 'رابط التغريدة';
		$lang['sound_link'] = 'رابط الملف الصوتي';
		$lang['video_link'] = 'رابط الفيديو';
    $lang['listen_now_img'] = '<img alt="استمع الآن" src="'.base_url('images/listen-now-ar.png').'">';
		$lang['listen_now'] = 'استمع الآن';
		$lang['browse_post_img'] = '<img alt="تصفح الموضوع" src="'.base_url('images/browse-post-ar.png').'">';
    $lang['browse_post'] = 'تصفح الموضوع';
		$lang['listen'] = 'استمع الآن';
    $lang['watch_img'] = '<img alt="شاهد الآن" src="'.base_url('images/watch-now-ar.png').'">';
		$lang['watch'] = 'شاهد الآن';
		$lang['setting'] = 'الإعدادات';
		$lang['setting_site_name'] = 'اسم الموقع';
		$lang['setting_site_logo'] = 'شعار الموقع';
		$lang['setting_site_url'] = 'رابط الموقع';
		$lang['setting_box_category'] = 'القسم';
		$lang['setting_box_banner'] = 'البنر';
		$lang['setting_box'] = 'الصندوق';
		$lang['setting_box_limit'] = 'عدد المواضيع';
		$lang['setting_box_type'] = 'القالب';
		$lang['setting_box_code'] = 'كود';
		$lang['setting_site_description'] = 'وصف الموقع';
		$lang['setting_site_slogan'] = 'وصف مختصر';
		$lang['setting_subscribe_email'] = 'رابط الاشتراك في النشرة الإخبارية عبر البريد';
		$lang['setting_subscribe_whasapp'] = 'رابط الاشتراك في النشرة الإخبارية عبر واتساب';
		$lang['setting_unsubscribe'] = 'رابط إلغاء الإشتراك';
    $lang['setting_whatsapp_number'] = 'رقم هاتف الواتساب';
		$lang['setting_header_code'] = 'كود في الهيدر';
		$lang['setting_footer_code'] = 'كود في الفوتر';
		$lang['setting_close_site'] = 'إغلاق الموقع';
		$lang['setting_close_site_cause'] = 'سبب الإغلاق';
		$lang['setting_home_title'] = 'إعدادات الصفحة الرئيسية';
		$lang['setting_pdf_home_title'] = 'إعدادات أقسام ملف الـ PDF';
		$lang['setting_newspaper_name'] = 'عرض اسم الجريدة بدل الشعار';
    $lang['setting_general'] = 'إعدادات عامة';
		$lang['yes'] = 'نعم';
		$lang['no'] = 'لا';
		$lang['is_close'] = 'الموقع مغلق';
		$lang['summary'] = 'بالمختصر';
		$lang['pdf'] = 'قراءة بواسطة PDF';
		$lang['source'] = 'المصدر';
		$lang['news_source'] = 'مصدر الخبر';
		$lang['page'] = 'الصفحة';
		$lang['cover'] = 'استخدام الصورة كغلاف للنشرة';
		$lang['search_result'] = 'نتائج البحث';
		$lang['users'] = 'الأعضاء';
		$lang['user_add'] = 'إضافة عضو جديد';
		$lang['users_name'] = 'الاسم';
		$lang['users_username'] = 'اسم المستخدم';
		$lang['users_password'] = 'الرقم السري';
		$lang['users_email'] = 'البريد الإلكتروني';
		$lang['users_group'] = 'المجموعة';
		$lang['users_empty_name'] = 'الاسم فارغ';
		$lang['users_username_already'] = 'اسم المستخدم موجود مسبقا';
		$lang['users_email_already'] = 'البريد الإلكتروني محجوز';
		$lang['users_empty_password'] = 'الرقم السري فارغ';
		$lang['users_not_allow'] = 'لا يمكنك عمل هذا الإجراء';
		$lang['token_not_verify'] = 'كود حماية النماذج غير مطابق';
		$lang['share'] = 'شارك';
		$lang['setting_pdf'] = 'إعدادات ملف PDF';
		$lang['setting_pdf_set_header'] = 'تفعيل الهيدر الثابت';
		$lang['setting_pdf_set_footer'] = 'تفعيل الفوتر الثابت';
		$lang['setting_pdf_format'] = 'حجم الملف';
		$lang['setting_pdf_margin_left'] = 'محاذاة الصفحات عن اليسار';
		$lang['setting_pdf_margin_right'] = 'محاذاة الصفحات عن اليمين';
		$lang['setting_pdf_margin_top'] = 'محاذاة الصفحات عن الأعلى';
		$lang['setting_pdf_margin_bottom'] = 'محاذاة الصفحات عن الأسفل';
		$lang['setting_pdf_margin_header'] = 'محاذاة الهيدر';
		$lang['setting_pdf_margin_footer'] = 'محاذاة الفوتر';
		$lang['setting_pdf_orientation'] = 'طريقة عرض الملف';
		$lang['setting_pdf_pages'] = 'صفحات الملف';
		$lang['setting_pdf_pages_page'] = 'صفحات مستقلة لكل قسم';
    $lang['setting_pdf_font'] = 'الخطوط';
		$lang['setting_pdf_pages_text'] = 'نص';
		$lang['download_publication'] = 'تحميل النشرة بصيغة PDF';
    $lang['show_publication'] = 'مشاهدة النشرة';
		$lang['last_news'] = 'أحدث %d أخبار';
		$lang['script_name'] = 'الأخبار والنشرات';
		$lang['copyright'] = 'جميع الحقوق محفوظة %s';
		$lang['banners'] = 'البنرات';
		$lang['banners_add'] = 'إضافة البنرات';
		$lang['html_code'] = 'كود HTML';
    $lang['news_text'] = 'خبر';
    $lang['news_sound'] = 'صوتي';
    $lang['news_video'] = 'مرئي';
    $lang['news_tweet'] = 'تغريدة';
    $lang['news_project'] = 'مشروع';
    $lang['donation_url'] = 'رابط التبرع';
    $lang['donation_now_img'] = '<img alt="تبرع الآن" src="'.base_url('images/donate-ar.png').'">';
    $lang['donation_now'] = 'تبرع الآن';
    $lang['read_from_source_img'] = '<img alt="طالع المصدر" src="'.base_url('images/source-ar-2.png').'">';
    $lang['read_from_source_img_white'] = '<img alt="طالع المصدر" src="'.base_url('images/source-white-ar.png').'">';
    $lang['archive_img'] = '<img alt="النشرات السابقة" src="'.base_url('images/archive-ar-2.png').'">';
    $lang['whatsapp_img'] = '<img alt="Whatsapp" src="'.base_url('images/whatsapp.png').'">';
    $lang['read_from_source'] = 'طالع المصدر';
    $lang['text_tweet'] = 'نص التغريدة';
    $lang['upload'] = 'تحميل الملفات';
    $lang['allowed'] = 'الملفات المسموح لك في رفعها للموقع';
    $lang['submit_upload'] = 'ارفع';
    $lang['mime'] = 'النوع';
    $lang['size'] = 'الحجم';
    $lang['tools'] = 'أدوات';
    $lang['new_upload'] = 'تحميل ملف جديد';
    $lang['hide_title'] = 'إخفاء العنوان في ملف الـ PDF';
    $lang['hide_in_pdf'] = 'إخفاء الخبر من الظهور بداخل ملف الـ PDF';
    $lang['hide_description'] = 'إخفاء الوصف في ملف الـ PDF';
    $lang['hide_more'] = 'إخفار رابط اقرأ المزيد في ملف الـ PDF';
    $lang['news_order'] = 'ترتيب';
    $lang['order'] = 'ترتيب الأخبار في PDF';
    $lang['publications_archive'] = 'أرشيف النشرات';
    $lang['visits'] = 'الزيارات';
    $lang['pdf_visits'] = 'الزيارات عن طريق النشرة';
    $lang['select_date'] = 'حدد تاريخ النشر';
    $lang['duplicate'] = 'نسخة مطابقة';
    $lang['add_duplicate'] = 'أضف نسخة مطابقة';
    $lang['newspaper_type'] = 'نوع الإصدار';
    $lang['newspaper_type_paper'] = 'ورقي';
    $lang['newspaper_type_web'] = 'موقع';
    $lang['other_file'] = 'ملحق النشرة';
	}
	if( array_key_exists($key, $lang) ){
		return $lang[$key];
	}else{
		return 'Not found '.strip_tags($key).' in index array;';
	}
}

function setting($meta_key){
	global $DB;
	$query = $DB->N_query("SELECT * FROM setting WHERE meta_key='".$meta_key."' LIMIT 1");
	$counts = $DB->N_num_rows( $query );
	if( $counts == 0 ){
		$code = '';
	}else{
		$row = $DB->N_fetch_array($query);
		$code = text_filter(2, $row['meta_value']);
	}
	return $code;
}

function site_url(){
	return rtrim(setting('site_url'), "/").'/';
}

function day_name($text){
	$str = str_replace(
		array('Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'),
		array(lang('saturday'), lang('sunday'), lang('monday') ,lang('tuesday'), lang('wednesday'), lang('thursday') ,lang('friday'), lang('january'), lang('february'), lang('march') ,lang('april'), lang('may'), lang('june') ,lang('july'), lang('august'), lang('september'), lang('october') ,lang('november'), lang('december'), lang('january'), lang('february'), lang('march') ,lang('april'), lang('may'), lang('june') ,lang('july'), lang('august'), lang('september'), lang('october') ,lang('november'), lang('december')),
		$text
	);
	return $str;
}

Function shortcode($text){
	$out = preg_replace_callback(
		"(\[news\](.*?)\[/news\])is",
		function($m) {
			static $id = 0;
			$id++;
			return news_show($m[1], 1);
		},
		$text
	);

	$search = array(
		'/\[news\](.*?)\[\/news\]/is'
	);

	$replace = array(
		'<strong>$1</strong>'
  );
	$text = preg_replace($search, $replace, $text);
	return $out;
}

if (! function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }
}

function crop(){
	$create_crop = array(
	'xsmall' => array('width' => 150, 'height' => 150),
	'small' => array('width' => 150, 'height' => 50),
	'medium' => array('width' => 350, 'height' => 155),
	'large' => array('width' => 920, 'height' => 550)
	);
	return $create_crop;
}

function get_image($filename='', $size='medium', $cp=0){
	if($cp == 1){
		$cp_path = '../';
	}else{
		$cp_path = '';
	}
	$url_site = str_replace('/cp', '', base_url());
	$upload_folder = 'upload/';
	$upload_folder_thumbs = 'thumbs/';

	$crop = crop();
	if( is_array($crop) ){
		if(array_key_exists($size, $crop) == true){
			$width = $crop[$size]['width'];
			$height = $crop[$size]['height'];
			$width_height = '_'.$width.'x'.$height;
			if($filename == ""){
				$img = '';
			}else{
				$path = pathinfo($filename); //$path['dirname'], $path['basename'], $path['extension'], $path['filename']
				$current_image = str_replace(array('.'.$path['extension'], $upload_folder), array($width_height.'.'.$path['extension'], $upload_folder.$upload_folder_thumbs), $filename);
				$thumb_img = $current_image;
				$get_basename = basename($thumb_img);
				if($get_basename == ''){
					$get_image = $filename;
				}else{
					$fullpath = $cp_path.$upload_folder.$upload_folder_thumbs.$get_basename;
					if( file_exists($fullpath) ){
						$img = $thumb_img;
					}else{
						$img = $filename;
					}
				}
			}
		}else{
			$img = '';
		}
	}else{
		$img = '';
	}
	return $img;
}

function upload_files($input_name='upload_file', $prefix=''){
	$url_site = str_replace('/cp', '', base_url());
	$upload_folder = '../upload/';
	$upload_folder_thumbs = 'thumbs/';
	$allow_rename = 1;

	if( isset( $_FILES[$input_name] ) && $_FILES[$input_name]['name'] != "" ){
		$allow_rename = 1;
		$allow_resize = 1;
		$image_x = 200;
		$image_convert = 'png';
		if($prefix == ""){
			$rename_file = time().'_'.rand_str(15);
		}else{
			$rename_file = $prefix.'_'.time().'_'.rand_str(15);
		}
		$success = 0;
		$allow_crop = 1;

		$upload = new \Verot\Upload\Upload($_FILES[$input_name]);
		$upload->file_safe_name = true;
		$upload->file_auto_rename = true;
		$upload->dir_auto_create = true;
		$upload->allowed = array('application/pdf','application/msword', 'image/*');
		$current_image = '';

		if($upload->uploaded){
			if($allow_rename == 1){
				$upload->file_new_name_body = $rename_file;
				$upload->Process($upload_folder);
				if($upload->processed){
					$src_mime = $upload->file_src_mime;
					$src_name = $upload->file_src_name;
					$src_name_body = $upload->file_src_name_body;
					$src_pathname = $upload->file_src_pathname;

					$dst_mime = $upload->file_dst_name_ext;
					$dst_name = $upload->file_dst_name;
					$dst_name_body = $upload->file_dst_name_body;
					$dst_pathname = $upload->file_dst_pathname;

					$current_image = $url_site.'/'.str_replace('../', '', $upload_folder).$dst_name;
					$success = 1;
				}else{
					$current_image = ''; //$upload->error
				}
			}else{
				$upload->Process($upload_folder);
				if($upload->processed){
					$src_mime = $upload->file_src_mime;
					$src_name = $upload->file_src_name;
					$src_name_body = $upload->file_src_name_body;
					$src_pathname = $upload->file_src_pathname;

					$dst_mime = $upload->file_dst_name_ext;
					$dst_name = $upload->file_dst_name;
					$dst_name_body = $upload->file_dst_name_body;
					$dst_pathname = $upload->file_dst_pathname;

					$current_image = $url_site.'/'.str_replace('../', '', $upload_folder).$dst_name;
					$success = 1;
				}else{
					$current_image = ''; //$upload->error
				}
			}

			if($success == 1){
				if($allow_crop == 1){
					$create_crop = crop();
					if($allow_resize == 1){
						if(is_array($create_crop)){
							foreach($create_crop as $k => $v){
								$upload->file_new_name_body = $dst_name_body.'_'.$v['width'].'x'.$v['height'].'';
								$upload->image_resize = true;
								$upload->image_ratio_crop = true;
								$upload->image_x = $v['width'];
								$upload->image_y = $v['height'];
								$crop_mime = $upload->file_src_mime;
								$crop_src_name = $upload->file_src_name;

								$crop_dst_mime = $upload->file_dst_name_ext;
								$crop_dst_name = $upload->file_dst_name;
								$crop_dst_name_body = $upload->file_dst_name_body;
								$crop_dst_pathname = $upload->file_dst_pathname;

								$upload->Process($upload_folder.'/'.$upload_folder_thumbs);
								/*
								if($upload->processed){
									$code .= '<p>'.$crop_dst_pathname.'</p>';
								}else{
									$code .= 'error : ' . $upload->error;
								}
								*/
							}
						}

						$upload->file_new_name_body = $dst_name_body.'_thumbnail';
						$upload->image_resize = true;
						//$upload->image_convert = $image_convert;
						$upload->image_x = $image_x;
						$upload->image_ratio_y = true;

						$thumbnail_dst_mime = $upload->file_dst_name_ext;
						$thumbnail_dst_name = $upload->file_dst_name;
						$thumbnail_dst_name_body = $upload->file_dst_name_body;
						$thumbnail_dst_pathname = $upload->file_dst_pathname;

						$upload->Process($upload_folder);
						if($upload->processed){
							//$code .= '<p>'.$thumbnail_dst_name.'</p>';
							$upload->Clean();
						}else{
							//$code .= 'error : ' . $upload->error;
						}
					}
				}
			}
		}
	}else{
		$current_image = '';
	}
	return $current_image;
}

function rand_str($length = 32){
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
	$chars_length = (strlen($chars) - 1);
	$string = $chars[rand(0, $chars_length)];
	for($i = 1; $i < $length; $i = strlen($string)){
		$r = $chars[rand(0, $chars_length)];
		if ($r != $string[$i - 1]) $string .=  $r;
	}
	return $string;
}

function pagination_code($posts, $perpage, $page, $urlpage){

	$pagestr = 'page';
	$pagination = array();

	$lastpage = ceil($posts / $perpage);

	$adjacent = 5;
	$prevtext = "&raquo;";
	$nexttext = "&laquo;";
	$prev = $page - 1; //previous page is page - 1
	$next = $page + 1; //next page is page + 1
	$lpm1 = $lastpage - 1; //last page minus 1
	$adjacents = $adjacent; // How many adjacent pages should be shown on each side?

	if($lastpage > 1){

		if ($page > 1){
			$href_1 = $urlpage.$pagestr.'='.$prev;
			$pagination[] = '<li class="page-item disabled"><a class="page-link" href="'.$href_1.'" aria-label="Previous"><span aria-hidden="true">'.$prevtext.'</span></a></li>';
		}else{
			$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">'.$prevtext.'</span></a></li>';
		}

		if($lastpage < 7 + ($adjacents * 2)){
			for ($counter = 1; $counter <= $lastpage; $counter++){
				if ($counter == $page){
					$pagination[] = '<li class="page-item active"><a class="page-link" href="#">'.$counter.' <span class="sr-only">(current)</span></a></li>';
				}else{
					$href_2 = $urlpage.$pagestr.'='.$counter;
					$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_2.'">'.$counter.'</a></li>';
				}
			}
		}elseif($lastpage > 5 + ($adjacents * 2)){
			if($page < 1 + ($adjacents * 2)){
				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++){
					if ($counter == $page){
						$pagination[] = '<li class="page-item active"><a class="page-link" href="#">'.$counter.' <span class="sr-only">(current)</span></a></li>';
					}else{
						$href_3 = $urlpage.$pagestr.'='.$counter;
						$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_3.'">'.$counter.'</a></li>';
					}
				}
				$href_4 = $urlpage.$pagestr.'='.$lpm1;
				$href_5 = $urlpage.$pagestr.'='.$lastpage;

				$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_4.'">'.$lpm1.'</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_5.'">'.$lastpage.'</a></li>';
			}elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)){
				$href_6 = $urlpage.$pagestr.'=1';
				$href_7 = $urlpage.$pagestr.'=2';

				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_6.'">1</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_7.'">2</a></li>';
				$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++){
					if ($counter == $page){
						$pagination[] = '<li class="page-item active"><a class="page-link" href="#">'.$counter.' <span class="sr-only">(current)</span></a></li>';
					}else{
						$href_8 = $urlpage.$pagestr.'='.$counter;
						$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_8.'">'.$counter.'</a></li>';
					}
				}
				$href_9 = $urlpage.$pagestr.'='.$lpm1;
				$href_10 = $urlpage.$pagestr.'='.$lastpage;

				$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.	$href_9.'">'.$lpm1.'</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_10.'">'.$lastpage.'</a></li>';
			}else{
				$href_11 = $urlpage.$pagestr.'=1';
				$href_12 = $urlpage.$pagestr.'=2';

				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_11.'">1</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_12.'">2</a></li>';
				$pagination[] = '<li class="page-item disabled"><a href="#">...</a></li>';
				for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++){
					if ($counter == $page){
						$pagination[] = '<li class="page-item active"><a class="page-link" href="#">'.$counter.' <span class="sr-only">(current)</span></a></li>';
					}else{
						$href_13 = $urlpage.$pagestr.'='.$counter;
						$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_13.'">'.$counter.'</a></li>';
					}
				}
			}
		}

		if ($page < $counter - 1){
			$href_14 = $urlpage.$pagestr.'='.$next;
			$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_14.'" aria-label="Next"><span aria-hidden="true">'.$nexttext.'</span></a></li>';
		}else{
			$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">'.$nexttext.'</span></a></li>';
		}

	}

	return $pagination;
}

function pagination($posts, $perpage, $page, $urlpage){
	$pagination = pagination_code($posts, $perpage, $page, $urlpage);
	if( is_array($pagination) && count($pagination) > 0 ){
		$pagination_code = '<nav aria-label="Page navigation" class="mt-3">';
		$pagination_code .= '<ul class="pagination">';
		foreach ($pagination as $key => $value) {
			$pagination_code .= $value;
		}
		$pagination_code .= '</ul>';
		$pagination_code .= '</nav>';
	}else{
		$pagination_code = '';
	}
	return $pagination_code;
}


function countries(){
	return array(
	'ad' => 'Andorra',
	'ae' => 'United Arab Emirates',
	'af' => 'Afghanistan',
	'ag' => 'Antigua & Barbuda',
	'ai' => 'Anguilla',
	'al' => 'Albania',
	'am' => 'Armenia',
	'ao' => 'Angola',
	'aq' => 'Antarctica',
	'ar' => 'Argentina',
	'as' => 'American Samoa',
	'at' => 'Austria',
	'au' => 'Australia',
	'aw' => 'Aruba',
	'ax' => '\u00c5land Islands',
	'az' => 'Azerbaijan',
	'ba' => 'Bosnia & Herzegovina',
	'bb' => 'Barbados',
	'bd' => 'Bangladesh',
	'be' => 'Belgium',
	'bf' => 'Burkina Faso',
	'bg' => 'Bulgaria',
	'bh' => 'Bahrain',
	'bi' => 'Burundi',
	'bj' => 'Benin',
	'bl' => 'St. Barth\u00e9lemy',
	'bm' => 'Bermuda',
	'bn' => 'Brunei',
	'bo' => 'Bolivia',
	'bq' => 'Caribbean Netherlands',
	'br' => 'Brazil',
	'bs' => 'Bahamas',
	'bt' => 'Bhutan',
	'bv' => 'Bouvet Island',
	'bw' => 'Botswana',
	'by' => 'Belarus',
	'bz' => 'Belize',
	'ca' => 'Canada',
	'cc' => 'Cocos (Keeling) Islands',
	'cd' => 'Congo - Kinshasa',
	'cf' => 'Central African Republic',
	'cg' => 'Congo - Brazzaville',
	'ch' => 'Switzerland',
	'ci' => 'C\u00f4te d\u2019Ivoire',
	'ck' => 'Cook Islands',
	'cl' => 'Chile',
	'cm' => 'Cameroon',
	'cn' => 'China',
	'co' => 'Colombia',
	'cr' => 'Costa Rica',
	'cu' => 'Cuba',
	'cv' => 'Cape Verde',
	'cw' => 'Cura\u00e7ao',
	'cx' => 'Christmas Island',
	'cy' => 'Cyprus',
	'cz' => 'Czechia',
	'de' => 'Germany',
	'dj' => 'Djibouti',
	'dk' => 'Denmark',
	'dm' => 'Dominica',
	'do' => 'Dominican Republic',
	'dz' => 'Algeria',
	'ec' => 'Ecuador',
	'ee' => 'Estonia',
	'eg' => 'Egypt',
	'eh' => 'Western Sahara',
	'er' => 'Eritrea',
	'es' => 'Spain',
	'et' => 'Ethiopia',
	'fi' => 'Finland',
	'fj' => 'Fiji',
	'fk' => 'Falkland Islands',
	'fm' => 'Micronesia',
	'fo' => 'Faroe Islands',
	'fr' => 'France',
	'ga' => 'Gabon',
	'gb' => 'United Kingdom',
	'gd' => 'Grenada',
	'ge' => 'Georgia',
	'gf' => 'French Guiana',
	'gg' => 'Guernsey',
	'gh' => 'Ghana',
	'gi' => 'Gibraltar',
	'gl' => 'Greenland',
	'gm' => 'Gambia',
	'gn' => 'Guinea',
	'gp' => 'Guadeloupe',
	'gq' => 'Equatorial Guinea',
	'gr' => 'Greece',
	'gs' => 'South Georgia & South Sandwich Islands',
	'gt' => 'Guatemala',
	'gu' => 'Guam',
	'gw' => 'Guinea-Bissau',
	'gy' => 'Guyana',
	'hk' => 'Hong Kong SAR China',
	'hm' => 'Heard & McDonald Islands',
	'hn' => 'Honduras',
	'hr' => 'Croatia',
	'ht' => 'Haiti',
	'hu' => 'Hungary',
	'id' => 'Indonesia',
	'ie' => 'Ireland',
	'il' => 'Israel',
	'im' => 'Isle of Man',
	'in' => 'India',
	'io' => 'British Indian Ocean Territory',
	'iq' => 'Iraq',
	'ir' => 'Iran',
	'is' => 'Iceland',
	'it' => 'Italy',
	'je' => 'Jersey',
	'jm' => 'Jamaica',
	'jo' => 'Jordan',
	'jp' => 'Japan',
	'ke' => 'Kenya',
	'kg' => 'Kyrgyzstan',
	'kh' => 'Cambodia',
	'ki' => 'Kiribati',
	'km' => 'Comoros',
	'kn' => 'St. Kitts & Nevis',
	'kp' => 'North Korea',
	'kr' => 'South Korea',
	'kw' => 'Kuwait',
	'ky' => 'Cayman Islands',
	'kz' => 'Kazakhstan',
	'la' => 'Laos',
	'lb' => 'Lebanon',
	'lc' => 'St. Lucia',
	'li' => 'Liechtenstein',
	'lk' => 'Sri Lanka',
	'lr' => 'Liberia',
	'ls' => 'Lesotho',
	'lt' => 'Lithuania',
	'lu' => 'Luxembourg',
	'lv' => 'Latvia',
	'ly' => 'Libya',
	'ma' => 'Morocco',
	'mc' => 'Monaco',
	'md' => 'Moldova',
	'me' => 'Montenegro',
	'mf' => 'St. Martin',
	'mg' => 'Madagascar',
	'mh' => 'Marshall Islands',
	'mk' => 'Macedonia',
	'ml' => 'Mali',
	'mm' => 'Myanmar (Burma)',
	'mn' => 'Mongolia',
	'mo' => 'Macau SAR China',
	'mp' => 'Northern Mariana Islands',
	'mq' => 'Martinique',
	'mr' => 'Mauritania',
	'ms' => 'Montserrat',
	'mt' => 'Malta',
	'mu' => 'Mauritius',
	'mv' => 'Maldives',
	'mw' => 'Malawi',
	'mx' => 'Mexico',
	'my' => 'Malaysia',
	'mz' => 'Mozambique',
	'na' => 'Namibia',
	'nc' => 'New Caledonia',
	'ne' => 'Niger',
	'nf' => 'Norfolk Island',
	'ng' => 'Nigeria',
	'ni' => 'Nicaragua',
	'nl' => 'Netherlands',
	'no' => 'Norway',
	'np' => 'Nepal',
	'nr' => 'Nauru',
	'nu' => 'Niue',
	'nz' => 'New Zealand',
	'om' => 'Oman',
	'pa' => 'Panama',
	'pe' => 'Peru',
	'pf' => 'French Polynesia',
	'pg' => 'Papua New Guinea',
	'ph' => 'Philippines',
	'pk' => 'Pakistan',
	'pl' => 'Poland',
	'pm' => 'St. Pierre & Miquelon',
	'pn' => 'Pitcairn Islands',
	'pr' => 'Puerto Rico',
	'ps' => 'Palestinian Territories',
	'pt' => 'Portugal',
	'pw' => 'Palau',
	'py' => 'Paraguay',
	'qa' => 'Qatar',
	're' => 'R\u00e9union',
	'ro' => 'Romania',
	'rs' => 'Serbia',
	'ru' => 'Russia',
	'rw' => 'Rwanda',
	'sa' => 'Saudi Arabia',
	'sb' => 'Solomon Islands',
	'sc' => 'Seychelles',
	'sd' => 'Sudan',
	'se' => 'Sweden',
	'sg' => 'Singapore',
	'sh' => 'St. Helena',
	'si' => 'Slovenia',
	'sj' => 'Svalbard & Jan Mayen',
	'sk' => 'Slovakia',
	'sl' => 'Sierra Leone',
	'sm' => 'San Marino',
	'sn' => 'Senegal',
	'so' => 'Somalia',
	'sr' => 'Suriname',
	'ss' => 'South Sudan',
	'st' => 'S\u00e3o Tom\u00e9 & Pr\u00edncipe',
	'sv' => 'El Salvador',
	'sx' => 'Sint Maarten',
	'sy' => 'Syria',
	'sz' => 'Swaziland',
	'tc' => 'Turks & Caicos Islands',
	'td' => 'Chad',
	'tf' => 'French Southern Territories',
	'tg' => 'Togo',
	'th' => 'Thailand',
	'tj' => 'Tajikistan',
	'tk' => 'Tokelau',
	'tl' => 'Timor-Leste',
	'tm' => 'Turkmenistan',
	'tn' => 'Tunisia',
	'to' => 'Tonga',
	'tr' => 'Turkey',
	'tt' => 'Trinidad & Tobago',
	'tv' => 'Tuvalu',
	'tw' => 'Taiwan',
	'tz' => 'Tanzania',
	'ua' => 'Ukraine',
	'ug' => 'Uganda',
	'um' => 'U.S. Outlying Islands',
	'us' => 'United States',
	'uy' => 'Uruguay',
	'uz' => 'Uzbekistan',
	'va' => 'Vatican City',
	'vc' => 'St. Vincent & Grenadines',
	've' => 'Venezuela',
	'vg' => 'British Virgin Islands',
	'vi' => 'U.S. Virgin Islands',
	'vn' => 'Vietnam',
	'vu' => 'Vanuatu',
	'wf' => 'Wallis & Futuna',
	'ws' => 'Samoa',
	'ye' => 'Yemen',
	'yt' => 'Mayotte',
	'za' => 'South Africa',
	'zm' => 'Zambia',
	'zw' => 'Zimbabwe'
	);
}

function generate_form_token($token_title = 'form'){
  $timeout = 86400;

	$get_token = md5(uniqid(microtime(), true));
	if( isset($_SESSION[$token_title.'_token']) && isset($_POST['token']) && isset($_SESSION['token_timeout']) ){
		$_SESSION[$token_title.'_token'] = strip_tags($_POST['token']);
		//return strip_tags($_POST['token']);
    return strip_tags($_SESSION[$token_title.'_token']);
	}else{
    if( isset($_SESSION[$token_title.'_token']) && !empty($_SESSION[$token_title.'_token']) && isset($_SESSION['token_timeout']) && $_SESSION['token_timeout'] > time() ){
      $get_token = $_SESSION[$token_title.'_token'];
    }else{
      $_SESSION[$token_title.'_token'] = $get_token;
      $_SESSION['token_timeout'] = time() + $timeout;
    }
		return $get_token;
	}
}

function verify_form_token($token_title = 'form'){
	if( isset($_SESSION[$token_title.'_token']) && isset($_POST['token']) ){
		if($_SESSION[$token_title.'_token'] == $_POST['token']){
			return true;
		}else{
			return false;
		}
	}else{
		return false;
	}
}

function news($limit=10, $newspaperID=0){
	global $DB;

  if( $limit == 0 ){
    $limit = 10;
  }
	$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
	$page = ($page == 0 ? 1 : $page);
	$perpage = $limit;
	$startpoint = ($page * $perpage) - $perpage;

	if( $newspaperID != 0 ){
		$query_newspaper_id = " AND newspaper_id='".intval($newspaperID)."'";
	}else{
		$query_newspaper_id = "";
	}

	if( isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['s']) && strip_tags($_GET['s']) != '' ){
		$search = strip_tags($_GET['s']);
		$s = $DB->N_escape_string($search);
		$counts = $DB->N_num_rows( $DB->N_query("SELECT id FROM news WHERE (active=1) AND (`title` LIKE '%".$s."%' OR `text` LIKE '%".$s."%')") );
		$query_d = $DB->N_query("SELECT * FROM news WHERE (active=1) AND (`title` LIKE '%".$s."%' OR `text` LIKE '%".$s."%') ORDER BY id DESC LIMIT $startpoint,$perpage");
	}else{
		$counts = $DB->N_num_rows( $DB->N_query("SELECT id FROM news WHERE active=1") );
		$query_d = $DB->N_query("SELECT * FROM news WHERE active=1 ".$query_newspaper_id." ORDER BY id DESC LIMIT $startpoint,$perpage");
	}

	$data_count = $DB->N_num_rows($query_d);

	$news_data = array();

	if($data_count == 0){
		$news_data['msg'] = lang('not_found');
	}else{
		$i=0;
		$news_data['msg'] = 'ok';
		while ($row = $DB->N_fetch_array($query_d)){
			$title = text_filter(3, $row['title']);
			$image = text_filter(3, $row['image']);
			$url = text_filter(3, $row['url']);
			$description = text_filter(3, $row['description']);
			$text = text_filter(3, $row['text']);
			$user_id = intval($row['user_id']);
			$newspaper_number = intval($row['newspaper_number']);
			$newspaper_id = intval($row['newspaper_id']);
			$published_date = text_filter(3, $row['published_date']);
			$tweet_url = text_filter(3, $row['tweet_url']);
			$sound_url = text_filter(3, $row['sound_url']);
			$video_url = text_filter(3, $row['video_url']);

			$query_newspaper = $DB->N_query("SELECT id,name FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
			$counts_newspaper = $DB->N_num_rows($query_newspaper);
			if($counts_newspaper == 0){
				$get_newspaper = '';
			}else{
				$row_newspaper = $DB->N_fetch_array($query_newspaper);
				$get_newspaper = text_filter(3, $row_newspaper['name']);
			}

			$added = date("j/n/Y", $row['date']);

			$get_image = ( empty($image) ? '' : '<img src="'.$image.'" alt="'.$title.'" class="w-100"> ' );

			$shortcode_text = '<pre><kbd>[news]'.$row['id'].'[/news]</kbd></pre>';

			++$i;

			$news_data['posts'][] = array(
				'id' => $row['id'],
				'title' => $title,
				'description' => $description,
				'image' => $image,
				'url' => $url,
				'text' => $text,
				'tweet_url' => $tweet_url,
				'sound_url' => $sound_url,
				'video_url' => $video_url,
				'newspaper_number' => $newspaper_number,
				'newspaper_id' => $newspaper_id,
				'newspaper_name' => $get_newspaper,
				'date' => $added,
				'published_date' => $published_date,
				'shortcode' => $shortcode_text
			);

		}
		if( isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['s']) && $_GET['s'] != '' ){
			$search = strip_tags($_GET['s']);
			$s = $DB->N_escape_string($search);
			$news_data['pagination'] = pagination($counts, $perpage, $page, 'index.php?action=search&s='.$s.'&');
		}else{
			$news_data['pagination'] = pagination($counts, $perpage, $page, 'index.php?action=news&');
		}

	}

	return $news_data;
}

function news_info_by_id( $queryAdd = array(), $by_date = 0 ){
	global $DB;
	$queries = 'WHERE active=1';
	if( is_array($queryAdd) && isset($queryAdd['id']) && intval($queryAdd['id']) > 0 ){
		$id = intval($queryAdd['id']);
		$queries .= ' AND id='.$id;
	}else{
		$id = ( isset($_GET['id']) ? intval($_GET['id']) : 0 );
		$queries .= ' AND id='.$id;
	}
	if( is_array($queryAdd) && isset($queryAdd['published_date']) && $queryAdd['published_date'] != '' ){
		$queries .= " AND published_date='".$queryAdd['published_date']."'";
	}else{
		$get_published_date = ( isset($_GET['date']) ? strip_tags($_GET['date']) : '' );
		if( !empty($get_published_date) && preg_match("/(\d{4})-(\d{2})-(\d{2})$/", $get_published_date ) ){
			$queries .= ' AND published_date="'.$get_published_date.'"';
		}
	}
  if( is_array($queryAdd) && isset($queryAdd['in_pdf']) && intval($queryAdd['in_pdf']) == 1 ){
		$queries .= ' AND hide_in_pdf=0';
	}

  if( $by_date == 1 ){
    $publishedDate = ( isset($queryAdd['published_date']) ? $queryAdd['published_date'] : '' );
    $query_d = $DB->N_query("SELECT news.*, news_meta.* FROM news, news_meta WHERE news.active=1 AND news.published_date='".$publishedDate."' AND news.id = news_meta.news_id ORDER BY news.id DESC");
  }else{
    $query_d = $DB->N_query("SELECT * FROM news ".$queries);
  }

	$data_count = $DB->N_num_rows($query_d);

	$news_data = array();

	if($data_count == 0){
		$news_data['msg'] = lang('not_found');
	}else{
		$news_data['msg'] = 'ok';
		$row = $DB->N_fetch_array($query_d);

		$title = text_filter(3, $row['title']);
		$image = text_filter(3, $row['image']);
		$url = text_filter(3, $row['url']);
		$description = text_filter(3, $row['description']);
		$text = text_filter(2, $row['text']);
		$user_id = intval($row['user_id']);
		$newspaper_number = intval($row['newspaper_number']);
		$newspaper_id = intval($row['newspaper_id']);
		$published_date = text_filter(3, $row['published_date']);
		$tweet_url = text_filter(3, $row['tweet_url']);
		$sound_url = text_filter(3, $row['sound_url']);
		$video_url = text_filter(3, $row['video_url']);

    $original_image = intval($row['original_image']);
    $hide_title = intval($row['hide_title']);
    $hide_description = intval($row['hide_description']);
    $hide_more = intval($row['hide_more']);

    $orders = intval($row['orders']);

		$query_newspaper = $DB->N_query("SELECT id,name,logo FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
		$counts_newspaper = $DB->N_num_rows($query_newspaper);
		if($counts_newspaper == 0){
			$get_newspaper = '';
			$get_newspaper_logo = '';
		}else{
			$row_newspaper = $DB->N_fetch_array($query_newspaper);
			$get_newspaper = text_filter(3, $row_newspaper['name']);
			$get_newspaper_logo = text_filter(3, $row_newspaper['logo']);
		}

		  $query_c = $DB->N_query("SELECT * FROM news_meta WHERE meta_key='category_id' AND news_id=".$id."");
		  if($DB->N_num_rows($query_c) > 0){
		    while($rowc = $DB->N_fetch_array($query_c)){
			    $categoryID = intval($rowc['meta_value']);

				$query_category = $DB->N_query("SELECT id,title FROM category WHERE id='".$categoryID."' AND active=1 LIMIT 1");
				$counts_category = $DB->N_num_rows( $query_category );
				if( $counts_category > 0 ){
					$row_category = $DB->N_fetch_array($query_category);
					$title_category = text_filter(3, $row_category['title']);
					$post_categories[] = array( 'id' => $categoryID, 'title' => $title_category );
				}
			}
		  }

		$added = date("j/n/Y", $row['date']);

		$get_image = ( empty($image) ? '' : '<img src="'.$image.'" alt="'.$title.'" class="w-100"> ' );

		$news_data['posts'] = array(
			'id' => $row['id'],
			'title' => $title,
			'description' => $description,
			'image' => $image,
			'url' => $url,
			'text' => $text,
			'tweet_url' => $tweet_url,
			'sound_url' => $sound_url,
			'video_url' => $video_url,
			'newspaper_number' => $newspaper_number,
			'newspaper_id' => $newspaper_id,
			'newspaper_name' => $get_newspaper,
			'newspaper_logo' => $get_newspaper_logo,
			'date' => $added,
			'published_date' => $published_date,
			'categories' => $post_categories,
      'original_image' => $original_image,
      'hide_title' => $hide_title,
      'hide_description' => $hide_description,
      'hide_more' => $hide_more,
      'order' => $orders
		);
	}

	return $news_data;
}

function news_show($post_id=0, $shortcode_template=0){
	global $template, $DB;
	$news = news_info_by_id( array( 'id' => $post_id ) );
	if( isset($news['msg']) && $news['msg'] == 'ok' ){
	  if( isset($news['posts']) ){
	    $value = $news['posts'];
      $id = ( isset($value['id']) ? $value['id'] : 0 );
      $title = ( isset($value['title']) ? $value['title'] : '' );
      $description = ( isset($value['description']) ? $value['description'] : '' );
      $image = ( isset($value['image']) ? $value['image'] : '' );
      $url = ( isset($value['url']) ? $value['url'] : '' );
      $text = ( isset($value['text']) ? $value['text'] : '' );
      $newspaper_number = ( isset($value['newspaper_number']) ? $value['newspaper_number'] : 0 );
      $newspaper_id = ( isset($value['newspaper_id']) ? $value['newspaper_id'] : 0 );
      $newspaper_name = ( isset($value['newspaper_name']) ? $value['newspaper_name'] : '' );
      $newspaper_logo = ( isset($value['newspaper_logo']) ? $value['newspaper_logo'] : '' );
      $date = ( isset($value['date']) ? $value['date'] : '' );
      $published_date = ( isset($value['published_date']) ? $value['published_date'] : '' );
      $shortcode = ( isset($value['shortcode']) ? $value['shortcode'] : '' );
      $categories = ( isset($value['categories']) ? $value['categories'] : '' );

      $tweet_url = ( isset($value['tweet_url']) ? $value['tweet_url'] : '' );
      $sound_url = ( isset($value['sound_url']) ? $value['sound_url'] : '' );
      $video_url = ( isset($value['video_url']) ? $value['video_url'] : '' );

      if( !isset($_SESSION['visit_article_'.$id]) ){
        $_SESSION['visit_article_'.$id] = 1;
        $query = $DB->N_query("UPDATE news SET visit=visit+1 WHERE id=$id LIMIT 1");
      }

      if( preg_match('/open=pdf/', $_SERVER["REQUEST_URI"]) ){
        if( !isset($_SESSION['visit_article_pdf_'.$id]) ){
          $_SESSION['visit_article_pdf_'.$id] = 1;
          $query2 = $DB->N_query("UPDATE news SET visit_pdf=visit_pdf+1 WHERE id=$id LIMIT 1");
        }
      }

      $get_categories = array();
      if( is_array($categories) && count($categories) > 0 ){
      	  foreach( $categories as $k => $v ){
    	  	  $cat_id = ( isset($v['id']) ? $v['id'] : 0 );
    	  	  $cat_title = ( isset($v['title']) ? $v['title'] : '' );
    	  	  if( $cat_id != 0 && $cat_title != '' ){
    	  	 	 $get_categories[] = array( 'title' => $cat_title, 'url' => url( array( 'action' => 'category', 'id' => $cat_id ) ) );
    	  	  }
      	  }
      }

      $get_newspaper_logo = get_image($newspaper_logo, 'small');

      $template->title = $title;
			$template->description = $description;
			$template->image = $image;
			$template->url = url( array( 'action' => 'show', 'id' => $id ) );
			$template->article_date = $date;

			$get_large_image = get_image($image, 'large');
			$get_xsmall_image = get_image($image, 'xsmall');
			$get_small_image = get_image($image, 'small');
			$get_medium_image = get_image($image, 'medium');

			$code_image = ( empty($image) ? '' : '<img src="'.$image.'" alt="'.$title.'" class="w-100">' );
			$code_image2 = ( empty($image) ? '' : '<img src="'.$get_medium_image.'" alt="'.$title.'" class="w-100">' );

			if( $shortcode_template == 1 ){
				$content = '<div class="news">';
				$content .= '<div class="row">';
				if( empty($code_image2) ){
					$content .= '<div class="col-12 col-md-12">';
					$content .= '<h5><a href="'.url( array( 'action' => 'show', 'id' => $id ) ).'">'.$title.'</a></h5>';
					$content .= $description;
					//$content .= '<p><a class="btn btn-primary mt-3" href="'.url( array( 'action' => 'pdf-show', 'id' => $id, 'read' => 'pdf' ) ).'"><i class="fas fa-file-pdf"></i></a></p>';
					$content .= '</div>';
				}else{
					$content .= '<div class="col-4 col-md-3">';
					$content .= $code_image2;
					$content .= '</div>';
					$content .= '<div class="col-8 col-md-9">';
					$content .= '<h5><a href="'.url( array( 'action' => 'show', 'id' => $id ) ).'">'.$title.'</a></h5>';
					$content .= $description;
					//$content .= '<p><a class="btn btn-primary mt-3" href="'.url( array( 'action' => 'pdf-show', 'id' => $id, 'read' => 'pdf' ) ).'"><i class="fas fa-file-pdf"></i></a></p>';
					$content .= '</div>';
				}
				$content .= '</div>';
				$content .= '</div>';

				$pdf_content = $content;
				$pdf_content .= '<p style="margin-top: 15px;"><a href="'.url( array( 'action' => 'show', 'id' => $id ) ).'">'.lang('source').'</a></p>';
			}else{
				$content = '<section class="sectinon-a">';
				$content .= '<div class="container">';
				$content .= $template->breadcrumb( $get_categories );

				if( !empty($image) ){
					$content .= '<div class="largeimage">';
					$content .= '<img src="'.$image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
					if( empty($newspaper_logo) || setting('') == 1 ){
						if( !empty($newspaper_name) ){
							$content .= '<div class="news-logo"><p>'.$newspaper_name.'</p></div>';
						}
					}else{
						$content .= '<div class="news-logo"><img src="'.$get_newspaper_logo.'" width="140" hight="70" alt="'.$newspaper_name.'" title="'.$newspaper_name.'"></div>';
					}
					$content .= '</div>';
				}
				$content .= '<div class="news-title">';
				$content .= '<h2>'.$title.'</h2>';
				$content .= '</div>';
				if( !empty($text) ){
					$content .= '<div class="news-excerpet">';
					$content .= $text;
					$content .= '</div>';
				}

				//$content .= '<p><a class="btn btn-primary mt-3" href="'.url( array( 'action' => 'pdf-show', 'id' => $id, 'read' => 'pdf' ) ).'"><i class="fas fa-file-pdf"></i></a></p>';
				if( !empty($url) ){
					$content .= '<div class="row-devider">';
					$content .= '<div class="source">';
					$content .= '<a href="'.$url.'" title="'.$newspaper_name.': '.$title.'" target="_blank">'.lang('news_source').'</a>';
					$content .= '</div>';
					$content .= '</div>';
				}

        if( !empty($sound_url) ){
          $content .= '<div class="row-devider">';
					$content .= '<div class="source">';
					$content .= '<a class="btn btn-warning" role="button" target="_blank" href="'.$sound_url.'"><i class="fas fa-headphones"></i> '.lang('listen_now').'</a>';
					$content .= '</div>';
					$content .= '</div>';
        }

        if( !empty($video_url) ){
          $content .= '<div class="row-devider">';
					$content .= '<div class="source">';
					$content .= '<a class="btn btn-warning" role="button" target="_blank" href="'.$video_url.'"><i class="fas fa-film"></i> '.lang('watch').'</a>';
					$content .= '</div>';
					$content .= '</div>';
        }

				$content .= share($title, url(array( 'action' => 'show', 'id' => $id)) );
				$content .= '</div>';
				$content .= '</section>';

				$pdf_content = $code_image;
				$pdf_content .= $text;
				$pdf_content .= '<p style="margin-top: 15px;">'.lang('source').': <a href="'.url( array( 'action' => 'show', 'id' => $id ) ).'">'.url( array( 'action' => 'show', 'id' => $id ) ).'</a></p>';
			}

      $panel = $content;
      if( isset($_GET['read']) && $_GET['read'] == 'pdf' && isset($_GET['action']) && $_GET['action'] == 'show' ){
        create_pdf($title, $pdf_content, 'news-'.$id);
      }
	  }
	}elseif( isset($news['msg']) && $news['msg'] != 'ok' ){
		$panel = $template->tpl_panel(lang('query_error'), $news['msg'], 0);
	}
	return $panel;
}

function news_by_category($category_id=0, $limit=10, $publishedDate='', $hide_category_title = 0, $in_pdf = 0, $by_date = 0 ){
	global $DB;

	if( isset($_GET['id']) && intval($_GET['id']) != 0 ){
		$category_id = intval($_GET['id']);
	}

	$news_data = array();

	$query_category = $DB->N_query("SELECT id,title FROM category WHERE id='".$category_id."' AND active=1 LIMIT 1");
	$counts_category = $DB->N_num_rows($query_category);
	if($counts_category == 0){
		$news_data['msg'] = ( $hide_category_title == 1 ? '' : lang('category_not_found') );
	}else{
		$row_category = $DB->N_fetch_array($query_category);
		$get_category_name = text_filter(3, $row_category['title']);

		$counts = $DB->N_num_rows( $DB->N_query("SELECT id FROM news_meta WHERE meta_key='category_id' AND meta_value='".$category_id."'") );

		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$page = ($page == 0 ? 1 : $page);
		$perpage = $limit;
		$startpoint = ($page * $perpage) - $perpage;

		$query_d = $DB->N_query("SELECT * FROM news_meta WHERE meta_key='category_id' AND meta_value='".$category_id."' ORDER BY news_id DESC LIMIT $startpoint,$perpage");
		$data_count = $DB->N_num_rows($query_d);

		if($data_count == 0){
			$news_data['msg'] = ( $hide_category_title == 1 ? '' : lang('not_found') );
		}else{
			$i=0;
			$news_data['msg'] = 'ok';
			$news_data['category_name'] = $get_category_name;
      $news_data['post_count'] = $data_count;
			while ($row = $DB->N_fetch_array($query_d)){
				$news_id = intval($row['news_id']);

				if( empty($publishedDate) ){
					$news = news_info_by_id( array( 'id' => $news_id, 'in_pdf' => $in_pdf, 'category_id' => $category_id ) );
				}else{
					$news = news_info_by_id( array( 'id' => $news_id, 'published_date' => $publishedDate, 'in_pdf' => $in_pdf, 'category_id' => $category_id ), $by_date );
				}

				if( isset($news['msg']) && $news['msg'] == 'ok' ){
				  if( isset($news['posts']) ){
				    $value = $news['posts'];
			      $id = ( isset($value['id']) ? $value['id'] : 0 );
			      $title = ( isset($value['title']) ? $value['title'] : '' );
			      $description = ( isset($value['description']) ? $value['description'] : '' );
			      $image = ( isset($value['image']) ? $value['image'] : '' );
			      $url = ( isset($value['url']) ? $value['url'] : '' );
			      $text = ( isset($value['text']) ? $value['text'] : '' );
			      $newspaper_number = ( isset($value['newspaper_number']) ? $value['newspaper_number'] : 0 );
			      $newspaper_id = ( isset($value['newspaper_id']) ? $value['newspaper_id'] : 0 );
			      $newspaper_name = ( isset($value['newspaper_name']) ? $value['newspaper_name'] : '' );
			      $date = ( isset($value['date']) ? $value['date'] : '' );
			      $published_date = ( isset($value['published_date']) ? $value['published_date'] : '' );
			      $shortcode = ( isset($value['shortcode']) ? $value['shortcode'] : '' );
						$tweet_url = ( isset($value['tweet_url']) ? $value['tweet_url'] : '' );
						$sound_url = ( isset($value['sound_url']) ? $value['sound_url'] : '' );
						$video_url = ( isset($value['video_url']) ? $value['video_url'] : '' );
            $original_image = ( isset($value['original_image']) ? $value['original_image'] : 0 );
            $hide_title = ( isset($value['hide_title']) ? $value['hide_title'] : 0 );
            $hide_description = ( isset($value['hide_description']) ? $value['hide_description'] : 0 );
            $hide_more = ( isset($value['hide_more']) ? $value['hide_more'] : 0 );
            $order = ( isset($value['order']) ? $value['order'] : 0 );

						$query_newspaper = $DB->N_query("SELECT id,name,logo FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
						$counts_newspaper = $DB->N_num_rows($query_newspaper);
						if($counts_newspaper == 0){
							$get_newspaper = '';
							$get_newspaper_logo = '';
						}else{
							$row_newspaper = $DB->N_fetch_array($query_newspaper);
							$get_newspaper = text_filter(3, $row_newspaper['name']);
							$get_newspaper_logo = text_filter(3, $row_newspaper['logo']);
						}

						++$i;

						$news_data['posts'][] = array(
							'id' => $id,
							'title' => $title,
							'description' => $description,
							'image' => $image,
							'url' => $url,
							'text' => $text,
							'tweet_url' => $tweet_url,
							'sound_url' => $sound_url,
							'video_url' => $video_url,
							'newspaper_number' => $newspaper_number,
							'newspaper_id' => $newspaper_id,
							'newspaper_name' => $get_newspaper,
							'newspaper_logo' => $get_newspaper_logo,
							'date' => $date,
							'published_date' => $published_date,
							'hide_category_title' => $hide_category_title,
              'original_image' => $original_image,
              'hide_title' => $hide_title,
              'hide_description' => $hide_description,
              'hide_more' => $hide_more,
              'order' => $order
						);

				  }
				}
			}
			if( isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['s']) && $_GET['s'] != '' ){
				$search = strip_tags($_GET['s']);
				$s = $DB->N_escape_string($search);
				$news_data['pagination'] = pagination($counts, $perpage, $page, site_url().'index.php?action=search&s='.$s.'&');
			}else{
				$news_data['pagination'] = pagination($counts, $perpage, $page, site_url().'index.php?action=category&id='.$category_id.'&');
			}
		}
	}

	return $news_data;
}
/*
$info = array(
  'limit' => 10,
  'category_id' => 1,
  'publishedDate' => '2019-09-15',
  'hide_category_title' => 1
);
$news = news_by_category_and_date( $info );
print_r($news);
*/
function news_by_category_and_date( $info = array() ){
	global $DB;

  $news_data = array();

  $category_id = ( isset($info['category_id']) ? intval($info['category_id']) : 0 );
  $limit = ( isset($info['limit']) ? intval($info['limit']) : 10 );
  $publishedDate = ( isset($info['publishedDate']) ? strip_tags($info['publishedDate']) : '' );
  $hide_category_title = ( isset($info['hide_category_title']) ? intval($info['hide_category_title']) : 0 );

	$query_category = $DB->N_query("SELECT id,title FROM category WHERE id='".$category_id."' AND active=1 LIMIT 1");
	$counts_category = $DB->N_num_rows($query_category);
	if($counts_category == 0){
		$news_data['msg'] = ( $hide_category_title == 1 ? '' : lang('category_not_found') );
	}else{
		$row_category = $DB->N_fetch_array($query_category);
		$get_category_name = text_filter(3, $row_category['title']);

    /*
    $counts = $DB->N_num_rows( $DB->N_query("SELECT news.id, news_meta.id FROM news, news_meta WHERE news.active=1 AND news.published_date='".$publishedDate."' AND new_meta.meta_key='category_id' AND news_meta.meta_value = '".$category_id."' AND news.id = news_meta.news_id") );
		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$page = ($page == 0 ? 1 : $page);
		$perpage = $limit;
		$startpoint = ($page * $perpage) - $perpage;
    */

    $query_d = $DB->N_query("SELECT news.*, news_meta.* FROM news, news_meta WHERE news.active=1 AND news.published_date='".$publishedDate."' AND news_meta.meta_key='category_id' AND news_meta.meta_value = '".$category_id."' AND news.id = news_meta.news_id ORDER BY news.id DESC LIMIT $limit");
		$data_count = $DB->N_num_rows($query_d);
		if($data_count == 0){
			$news_data['msg'] = ( $hide_category_title == 1 ? '' : lang('not_found') );
		}else{
			$i=0;
			$news_data['msg'] = 'ok';
			$news_data['category_name'] = $get_category_name;
      $news_data['post_count'] = $data_count;
			while ($row = $DB->N_fetch_array($query_d)){
				$id = intval($row['news_id']);
        $title = text_filter(3, $row['title']);
    		$image = text_filter(3, $row['image']);
    		$url = text_filter(3, $row['url']);
    		$description = text_filter(3, $row['description']);
    		$text = text_filter(2, $row['text']);
    		$user_id = intval($row['user_id']);
    		$newspaper_number = intval($row['newspaper_number']);
    		$newspaper_id = intval($row['newspaper_id']);
    		$published_date = text_filter(3, $row['published_date']);
    		$tweet_url = text_filter(3, $row['tweet_url']);
    		$sound_url = text_filter(3, $row['sound_url']);
    		$video_url = text_filter(3, $row['video_url']);
        $original_image = intval($row['original_image']);
        $hide_title = intval($row['hide_title']);
        $hide_description = intval($row['hide_description']);
        $hide_more = intval($row['hide_more']);
        $order = intval($row['orders']);
        $date = date("j/n/Y", $row['date']);

        ++$i;

        $query_newspaper = $DB->N_query("SELECT id,name,logo FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
        $counts_newspaper = $DB->N_num_rows($query_newspaper);
        if($counts_newspaper == 0){
          $newspaper_name = '';
          $get_newspaper_logo = '';
        }else{
          $row_newspaper = $DB->N_fetch_array($query_newspaper);
          $newspaper_name = text_filter(3, $row_newspaper['name']);
          $get_newspaper_logo = text_filter(3, $row_newspaper['logo']);
        }

        $news_data['posts'][] = array(
          'id' => $id,
          'title' => $title,
          'description' => $description,
          'image' => $image,
          'url' => $url,
          'text' => $text,
          'tweet_url' => $tweet_url,
          'sound_url' => $sound_url,
          'video_url' => $video_url,
          'newspaper_number' => $newspaper_number,
          'newspaper_id' => $newspaper_id,
          'newspaper_name' => $newspaper_name,
          'newspaper_logo' => $get_newspaper_logo,
          'date' => $date,
          'published_date' => $published_date,
          'hide_category_title' => $hide_category_title,
          'original_image' => $original_image,
          'hide_title' => $hide_title,
          'hide_description' => $hide_description,
          'hide_more' => $hide_more,
          'order' => $order
        );
			}
			//$news_data['pagination'] = pagination($counts, $perpage, $page, site_url().'index.php?action=category&id='.$category_id.'&');
		}
	}

	return $news_data;
}

function news_by_date($publishedDate='', $category_id = 0 ){
	global $DB;

  if( $category_id == 0 ){
    $query_tables = $DB->N_query("SELECT news.*, news_meta.news_id, news_meta.meta_value FROM news, news_meta WHERE news.active=1 AND news.published_date='".$publishedDate."' AND news.id = news_meta.news_id ORDER BY news.id DESC");
  }else{
    $query_tables = $DB->N_query("SELECT news.*, news_meta.* FROM news, news_meta WHERE news.active=1 AND news.published_date='".$publishedDate."' AND new_meta.meta_key='category_id' AND news_meta.meta_value = '".$category_id."' ORDER BY news.id DESC");
  }

  $data_count_tables = $DB->N_num_rows($query_tables);
  $data = array();
  $post_categories = array();
  if($data_count_tables == 0){
    $data['msg'] = lang('not_found');
  }else{
    $i=0;
    $data['msg'] = 'ok';
    $data['post_count'] = $data_count_tables;
    while ($row = $DB->N_fetch_array($query_tables)){
      $id = intval($row['id']);
  		$title = text_filter(3, $row['title']);
  		$image = text_filter(3, $row['image']);
  		$url = text_filter(3, $row['url']);
  		$description = text_filter(3, $row['description']);
  		$text = text_filter(2, $row['text']);
  		$user_id = intval($row['user_id']);
  		$newspaper_number = intval($row['newspaper_number']);
  		$newspaper_id = intval($row['newspaper_id']);
  		$published_date = text_filter(3, $row['published_date']);
  		$tweet_url = text_filter(3, $row['tweet_url']);
  		$sound_url = text_filter(3, $row['sound_url']);
  		$video_url = text_filter(3, $row['video_url']);

      $original_image = intval($row['original_image']);
      $hide_title = intval($row['hide_title']);
      $hide_description = intval($row['hide_description']);
      $hide_more = intval($row['hide_more']);

      $orders = intval($row['orders']);

      $meta_value_category_id = intval($row['meta_value']);

  		$query_newspaper = $DB->N_query("SELECT id,name,logo FROM newspaper WHERE id='".$newspaper_id."' LIMIT 1");
  		$counts_newspaper = $DB->N_num_rows($query_newspaper);
  		if($counts_newspaper == 0){
  			$get_newspaper = '';
  			$get_newspaper_logo = '';
  		}else{
  			$row_newspaper = $DB->N_fetch_array($query_newspaper);
  			$get_newspaper = text_filter(3, $row_newspaper['name']);
  			$get_newspaper_logo = text_filter(3, $row_newspaper['logo']);
  		}

  		  $query_c = $DB->N_query("SELECT * FROM news_meta WHERE meta_key='category_id' AND news_id=".$id."");
  		  if($DB->N_num_rows($query_c) > 0){
  		    while($rowc = $DB->N_fetch_array($query_c)){
  			    $categoryID = intval($rowc['meta_value']);

  				$query_category = $DB->N_query("SELECT id,title FROM category WHERE id='".$categoryID."' AND active=1 LIMIT 1");
  				$counts_category = $DB->N_num_rows( $query_category );
  				if( $counts_category > 0 ){
  					$row_category = $DB->N_fetch_array($query_category);
  					$title_category = text_filter(3, $row_category['title']);
  					$post_categories[] = array( 'id' => $categoryID, 'title' => $title_category );
  				}
  			}
  		  }

  		$added = date("j/n/Y", $row['date']);

  		$get_image = ( empty($image) ? '' : '<img src="'.$image.'" alt="'.$title.'" class="w-100"> ' );

  		$data[$meta_value_category_id][] = array(
  			'id' => $row['id'],
  			'title' => $title,
  			'description' => $description,
  			'image' => $image,
  			'url' => $url,
  			'text' => $text,
  			'tweet_url' => $tweet_url,
  			'sound_url' => $sound_url,
  			'video_url' => $video_url,
  			'newspaper_number' => $newspaper_number,
  			'newspaper_id' => $newspaper_id,
  			'newspaper_name' => $get_newspaper,
  			'newspaper_logo' => $get_newspaper_logo,
  			'date' => $added,
  			'published_date' => $published_date,
  			'categories' => $post_categories,
        'original_image' => $original_image,
        'hide_title' => $hide_title,
        'hide_description' => $hide_description,
        'hide_more' => $hide_more,
        'order' => $orders
  		);
    }
  }

	return $data;
}

function get_news($category_id=0, $limit=10, $allow_pagination=1, $type=0, $publisheDate='', $hide_category_title = 0, $in_pdf = 0, $by_date=0 ){
	global $DB;

	if( $category_id != 0 ){
		$news = news_by_category($category_id, $limit, $publisheDate, $hide_category_title, $in_pdf, $by_date);
		if( !isset($news['posts']) ){
			//$news = news_by_category($category_id, $limit, '', $hide_category_title);
		}
	}else{
		$news = news($limit);
	}

	$get_news = '';
	if( isset($news['msg']) && $news['msg'] == 'ok' ){
	  if( isset($news['posts']) && count($news['posts']) > 0 ){
      if( $in_pdf == 1 ){
        //array_multisort(array_column($wek, 'order'), SORT_ASC, $news['posts']);
      }
      $wek = array();
			$category_name = ( isset($news['category_name']) ? $news['category_name'] : '' );
			$posts = array();
	    foreach ($news['posts'] as $key => $value){
	      $id = ( isset($value['id']) ? $value['id'] : 0 );
	      $title = ( isset($value['title']) ? $value['title'] : '' );
	      $description = ( isset($value['description']) ? $value['description'] : '' );
	      $image = ( isset($value['image']) ? $value['image'] : '' );
	      $url = ( isset($value['url']) ? $value['url'] : '' );
	      $text = ( isset($value['text']) ? $value['text'] : '' );
	      $newspaper_number = ( isset($value['newspaper_number']) ? $value['newspaper_number'] : 0 );
	      $newspaper_id = ( isset($value['newspaper_id']) ? $value['newspaper_id'] : 0 );
	      $newspaper_name = ( isset($value['newspaper_name']) ? $value['newspaper_name'] : '' );
				$newspaper_logo = ( isset($value['newspaper_logo']) ? $value['newspaper_logo'] : '' );
	      $date = ( isset($value['date']) ? $value['date'] : '' );
	      $published_date = ( isset($value['published_date']) ? $value['published_date'] : '' );
	      $shortcode = ( isset($value['shortcode']) ? $value['shortcode'] : '' );
				$tweet_url = ( isset($value['tweet_url']) ? $value['tweet_url'] : '' );
				$sound_url = ( isset($value['sound_url']) ? $value['sound_url'] : '' );
				$video_url = ( isset($value['video_url']) ? $value['video_url'] : '' );
				$hide_category_title = ( isset($value['hide_category_title']) ? $value['hide_category_title'] : 0 );
        $original_image = ( isset($value['original_image']) ? $value['original_image'] : 0 );
        $hide_title = ( isset($value['hide_title']) ? $value['hide_title'] : 0 );
        $hide_description = ( isset($value['hide_description']) ? $value['hide_description'] : 0 );
        $hide_more = ( isset($value['hide_more']) ? $value['hide_more'] : 0 );
        $order = ( isset($value['order']) ? $value['order'] : 0 );

				$get_image = get_image($image, 'large'); // xsmall, small, medium, large
				$get_xsmall_image = get_image($image, 'xsmall');
				$get_small_image = get_image($image, 'small');
				$get_medium_image = get_image($image, 'medium');
				$get_newspaper_logo = $newspaper_logo; //get_image($newspaper_logo, 'small');

        $wek[$key]  = $order;

				$posts[] = array(
					'id' => $id,
					'title' => $title,
          'url' => $url,
					'tweet_url' => $tweet_url,
					'sound_url' => $sound_url,
					'video_url' => $video_url,
					'image' => $image,
					'description' => $description,
					'post_url' => url( array( 'action' => 'show', 'id' => $id ) ),
					'newspaper_logo' => $get_newspaper_logo,
					'newspaper_name' => $newspaper_name,
          'original_image' => $original_image,
          'hide_title' => $hide_title,
          'hide_description' => $hide_description,
          'hide_more' => $hide_more
				);
	    }

      if( $in_pdf == 1 ){
        array_multisort($wek, SORT_ASC, $posts);
      }

			$data = array();
			$data['type'] = $type;
			$data['category_name'] = $category_name;
			$data['category_id'] = $category_id;
			$data['hide_category_title'] = $hide_category_title;
			if( isset($news['pagination']) && $news['pagination'] != '' && $allow_pagination == 1 ){
				$data['pagination'] = $news['pagination'];
			}
			$data['posts'] = $posts;

      if( $hide_category_title == 1 ){
        return news_template_pdf( $data );
      }else{
        $get_news .= news_template( $data );
      }
	  }
	}elseif( isset($news['msg']) && $news['msg'] != 'ok' ){
	  $get_news .= $news['msg'];
	}
	return $get_news;
}

function get_news_pdf( $info = array() ){
	global $DB;
  $category_id = ( isset($info['category_id']) ? intval($info['category_id']) : 0 );
  $limit = ( isset($info['limit']) ? intval($info['limit']) : 10 );
  $allow_pagination = ( isset($info['allow_pagination']) ? intval($info['allow_pagination']) : 1 );
  $type = ( isset($info['type']) ? intval($info['type']) : 0 );
  $publishedDate = ( isset($info['publishedDate']) ? strip_tags($info['publishedDate']) : '' );
  $hide_category_title = ( isset($info['hide_category_title']) ? intval($info['hide_category_title']) : 0 );
  $by_date = ( isset($info['by_date']) ? intval($info['by_date']) : 0 );

  $args = array(
    'limit' => $limit,
    'category_id' => $category_id,
    'publishedDate' => $publishedDate,
    'type' => $type,
    'hide_category_title' => $hide_category_title
  );
  $news = news_by_category_and_date( $args );
	//$news = news_by_category($category_id, $limit, $publishedDate, $hide_category_title, $by_date);

	if( isset($news['msg']) && $news['msg'] == 'ok' ){
	  if( isset($news['posts']) && count($news['posts']) > 0 ){
      $wek = array();
			$category_name = ( isset($news['category_name']) ? $news['category_name'] : '' );
			$posts = array();
	    foreach ($news['posts'] as $key => $value){
	      $id = ( isset($value['id']) ? $value['id'] : 0 );
	      $title = ( isset($value['title']) ? $value['title'] : '' );
	      $description = ( isset($value['description']) ? $value['description'] : '' );
	      $image = ( isset($value['image']) ? $value['image'] : '' );
	      $url = ( isset($value['url']) ? $value['url'] : '' );
	      $text = ( isset($value['text']) ? $value['text'] : '' );
	      $newspaper_number = ( isset($value['newspaper_number']) ? $value['newspaper_number'] : 0 );
	      $newspaper_id = ( isset($value['newspaper_id']) ? $value['newspaper_id'] : 0 );
	      $newspaper_name = ( isset($value['newspaper_name']) ? $value['newspaper_name'] : '' );
				$newspaper_logo = ( isset($value['newspaper_logo']) ? $value['newspaper_logo'] : '' );
	      $date = ( isset($value['date']) ? $value['date'] : '' );
	      $published_date = ( isset($value['published_date']) ? $value['published_date'] : '' );
	      $shortcode = ( isset($value['shortcode']) ? $value['shortcode'] : '' );
				$tweet_url = ( isset($value['tweet_url']) ? $value['tweet_url'] : '' );
				$sound_url = ( isset($value['sound_url']) ? $value['sound_url'] : '' );
				$video_url = ( isset($value['video_url']) ? $value['video_url'] : '' );
				$hide_category_title = ( isset($value['hide_category_title']) ? $value['hide_category_title'] : 0 );
        $original_image = ( isset($value['original_image']) ? $value['original_image'] : 0 );
        $hide_title = ( isset($value['hide_title']) ? $value['hide_title'] : 0 );
        $hide_description = ( isset($value['hide_description']) ? $value['hide_description'] : 0 );
        $hide_more = ( isset($value['hide_more']) ? $value['hide_more'] : 0 );
        $order = ( isset($value['order']) ? $value['order'] : 0 );

				$get_image = get_image($image, 'large'); // xsmall, small, medium, large
				$get_xsmall_image = get_image($image, 'xsmall');
				$get_small_image = get_image($image, 'small');
				$get_medium_image = get_image($image, 'medium');
				$get_newspaper_logo = $newspaper_logo; //get_image($newspaper_logo, 'small');

        $wek[$key]  = $order;

				$posts[] = array(
					'id' => $id,
					'title' => $title,
          'url' => $url,
					'tweet_url' => $tweet_url,
					'sound_url' => $sound_url,
					'video_url' => $video_url,
					'image' => $image,
					'description' => $description,
					'post_url' => url( array( 'action' => 'show', 'id' => $id ) ),
					'newspaper_logo' => $get_newspaper_logo,
					'newspaper_name' => $newspaper_name,
          'original_image' => $original_image,
          'hide_title' => $hide_title,
          'hide_description' => $hide_description,
          'hide_more' => $hide_more
				);
	    }

      array_multisort($wek, SORT_ASC, $posts);

			$data = array();
			$data['type'] = $type;
			$data['category_name'] = $category_name;
			$data['category_id'] = $category_id;
			$data['hide_category_title'] = $hide_category_title;
			if( isset($news['pagination']) && $news['pagination'] != '' && $allow_pagination == 1 ){
				$data['pagination'] = $news['pagination'];
			}
			$data['posts'] = $posts;

      return news_template_pdf( $data );
	  }
	}elseif( isset($news['msg']) && $news['msg'] != 'ok' ){
	  return $news['msg'];
	}

}

Function news_template( $data = array() ){
	global $template, $DB;
	$pdf_page = ( isset($_GET['read']) && $_GET['read'] == 'pdf' ? 1 : 0 );

	$code = '';
	$get_news = '';
	if( is_array($data) ){
		$type = ( isset($data['type']) ? $data['type'] : 0 );
		$category_name = ( isset($data['category_name']) ? $data['category_name'] : '' );
		$category_id = ( isset($data['category_id']) ? $data['category_id'] : '' );
		$pagination = ( isset($data['pagination']) ? $data['pagination'] : '' );
		$hide_category_title = ( isset($data['hide_category_title']) ? $data['hide_category_title'] : 0 );
		if( isset($data['posts']) && count($data['posts']) > 0 ){
			$input_array = array();
			foreach ($data['posts'] as $key => $value) {
				$id = ( isset($value['id']) ? $value['id'] : 0 );
				$title = ( isset($value['title']) ? $value['title'] : '' );
				$image = ( isset($value['image']) ? $value['image'] : '' );
				$newspaper_logo = ( isset($value['newspaper_logo']) ? $value['newspaper_logo'] : '' );
				$newspaper_name = ( isset($value['newspaper_name']) ? $value['newspaper_name'] : '' );
				$description = ( isset($value['description']) ? $value['description'] : '' );
				$post_url = ( isset($value['post_url']) ? $value['post_url'] : '' );
				$tweet_url = ( isset($value['tweet_url']) ? $value['tweet_url'] : '' );
				$sound_url = ( isset($value['sound_url']) ? $value['sound_url'] : '' );
				$video_url = ( isset($value['video_url']) ? $value['video_url'] : '' );

				if( $pdf_page == 1 ){
					$post_url = $post_url.'?open=pdf';
				}

				$get_large_image = get_image($image, 'large'); // xsmall, small, medium, large
				$get_xsmall_image = get_image($image, 'xsmall');
				$get_small_image = get_image($image, 'small');
				$get_medium_image = get_image($image, 'medium');

				if( empty($image) ){
					if( preg_match('/instagram/', $tweet_url) ){
						$social_image = base_url('images/pdf-instagram.jpg');
						$tweet_link_name = lang('browse_post');
					}elseif( preg_match('/facebook/', $tweet_url) ){
						$social_image = base_url('images/pdf-facebook.jpg');
						$tweet_link_name = lang('browse_post');
					}else{
						$social_image = base_url('images/pdf-twitter.jpg');
						$tweet_link_name = lang('browse_post');
					}
				}else{
					$tweet_link_name = lang('browse_post');
					$social_image = $get_medium_image;
				}

				$get_tweet_url = ( empty($tweet_url) ? '#' : $tweet_url );

				if( $type == 1 ){
					if( $pdf_page == 1 ){
						$code .= '<div class="row">';
						$code .= '<div class="largeimage">';
						$code .= '<img src="'.$image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
						$code .= '</div>';
						$code .= '<div class="news-title">';
						$code .= '<h2>'.$title.'</h2>';
						$code .= '</div>';
						$code .= '<div class="news-excerpet">';
						$code .= '<p>'.$description.' <a href="'.$post_url.'" class="read-more">'.lang('read_more').'</a></p>';
						$code .= '</div>';
						$code .= '</div>';
					}else{
						$code .= '<div class="row">';
						$code .= '<div class="largeimage">';
						$code .= '<img src="'.$get_large_image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
						if( empty($newspaper_logo) || setting('newspaper_name') == 1 ){
							if( !empty($newspaper_name) ){
								$code .= '<div class="news-logo"><p>'.$newspaper_name.'</p></div>';
							}
						}else{
							$code .= '<div class="news-logo withImage"><img src="'.$newspaper_logo.'" width="140" hight="70" alt="'.$newspaper_name.'" title="'.$newspaper_name.'"></div>';
						}
						$code .= '</div>';
						$code .= '<div class="news-title">';
						$code .= '<h2>'.$title.'</h2>';
						$code .= '</div>';
						$code .= '<div class="news-excerpet">';
						$code .= '<p>'.cuttext( 300, $description, '...' ).' <a href="'.$post_url.'" class="read-more">'.lang('read_more').'</a></p>';
						$code .= '</div>';
						$code .= '</div>';
					}
				}elseif( $type == 2 ){
					if( $pdf_page == 1 ){
						$col = '<div class="col-12 col-md-6">';
						$col .= '<div class="thumbnail"><div class="circle-div"><img src="'.$social_image.'" alt="'.$title.'" title="'.$title.'"></div></div>';
						$col .= '<div class="tweet-text">';
						$col .= '<p>'.cuttext( 400, $description, '...' ).'</p>';
						$col .= '<div class="social-contaner"><a target="_blank" href="'.$get_tweet_url.'">'.$tweet_link_name.'</a></div>';
						$col .= '</div>';
						$col .= '</div>';
					}else{
						if( empty($newspaper_logo) ){
							$newspaper_logo = base_url('images/logo.png');
						}
						$col = '<div class="col-12 col-md-6">';
						$col .= '<div class="thumbnail circle"><div class="circle-div"><img src="'.$newspaper_logo.'" alt="'.$title.'" title="'.$title.'"></div></div>';
						$col .= '<span class="social-icon"><i class="fab fa-twitter"></i></span>';
						$col .= '<div class="tweet-text">';
						$col .= '<p>'.$description.'</p>';
						$col .= '<a class="btn btn-large btn-default read-more-button" target="_blank" href="'.$get_tweet_url.'">'.lang('tweet_link').'</a>';
						$col .= '</div>';
						$col .= '</div>';
					}
					$input_array[] = $col;
				}elseif( $type == 3 ){
					if( empty($image) ){
						$get_xsmall_image = base_url('images/njat-radio.png');
					}
					$code .= '<div class="row">';
					$code .= '<div class="col-3 col-md-3">';
					$code .= '<a href="'.$post_url.'"><img src="'.$get_xsmall_image.'" alt="'.$title.'" title="'.$title.'" class="w-100"></a>';
					$code .= '</div>';
					$code .= '<div class="col-9 col-md-9">';
          $code .= '<div class="news-title">';
          $code .= '<h2><a href="'.$post_url.'">'.$title.'</a></h2>';
          $code .= '</div>';

          if( $description != $title && !empty($description) ){
            $code .= '<p class="radio-desc">'.$description.'</p>';
          }

					if( !empty($sound_url) ){
						if( preg_match("/(\w+\.mp[34])/", $sound_url) && $pdf_page == 0 ){
							$code .= '<span class="radio-listen pull-left" href="#">'.lang('listen').' <span id="b_x_'.$id.'" onclick="playAudio(\'x_'.$id.'\', \''.$sound_url.'\')"><i class="fa fa-play"></i></span></span>';
							$code .= '<div id="x_'.$id.'"></div>';
						}else{
							$code .= '<div class="radio-listen-contaner"><a class="radio-listen pull-left" target="_blank" href="'.$sound_url.'">'.lang('listen_now').' <i class="fas fa-link"></i></a></div>';
						}

					}
					$code .= '</div>';
					$code .= '</div>';
				}elseif( $type == 4 ){
					if( $pdf_page == 1 ){
						$code .= '<div class="row">';
						$code .= '<div class="col-6">';
						$code .= '<h3><a href="'.$post_url.'">'.$title.'</a></h3>';
						$code .= '<p class="text">'.cuttext( 300, $description, '...' ).'</p>';
						$code .= '<div class="watch-contaner"><a class="radio-listen watch" href="'.$video_url.'">'.lang('watch').' <i class="fa fa-play"></i></a></div>';
						$code .= '</div>';
						$code .= '<div class="col-6">';
						$code .= '<img src="'.$get_xsmall_image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
						$code .= '</div>';
						$code .= '</div>';
					}else{
						$get_youtube_id = get_youtube_id( $video_url );
						$code .= '<div class="row">';
						$code .= '<div class="col-12 col-md-6">';
						$code .= '<h3>'.$title.'</h3>';
						$code .= '<p class="text">'.$description.'</p>';
						$code .= '<a class="radio-listen watch" href="'.$video_url.'">'.lang('watch').' <i class="fa fa-play"></i></a>';
						$code .= '</div>';
						$code .= '<div class="col-12 col-md-6">';
						if( $get_youtube_id ){
							$embed = '<iframe width="100%" height="315" src="https://www.youtube.com/embed/'.$get_youtube_id.'?rel=0&showinfo=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
						}else{
							$embed = '';
						}
						$code .= $embed;
						$code .= '</div>';
						$code .= '</div>';
					}
				}elseif( $type == 5 ){
					$code .= '<div class="row">';
					$code .= '<div class="largeimage">';
					$code .= '<img src="'.$get_large_image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
					if( $pdf_page != 1 ){
						if( empty($newspaper_logo) || setting('newspaper_name') == 1 ){
							if( !empty($newspaper_name) ){
								$code .= '<div class="news-logo"><p>'.$newspaper_name.'</p></div>';
							}
						}else{
							$code .= '<div class="news-logo withImage"><img src="'.$newspaper_logo.'" width="140" hight="70" alt="'.$newspaper_name.'" title="'.$newspaper_name.'"></div>';
						}
					}
					$code .= '</div>';
					$code .= '<div class="news-title">';
					$code .= '<h2>'.$title.'</h2>';
					$code .= '</div>';
					$code .= '<div class="news-excerpet">';
					$code .= '<p>'.$description.' <a href="'.$post_url.'" class="read-more">'.lang('read_more').'</a></p>';
					$code .= '</div>';
					$code .= '</div>';
				}elseif( $type == 6 ){
					$code .= '<div class="col-12 col-md-6">';
					$code .= '<div class="largeimage">';
					$code .= '<img src="'.$get_medium_image.'" alt="'.$title.'" title="'.$title.'" class="w-100 pdf-img-tpl-5">';
					if( $pdf_page != 1 ){
						if( empty($newspaper_logo) || setting('newspaper_name') == 1 ){
							if( !empty($newspaper_name) ){
								$code .= '<div class="news-logo"><p>'.$newspaper_name.'</p></div>';
							}
						}else{
							$code .= '<div class="news-logo withImage"><img src="'.$newspaper_logo.'" width="140" hight="70" alt="'.$newspaper_name.'" title="'.$newspaper_name.'"></div>';
						}
					}
					$code .= '</div>';
					$code .= '<div class="news-title">';
					$code .= '<h2>'.$title.'</h2>';
					$code .= '</div>';
					$code .= '<div class="news-excerpet">';
					$code .= '<p>'.$description.' <a href="'.$post_url.'" class="read-more">'.lang('read_more').'</a></p>';
					$code .= '</div>';
					$code .= '</div>';
				}elseif( $type == 7 ){
					$code .= '<div class="row">';
					$code .= '<div class="col-md-3 col-sm-12 col-12">';
					$code .= '<img src="'.$get_medium_image.'" alt="'.$title.'" title="'.$title.'" class="w-100 img-thumbnail archive-img">';
					$code .= '</div>';
					$code .= '<div class="col-md-9 col-sm-12 col-12">';
					$code .= '<div class="news-title">';
					$code .= '<h3>'.$title.'</h3>';
					$code .= '</div>';
					$code .= '<div class="news-excerpet">';
					$code .= '<p>'.$description.' <a href="'.$post_url.'" class="read-more">'.lang('read_more').'</a></p>';
					$code .= '</div>';
          if( !empty($sound_url) ){
  					$code .= '<div class="source mb-3">';
  					$code .= '<a class="btn btn-warning" role="button" target="_blank" href="'.$sound_url.'"><i class="fas fa-headphones"></i> '.lang('listen_now').'</a>';
  					$code .= '</div>';
          }
					$code .= '</div>';
					$code .= '</div>';
				}elseif( $type == 8 ){
					if( $pdf_page == 1 ){
						$code .= '<div class="just-image">';
						$code .= '<img src="'.$image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
						$code .= '</div>';
					}else{
						$code .= '<div class="row">';
						$code .= '<div class="largeimage">';
						$code .= '<img src="'.$image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
						if( empty($newspaper_logo) || setting('newspaper_name') == 1 ){
							if( !empty($newspaper_name) ){
								$code .= '<div class="news-logo"><p>'.$newspaper_name.'</p></div>';
							}
						}else{
							$code .= '<div class="news-logo withImage"><img src="'.$newspaper_logo.'" width="140" hight="70" alt="'.$newspaper_name.'" title="'.$newspaper_name.'"></div>';
						}
						$code .= '</div>';
						$code .= '</div>';
					}
				}
			}

			$category_url = '<a href="'.url( array('action' => 'category', 'id' => $category_id ) ).'">'.$category_name.'</a>';

			if( $type == 1 ){
				$get_news .= '<section class="sectinon-a">';
				$get_news .= '<div class="container">';
				if( !empty($category_name) && $hide_category_title != 1 ){
					$get_news .= '<div class="row">';
					$get_news .= '<div class="the-title">';
					$get_news .= '<h2>'.$category_url.'</h2>';
					$get_news .= '<span><img src="'.site_url().'images/title-devider.png" alt="'.$category_name.'" title="'.$category_name.'"></span>';
					$get_news .= '</div>';
					$get_news .= '</div>';
				}
				$get_news .= $code;
				$get_news .= $pagination;
				$get_news .= '</div>';
				$get_news .= '</section>';
			}elseif( $type == 2 ){
				$get_news .= '<section class="social">';
				$get_news .= '<div class="container">';
				if( !empty($category_name) && $hide_category_title != 1 ){
					$get_news .= '<div class="row">';
					$get_news .= '<div class="the-title">';
					$get_news .= '<h2>'.$category_url.'</h2>';
					$get_news .= '<span><img src="'.site_url().'images/title-devider.png" alt="'.$category_name.'" title="'.$category_name.'"></span>';
					$get_news .= '</div>';
					$get_news .= '</div>';
				}
				if( is_array($input_array) && count($input_array) > 0 ){
					$rows = array_chunk($input_array, 2);
					foreach ($rows as $key => $value) {
						$get_news .= '<div class="row">';
						if( is_array($value) ){
							foreach ($value as $key2 => $value2) {
								$get_news .= $value2;
							}
						}
						$get_news .= '</div>';
					}
				}
				$get_news .= $pagination;
				$get_news .= '</div>';
				$get_news .= '</section>';
			}elseif( $type == 3 ){
				$get_news .= '<section class="radio">';
				$get_news .= '<div class="container">';
				if( !empty($category_name) && $hide_category_title != 1 ){
					$get_news .= '<div class="row">';
					$get_news .= '<div class="the-title">';
					$get_news .= '<h2>'.$category_url.'</h2>';
					$get_news .= '<span><img src="'.site_url().'images/title-devider.png" alt="'.$category_name.'" title="'.$category_name.'"></span>';
					$get_news .= '</div>';
					$get_news .= '</div>';
				}
				$get_news .= $code;
				$get_news .= $pagination;
				$get_news .= '</div>';
				$get_news .= '</section>';
			}elseif( $type == 4 ){
				$get_news .= '<section class="najat-tv">';
				$get_news .= '<div class="container">';
				if( !empty($category_name) && $hide_category_title != 1 ){
					$get_news .= '<div class="row">';
					$get_news .= '<div class="the-title">';
					$get_news .= '<h2>'.$category_url.'</h2>';
					$get_news .= '<span><img src="'.site_url().'images/title-devider.png" alt="'.$category_name.'" title="'.$category_name.'"></span>';
					$get_news .= '</div>';
					$get_news .= '</div>';
				}
				$get_news .= $code;
				$get_news .= $pagination;
				$get_news .= '</div>';
				$get_news .= '</section>';
			}elseif( $type == 5 ){
				$get_news .= '<section class="charity-news">';
				$get_news .= '<div class="container">';
				if( !empty($category_name) && $hide_category_title != 1 ){
					$get_news .= '<div class="row">';
					$get_news .= '<div class="the-title">';
					$get_news .= '<h2>'.$category_url.'</h2>';
					$get_news .= '<span><img src="'.site_url().'images/title-devider.png" alt="'.$category_name.'" title="'.$category_name.'"></span>';
					$get_news .= '</div>';
					$get_news .= '</div>';
				}
				$get_news .= $code;
				$get_news .= $pagination;
				$get_news .= '</div>';
				$get_news .= '</section>';
			}elseif( $type == 6 ){
				$get_news .= '<section class="charity-news">';
				$get_news .= '<div class="container">';
				if( !empty($category_name) && $hide_category_title != 1 ){
					$get_news .= '<div class="row">';
					$get_news .= '<div class="the-title">';
					$get_news .= '<h2>'.$category_url.'</h2>';
					$get_news .= '<span><img src="'.site_url().'images/title-devider.png" alt="'.$category_name.'" title="'.$category_name.'"></span>';
					$get_news .= '</div>';
					$get_news .= '</div>';
				}
				$get_news .= '<div class="row">';
				$get_news .= $code;
				$get_news .= '</div>';
				$get_news .= $pagination;
				$get_news .= '</div>';
				$get_news .= '</section>';
			}elseif( $type == 7 ){
				$get_news .= '<section class="sectinon-a">';
				$get_news .= '<div class="container">';
				if( isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['s']) && $_GET['s'] != '' ){
					$search = strip_tags($_GET['s']);
					$s = $DB->N_escape_string($search);
					$template->title = lang('search_result');
				}else{
					$template->title = $category_name;
				}
				$get_news .= $template->breadcrumb();
				$get_news .= $code;
				$get_news .= $pagination;
				$get_news .= '</div>';
				$get_news .= '</section>';
			}elseif( $type == 8 ){
				$get_news .= '<section class="sectinon-a">';
				$get_news .= '<div class="container">';
				if( !empty($category_name) && $hide_category_title != 1 ){
					$get_news .= '<div class="row">';
					$get_news .= '<div class="the-title">';
					$get_news .= '<h2>'.$category_url.'</h2>';
					$get_news .= '<span><img src="'.site_url().'images/title-devider.png" alt="'.$category_name.'" title="'.$category_name.'"></span>';
					$get_news .= '</div>';
					$get_news .= '</div>';
				}
				$get_news .= $code;
				$get_news .= $pagination;
				$get_news .= '</div>';
				$get_news .= '</section>';
			}
		}

	}else{
    return $get_news;
  }

	return $get_news;
}

Function news_template_pdf( $data = array() ){
	global $template, $DB;
	$pdf_page = ( isset($_GET['read']) && $_GET['read'] == 'pdf' ? 1 : 0 );

	$get_news = '';
	if( is_array($data) ){
		$type = ( isset($data['type']) ? $data['type'] : 0 );
		$category_name = ( isset($data['category_name']) ? $data['category_name'] : '' );
		$category_id = ( isset($data['category_id']) ? $data['category_id'] : '' );
		$pagination = ( isset($data['pagination']) ? $data['pagination'] : '' );
		$hide_category_title = ( isset($data['hide_category_title']) ? $data['hide_category_title'] : 0 );

		if( isset($data['posts']) && count($data['posts']) > 0 ){
			$input_array = array();
      $pdf_arr = array();
			foreach ($data['posts'] as $key => $value) {
				$id = ( isset($value['id']) ? $value['id'] : 0 );
				$title = ( isset($value['title']) ? $value['title'] : '' );
				$image = ( isset($value['image']) ? $value['image'] : '' );
				$newspaper_logo = ( isset($value['newspaper_logo']) ? $value['newspaper_logo'] : '' );
				$newspaper_name = ( isset($value['newspaper_name']) ? $value['newspaper_name'] : '' );
				$description = ( isset($value['description']) ? $value['description'] : '' );
				$post_url = ( isset($value['post_url']) ? $value['post_url'] : '' );
				$tweet_url = ( isset($value['tweet_url']) ? $value['tweet_url'] : '' );
				$sound_url = ( isset($value['sound_url']) ? $value['sound_url'] : '' );
				$video_url = ( isset($value['video_url']) ? $value['video_url'] : '' );

        $original_image = ( isset($value['original_image']) ? $value['original_image'] : 0 );
        $hide_title = ( isset($value['hide_title']) ? $value['hide_title'] : 0 );
        $hide_description = ( isset($value['hide_description']) ? $value['hide_description'] : 0 );
        $hide_more = ( isset($value['hide_more']) ? $value['hide_more'] : 0 );
        $url = ( isset($value['url']) ? $value['url'] : '' );

				if( $pdf_page == 1 ){
					$post_url = $post_url.'?open=pdf';
				}

				$get_large_image = get_image($image, 'large'); // xsmall, small, medium, large
				$get_xsmall_image = get_image($image, 'xsmall');
				$get_small_image = get_image($image, 'small');
				$get_medium_image = get_image($image, 'medium');

				if( empty($image) ){
					if( preg_match('/instagram/', $tweet_url) ){
						$social_image = base_url('images/pdf-instagram.jpg');
						$tweet_link_name = lang('read_from_source_img_white');
					}elseif( preg_match('/facebook/', $tweet_url) ){
						$social_image = base_url('images/pdf-facebook.jpg');
						$tweet_link_name = lang('browse_post_img');
					}else{
						$social_image = base_url('images/pdf-twitter.jpg');
						$tweet_link_name = lang('read_from_source_img_white');
					}
				}else{
					$tweet_link_name = lang('read_from_source_img_white');
					$social_image = $get_medium_image;
				}

				$get_tweet_url = ( empty($tweet_url) ? '#' : $tweet_url );

				if( $type == 1 ){
					$code = '<div class="row">';
          if( !empty($newspaper_logo) ){
            $code .= '<div class="magazineLogo">';
  					$code .= '<img src="'.$newspaper_logo.'" alt="'.$newspaper_name.'" title="'.$newspaper_name.'">';
  					$code .= '</div>';
          }

					$code .= '<div class="largeimage">';
					$code .= '<img src="'.$image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
					$code .= '</div>';

          if( $hide_title != 1 ){
            $code .= '<div class="news-title">';
            $code .= '<h2>'.$title.'</h2>';
            $code .= '</div>';
          }
          if( $hide_description != 1 ){
            $code .= '<div class="news-excerpet">';
  					$code .= '<p>'.$description;
            if( $hide_more != 1 && empty($sound_url) ){
              $code .= ' <a href="'.$post_url.'" class="read-more">'.lang('read_more_img').'</a>';
            }
            $code .= '</p>';
  					$code .= '</div>';
          }

          if( !empty($url) && empty($sound_url) ){
            $code .= '<div class="source">';
            $code .= '<p><a href="'.$url.'">'.lang('read_from_source_img').'</a></p>';
            $code .= '</div>';
          }

          if( !empty($tweet_url) ){
            $code .= '<div class="show_tweet"><p><a target="_blank" href="'.$get_tweet_url.'">'.$tweet_link_name.'</a></p></div>';
          }

          if( !empty($sound_url) ){
						//$code .= '<div class="radio-listen-contaner"><a class="radio-listen pull-left" target="_blank" href="'.$sound_url.'">'.lang('listen_now_img').'</a></div>';
            $code .= '<div class="show_sound"><p><a target="_blank" href="'.$post_url.'">'.lang('listen_now_img').'</a></p></div>';
					}

          if( !empty($video_url) ){
            $code .= '<div class="show_tv"><p><a target="_blank" href="'.$post_url.'">'.lang('watch_img').'</a></p></div>';
					}

					$code .= '</div>';
          $pdf_arr[] = $code;
				}elseif( $type == 2 ){
					$col = '<div class="col-12 col-md-6">';
					$col .= '<div class="thumbnail"><div class="circle-div"><img src="'.$social_image.'" alt="'.$title.'" title="'.$title.'"></div></div>';
					$col .= '<div class="tweet-text">';
					$col .= '<p>'.cuttext( 400, $description, '...' ).'</p>';
					$col .= '<div class="social-contaner"><a target="_blank" href="'.$get_tweet_url.'">'.$tweet_link_name.'</a></div>';
					$col .= '</div>';
					$col .= '</div>';
					$input_array[] = $col;
          $pdf_arr[] = $col;
				}elseif( $type == 3 ){
					if( empty($image) ){
						$get_xsmall_image = base_url('images/njat-radio.png');
					}
					$code = '<div class="row-devider">';
					$code .= '<div class="col-3">';
					$code .= '<img src="'.$get_xsmall_image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
					$code .= '</div>';
					$code .= '<div class="col-9">';
          //$code .= '<h3><a href="'.$post_url.'">'.$title.'</a></h3>';
					$code .= '<p class="radio-desc">'.$description.'</p>';
					if( !empty($sound_url) ){
            //$sound_url
						$code .= '<div class="radio-listen-contaner"><a class="radio-listen pull-left" target="_blank" href="'.$post_url.'">'.lang('listen_now_img').'</a></div>';
					}
					$code .= '</div>';
					$code .= '</div>';
          $pdf_arr[] = $code;
				}elseif( $type == 4 ){
					$code = '<div class="row">';
					$code .= '<div class="col-6">';
					$code .= '<h3><a href="'.$post_url.'">'.$title.'</a></h3>';
					$code .= '<p class="text">'.cuttext( 300, $description, '...' ).'</p>';
          //$video_url
					$code .= '<div class="watch-contaner"><a class="radio-listen watch" href="'.$post_url.'">'.lang('watch_img').'</a></div>';
					$code .= '</div>';
					$code .= '<div class="col-6">';
					$code .= '<img src="'.$get_xsmall_image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
					$code .= '</div>';
					$code .= '</div>';
          $pdf_arr[] = $code;
				}elseif( $type == 5 ){
					$code = '<div class="row">';
					$code .= '<div class="largeimage">';
					$code .= '<img src="'.$image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
					$code .= '</div>';
					$code .= '<div class="news-title">';
					$code .= '<h2>'.$title.'</h2>';
					$code .= '</div>';
					$code .= '<div class="news-excerpet">';
					$code .= '<p>'.$description.' <a href="'.$post_url.'" class="read-more">'.lang('read_more_img').'</a></p>';
					$code .= '</div>';
					$code .= '</div>';
          $pdf_arr[] = $code;
				}elseif( $type == 6 ){
					$code = '<div class="col-12 col-md-6">';
					$code .= '<div class="largeimage">';
					$code .= '<img src="'.$image.'" alt="'.$title.'" title="'.$title.'" class="w-100 pdf-img-tpl-5">';
					$code .= '</div>';
					$code .= '<div class="news-title">';
					$code .= '<h2>'.$title.'</h2>';
					$code .= '</div>';
					$code .= '<div class="news-excerpet">';
					$code .= '<p>'.$description.' <a href="'.$post_url.'" class="read-more">'.lang('read_more_img').'</a></p>';
					$code .= '</div>';
					$code .= '</div>';
          $pdf_arr[] = $code;
				}elseif( $type == 7 ){
					$code = '<div class="row-devider">';
					$code .= '<div class="col-md-3 col-sm-12 col-12">';
					$code .= '<img src="'.$get_medium_image.'" alt="'.$title.'" title="'.$title.'" class="w-100 img-thumbnail archive-img">';
					$code .= '</div>';
					$code .= '<div class="col-md-9 col-sm-12 col-12">';
					$code .= '<div class="news-title">';
					$code .= '<h3>'.$title.'</h3>';
					$code .= '</div>';
					$code .= '<div class="news-excerpet">';
					$code .= '<p>'.$description.' <a href="'.$post_url.'" class="read-more">'.lang('read_more_img').'</a></p>';
					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';
          $pdf_arr[] = $code;
				}elseif( $type == 8 ){
          $code = '<div class="row">';
          if( !empty($newspaper_logo) ){
            $code .= '<div class="magazineLogo">';
  					$code .= '<img src="'.$newspaper_logo.'" alt="'.$newspaper_name.'" title="'.$newspaper_name.'">';
  					$code .= '</div>';
          }
					$code .= '<div class="just-image">';
					$code .= '<img src="'.$image.'" alt="'.$title.'" title="'.$title.'" class="w-100">';
					$code .= '</div>';

          if( $hide_title != 1 ){
            $code .= '<div class="news-title">';
            $code .= '<h2>'.$title.'</h2>';
            $code .= '</div>';
          }
          if( $hide_description != 1 ){
            $code .= '<div class="news-excerpet">';
  					$code .= '<p>'.$description;
            if( $hide_more != 1 ){
              $code .= ' <a href="'.$post_url.'" class="read-more">'.lang('read_more_img').'</a>';
            }
            $code .= '</p>';
  					$code .= '</div>';
          }

          if( !empty($url) ){
            $code .= '<div class="source">';
            $code .= '<p><a href="'.$url.'">'.lang('read_from_source_img').'</a></p>';
            $code .= '</div>';
          }

          if( !empty($tweet_url) ){
            $code .= '<div class="show_tweet"><p><a target="_blank" href="'.$get_tweet_url.'">'.$tweet_link_name.'</a></p></div>';
          }

					$code .= '</div>';
          $pdf_arr[] = $code;
        }elseif( $type == 9 ){
          $code = '<div class="row">';
          if( !empty($newspaper_logo) ){
            $code .= '<div class="magazineLogo">';
  					$code .= '<img src="'.$newspaper_logo.'" alt="'.$newspaper_name.'" title="'.$newspaper_name.'">';
  					$code .= '</div>';
          }

					$code .= '<div class="largeimage">';
					$code .= '<a href="'.$url.'"><img src="'.$image.'" alt="'.$title.'" title="'.$title.'" class="w-100"></a>';
					$code .= '</div>';

          if( $hide_title != 1 ){
            $code .= '<div class="news-title">';
            $code .= '<h2>'.$title.'</h2>';
            $code .= '</div>';
          }
          if( $hide_description != 1 ){
            $code .= '<div class="news-excerpet">';
  					$code .= '<p>'.$description;
            if( $hide_more != 1 ){
              $code .= ' <a href="'.$post_url.'" class="read-more">'.lang('read_more_img').'</a>';
            }
            $code .= '</p>';
  					$code .= '</div>';
          }

          if( !empty($url) ){
            $code .= '<div class="donation_url">';
            $code .= '<p><a href="'.$url.'">'.lang('donation_now_img').'</a></p>';
            $code .= '</div>';
          }

					$code .= '</div>';
          $pdf_arr[] = $code;
				}
			}

			$category_url = '<a href="'.url( array('action' => 'category', 'id' => $category_id ) ).'">'.$category_name.'</a>';

      $content_arr = array();
			if( $type == 1 ){
        foreach ($pdf_arr as $keyx => $valuex) {
          $get_news = '<section class="sectinon-a">';
  				$get_news .= '<div class="container">';
          $get_news .= $valuex;
          $get_news .= '</div>';
  				$get_news .= '</section>';
          $content_arr[] = $get_news;
        }
			}elseif( $type == 2 ){

				if( is_array($input_array) && count($input_array) > 0 ){
					$rows = array_chunk($input_array, 2);
					foreach ($rows as $key => $value) {
            $get_news = '<section class="social">';
    				$get_news .= '<div class="container">';
						$get_news .= '<div class="row">';
						if( is_array($value) ){
							foreach ($value as $key2 => $value2) {
								$get_news .= $value2;
							}
						}
						$get_news .= '</div>';
            $get_news .= '</div>';
    				$get_news .= '</section>';
            $content_arr[] = $get_news;
					}
				}

			}elseif( $type == 3 ){
        foreach ($pdf_arr as $keyx => $valuex) {
          $get_news = '<section class="radio">';
  				$get_news .= '<div class="container">';
          $get_news .= $valuex;
          $get_news .= '</div>';
  				$get_news .= '</section>';
          $content_arr[] = $get_news;
        }
			}elseif( $type == 4 ){
        foreach ($pdf_arr as $keyx => $valuex) {
          $get_news = '<section class="najat-tv">';
  				$get_news .= '<div class="container">';
          $get_news .= $valuex;
          $get_news .= '</div>';
  				$get_news .= '</section>';
          $content_arr[] = $get_news;
        }
			}elseif( $type == 5 ){
        foreach ($pdf_arr as $keyx => $valuex) {
          $get_news = '<section class="charity-news">';
  				$get_news .= '<div class="container">';
          $get_news .= $valuex;
          $get_news .= '</div>';
  				$get_news .= '</section>';
          $content_arr[] = $get_news;
        }
			}elseif( $type == 6 ){
        foreach ($pdf_arr as $keyx => $valuex) {
          $get_news = '<section class="charity-news">';
  				$get_news .= '<div class="container">';
  				$get_news .= '<div class="row">';
          $get_news .= $valuex;
          $get_news .= '</div>';
  				$get_news .= '</div>';
  				$get_news .= '</section>';
          $content_arr[] = $get_news;
        }
			}elseif( $type == 8 ){
        foreach ($pdf_arr as $keyx => $valuex) {
          $get_news = '<section class="sectinon-a">';
  				$get_news .= '<div class="container">';
          $get_news .= $valuex;
          $get_news .= '</div>';
  				$get_news .= '</section>';
          $content_arr[] = $get_news;
        }
      }elseif( $type == 9 ){
        foreach ($pdf_arr as $keyx => $valuex) {
          $get_news = '<section class="sectinon-a">';
  				$get_news .= '<div class="container">';
          $get_news .= $valuex;
          $get_news .= '</div>';
  				$get_news .= '</section>';
          $content_arr[] = $get_news;
        }
			}else{
        foreach ($pdf_arr as $keyx => $valuex) {
          $get_news = '<section class="sectinon-a">';
  				$get_news .= '<div class="container">';
          $get_news .= $valuex;
          $get_news .= '</div>';
  				$get_news .= '</section>';
          $content_arr[] = $get_news;
        }
      }
		}

	}

	return $content_arr;
}

function publications_count(){
	global $DB;
	return $DB->N_num_rows( $DB->N_query("SELECT id FROM publications WHERE active=1") );
}

function publications(){
	global $DB;
  $allcount = $DB->N_num_rows( $DB->N_query("SELECT id FROM publications WHERE active=1") );

  $page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
  $page = ($page == 0 ? 1 : $page);
  $perpage = 20;
  $startpoint = ($page * $perpage) - $perpage;

	$info = array();
	$query = $DB->N_query("SELECT * FROM publications WHERE active=1 ORDER BY id DESC LIMIT $startpoint,$perpage");
	$counts = $DB->N_num_rows( $query );
	if( $counts > 0 ){
		while( $row = $DB->N_fetch_array($query) ){
      $id = intval($row['id']);
  		$title = text_filter(3, $row['title']);
  		$description = text_filter(3, $row['description']);
  		$url = text_filter(3, $row['url']);
  		$image = text_filter(3, $row['image']);
  		$text = text_filter(2, $row['text']);
  		$cover = intval($row['cover']);
      $publication_date = text_filter(3, $row['publication_date']);
      $other_file = text_filter(3, $row['other_file']);

  		$info['posts'][] = array(
  			'id' => $id,
  			'title' => $title,
  			'description' => $description,
  			'image' => $image,
  			'cover' => $cover,
        'other_file' => $other_file,
  			'text' => shortcode($text),
        'publication_date' => $publication_date
  		);

    }

    $info['pagination'] = pagination($allcount, $perpage, $page, site_url().'index.php?action=publications&');
	}
	return $info;
}

function get_publications(){
	global $template;

  $template->title = lang('publications_archive');
  $template->url = url( array( 'action' => 'publications' ) );

  $publications = publications();

  if( is_array($publications) && isset($publications['posts']) && is_array($publications['posts']) && count($publications['posts']) > 0 ){
    $output = '<table class="table table-striped mt-4">';
    $output .= '<thead class="thead-dark">';
    $output .= '<tr>';
    //$output .= '<th scope="col">#</th>';
    $output .= '<th scope="col">'.lang('title').'</th>';
    $output .= '<th scope="col" class="text-center">'.lang('publication_date').'</th>';
    $output .= '<th scope="col" class="text-center">'.lang('other_file').'</th>';
    $output .= '<th scope="col" class="text-center"><i class="fas fa-file-pdf"></i></th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';
    $i=0;
    foreach( $publications['posts'] as $key => $publication ){
      $publication_id = ( isset($publication['id']) ? $publication['id'] : 0 );
      $publication_title = ( isset($publication['title']) ? $publication['title'] : '' );
      $publication_description = ( isset($publication['description']) ? $publication['description'] : '' );
      $publication_image = ( isset($publication['image']) ? $publication['image'] : '' );
      $publication_text = ( isset($publication['text']) ? $publication['text'] : '' );
    	$publication_cover = ( isset($publication['cover']) ? $publication['cover'] : '' );
      $publication_date = ( isset($publication['publication_date']) ? $publication['publication_date'] : '' );
      $publication_url = url( array('action' => 'publication', 'publication_id' => $publication_id ) );
      $other_file = ( isset($publication['other_file']) ? $publication['other_file'] : '' );

      $get_other_file = ( !empty($other_file) ? '<a target="_blank" href="'.$other_file.'"><i class="fas fa-file-pdf"></i></a>' : '- - -' );

      ++$i;

      $output .= '<tr>';
      //$output .= '<th scope="row">'.$i.'</th>';
      $output .= '<td><a href="'.$publication_url.'">'.$publication_title.'</a></td>';
      $output .= '<td class="text-center">'.$publication_date.'</td>';
      $output .= '<td class="text-center">'.$get_other_file.'</td>';
      $output .= '<td class="text-center"><a target="_blank" href="'.site_url().'index.php?read=pdf&online=1&publication_id='.$publication_id.'"><i class="fas fa-file-pdf"></i></a></td>';
      $output .= '</tr>';
    }
    $output .= '</tbody>';
    $output .= '</table>';

    $output .= ( isset($publications['pagination']) ? $publications['pagination'] : '' );
  }else{
    $output = lang('not_found');
  }

	return $output;
}

function publication($publication_id=0){
	global $DB;
	//$publication_id = ( isset($_GET['publication_id']) ? intval($_GET['publication_id']) : 0 );
	$info = array();
	if( $publication_id > 0 ){
		$query = $DB->N_query("SELECT * FROM publications WHERE active=1 AND id=$publication_id ORDER BY id DESC LIMIT 1");
	}else{
		$query = $DB->N_query("SELECT * FROM publications WHERE active=1 ORDER BY id DESC LIMIT 1");
	}
	$counts = $DB->N_num_rows( $query );
	if( $counts > 0 ){
		$row = $DB->N_fetch_array($query);
		$id = intval($row['id']);
		$title = text_filter(3, $row['title']);
		$description = text_filter(3, $row['description']);
		$url = text_filter(3, $row['url']);
		$image = text_filter(3, $row['image']);
		$text = text_filter(2, $row['text']);
		$cover = intval($row['cover']);
    $publication_date = text_filter(3, $row['publication_date']);
    $other_file = ( isset($row['other_file']) ? text_filter(3, $row['other_file']) : '' );

		$info = array(
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'image' => $image,
			'cover' => $cover,
      'other_file' => $other_file,
			'text' => shortcode($text),
      'publication_date' => $publication_date,
      'date' => $row['date']
		);
	}
	return $info;
}

function get_publication(){
	global $template, $DB;

  $id = ( isset($_GET['publication_id']) ? intval($_GET['publication_id']) : 0 );
  $publication = publication($id);
  $publication_id = ( isset($publication['id']) ? $publication['id'] : 0 );
  $publication_title = ( isset($publication['title']) ? $publication['title'] : '' );
  $publication_description = ( isset($publication['description']) ? $publication['description'] : '' );
  $publication_image = ( isset($publication['image']) ? $publication['image'] : '' );
  $publication_text = ( isset($publication['text']) ? $publication['text'] : '' );
	$publication_cover = ( isset($publication['cover']) ? $publication['cover'] : '' );
  $publication_d = ( isset($publication['date']) ? $publication['date'] : '' );
  $publication_date = ( isset($publication['publication_date']) ? $publication['publication_date'] : '' );
  $publication_url = url( array('action' => 'publication', 'publication_id' => $publication_id ) );

  if( !empty($publication_date) ){
    $d_time = new DateTime( $publication_date );
    $getTimestamp = $d_time->getTimestamp();
    $today = ( empty($getTimestamp) ? date("l j M Y", time()) : date("l j M Y", $getTimestamp) );
  }else{
    $today = ( empty($publication_d) ? date("l j M Y", time()) : date("l j M Y", $publication_d) );
  }
  //$today = ( empty($publication_d) ? date("l j M Y", time()) : date("l j M Y", $publication_d)  );

	$hide_category_title = 0;
	$original_image = 0;
	$hide_title = 0;
	$hide_description = 0;
	$hide_more = 0;
	$order = 0;

  $get_image = ( empty($publication_image) ? '' : '<img src="'.$publication_image.'" alt="'.$publication_title.'" class="w-100">' );
	$template->title = day_name($today);
	$template->image = $publication_image;
	$template->description = $publication_description;
	$template->url = $publication_url;

  $publisheDate = $publication_date;
  $data_news = news_by_date($publisheDate);

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

	$posts = array();
    if( isset($data_news[$box_category_id]) && count($data_news[$box_category_id]) > 0 ){

      foreach($data_news[$box_category_id] as $key => $value){
        $id = ( isset($value['id']) ? $value['id'] : 0 );
        $title = ( isset($value['title']) ? $value['title'] : '' );
        $description = ( isset($value['description']) ? $value['description'] : '' );
        $image = ( isset($value['image']) ? $value['image'] : '' );
        $url = ( isset($value['url']) ? $value['url'] : '' );
        $text = ( isset($value['text']) ? $value['text'] : '' );
        $newspaper_number = ( isset($value['newspaper_number']) ? $value['newspaper_number'] : 0 );
        $newspaper_id = ( isset($value['newspaper_id']) ? $value['newspaper_id'] : 0 );
        $newspaper_name = ( isset($value['newspaper_name']) ? $value['newspaper_name'] : '' );
        $newspaper_logo = ( isset($value['newspaper_logo']) ? $value['newspaper_logo'] : '' );
        $date = ( isset($value['date']) ? $value['date'] : '' );
        $published_date = ( isset($value['published_date']) ? $value['published_date'] : '' );
        $shortcode = ( isset($value['shortcode']) ? $value['shortcode'] : '' );
        $tweet_url = ( isset($value['tweet_url']) ? $value['tweet_url'] : '' );
        $sound_url = ( isset($value['sound_url']) ? $value['sound_url'] : '' );
        $video_url = ( isset($value['video_url']) ? $value['video_url'] : '' );
        $hide_category_title = ( isset($value['hide_category_title']) ? $value['hide_category_title'] : 0 );
        $original_image = ( isset($value['original_image']) ? $value['original_image'] : 0 );
        $hide_title = ( isset($value['hide_title']) ? $value['hide_title'] : 0 );
        $hide_description = ( isset($value['hide_description']) ? $value['hide_description'] : 0 );
        $hide_more = ( isset($value['hide_more']) ? $value['hide_more'] : 0 );
        $order = ( isset($value['order']) ? $value['order'] : 0 );

        $get_image = get_image($image, 'large'); // xsmall, small, medium, large
        $get_xsmall_image = get_image($image, 'xsmall');
        $get_small_image = get_image($image, 'small');
        $get_medium_image = get_image($image, 'medium');
        $get_newspaper_logo = $newspaper_logo; //get_image($newspaper_logo, 'small');

        if( !empty($title) ){
          $posts[] = array(
  					'id' => $id,
  					'title' => $title,
            'url' => $url,
  					'tweet_url' => $tweet_url,
  					'sound_url' => $sound_url,
  					'video_url' => $video_url,
  					'image' => $image,
  					'description' => $description,
  					'post_url' => url( array( 'action' => 'show', 'id' => $id ) ),
  					'newspaper_logo' => $get_newspaper_logo,
  					'newspaper_name' => $newspaper_name,
            'original_image' => $original_image,
            'hide_title' => $hide_title,
            'hide_description' => $hide_description,
            'hide_more' => $hide_more
  				);
        }
      }
    }

    $data = array();
    $data['type'] = $box_type;
    $data['category_name'] = $category_name;
    $data['category_id'] = $box_category_id;
    $data['hide_category_title'] = $hide_category_title;
    $data['posts'] = $posts;

    if( empty($banner) ){
      $news .= ( empty($box_code) ? news_template( $data ) : $box_code );
    }else{
      $news .= $banner;
    }

  }

  $content = '<section class="sectinon-a">';
	$content .= '<div class="container">';
	$content .= $template->breadcrumb();
	$content .= '<div class="publication-content">';
	if( !empty($publication_image) ){
		$content .= '<div class="largeimage">';
		$content .= '<img src="'.$publication_image.'" alt="'.$publication_title.'" title="'.$publication_title.'" class="w-100">';
		$content .= '</div>';
	}
	$content .= '<div class="news-title">';
	$content .= '<h2 class="text-center border-bottom mb-4 pb-3">'.day_name($today).'</h2>';
	$content .= '</div>';
	$content .= '<div class="news-excerpet">';
	$content .= $publication_text;
  $content .= $news;
	$content .= '</div>';
	$content .= '</div>';

	return $content;
}

function url($var = array()){
	$site_url = site_url();
	$RewriteRule = 1;

	if( is_array($var) ){
		$url = '';
		$url2 = '';
		foreach ($var as $key => $value) {
			$url .= $key.'='.$value.'&';
      if( $value == 'publications' ){
        $url2 .= 'archive.html';
      }else{
        $url2 .= $value.'/';
      }
		}
		if( $RewriteRule == 1 ){
			$get_url = rtrim($url2, '/');
		}else{
			$get_url = 'index.php?'.rtrim($url, '&');
		}
	}else{
		$get_url = $var;
	}
	return $site_url.$get_url;
}

function share($titles='', $links=''){

	//$twitter_via = '&amp;via='.$twitter_username;
	$twitter_via = '';

	$title = htmlentities(urlencode($titles));
	$url = urlencode($links);

	$code = '<div class="share">';
	$code .= '<div class="row">';
	$code .= '<div class="col-1">'.lang('share').'</div>';
	$code .= '<div class="col-1"><a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u='.$url.'&title='.$title.'"><i class="fab fa-facebook-square" aria-hidden="true"></i></a></div>';
	$code .= '<div class="col-1"><a target="_blank" href="https://twitter.com/intent/tweet?text='.$title.'&amp;url='.$url.''.$twitter_via.'"><i class="fab fa-twitter-square" aria-hidden="true"></i></a></div>';
	//$code .= '<div class="col-1"><a target="_blank" href="https://plus.google.com/share?url='.$url.'"><i class="fab fa-google-plus-square" aria-hidden="true"></i></a></div>';
	$code .= '<div class="col-1"><a target="_blank" href="http://pinterest.com/pin/create/bookmarklet/?media=[MEDIA]&url='.$url.'&is_video=false&description='.$title.'"><i class="fab fa-pinterest-square" aria-hidden="true"></i></a></div>';
	$code .= '<div class="col-1"><a target="_blank" href="http://www.reddit.com/submit?url='.$url.'&title='.$title.'"><i class="fab fa-reddit-square" aria-hidden="true"></i></a></div>';
	$code .= '<div class="col-1"><a target="_blank" href="http://www.stumbleupon.com/submit?url='.$url.'&title='.$title.'"><i class="fab fa-stumbleupon-circle"></i></a></div>';
	$code .= '<div class="col-1"><a target="_blank" href="http://www.linkedin.com/shareArticle?mini=true&url='.$url.'&title='.$title.'&source="><i class="fab fa-linkedin"></i></a></div>';
	$code .= '<div class="col-1"><a target="_blank" href="http://www.tumblr.com/share?v=3&u='.$url.'&t='.$title.'"><i class="fab fa-tumblr-square" aria-hidden="true"></i></a></div>';
	$code .= '<div class="col-1"><a target="_blank" href="https://api.whatsapp.com/send?text='.strip_tags($titles).' '.$links.'"><i class="fab fa-whatsapp-square"></i></a></div>';
	$code .= '<div class="col-1"><a href="mailto:?subject='.$title.'&amp;body='.$url.'."><i class="fas fa-envelope-square"></i></a></div>';
	$code .= '<div class="col-1"></div>';
  $code .= '<div class="col-1"></div>';
	$code .= '</div>';
	$code .= '</div>';

	return $code;
}

function youtube_match( $data ){
	if( strlen($data) == 11 ){
		return $data;
	}
	preg_match( "/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/", $data, $matches);
	return isset($matches[2]) ? $matches[2] : false;
}

function get_youtube_id( $url ){
	$url_string = parse_url($url, PHP_URL_QUERY);
	parse_str($url_string, $args);

	$youtube_id = ( isset($args['v']) ? $args['v'] : '' );

	if( empty($youtube_id) ){
		return youtube_match( $url );
	}else{
		return $youtube_id;
	}
}

function cuttext( $limit=200, $text="", $end_text='...' ) {
	if ( strlen ( $text ) < $limit ) {
		return $text;
	}
	$split_words = explode(' ', $text );
	$out = '';
	foreach ( $split_words as $w ) {
		if ( ( strlen( $w ) > $limit ) && $out == null ) {
			return substr( $w, 0, $limit ).$end_text;
		}

		if (( strlen( $out ) + strlen( $w ) ) > $limit) {
			return $out . $end_text;
		}
		$out .= " " . $w;
	}

	return $out;
}

$template->site_name = setting('site_title');
$template->site_tagline = setting('site_slogan');
$template->site_description = setting('site_description');
$template->site_logo = setting('site_logo');
$template->site_url = site_url();
$template->js = setting('header_code');
$template->footer_js = setting('footer_code');
?>
