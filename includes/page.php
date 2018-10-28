<?php
get_header();
$data = auto_generator_get_data($auto_generator_id, urldecode($auto_generator_name));

if (!$data) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( 404 ); exit();
}; ?>
    <style>h1{padding:20px 0}.images a{display:inline-block}.images a.main{vertical-align:top}.images div.im-right{display:inline-block;width:15.1vw}.images div.im-bottom a{vertical-align:middle;width:11.3vw}.images a img{box-shadow:1px 0 1px 1px rgba(0,0,0,0.5);margin-bottom:5px;width:100%}.flex>div{margin-right:10px}.flex.flex-nav>div{display:inline-block;width:48%}.flex.flex-nav>div:last-of-type{text-align:right}.price{font-size:23px;margin-bottom:15px}.price span{color:red}.text{font-size:14px;line-height:21px;text-align:justify}.text>p{padding-bottom:5px;font-size:14px;width:50%;}@media (min-width:1270px){.images div.im-right{width:32.6%}.images div.im-bottom a{width:18%;margin-left:1%}}@media (max-width:812px){.flex>div{width:100%;text-align:center}.images a.main{width:auto}.images div.im-right{width:auto}.images div.im-bottom a{width:auto}}.tpl2.flex{flex-direction:column-reverse}.tpl2.flex>div.images{width:100%}.tpl2.flex>div.part-content{margin-left:10px}.tpl2 .images div.im-bottom a{margin:15px 10px 10px;width:18%}.tpl2 .images div.im-bottom a:first-of-type{display:block;width:65%}.tpl2 .images a img{border-radius:0}.hide{display:none}@media (max-width: 824px){.tpl2 .images div.im-bottom a{width:auto}}.art{margin: 15px 0 40px}.re-link{display:flex}.re-link>div{width:50%}</style>
    <h1><?php echo $data->title; ?></h1>
    <div class="flex <?php echo $data->template ?: 'tpl2' ?>">
        <div class="part-content">
            <div class="price"><strong>Цена: </strong><span><?php echo $data->price ?> руб.</span></div>
            <div class="text">
                <p><?php
					$multiple = explode('[auto]', $data->multiple);
					$name = $data->title;
					foreach ($multiple as $part) {
					    $name = str_replace($part, '', $name);
                    }

					$list = file_get_contents(__DIR__ . '/../list.json');
					$list = json_decode($list, 1);
					$list2 = file_get_contents(__DIR__ . '/../list.rus.json');
					$list2 = json_decode($list2, 1);
					$text = '';
					if (array_key_exists($name, $list)) {
						$elements = array();
						foreach ($list[$name] as $model => $bodies) {
							$element = $model . ' (' . implode(', ', $bodies) . ')';
							$element = preg_replace('/\s*\(\d{4}(\-|–)(\d{4}|нв)\)/', '', $element);
							$element = preg_replace('/\s*\(\)/', '', $element);
							$elements[] = $element;
						}
						$text = implode(', ', $elements);
					}
					$years = array();
					foreach ($list2 as $mark => $models) {
						foreach ($models as $model => $bodies) {
							foreach ($bodies as $body) {
								$name2 = $mark . ' ' . $model . ' ' . $body;
								if (stripos($name, $name2) !== false) {
									preg_match_all('/\s*\(\d{4}\-(\d{4}|нв)\)/', $name2, $patterns);
									$name = str_replace($patterns[0], '', $name2);
                                    preg_match('/(\d{4})\-(\d{4}|нв)/', $patterns[0], $gates);
                                    if (count($gates) != 3) {
                                        continue;
                                    }
                                    $gates[2] = $gates[2] == 'нв' ? date('Y') : $gates[2];
                                    $gates = range($gates[1], $gates[2]);
                                    foreach ($gates as $year) {
                                        $years[$year] = $year;
                                    }
								}
                            }
						}
					}
					$years = implode(', ', $years);
					echo str_replace(array('[name]', '[title]', '[models-n-bodies]', '[years]'), array($name, $data->title, $text, $years), $data->text_before);?>
                </p>
            </div>
        </div>

        <div class="images">
            <div class="im-bottom">
				<?php $i = 0; foreach ($data->images as $key => $link): ?>
                    <a class="image" data-fancybox="gallery" href="<?php echo $link ?>"><img src="<?php echo $link ?>"/></a>
                    <div class="hide"><?php echo ++$i.'. '.str_replace($data->multiple.' ', '', $data->title) ?></div>
				<?php endforeach; ?>
            </div>
        </div>

        <div class="part-content">
            <div class="text"><?php
				echo str_replace(array('[name]', '[title]', '[models-n-bodies]', '[years]'), array($name, $data->title, $text, $years), $data->text_after);?></div>
        </div>
    </div>

    <div class="art"><strong>Артикул</strong>: <?php echo $data->art; ?></div>
    <div class="art"><?php echo date('d.m.Y    H:i:s', $data->date); ?></div>

<?php
$path = wp_upload_dir();
$path = $path['basedir'] . '/ag_json';
$files = scandir($path);
$files = array_slice($files, 2, count($files));

if (!get_option( 'auto_catalog_ids') || (strtotime("-21 day") > get_option( 'auto_catalog_time'))) {
	$ids = range(0, count($files));
	shuffle($ids);
	$ids = implode(',', $ids);
	update_option('auto_catalog_ids', $ids);
	update_option('auto_catalog_time', time());
}

$ids = get_option('auto_catalog_ids', '0');
$ids = explode(',', $ids);
$key = array_search($data->title . '.json', $files);
$prev = isset($ids[$key - 1]) ? $ids[$key - 1] : $ids[$key + 2];
$next = isset($ids[$key + 1]) ? $ids[$key + 1] : $ids[$key - 2];

$prev = json_decode(file_get_contents($path . '/' . $files[$prev]));
$next = json_decode(file_get_contents($path . '/' . $files[$next])); ?>

    <div class="re-link">
        <div><a href="<?php echo get_site_url(); ?>/1<?php echo $auto_generator_id?>/<?php echo str_replace(' ', '_', $prev->title); ?>"><?php echo $prev->title ?></a></div>
        <div style="text-align:right"><a href="<?php echo get_site_url(); ?>/1<?php echo $auto_generator_id?>/<?php echo str_replace(' ', '_', $next->title); ?>"><?php echo $next->title ?></a></div>
    </div>

    <link rel="stylesheet" href="<?php echo plugins_url( '/css/jquery.fancybox.css', __FILE__);?>"/>
    <script src="<?php echo plugins_url( '/js/jquery.fancybox.min.js', __FILE__ );?>"></script>
<?php get_footer(); ?>