<?php
// volunteers_new.php
try {
    $token = bin2hex(random_bytes(32));
} catch (Exception $e) {
    _log('Cannot detect proper randomness source');
    echo "<h1>Грешка в системата!</h1>";
    echo "<p>Моля, опитайте отново по-късно.</p>";
    return;
}
$_SESSION['csrf_token'] = $token;

$activeConf = $this->database->query('SELECT slug, title FROM conferences WHERE registration_open <= now() AND registration_close >= now() ORDER BY start_date DESC LIMIT 1');

if (empty($activeConf)) {
    echo "<h1>Регистрацията е затворена!</h1>";
    echo "<p>Моля, опитайте отново по-късно.</p>";
    return; // Stop further processing
}
$activeConf = $activeConf[0];

$teams = $this->database->query("SELECT slug, name FROM teams WHERE conference = :conference", ['conference' => $activeConf->slug]);

$volunteerTeams = [];
foreach ($teams as $row) {
    $volunteerTeams[$row->slug] = $row->name;
}

?>

   <h1>Кандидатствай за доброволец (<?php echo htmlspecialchars($activeConf->title);?>)</h1>
    <form class="new_volunteer" id="new_volunteer" novalidate="novalidate" enctype="multipart/form-data" action="/volunteers/submit" accept-charset="UTF-8" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>" />
        <div class="form-inputs">
            <div class="input">
                <label for="volunteer_picture">Снимка</label>
                <img id="preview" alt="Image Preview" style="display:none;" src=""/>
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
                <span class="hint">Ако имате предишен опит като доброволец, моля, споделете го тук. Това не е задължително поле.</span>
            </div>

            <div class="input">
                <label for="volunteer_notes">Бележки</label>
                <textarea name="volunteer[notes]" id="volunteer_notes"></textarea>
                <span class="hint">Тук можете да добавите допълнителна информация, която смятате, че е важна за вашата регистрация. Това не е задължително поле.</span>
            </div>

            <div class="input checkbox-alone">
                <input value="0" autocomplete="off" type="hidden" name="volunteer[terms_accepted]" />
                <label for="volunteer_terms_accepted">
                    <abbr title="Задължително поле">*</abbr>
                    <input type="checkbox" value="1" name="volunteer[terms_accepted]" id="volunteer_terms_accepted" />
                    Съгласен съм екипът да се свързва с мен
                </label>
            </div>
        </div>

        <div class="form-actions">
            <input type="submit" name="commit" value="Изпрати кандидатура" class="btn" data-disable-with="Изпрати кандидатура" />
        </div>
    </form>
<script>
    const form = document.querySelector('#new_volunteer');
    const storageKey = 'newVolunteerFormData';
    const imageInput = form.querySelector('#volunteer_picture');
    const preview = form.querySelector('#preview');

    // Restore form data on load
    window.addEventListener('DOMContentLoaded', () => {
        const savedData = localStorage.getItem(storageKey);
        if (savedData) {
            const data = JSON.parse(savedData);
            for (const [name, value] of Object.entries(data)) {
                const field = form.querySelector(`[name="${CSS.escape(name)}"]`);
                if (!field) continue;

                if (field.type === 'checkbox') {
                    field.checked = value === true;
                } else {
                    field.value = value;
                }
            }
        }
    });

    // Save form data on input/change
    form.addEventListener('input', saveFormData);
    form.addEventListener('change', saveFormData);
    imageInput.addEventListener('change', previewImage);

    function saveFormData() {
        const formData = new FormData(form);
        const data = {};

        //store form data in localStorage
        formData.forEach((value, key) => {
            if (key === 'csrf_token') return; // Skip CSRF token
            const field = form.querySelector(`[name="${CSS.escape(key)}"]`);
            if (field && (field.type === 'checkbox' || field.type === 'radio')) {
                data[key] = field.checked;
            } else if (field && field.type === 'file') {
                // Skip file inputs, as they cannot be stored in localStorage

            } else {
                data[key] = value;
            }
        });

        localStorage.setItem(storageKey, JSON.stringify(data));
    }

    function previewImage()
    {
        const file = imageInput.files[0];

        // Handle image preview
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();

            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };

            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            preview.src = '#';
        }
    }
</script>