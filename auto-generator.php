<?php
/*
 * Plugin Name: Car-named pages Generator
 * Plugin URI:  https://github.com/Endoroo/wp-autogen-plugin
 * Version:     1.0
 */

function auto_generator_install() {
	global $wpdb;
	$settings_table = $wpdb->prefix . 'ag_multiply';
	add_option('auto_catalog_table', $settings_table);

	if ($wpdb->get_var("SHOW TABLES LIKE '$settings_table'") != $settings_table) {
		$sql = "CREATE TABLE $settings_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name text NOT NULL,
			settings longtext DEFAULT NULL,
			UNIQUE KEY id (id)
		) CHARACTER SET utf8 COLLATE utf8_bin;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'auto_generator_install');

function auto_generator_deactivation() {
	global $wpdb;
	$settings_table = get_option('auto_catalog_table');
	delete_option('auto_catalog_table');
	$wpdb->query("DROP TABLE IF EXISTS $settings_table");
	$path = wp_upload_dir();
	rmdir($path['basedir'] . '/ag_json/', 1);
	rmdir($path['basedir'] . '/ag_images/', 1);

	flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'auto_generator_deactivation');

function auto_generator_rewrites_init() {
	add_rewrite_rule(
		'(\d+)/([\d\w%\-\_\.\,]+)/?$',
		'index.php?auto_generator_id=$matches[1]&auto_generator_name=$matches[2]',
		'top');
	flush_rewrite_rules();
}

add_action('init', 'auto_generator_rewrites_init');

function auto_generator_query_vars($query_vars) {
	$query_vars[] = 'auto_generator_id';
	$query_vars[] = 'auto_generator_name';
	return $query_vars;
}

add_filter('query_vars', 'auto_generator_query_vars');

function auto_generator_rewrite_templates() {
	if (($id = get_query_var('auto_generator_id'))
		&& ($name = get_query_var('auto_generator_name'))) {
		add_filter('template_include', function () {
			return plugin_dir_path(__FILE__) . '/includes/page.php';
		});
	}
}

add_action('template_redirect', 'auto_generator_rewrite_templates');

function auto_generator_meta_tags() {
	global $post;
	if ($post->post_type == 'auto-generator') {
		$data = auto_generator_get_data($post->id);

		$post->post_title = $data->title;
		$keywords = str_replace(['[auto]', '[title]'], [
			$data->multiple,
			$data->title,
		], $data->keywords);
		$description = str_replace(['[auto]', '[title]'], [
			$data->multiple,
			$data->title,
		], $data->description);
		echo '<meta name="description" content="' . $description . '" />' . "\n";
		echo '<meta name="keywords" content="' . $keywords . '" />' . "\n";
		echo '<title>' . $data->title . '</title>' . "\n";
	}
	else {
		echo '<title>' . wp_get_document_title() . '</title>' . "\n";
	}
}

add_action('wp_head', 'auto_generator_meta_tags', 2);
remove_action('wp_head', '_wp_render_title_tag', 1);

function auto_generator_get_data($name) {
	$name = str_replace('_', ' ', $name);

	$path = wp_upload_dir();
	$path = $path['basedir'] . '/ag_json/' . $name . '.json';
	if (file_exists($path)) {
		return json_decode(file_get_contents($path));
	}

	return FALSE;
}

/*
 * Admin page
 */
add_action('wp_default_scripts', function ($scripts) {
	if (!empty($scripts->registered['jquery'])) {
		$scripts->registered['jquery']->deps = array_diff($scripts->registered['jquery']->deps, ['jquery-migrate']);
	}
});

function auto_generator_options_page_html() {
	// check user capabilities
	if (!current_user_can('manage_options')) {
		return;
	}

	global $wpdb;
	$settings_table = get_option('auto_catalog_table');

	$rows = $wpdb->get_results("SELECT * FROM $settings_table ORDER BY name");
	$values = [];
	foreach ($rows as $row) {
		$values[$row->id] = $row->name;
	}

	wp_enqueue_media('media-upload');
	wp_enqueue_media('thickbox');
	wp_enqueue_style('thickbox');

	// add custom js
	wp_enqueue_script('auto_generator-init', plugins_url('/includes/js/init.js', __FILE__));
	wp_enqueue_script('auto_generator-tablesort', plugins_url('/includes/js/jquery.tablesorter.min.js', __FILE__));
	wp_enqueue_script('auto_generator-tablesort-pager', plugins_url('/includes/js/jquery.tablesorter.pager.js', __FILE__));
	wp_enqueue_media('auto_generator-init');
	wp_enqueue_media('auto_generator-tablesort');
	wp_enqueue_media('auto_generator-tablesort-pager');

	// add custom css
	wp_register_style('auto_generator-tablesort', plugins_url('/includes/css/jquery.tablesorter.blue-theme.css', __FILE__));
	wp_register_style('auto_generator-tablesort-pager', plugins_url('/includes/css/jquery.tablesorter.pager.css', __FILE__));
	wp_enqueue_style('auto_generator-tablesort');
	wp_enqueue_style('auto_generator-tablesort-pager');

	$url = plugin_dir_url(__FILE__);
	$content_img = $_POST['content_img'];
	include_once('includes/settings.php');
}

