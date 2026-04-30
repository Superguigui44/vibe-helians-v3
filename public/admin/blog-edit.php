<?php
require __DIR__ . '/bootstrap.php';
requireAuth();

if (empty($config['blog']['enabled'])) {
    setFlash('error', 'Le blog n\'est pas activé.');
    header('Location: index.php');
    exit;
}

$content = loadContent();
$articles = $content['blog']['articles'] ?? [];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$article = ($id !== null && isset($articles[$id])) ? $articles[$id] : null;
$isEdit = $article !== null;
$blogFields = $config['blog']['fields'] ?? [];

// Tags déjà utilisés dans tous les articles (déduplication insensible à la casse)
$existingTags = [];
foreach ($articles as $a) {
    foreach (($a['tags'] ?? []) as $t) {
        $key = mb_strtolower(trim($t));
        if ($key !== '' && !isset($existingTags[$key])) $existingTags[$key] = $t;
    }
}
$existingTags = array_values($existingTags);
sort($existingTags, SORT_NATURAL | SORT_FLAG_CASE);

$pageTitle = $isEdit ? 'Modifier l\'article' : 'Nouvel article';
echo renderHeader($pageTitle, 'blog');
?>

<div class="page-header">
    <h1><?= $pageTitle ?></h1>
    <a href="blog.php" class="btn">← Retour</a>
</div>

<div class="content-card">
    <form id="article-form">
        <?php foreach ($blogFields as $key => $def):
            $value = $article[$key] ?? '';
            $label = htmlspecialchars($def['label'] ?? $key);
            $type = $def['type'] ?? 'text';
            $required = !empty($def['required']) ? ' required' : '';
        ?>
            <div class="form-group">
                <label><?= $label ?></label>
                <?php switch ($type):
                    case 'text': ?>
                        <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>"<?= $required ?>>
                    <?php break;
                    case 'textarea': ?>
                        <textarea name="<?= $key ?>"<?= $required ?>><?= htmlspecialchars($value) ?></textarea>
                    <?php break;
                    case 'date': ?>
                        <input type="date" name="<?= $key ?>" value="<?= htmlspecialchars($value ?: date('Y-m-d')) ?>">
                    <?php break;
                    case 'image': ?>
                        <div class="image-upload">
                            <?php if ($value): ?>
                                <img src="<?= htmlspecialchars($value) ?>" class="image-preview" alt="Preview" id="preview-<?= $key ?>">
                            <?php else: ?>
                                <div class="image-preview-empty" id="preview-<?= $key ?>">Aucune image</div>
                            <?php endif; ?>
                            <div>
                                <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
                                <input type="file" accept="image/*" onchange="uploadBlogImage(this, '<?= $key ?>')">
                            </div>
                        </div>
                    <?php break;
                    case 'tags': ?>
                        <div class="tags-input-container" id="tags-container">
                            <?php foreach ((is_array($value) ? $value : []) as $tag): ?>
                                <span class="tag-pill"><?= htmlspecialchars($tag) ?> <button type="button" onclick="removeTag(this)">&times;</button></span>
                            <?php endforeach; ?>
                            <input type="text" id="tag-input" list="existing-tags" placeholder="Ajouter un tag + Entrée" style="border: none; outline: none; flex: 1; min-width: 120px; padding: 0.3rem;">
                        </div>
                        <datalist id="existing-tags">
                            <?php foreach ($existingTags as $t): ?><option value="<?= htmlspecialchars($t) ?>"><?php endforeach; ?>
                        </datalist>
                        <?php if ($existingTags): ?>
                            <div class="tag-suggestions" id="tag-suggestions" aria-label="Tags déjà utilisés — cliquer pour ajouter">
                                <span class="tag-suggestions-label">Tags existants :</span>
                                <?php foreach ($existingTags as $t): ?>
                                    <button type="button" class="tag-suggestion" data-tag="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php break;
                    case 'richtext': ?>
                        <div id="editor-<?= $key ?>"><?= $value ?></div>
                    <?php break;
                endswitch; ?>
            </div>
        <?php endforeach; ?>

        <div class="status-toggle">
            <label>Statut</label>
            <div class="status-toggle-options">
                <input type="radio" name="status" id="status-draft" value="draft" <?= ($article['status'] ?? 'draft') === 'draft' ? 'checked' : '' ?>>
                <label class="status-opt" for="status-draft">Brouillon</label>
                <input type="radio" name="status" id="status-published" value="published" <?= ($article['status'] ?? 'draft') === 'published' ? 'checked' : '' ?>>
                <label class="status-opt" for="status-published">Publié</label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="blog.php" class="btn">Annuler</a>
        </div>
    </form>
