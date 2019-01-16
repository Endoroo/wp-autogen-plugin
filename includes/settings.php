<link rel="stylesheet"
      href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
    .part-left, .part-right {display: inline-block;max-width: 47%;margin-right: 2%;vertical-align: top}
    textarea {display: block;min-height: 100px;width: 100%}
    form {margin: 5px 0}
    #download-mark-model > label {margin-right: 40px}
    #message {display: none}
    .wrap {position: relative}
    .loader {background: url(<?php echo plugin_dir_url( '.').'auto-catalog/images/loader.gif' ?>) no-repeat 25% 25%;background-color: rgba(255, 255, 255, .5); height: 100%;position: fixed; width: 100%;z-index: 3;}
    .ag-prices input {width: 28%;}
</style>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>

    <div id="message" class="updated notice notice-success is-dismissible">
        <p></p>
        <button type="button" class="notice-dismiss"><span
                    class="screen-reader-text">Скрыть это уведомление.</span>
        </button>
    </div>
    <div class="part-left">
        <h2>Управление множителями</h2>
        <form action="#" method="post" id="add-multiply">
            <label for="mark-model-text">Введите множитель</label>
            <input type="text" name="multiply" id="multiply-text"/>
            <input type="submit" class="button action" value="Добавить"/>
        </form>
        <div>
			<?php
			$path = plugin_dir_path(__FILE__);
			if (file_exists($path . '/../auto_generator.csv')) : ?>
                <div>
                    <label style="display:inline-block;width:160px">Импорт</label>
                    <input type="submit" class="button action import-all"
                           value="Импорт"/></div>
			<?php endif; ?>
            <div><label style="display:inline-block;width:160px">Выгрузить все
                    страницы</label><input type="submit"
                                           class="button action csv-all"
                                           value="Выгрузить"/></div>
            <div><label style="display:inline-block;width:160px">Генерировать
                    страницы</label><input type="submit"
                                           class="button action generate-all"
                                           value="Генерировать"/>
                <select name="mode" class="generate-mode">
                    <option value="1">Марки</option>
                    <option value="2">Марки-модели</option>
                    <option value="3">Марки-модели-поколения</option>
                    <option value="4">Марки-модели с переводом</option>
                    <option value="5">Марки-модели-поколения с переводом</option>
                </select></div>
            <div><label style="display:inline-block;width:160px">Очистить все
                    страницы</label><input type="submit"
                                           class="button action clear-all"
                                           value="Очистить"/></div>
        </div>
        <div id="multiply-list">
            <div class="pager tablesorterPager">
                <img src="https://mottie.github.io/tablesorter/addons/pager/icons/first.png"
                     class="first"/>
                <img src="https://mottie.github.io/tablesorter/addons/pager/icons/prev.png"
                     class="prev"/>
                <span class="pagedisplay"></span>
                <!-- this can be any element, including an input -->
                <img src="https://mottie.github.io/tablesorter/addons/pager/icons/next.png"
                     class="next"/>
                <img src="https://mottie.github.io/tablesorter/addons/pager/icons/last.png"
                     class="last"/>
                <select class="pagesize" title="Select page size">
                    <option selected="selected" value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="<?php echo count($values) ?>">Все</option>
                </select>
            </div>
            <table class="tablesorter" cellspacing="0" style="display: none">
                <thead>
                <tr>
                    <th data-sorter="false">№</th>
                    <th data-sorter="false"><input type="checkbox"
                                                   name="multiply-all"
                                                   value="all"></th>
                    <th>Множитель</th>
                </tr>
                ￼
                </thead>
                <tbody>
				<?php $i = 0;
				foreach ($values as $key => $value): ?>
                    <tr>
                        <td><?php echo ++$i ?></td>
                        <td><input type="checkbox" name="multiply-ids[]"
                                   value="<?php echo $key ?>"></td>
                        <td>
                            <div class="title"><?php echo $value ?></div>
                            <div class="links">
                                <a href="#" title="выгрузить"
                                   class="csv-multiply"
                                   data-id="<?php echo $key ?>">Выгрузить</a>
                                <a href="#" title="генерировать"
                                   class="generate-multiply"
                                   data-id="<?php echo $key ?>">Генерировать</a>
                                <a href="#" title="загрузить параметры"
                                   class="load-multiply"
                                   data-id="<?php echo $key ?>">Загрузить</a>
                                <a href="#" title="удалить позицию"
                                   class="remove-multiply"
                                   data-id="<?php echo $key ?>">Удалить</a>
                                <a href="#" title="удалить страницы"
                                   class="clear-multiply"
                                   data-id="<?php echo $key ?>">Удалить
                                    страницы</a>
                            </div>
                        </td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2>Фото для товаров</h2>
        <p>
            <input type="button" value="Выбрать фото"
                   class="button button-primary button-large"
                   onclick="upload_new_img(this)"/>
        </p>
        <div class="auto-images"><?php
			$images = get_option('auto_catalog_images');
			if ($images) {
				$images = explode('~', $images);
				foreach ($images as $image) {
					echo '<img src="' . $image . '" height="128"/>';
				}
			}
			?></div>
        <form action="#" method="post" class="upload_image"></form>
        <button id="change-images" class="wp-core-ui button">Заменить картинки</button>
    </div>
    <div class="part-right">
        <form action="#" method="post" id="multiply-params">
            <input type="hidden" name="multiply" value="0"/>

            <h2>Цены</h2>
            <div class="ag-prices">
                <label for="price-from-1">От</label>
                <input type="text" name="price-from[]" id="price-from-1"/>
                <label for="price-to-1">до</label>
                <input type="text" name="price-to[]" id="price-to-1"/>
                <label for="price-step-1">Шаг</label>
                <input type="text" name="price-step[]" id="price-step-1"/><br/>
                <label for="price-from-2">От</label>
                <input type="text" name="price-from[]" id="price-from-2"/>
                <label for="price-to-2">до</label>
                <input type="text" name="price-to[]" id="price-to-2"/>
                <label for="price-step-2">Шаг</label>
                <input type="text" name="price-step[]" id="price-step-2"/>
                <div style="margin-top:10px;"><label for="price-word">Использовать слова вместо цифр</label><input type="checkbox" name="price-word" id="price-word" style="height:16px;margin-top:2px;width:16px;" value="1"/></div>
            </div>

            <h2>Даты</h2>
            <div class="ag-dates">
                <label for="date-from">От</label>
                <input type="text" name="date-from" id="date-from"/>
                <label for="date-to">до</label>
                <input type="text" name="date-to" id="date-to"/>
            </div>

            <h2>SEO</h2>
            <div>
                <label for="keywords">Ключевые слова</label>
                <textarea name="keywords" id="keywords"></textarea>
                <label for="description">Описание</label>
                <textarea name="description" id="description"></textarea>
                <label for="text">Текст до фото</label>
                <textarea name="text-before" id="text-before"></textarea>
                <label for="text">Текст после фото</label>
                <textarea name="text-after" id="text-after"></textarea>
            </div>
            <h2>Прочие параметры</h2>
            <div>
                <label for="template">Шаблон</label>
                <input type="text" name="template" id="template"/>
            </div>
            <input type="submit" class="button action" value="Сохранить"/>
        </form>
    </div>
</div>