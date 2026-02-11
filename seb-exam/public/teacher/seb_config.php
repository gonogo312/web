<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/lib/seb_generator.php';
require_once __DIR__ . '/../../app/models/Exam.php';
require_once __DIR__ . '/../../app/models/Game.php';
require_once __DIR__ . '/../../app/models/SebConfig.php';
init_session();
require_role('teacher');

$tid = current_user_id();
$errors = [];


$activityType = validate_enum($_GET['activity_type'] ?? '', ['exam', 'game']) ?: '';
$activityId = validate_int($_GET['activity_id'] ?? 0, 1) ?: 0;


$configId = validate_int($_GET['config_id'] ?? 0, 1);
$existingConfig = null;
$existingSettings = [];

if ($configId) {
    $existingConfig = SebConfig::findById($configId);
    if (!$existingConfig || !SebConfig::isOwner($configId, $tid)) {
        http_response_code(403); die('Access denied.');
    }
    $existingSettings = json_decode($existingConfig['settings_json'], true) ?? [];
    $activityType = $existingConfig['activity_type'];
    $activityId = $existingConfig['activity_id'];
}


$activityTitle = '';
if ($activityType === 'exam' && $activityId) {
    $exam = Exam::findById($activityId);
    $activityTitle = $exam ? $exam['title'] : '';
} elseif ($activityType === 'game' && $activityId) {
    $game = Game::findById($activityId);
    $activityTitle = $game ? $game['title'] : '';
}


$defaultStartUrl = 'http://localhost' . BASE_URL;
if ($activityType === 'exam' && $activityId) {
    $defaultStartUrl .= '/student/exam_take.php?exam_id=' . $activityId;
} elseif ($activityType === 'game' && $activityId) {
    $defaultStartUrl .= '/student/game_play.php?game_id=' . $activityId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title = validate_string($_POST['title'] ?? '', 1, 255);
    $postActivityType = validate_enum($_POST['activity_type'] ?? '', ['exam', 'game']);
    $postActivityId = validate_int($_POST['activity_id'] ?? 0, 1);

    if ($title === false) $errors[] = 'Config title is required.';
    if (!$postActivityType) $errors[] = 'Activity type is required.';
    if (!$postActivityId) $errors[] = 'Activity ID is required.';

    $settings = [
        'start_url'            => trim($_POST['start_url'] ?? $defaultStartUrl),
        'browser_view_mode'    => (int)($_POST['browser_view_mode'] ?? 1),
        'enable_toolbar'       => !empty($_POST['enable_toolbar']),
        'allow_reload'         => !empty($_POST['allow_reload']),
        'show_taskbar'         => !empty($_POST['show_taskbar']),
        'allow_navigation'     => !empty($_POST['allow_navigation']),
        'allow_quit'           => !empty($_POST['allow_quit']),
        'quit_password'        => trim($_POST['quit_password'] ?? ''),
        'allow_screen_capture' => !empty($_POST['allow_screen_capture']),
        'allow_printing'       => !empty($_POST['allow_printing']),
        'allow_clipboard'      => !empty($_POST['allow_clipboard']),
        'allow_spellcheck'     => !empty($_POST['allow_spellcheck']),
        'allow_dictionary'     => !empty($_POST['allow_dictionary']),
        'allow_switch_apps'    => !empty($_POST['allow_switch_apps']),
        'url_filter_enable'    => !empty($_POST['url_filter_enable']),
        'url_allowlist'        => trim($_POST['url_allowlist'] ?? ''),
        'url_blocklist'        => trim($_POST['url_blocklist'] ?? ''),
        'exam_key'             => trim($_POST['exam_key'] ?? ''),
    ];

    if (empty($errors)) {
        
        $xml = generate_seb_xml($settings);

        if ($existingConfig) {
            
            $xmlPath = save_seb_xml($existingConfig['id'], $xml);
            SebConfig::update($existingConfig['id'], $title, $settings, $xmlPath);
            $_SESSION['flash_success'] = 'SEB config updated!';
            header('Location: seb_config.php?config_id=' . $existingConfig['id']);
        } else {
            
            $newId = SebConfig::create($tid, $postActivityType, $postActivityId, $title, $settings, null);
            $xmlPath = save_seb_xml($newId, $xml);
            SebConfig::update($newId, $title, $settings, $xmlPath);
            $_SESSION['flash_success'] = 'SEB config created!';
            header('Location: seb_config.php?config_id=' . $newId);
        }
        exit;
    }
}