</div>

<!-- Quill -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
const CSRF_TOKEN = '<?= generateCsrf() ?>';
const ARTICLE_ID = <?= $id !== null ? $id : 'null' ?>;
const quillEditors = {};

// Initialize Quill editors
document.querySelectorAll('[id^="editor-"]').forEach(el => {
    const key = el.id.replace('editor-', '');
    quillEditors[key] = new Quill(el, {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'link', 'image'],
                ['clean']
            ]
        }
    });
});

// Tags
const tagInput = document.getElementById('tag-input');

function escapeHtml(s) {
    return String(s).replace(/[<>&"']/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&#39;'}[c]));
}

function getTags() {
    const pills = document.querySelectorAll('#tags-container .tag-pill');
    return Array.from(pills).map(p => p.textContent.replace('×', '').trim());
}

function addTag(val) {
    val = String(val || '').trim().replace(/,$/, '').trim();
    if (!val) return;
    const existing = getTags().map(t => t.toLowerCase());
    if (existing.includes(val.toLowerCase())) return;
    const pill = document.createElement('span');
    pill.className = 'tag-pill';
    pill.innerHTML = escapeHtml(val) + ' <button type="button" onclick="removeTag(this)">&times;</button>';
    tagInput.parentNode.insertBefore(pill, tagInput);
    refreshSuggestionsState();
}

function removeTag(btn) {
    btn.parentElement.remove();
    refreshSuggestionsState();
}

function refreshSuggestionsState() {
    const active = new Set(getTags().map(t => t.toLowerCase()));
    document.querySelectorAll('.tag-suggestion').forEach(btn => {
        btn.classList.toggle('is-active', active.has(btn.dataset.tag.toLowerCase()));
    });
}

if (tagInput) {
    tagInput.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addTag(tagInput.value);
            tagInput.value = '';
        }
    });
}

document.querySelectorAll('.tag-suggestion').forEach(btn => {
    btn.addEventListener('click', () => addTag(btn.dataset.tag));
});

refreshSuggestionsState();

// Image upload
function uploadBlogImage(input, fieldKey) {
    const file = input.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('subdir', 'blog');
    formData.append('csrf_token', CSRF_TOKEN);

    fetch('upload.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const hidden = document.querySelector(`input[name="${fieldKey}"]`);
            hidden.value = res.url;

            const preview = document.getElementById('preview-' + fieldKey);
            if (preview.tagName === 'IMG') {
                preview.src = res.url;
            } else {
                const img = document.createElement('img');
                img.src = res.url;
                img.className = 'image-preview';
                img.alt = 'Preview';
                img.id = 'preview-' + fieldKey;
                preview.replaceWith(img);
            }
        } else {
            alert(res.message || 'Erreur upload');
        }
    })
    .catch(() => alert('Erreur réseau'));
}

// Form submit
document.getElementById('article-form').addEventListener('submit', e => {
    e.preventDefault();

    const article = {};
    const blogFields = <?= json_encode($blogFields, JSON_UNESCAPED_UNICODE) ?>;

    article.status = document.querySelector('input[name="status"]:checked')?.value || 'draft';

    for (const [key, def] of Object.entries(blogFields)) {
        switch (def.type) {
            case 'richtext':
                article[key] = quillEditors[key] ? quillEditors[key].root.innerHTML : '';
                break;
            case 'tags':
                article[key] = getTags();
                break;
            default:
                const input = document.querySelector(`[name="${key}"]`);
                article[key] = input ? input.value : '';
        }
    }

    fetch('api.php?action=save_article', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify({ article: article, id: ARTICLE_ID })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.href = 'blog.php';
        } else {
            alert(res.message || 'Erreur');
        }
    })
    .catch(() => alert('Erreur réseau'));
});
</script>

<?php
echo renderFooter();