function auto_generator_options_page() {
	add_submenu_page(
		'options-general.php',
		'Настроки плагина',
		'Настройки генератора',
		'manage_options',
		'ag_options',
		'auto_generator_options_page_html'
	);
}

add_action('admin_menu', 'auto_generator_options_page');

/*
 * Admin page AJAX callbacks
 */
function auto_generator_add_multiply() {
	if (isset($_REQUEST['multiply']) && !empty($_REQUEST['multiply'])) {
		$value = trim($_REQUEST['multiply']);
		global $wpdb;
		$settings_table = get_option('auto_catalog_table');

		$value = strpos($value, '[auto]') === FALSE ? $value . ' [auto]' : $value;
		$wpdb->insert($settings_table, [
			'name' => $value,
		]);
		$result = $wpdb->get_var("SELECT COUNT(*) FROM $settings_table;");
		echo json_encode([
			'message' => 'Добавление прошло успешно',
			'add' => "<tr>
<td>$result</td>
<td><input type='checkbox' name='multiply-ids[]' value='{$wpdb->insert_id}'></td>
<td>
	<div class='title'>$value</div>
	<div class='links'>
		<a href='#' title='выгрузить'
		   class='csv-multiply'
		   data-id='{$wpdb->insert_id}'>Выгрузить</a>
		<a href='#' title='генерировать'
		   class='generate-multiply'
		   data-id='{$wpdb->insert_id}'>Генерировать</a>
		<a href='#' title='загрузить параметры'
		   class='load-multiply'
		   data-id='{$wpdb->insert_id}'>Загрузить</a>
		<a href='#' title='удалить позицию'
		   class='remove-multiply'
		   data-id='{$wpdb->insert_id}'>Удалить</a>
		<a href='#' title='удалить страницы'
		   class='clear-multiply'
		   data-id='{$wpdb->insert_id}'>Удалить страницы</a>
	</div>
</td></tr>",
		]);
	}
	else {
		echo json_encode(['message' => 'Произошла ошибка при добавлении']);
	}
	wp_die();
}

add_action('wp_ajax_auto_generator_add_multiply', 'auto_generator_add_multiply');

function auto_generator_remove_multiply() {
	if (isset($_REQUEST['multiply']) && is_numeric($_REQUEST['multiply'])) {
		$value = trim($_REQUEST['multiply']);
		global $wpdb;
		$settings_table = get_option('auto_catalog_table');

		$wpdb->query($wpdb->prepare("DELETE FROM $settings_table WHERE id = %s", $value));
		echo json_encode(['message' => 'Удаление прошло успешно']);
	}
	else {
		echo json_encode(['message' => 'Произошла ошибка при удалении']);
	}
	wp_die();
}

add_action('wp_ajax_auto_generator_remove_multiply', 'auto_generator_remove_multiply');

function auto_generator_load_multiply() {
	if (isset($_REQUEST['multiply']) && is_numeric($_REQUEST['multiply'])) {
		$value = trim($_REQUEST['multiply']);
		global $wpdb;
		$settings_table = get_option('auto_catalog_table');

		$settings = $wpdb->get_results($wpdb->prepare("SELECT * FROM $settings_table WHERE id = %d", $value));
		if (count($settings)) {
			$settings = array_shift($settings);
			$settings = json_decode($settings->settings);
			echo json_encode([
				'message' => 'Параметры успешно загружены',
				'settings' => $settings,
			]);
		}
		else {
			echo json_encode([
				'message' => 'Параметры ещё не были установлены для этого множителя. Можно использовать для создания формы ниже',
				'settings' => [],
			]);
		}
	}
	else {
		echo json_encode(['message' => 'Произошла ошибка при загурзке параметров']);
	}
	wp_die();
}

add_action('wp_ajax_auto_generator_load_multiply', 'auto_generator_load_multiply');