$s = array_merge([
    'start_url' => $defaultStartUrl,
    'browser_view_mode' => 1,
    'enable_toolbar' => false,
    'allow_reload' => true,
    'show_taskbar' => false,
    'allow_navigation' => false,
    'allow_quit' => true,
    'quit_password' => '',
    'allow_screen_capture' => false,
    'allow_printing' => false,
    'allow_clipboard' => false,
    'allow_spellcheck' => false,
    'allow_dictionary' => false,
    'allow_switch_apps' => false,
    'url_filter_enable' => false,
    'url_allowlist' => '',
    'url_blocklist' => '',
    'exam_key' => '',
], $existingSettings);


$teacherExams = Exam::findByTeacher($tid);
$teacherGames = Game::findByTeacher($tid);

$pageTitle = $existingConfig ? 'Edit SEB Config' : 'Create SEB Config';
ob_start();
?>
<div class="page-title">
    <h1><?= $existingConfig ? 'Edit SEB Config' : 'SEB Configuration Wizard' ?></h1>
    <div class="btn-group">
        <a href="seb_list.php" class="btn btn-outline">Back to Configs</a>
        <?php if ($existingConfig): ?>
            <a href="seb_download.php?id=<?= $existingConfig['id'] ?>" class="btn btn-success">Download XML</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="" id="seb-config-form">
    <?= csrf_field() ?>

    
    <div class="card">
        <h2>Activity</h2>
        <div class="form-group">
            <label for="title">Config Title *</label>
            <input type="text" id="title" name="title" required
                   value="<?= e($existingConfig['title'] ?? ($activityTitle ? 'SEB - ' . $activityTitle : '')) ?>">
        </div>
        <div class="form-inline">
            <div class="form-group">
                <label for="activity_type">Activity Type *</label>
                <select id="activity_type" name="activity_type" required>
                    <option value="">-- Select --</option>
                    <option value="exam" <?= $activityType === 'exam' ? 'selected' : '' ?>>Exam</option>
                    <option value="game" <?= $activityType === 'game' ? 'selected' : '' ?>>Escape Room</option>
                </select>
            </div>
            <div class="form-group">
                <label for="activity_id">Activity ID *</label>
                <select id="activity_id" name="activity_id" required>
                    <option value="">-- Select activity type first --</option>
                    <?php foreach ($teacherExams as $ex): ?>
                        <option value="<?= $ex['id'] ?>" data-type="exam"
                                <?= ($activityType === 'exam' && $activityId == $ex['id']) ? 'selected' : '' ?>
                                style="<?= $activityType !== 'exam' ? 'display:none' : '' ?>">
                            [Exam] <?= e($ex['title']) ?>
                        </option>
                    <?php endforeach; ?>
                    <?php foreach ($teacherGames as $gm): ?>
                        <option value="<?= $gm['id'] ?>" data-type="game"
                                <?= ($activityType === 'game' && $activityId == $gm['id']) ? 'selected' : '' ?>
                                style="<?= $activityType !== 'game' ? 'display:none' : '' ?>">
                            [Game] <?= e($gm['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    
    <div class="seb-section">
        <h3>Start URL</h3>
        <div class="form-group">
            <label for="start_url">URL that SEB will open</label>
            <input type="url" id="start_url" name="start_url" value="<?= e($s['start_url']) ?>">
            <p class="form-help">This URL will be loaded when SEB starts. Usually points to the exam/game page.</p>
        </div>
    </div>

    
    <div class="seb-section">
        <h3>Browser Settings</h3>
        <div class="form-group">
            <label for="browser_view_mode">View Mode</label>
            <select id="browser_view_mode" name="browser_view_mode">
                <option value="1" <?= $s['browser_view_mode'] == 1 ? 'selected' : '' ?>>Fullscreen (recommended)</option>
                <option value="0" <?= $s['browser_view_mode'] == 0 ? 'selected' : '' ?>>Windowed</option>
            </select>
        </div>
        <div class="form-check">
            <input type="checkbox" id="enable_toolbar" name="enable_toolbar" value="1" <?= $s['enable_toolbar'] ? 'checked' : '' ?>>
            <label for="enable_toolbar">Show browser toolbar</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="allow_reload" name="allow_reload" value="1" <?= $s['allow_reload'] ? 'checked' : '' ?>>
            <label for="allow_reload">Allow page reload</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="show_taskbar" name="show_taskbar" value="1" <?= $s['show_taskbar'] ? 'checked' : '' ?>>
            <label for="show_taskbar">Show SEB taskbar</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="allow_navigation" name="allow_navigation" value="1" <?= $s['allow_navigation'] ? 'checked' : '' ?>>
            <label for="allow_navigation">Allow back/forward navigation</label>
        </div>
    </div>

    
    <div class="seb-section">
        <h3>Quit Settings</h3>
        <div class="form-check">
            <input type="checkbox" id="allow_quit" name="allow_quit" value="1" <?= $s['allow_quit'] ? 'checked' : '' ?>>
            <label for="allow_quit">Allow quitting SEB</label>
        </div>
        <div class="form-group">
            <label for="quit_password">Quit Password (optional)</label>
            <input type="text" id="quit_password" name="quit_password" value="<?= e($s['quit_password']) ?>"
                   placeholder="Leave empty for no password">
            <p class="form-help">If set, students must enter this password to quit SEB.</p>
        </div>
    </div>

    
    <div class="seb-section">
        <h3>Security Restrictions</h3>
        <div class="form-check">
            <input type="checkbox" id="allow_screen_capture" name="allow_screen_capture" value="1" <?= $s['allow_screen_capture'] ? 'checked' : '' ?>>
            <label for="allow_screen_capture">Allow screen capture / screenshots</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="allow_printing" name="allow_printing" value="1" <?= $s['allow_printing'] ? 'checked' : '' ?>>
            <label for="allow_printing">Allow printing</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="allow_clipboard" name="allow_clipboard" value="1" <?= $s['allow_clipboard'] ? 'checked' : '' ?>>
            <label for="allow_clipboard">Allow clipboard (copy/paste)</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="allow_spellcheck" name="allow_spellcheck" value="1" <?= $s['allow_spellcheck'] ? 'checked' : '' ?>>
            <label for="allow_spellcheck">Allow spell check</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="allow_dictionary" name="allow_dictionary" value="1" <?= $s['allow_dictionary'] ? 'checked' : '' ?>>
            <label for="allow_dictionary">Allow dictionary lookup</label>
        </div>
        <div class="form-check">
            <input type="checkbox" id="allow_switch_apps" name="allow_switch_apps" value="1" <?= $s['allow_switch_apps'] ? 'checked' : '' ?>>
            <label for="allow_switch_apps">Allow switching to other applications</label>
        </div>
    </div>

    
    <div class="seb-section">
        <h3>URL Filter</h3>
        <div class="form-check">
            <input type="checkbox" id="seb_url_filter_enable" name="url_filter_enable" value="1" <?= $s['url_filter_enable'] ? 'checked' : '' ?>>
            <label for="seb_url_filter_enable">Enable URL filtering</label>
        </div>
        <div id="seb-url-filter-section" style="display:<?= $s['url_filter_enable'] ? 'block' : 'none' ?>">
            <div class="form-group">
                <label for="url_allowlist">Allowed URLs (one per line)</label>
                <textarea id="url_allowlist" name="url_allowlist" rows="3"
                          placeholder="http://localhost/*"><?= e($s['url_allowlist']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="url_blocklist">Blocked URLs (one per line)</label>
                <textarea id="url_blocklist" name="url_blocklist" rows="3"
                          placeholder="*://*.facebook.com/*"><?= e($s['url_blocklist']) ?></textarea>
            </div>
        </div>
    </div>

    
    <div class="seb-section">
        <h3>Exam Key (Advanced)</h3>
        <div class="form-group">
            <label for="exam_key">Browser Exam Key</label>
            <input type="text" id="exam_key" name="exam_key" value="<?= e($s['exam_key']) ?>"
                   placeholder="Optional - used for server-side SEB verification">
            <p class="form-help">Advanced: This key can be used to verify that students are using the correct SEB config.</p>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <?= $existingConfig ? 'Update Config' : 'Generate SEB Config' ?>
        </button>
    </div>
</form>

<script>

document.getElementById('activity_type').addEventListener('change', function() {
    var type = this.value;
    var options = document.getElementById('activity_id').options;
    for (var i = 0; i < options.length; i++) {
        var opt = options[i];
        if (!opt.value) continue;
        opt.style.display = (opt.dataset.type === type) ? '' : 'none';
        if (opt.dataset.type !== type && opt.selected) opt.selected = false;
    }
});
</script>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';




