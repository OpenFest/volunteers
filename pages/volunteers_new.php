<?php
// volunteers_new.php
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;
$activeConf = 'of-2025';

$teams = $this->database->query("SELECT slug, name FROM teams WHERE conference = :conference", ['conference' => $activeConf]);

$volunteerTeams = [];
foreach ($teams as $row) {
    $volunteerTeams[$row->slug] = $row->name;
}

?>

   <h1>Кандидатствай за доброволец</h1>
    <form class="new_volunteer" id="new_volunteer" novalidate="novalidate" enctype="multipart/form-data" action="/volunteers/submit" accept-charset="UTF-8" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>" />
        <div class="form-inputs">
            <div class="input">
                <label for="volunteer_picture">Снимка</label>
                <input type="file" name="picture" id="volunteer_picture" accept="image/*" />
                <p class="hint-text">Ваша снимка в jpeg, png или gif формат</p>
            </div>

            <div class="input">
                <label for="volunteer_email"><abbr title="Задължително поле">*</abbr> E-mail</label>
                <input type="email" name="volunteer[email]" id="volunteer_email" />
                <span class="hint">Е-mail адресът Ви, който ще бъде видим само от организаторите</span>
            </div>

            <div class="input">
                <label for="volunteer_name"><abbr title="Задължително поле">*</abbr> Име</label>
                <input autofocus="autofocus" type="text" name="volunteer[name]" id="volunteer_name" />
                <span class="hint">Имайте предвид, че това име ще бъде изписано на грамотата ви за участие в конференцията</span>
            </div>

            <div class="input">
                <label for="volunteer_phone"><abbr title="Задължително поле">*</abbr> Телефон</label>
                <input type="tel" name="volunteer[phone]" id="volunteer_phone" />
                <span class="hint">Мобилният Ви телефон, който ще бъде видим само за организаторите</span>
            </div>

            <div class="input checkboxes-group">
                <label><abbr title="Задължително поле">*</abbr> Екипи доброволци</label>
                <div class="checkbox-options">
                    <?php foreach ($volunteerTeams as $team => $title) { ?>
                    <span class="checkbox">
                        <label for="volunteer_volunteer_team_ids_<?= htmlspecialchars($team) ?>">
                            <input type="checkbox" value="<?= htmlspecialchars($team) ?>" name="volunteer[volunteer_team_ids][]" id="volunteer_volunteer_team_ids_<?= htmlspecialchars($team) ?>" />
                            <?= htmlspecialchars($title) ?>
                        </label>
                    </span>
                    <?php } ?>
                </div>
                <span class="hint">Доброволческите екипи, от които искате да сте част. Подробни описания на екипите можете да намерите <a href="/teams" target="_blank">тук</a></span>
            </div>

            <div class="input radio-group">
                <label><abbr title="Задължително поле">*</abbr> Език</label>
                <input type="hidden" name="volunteer[language]" value="" autocomplete="off" />
                <div class="radio-options">
                    <span class="radio"><label for="volunteer_language_bg"><input type="radio" value="bg" checked="checked" name="volunteer[language]" id="volunteer_language_bg" />Български</label></span>
                    <span class="radio"><label for="volunteer_language_en"><input type="radio" value="en" name="volunteer[language]" id="volunteer_language_en" />Английски</label></span>
                </div>
            </div>

            <div class="input radio-group">
                <label><abbr title="Задължително поле">*</abbr> Размер тениска</label>
                <input type="hidden" name="volunteer[tshirt_size]" value="" autocomplete="off" />
                <div class="radio-options">
                    <span class="radio"><label for="volunteer_tshirt_size_s"><input type="radio" value="s" name="volunteer[tshirt_size]" id="volunteer_tshirt_size_s" />S</label></span>
                    <span class="radio"><label for="volunteer_tshirt_size_m"><input type="radio" value="m" checked="checked" name="volunteer[tshirt_size]" id="volunteer_tshirt_size_m" />M</label></span>
                    <span class="radio"><label for="volunteer_tshirt_size_l"><input type="radio" value="l" name="volunteer[tshirt_size]" id="volunteer_tshirt_size_l" />L</label></span>
                    <span class="radio"><label for="volunteer_tshirt_size_xl"><input type="radio" value="xl" name="volunteer[tshirt_size]" id="volunteer_tshirt_size_xl" />XL</label></span>
                    <span class="radio"><label for="volunteer_tshirt_size_xxl"><input type="radio" value="xxl" name="volunteer[tshirt_size]" id="volunteer_tshirt_size_xxl" />XXL</label></span>
                    <span class="radio"><label for="volunteer_tshirt_size_xxxl"><input type="radio" value="xxxl" name="volunteer[tshirt_size]" id="volunteer_tshirt_size_xxxl" />XXXL</label></span>
                </div>
            </div>

            <div class="input radio-group">
                <label><abbr title="Задължително поле">*</abbr> Кройка на тениска</label>
                <input type="hidden" name="volunteer[tshirt_cut]" value="" autocomplete="off" />
                <div class="radio-options">
                    <span class="radio"><label for="volunteer_tshirt_cut_unisex"><input type="radio" value="unisex" checked="checked" name="volunteer[tshirt_cut]" id="volunteer_tshirt_cut_unisex" />Унисекс</label></span>
                    <span class="radio"><label for="volunteer_tshirt_cut_female"><input type="radio" value="female" name="volunteer[tshirt_cut]" id="volunteer_tshirt_cut_female" />Дамска</label></span>
                </div>
            </div>

            <div class="input radio-group">
                <label><abbr title="Задължително поле">*</abbr> Предпочитана храна</label>
                <input type="hidden" name="volunteer[food_preferences]" value="" autocomplete="off" />
                <div class="radio-options">
                    <span class="radio"><label for="volunteer_food_preferences_none"><input type="radio" value="none" checked="checked" name="volunteer[food_preferences]" id="volunteer_food_preferences_none" />Нищо специфично</label></span>
                    <span class="radio"><label for="volunteer_food_preferences_vegetarian"><input type="radio" value="vegetarian" name="volunteer[food_preferences]" id="volunteer_food_preferences_vegetarian" />Вегетарианец</label></span>
                    <span class="radio"><label for="volunteer_food_preferences_vegan"><input type="radio" value="vegan" name="volunteer[food_preferences]" id="volunteer_food_preferences_vegan" />Веган</label></span>
                </div>
            </div>

            <div class="input">
                <label for="volunteer_previous_experience">Предишен опит</label>
                <textarea name="volunteer[previous_experience]" id="volunteer_previous_experience"></textarea>
            </div>

            <div class="input">
                <label for="volunteer_notes">Бележки</label>
                <textarea name="volunteer[notes]" id="volunteer_notes"></textarea>
            </div>

            <div class="input checkbox-alone">
                <input value="0" autocomplete="off" type="hidden" name="volunteer[terms_accepted]" />
                <label for="volunteer_terms_accepted"><input type="checkbox" value="1" name="volunteer[terms_accepted]" id="volunteer_terms_accepted" />Съгласен съм екипът да се свързва с мен</label>
            </div>
        </div>

        <div class="form-actions">
            <input type="submit" name="commit" value="Изпрати кандидатура" class="btn" data-disable-with="Изпрати кандидатура" />
        </div>
    </form>