function auto_generator_save_multiply() {
	if (isset($_REQUEST['multiply']) && is_numeric($_REQUEST['multiply'])) {
		$value = trim($_REQUEST['multiply']);
		global $wpdb;
		$settings_table = get_option('auto_catalog_table');

		$settings = new stdClass();
		$settings->price_from_1 = trim($_REQUEST['price-from'][0]);
		$settings->price_from_2 = trim($_REQUEST['price-from'][1]);
		$settings->price_to_1 = trim($_REQUEST['price-to'][0]);
		$settings->price_to_2 = trim($_REQUEST['price-to'][1]);
		$settings->price_step_1 = trim($_REQUEST['price-step'][0]);
		$settings->price_step_2 = trim($_REQUEST['price-step'][1]);
		$settings->date_from = trim($_REQUEST['date-from']);
		$settings->date_to = trim($_REQUEST['date-to']);
		$settings->keywords = trim($_REQUEST['keywords']);
		$settings->keywords = empty($settings->keywords) ? '[title]' : $settings->keywords;
		$settings->description = trim($_REQUEST['description']);
		$settings->text_before = trim($_REQUEST['text-before']);
		$settings->text_after = trim($_REQUEST['text-after']);
		$settings->template = isset($_REQUEST['template']) ? trim($_REQUEST['template']) : '';
		if ($_REQUEST['images'] && $_REQUEST['ids']) {
			$images = [];
			foreach ($_REQUEST['ids'] as $key => $id) {
				if (is_numeric($id)) {
					$images[$id] = $_REQUEST['images'][$key];
				}
			}

			$settings->images = $images;
		}

		$flag = $wpdb->get_results("SELECT id FROM $settings_table WHERE id = $value");
		if (count($flag)) {
			$wpdb->update($settings_table, ['settings' => json_encode($settings)], ['id' => $value]);
			echo json_encode(['message' => 'Обновление прошло успешно']);
		}
		else {
			echo json_encode(['message' => 'Множитель не найден']);
		}
	}
	else {
		echo json_encode(['message' => 'Произошла ошибка при сохранении параметров']);
	}
	wp_die();
}

add_action('wp_ajax_auto_generator_save_multiply', 'auto_generator_save_multiply');

if (!function_exists('transliteration')) {
	function transliteration($str) {
		$cyr = [
			'а',
			'б',
			'в',
			'г',
			'д',
			'е',
			'ё',
			'ж',
			'з',
			'и',
			'й',
			'к',
			'л',
			'м',
			'н',
			'о',
			'п',
			'р',
			'с',
			'т',
			'у',
			'ф',
			'х',
			'ц',
			'ч',
			'ш',
			'щ',
			'ъ',
			'ы',
			'ь',
			'э',
			'ю',
			'я',
			'А',
			'Б',
			'В',
			'Г',
			'Д',
			'Е',
			'Ё',
			'Ж',
			'З',
			'И',
			'Й',
			'К',
			'Л',
			'М',
			'Н',
			'О',
			'П',
			'Р',
			'С',
			'Т',
			'У',
			'Ф',
			'Х',
			'Ц',
			'Ч',
			'Ш',
			'Щ',
			'Ъ',
			'Ы',
			'Ь',
			'Э',
			'Ю',
			'Я',
			'/',
		];
		$lat = [
			'a',
			'b',
			'v',
			'g',
			'd',
			'e',
			'io',
			'zh',
			'z',
			'i',
			'y',
			'k',
			'l',
			'm',
			'n',
			'o',
			'p',
			'r',
			's',
			't',
			'u',
			'f',
			'h',
			'ts',
			'ch',
			'sh',
			'sht',
			'a',
			'i',
			'y',
			'e',
			'yu',
			'ya',
			'A',
			'B',
			'V',
			'G',
			'D',
			'E',
			'Io',
			'Zh',
			'Z',
			'I',
			'Y',
			'K',
			'L',
			'M',
			'N',
			'O',
			'P',
			'R',
			'S',
			'T',
			'U',
			'F',
			'H',
			'Ts',
			'Ch',
			'Sh',
			'Sht',
			'A',
			'I',
			'Y',
			'e',
			'Yu',
			'Ya',
			'-',
		];
		$res = str_replace($cyr, $lat, $str);
		$res = str_replace(" ", "-", strtolower($res));

		return $res;
	}
}

