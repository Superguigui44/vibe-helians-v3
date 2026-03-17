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

echo renderHeader('Blog', 'blog');
?>

<div class="page-header">
    <h1>Articles de blog</h1>
    <a href="blog-edit.php" class="btn btn-primary">+ Nouvel article</a>
</div>

<?php if (empty($articles)): ?>
    <div class="empty-state">
        <p>Aucun article pour le moment.</p>
        <a href="blog-edit.php" class="btn btn-primary">Créer votre premier article</a>
    </div>
<?php else: ?>

<table class="blog-table">
    <thead>
        <tr>
            <th>Titre</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Tags</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($articles as $i => $article):
            $status = $article['status'] ?? 'published';
            $isDraft = $status === 'draft';
        ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars($article['title'] ?? 'Sans titre') ?></strong>
                <?php if (!empty($article['description'])): ?>
                    <br><span class="text-muted text-sm"><?= htmlspecialchars(mb_substr($article['description'], 0, 80)) ?><?= mb_strlen($article['description'] ?? '') > 80 ? '…' : '' ?></span>
                <?php endif; ?>
            </td>
            <td><span class="status-badge <?= $isDraft ? 'draft' : 'published' ?>"><?= $isDraft ? 'Brouillon' : 'Publié' ?></span></td>
            <td class="text-sm"><?= htmlspecialchars($article['date'] ?? '') ?></td>
            <td>
                <?php foreach (($article['tags'] ?? []) as $tag): ?>
                    <span class="tag"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </td>
            <td>
                <div class="blog-actions">
                    <a href="blog-edit.php?id=<?= $i ?>" class="btn btn-sm">Modifier</a>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteArticle(<?= $i ?>, '<?= htmlspecialchars(addslashes($article['title'] ?? ''), ENT_QUOTES) ?>')">Supprimer</button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>

<script>
const CSRF_TOKEN = '<?= generateCsrf() ?>';

function deleteArticle(id, title) {
    if (!confirm('Supprimer l\'article "' + title + '" ? Cette action est irréversible.')) return;

    fetch('api.php?action=delete_article', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify({ id: id })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert(res.message || 'Erreur lors de la suppression');
        }
    })
    .catch(() => alert('Erreur réseau'));
}
</script>

<?php
echo renderFooter();