function auto_generator_generate_multiply() {
	$mode = empty($_REQUEST['mode']) ? 3 : (int)$_REQUEST['mode'];
	if (isset($_REQUEST['id'])) {
		$id = sanitize_text_field($_REQUEST['id']);
		$id = explode(',', $id);
		if (!count($id)) {
			echo json_encode(['message' => 'Произошла ошибка при генерации']);
			wp_die();
		}

		global $wpdb;
		$settings_table = get_option('auto_catalog_table');
		$id = implode(',', $id);
		$settings = $wpdb->get_results("SELECT * FROM $settings_table WHERE id IN ($id)");

		$list = file_get_contents(__DIR__ . '/list.json');
		$list = json_decode($list, 1);
		$mm = [];
		if ($mode == 1) {
			$list = array_keys($list);
			foreach ($list as $item) {
				$mm[] = (object) ['name' => $item];
			}
		}
		elseif ($mode == 2) {
			foreach ($list as $mark => $models) {
				$models = array_keys($models);
				foreach ($models as $model) {
					$mm[] = (object) ['name' => $mark . ' ' . $model];
				}
			}
		}
		elseif ($mode == 3) {
			foreach ($list as $mark => $models) {
				$mm[] = (object) ['name' => trim($mark)];
				foreach ($models as $model => $bodies) {
					$mm[] = (object) ['name' => trim($mark . ' ' . $model)];
					foreach ($bodies as $body) {
						preg_match('/\((\d{4})\-(\d{4}|нв)\)/', $body, $years);
						if (count($years) != 3) {
							continue;
						}
						$body = trim(str_replace($years[0], '', $body));
						$mm[] = (object) ['name' => trim($mark . ' ' . $model . ' ' . $body)];
					}
				}
			}
		}

		$count = 0;
		if (count($settings) && count($mm)) {
			// prepare images gen
			$path = wp_upload_dir();
			if (!file_exists($path['basedir'] . '/ag_images')) {
				wp_mkdir_p($path['basedir'] . '/ag_images');
			}
			// prepare json gen
			if (!file_exists($path['basedir'] . '/ag_json')) {
				wp_mkdir_p($path['basedir'] . '/ag_json');
			}

			// add generation for non exist
			$files = list_files($path['basedir'] . '/ag_json');
			$titles = [];
			foreach ($files as $file) {
				$file = pathinfo($file);
				$titles[$file['filename']] = $file['filename'];
			}

			// generation loop
			foreach ($settings as $item) {
				$s = json_decode($item->settings);
				$price1 = range($s->price_from_1, $s->price_to_1, $s->price_step_1);
				$price2 = range($s->price_from_2, $s->price_to_2, $s->price_step_2);

				foreach ($mm as $m) {
					$title = str_replace('[auto]', $m->name, $item->name);
					if (array_key_exists($title, $titles)) {
						continue;
					}

					$chars = ['A', 'N', 'R', 'T', 'X', 'W', 'P', 'Q', 'V', 'S'];
					$art = '';
					foreach (str_split(strval(mt_rand(10000, 99999))) as $char) {
						$art .= $chars[$char];
						if (strlen($art) > 15) {
							break;
						}
						$art .= $char;
						if (strlen($art) > 15) {
							break;
						}
						$art .= $chars[$char];
						if (strlen($art) > 15) {
							break;
						}
					}
					$post = [
						'title' => $title,
						'multiple' => $item->name,
						'price' => $price1[array_rand($price1)] . ' - ' . $price2[array_rand($price2)],
						'keywords' => $s->keywords,
						'description' => $s->description,
						'text_before' => $s->text_before,
						'text_after' => $s->text_after,
						'template' => $s->template,
						'art' => $art,
						'date' => mt_rand(strtotime($s->date_from), strtotime($s->date_to)),
					];

					$files = [];
					foreach ($s->images as $key => $id) {
						$id = get_attached_file($key);
						$ext = explode('.', $id);
						$ext = end($ext);
						$base = '/ag_images/' . $title . '-' . $key . str_pad(mt_rand(0, 99999999), 8, STR_PAD_BOTH) . '.' . $ext;
						$file = $path['basedir'] . $base;
						$files[] = $path['baseurl'] . $base;
						symlink($id, $file);
					}
					$post['images'] = $files;
					file_put_contents(
						$path['basedir'] . '/ag_json/' . $title . '.json',
						json_encode($post)
					);
					$count++;
				}
			}
		}
		if ($count) {
			echo json_encode(['message' => 'Генерация прошла успешно. Сгенерировано ' . $count . ' постов']);
		}
		else {
			echo json_encode(['message' => 'Генерировать больше нечего']);
		}
	}
	else {
		echo json_encode(['message' => 'Произошла ошибка при генерации']);
	}
	wp_die();
}

add_action('wp_ajax_auto_generator_generate_multiply', 'auto_generator_generate_multiply');

function auto_generator_clear_multiply() {
	if (isset($_REQUEST['id'])) {
		$id = is_numeric($_REQUEST['id']) ? $_REQUEST['id'] : 0;

		global $wpdb;
		$settings_table = get_option('auto_catalog_table');
		$settings = $wpdb->get_results($wpdb->prepare("SELECT * FROM $settings_table WHERE id = %d", $id));
		$s = array_shift($settings);

		$count = 0;
		$path = wp_upload_dir();
		$s->name = str_replace(' [auto]', '*', $s->name);
		foreach (glob($path['basedir'] . '/ag_json/' . $s->name . '.json') as $file) {
			$content = file_get_contents($file);
			if (file_exists($file)) {
				unlink($file);
				$count++;
			}
			$content = json_decode($content);
			$images = $content->images;

			foreach ($images as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
		}

		echo json_encode(['message' => $count ? 'Удаление запчастей(' . $count . ') прошло успешно' : 'Удалять больше нечего']);
	}
	else {
		echo json_encode(['message' => 'Произошла ошибка при удалении']);
	}
	wp_die();
}

add_action('wp_ajax_auto_generator_clear_multiply', 'auto_generator_clear_multiply');

function auto_generator_csv_multiply() {
	if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$id = trim($_REQUEST['id']);

		global $wpdb;
		$settings_table = get_option('auto_catalog_table');
		if ($id) {
			$settings = $wpdb->get_results($wpdb->prepare("SELECT * FROM $settings_table WHERE id = %d", $id));
		}
		else {
			$settings = $wpdb->get_results("SELECT * FROM $settings_table");
		}
		$path = wp_upload_dir();

		$csv = "title;url" . PHP_EOL;
		foreach ($settings as $s) {
			$s->name = str_replace('[auto]', '*', $s->name);
			foreach (glob($path['basedir'] . "/ag_json/$s->name*.json") as $file) {
				$file = file_get_contents($file);
				$file = json_decode($file);
				$csv .= "{$file->title};" . (get_site_url() . "/1$s->id/" . str_replace(' ', '_', $file->title)) . PHP_EOL;
			}
		}
		$file = $path['basedir'] . '/part' . $id . '.csv';
		file_put_contents($file, $csv);
		echo json_encode(['message' => 'Генерация CSV прошла успешно. Скачать можно по <a href="' . $path['baseurl'] . '/part' . $id . '.csv">ссылке</a>']);
	}
	else {
		echo json_encode(['message' => 'Произошла ошибка при генерации CSV']);
	}
	wp_die();
}

add_action('wp_ajax_auto_generator_csv_multiply', 'auto_generator_csv_multiply');

function auto_generator_import_multiply() {
	$path = plugin_dir_path(__FILE__);
	$path .= '/auto_generator.csv';
	if (file_exists($path)) {
		if (($handle = fopen($path, "r")) !== FALSE) {
			global $wpdb;
			$settings_table = get_option('auto_catalog_table');

			while (($data = fgetcsv($handle, 9000, ";")) !== FALSE) {
				// Skip first row f.e.
				if (!is_numeric($data[0])) {
					continue;
				}
				$id = (int) $data[0];
				$name = sanitize_text_field($data[1]);
				$name = strpos($data[1], '[auto]') === FALSE ? $name . ' [auto]' : $name;

				$settings = new stdClass();
				$price = preg_split('/[\-—]+/', $data[2]);
				if (count($price) < 2) {
					continue;
				}
				$settings->price_from_1 = trim($price[0]);
				$settings->price_from_2 = trim($price[1]);
				$price = preg_split('/[\-—]+/', $data[4]);
				if (count($price) < 2) {
					continue;
				}
				$settings->price_to_1 = trim($price[0]);
				$settings->price_to_2 = trim($price[1]);
				$settings->price_step_1 = trim($data[3]);
				$settings->price_step_2 = trim($data[5]);
				$settings->date_from = '01.01.' . date('Y', strtotime('-1 year')) . ' 00:00:00';
				$settings->date_to = date('d.m.Y H:i:s');
				$settings->keywords = '[title]';
				$settings->description = explode('.', $data[7]);
				$settings->description = trim(reset($settings->description));
				$settings->text_before = trim($data[7]);
				$settings->text_after = trim($data[8]);
				$settings->template = '';

				$wpdb->insert($settings_table, [
					'id' => $id,
					'name' => $name,
					'settings' => json_encode($settings),
				]);
			}
			fclose($handle);
			echo json_encode(['message' => 'Импорт прошёл успешно', 'ok' => 1]);
		}
	}
	else {
		echo json_encode(['message' => 'Произошла ошибка при импорте']);
	}
	wp_die();
}

add_action('wp_ajax_auto_generator_import_multiply', 'auto_generator_import_multiply');