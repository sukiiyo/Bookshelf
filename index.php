<?php
session_start();
require_once 'koneksi.php';
require_once 'language.php';

//Auth Guard
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit();
}

//CSRF token (protects create / update / delete)
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

//Shared validation for create + update. Returns [data, errors].
function validate_book(array $post): array {
    $errors = [];
    $title     = trim($post['title'] ?? '');
    $author    = trim($post['author'] ?? '');
    $publisher = trim($post['publisher'] ?? '');

    if ($title === '') {
        $errors[] = txt('err_title');
    }

    $year = filter_var($post['year'] ?? '', FILTER_VALIDATE_INT);
    if ($year === false || $year < 1000 || $year > (int)date('Y')) {
        $errors[] = txt('err_year');
    }

    $stock = filter_var($post['stock'] ?? '', FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
    if ($stock === false) {
        $errors[] = txt('err_stock');
    }

    return [compact('title', 'author', 'publisher', 'year', 'stock'), $errors];
}

$errors = [];
$success_msg = '';
$failed_action = ''; // which dialog to reopen when validation fails

//1. HANDLE POST (CREATE / UPDATE / DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'create';

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $errors[] = txt('err_invalid');

    } elseif ($action === 'create') {
        [$d, $errors] = validate_book($_POST);
        if (empty($errors)) {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO books (title, author, publisher, year, stock) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssii", $d['title'], $d['author'], $d['publisher'], $d['year'], $d['stock']);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_success'] = txt('success');
                mysqli_stmt_close($stmt);
                header("Location: index.php");
                exit();
            }
            $errors[] = "Database Error: " . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
        $failed_action = 'create';

    } elseif ($action === 'update') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        [$d, $errors] = validate_book($_POST);
        if ($id === false) {
            $errors[] = txt('err_invalid');
        }
        if (empty($errors)) {
            $stmt = mysqli_prepare($koneksi, "UPDATE books SET title = ?, author = ?, publisher = ?, year = ?, stock = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssiii", $d['title'], $d['author'], $d['publisher'], $d['year'], $d['stock'], $id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_success'] = txt('updated');
                mysqli_stmt_close($stmt);
                header("Location: index.php");
                exit();
            }
            $errors[] = "Database Error: " . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
        $failed_action = 'update';

    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id === false) {
            $errors[] = txt('err_invalid');
        } else {
            $stmt = mysqli_prepare($koneksi, "DELETE FROM books WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_success'] = txt('deleted');
                mysqli_stmt_close($stmt);
                header("Location: index.php");
                exit();
            }
            $errors[] = "Database Error: " . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
    }
}

//Flash message after redirect
if (isset($_SESSION['flash_success'])) {
    $success_msg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$old = fn($k) => e($_POST[$k] ?? '');
$old_create = $failed_action === 'create';
$old_update = $failed_action === 'update';

//2. FETCH ALL BOOKS FROM DATABASE (READ)
$books = [];
$books_result = mysqli_query($koneksi, "SELECT * FROM books ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($books_result)) {
    $books[] = $row;
}

//Cover gradient palette (books have no image)
$palettes = [
    ['#1f2937', '#4b5563'], ['#6d28d9', '#a78bfa'], ['#0f766e', '#5eead4'],
    ['#b45309', '#fbbf24'], ['#be123c', '#fb7185'], ['#1d4ed8', '#60a5fa'],
    ['#4d7c0f', '#a3e635'], ['#7c2d12', '#fb923c'],
];

$username = $_SESSION['username'];
$initial  = strtoupper(mb_substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="<?= e($_SESSION['lang']); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Bookshelf</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">Bookshelf</div>

        <div class="side-title"><?= txt('menu'); ?></div>
        <a href="index.php" class="side-link active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/></svg>
            <?= txt('my_books'); ?>
        </a>
        <button type="button" class="side-link" id="openAdd">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
            <?= txt('add_book'); ?>
        </button>

        <div class="side-spacer"></div>

        <div class="lang-switch">
            <?= txt('select_lang'); ?>
            <a href="?lang=id" class="<?= $_SESSION['lang'] === 'id' ? 'active' : ''; ?>">ID</a>
            <a href="?lang=en" class="<?= $_SESSION['lang'] === 'en' ? 'active' : ''; ?>">EN</a>
        </div>
        <a href="logout.php" class="side-link" style="margin-top:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>
            <?= txt('logout'); ?>
        </a>
    </aside>

    <!-- MAIN -->
    <div class="content">

        <header class="topbar">
            <div class="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input type="text" id="searchInput" placeholder="<?= e(txt('search')); ?>" autocomplete="off">
            </div>
            <div class="user-chip">
                <span><?= txt('Good day'); ?>, <?= e($username); ?></span>
                <div class="avatar"><?= e($initial); ?></div>
            </div>
        </header>

        <main class="page">
            <div class="page-head">
                <h1><?= txt('my_books'); ?> <span style="color:var(--muted);font-weight:400;font-size:13px;">(<?= count($books); ?> <?= txt('books_count'); ?>)</span></h1>
                <div class="head-actions">
                    <select class="select" id="stockFilter">
                        <option value="all"><?= txt('filter_all'); ?></option>
                        <option value="in"><?= txt('filter_in'); ?></option>
                        <option value="out"><?= txt('filter_out'); ?></option>
                    </select>
                    <button type="button" class="btn" id="openAdd2">+ <?= txt('add_book'); ?></button>
                </div>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert success"><?= e($success_msg); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors) && $failed_action === ''): // errors not tied to a form dialog ?>
                <div class="alert error">
                    <ul><?php foreach ($errors as $err): ?><li><?= e($err); ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <!-- Book cards -->
            <div class="grid" id="bookGrid">
                <?php if (empty($books)): ?>
                    <div class="empty"><?= txt('no_books'); ?></div>
                <?php else: ?>
                    <?php foreach ($books as $b):
                        $p = $palettes[$b['id'] % count($palettes)];
                        $has_stock = (int)$b['stock'] > 0;
                        $author_txt = $b['author'] !== '' ? $b['author'] : txt('unknown');
                        $pub_txt    = $b['publisher'] !== '' ? $b['publisher'] : txt('unknown');
                        $cover_style = "background:linear-gradient(160deg,{$p[0]},{$p[1]});";
                    ?>
                    <article class="card"
                        data-search="<?= e(mb_strtolower($b['title'] . ' ' . $b['author'] . ' ' . $b['publisher'])); ?>"
                        data-stock="<?= $has_stock ? 'in' : 'out'; ?>"
                        data-id="<?= e($b['id']); ?>"
                        data-title="<?= e($b['title']); ?>"
                        data-author="<?= e($author_txt); ?>"
                        data-rawauthor="<?= e($b['author']); ?>"
                        data-publisher="<?= e($pub_txt); ?>"
                        data-rawpublisher="<?= e($b['publisher']); ?>"
                        data-year="<?= e($b['year']); ?>"
                        data-stockqty="<?= e($b['stock']); ?>"
                        data-cover="<?= e($cover_style); ?>">
                        <div class="cover" style="<?= e($cover_style); ?>">
                            <span class="c-title"><?= e($b['title']); ?></span>
                            <span class="c-author"><?= e($author_txt); ?></span>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title" title="<?= e($b['title']); ?>"><?= e($b['title']); ?></h3>
                            <p class="card-desc">
                                <?= txt('by'); ?> <?= e($author_txt); ?>. <?= e($pub_txt); ?>,
                                <?= e($b['year']); ?>. <?= txt('stock'); ?>: <?= e($b['stock']); ?>.
                            </p>
                            <div class="card-foot">
                                <button type="button" class="read-more"><?= txt('read_more'); ?></button>
                                <div class="card-tools">
                                    <span class="badge <?= $has_stock ? 'in' : 'out'; ?>">
                                        <?= $has_stock ? txt('in_stock') : txt('out_of_stock'); ?>
                                    </span>
                                    <button type="button" class="icon-btn edit-btn" title="<?= e(txt('edit')); ?>" aria-label="<?= e(txt('edit')); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                    </button>
                                    <button type="button" class="icon-btn danger delete-btn" title="<?= e(txt('delete')); ?>" aria-label="<?= e(txt('delete')); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    <div class="empty" id="noResults" hidden><?= txt('no_results'); ?></div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- ADD BOOK MODAL -->
<dialog id="addDialog">
    <div class="modal-head">
        <h3><?= txt('add_book'); ?></h3>
        <button type="button" class="x" data-close>&times;</button>
    </div>
    <div class="modal-body">
        <?php if ($old_create && !empty($errors)): ?>
            <div class="alert error"><ul><?php foreach ($errors as $err): ?><li><?= e($err); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']); ?>">
            <div class="form-group">
                <label><?= txt('book_title'); ?></label>
                <input type="text" name="title" required autocomplete="off" value="<?= $old_create ? $old('title') : ''; ?>">
            </div>
            <div class="form-group">
                <label><?= txt('author'); ?></label>
                <input type="text" name="author" autocomplete="off" value="<?= $old_create ? $old('author') : ''; ?>">
            </div>
            <div class="form-group">
                <label><?= txt('publisher'); ?></label>
                <input type="text" name="publisher" autocomplete="off" value="<?= $old_create ? $old('publisher') : ''; ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= txt('year'); ?></label>
                    <input type="number" name="year" placeholder="e.g. 2024" required value="<?= $old_create ? $old('year') : ''; ?>">
                </div>
                <div class="form-group">
                    <label><?= txt('stock'); ?></label>
                    <input type="number" name="stock" min="0" placeholder="e.g. 5" required value="<?= $old_create ? $old('stock') : ''; ?>">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn ghost" data-close><?= txt('cancel'); ?></button>
                <button type="submit" class="btn"><?= txt('submit'); ?></button>
            </div>
        </form>
    </div>
</dialog>

<!-- EDIT BOOK MODAL -->
<dialog id="editDialog">
    <div class="modal-head">
        <h3><?= txt('edit_book'); ?></h3>
        <button type="button" class="x" data-close>&times;</button>
    </div>
    <div class="modal-body">
        <?php if ($old_update && !empty($errors)): ?>
            <div class="alert error"><ul><?php foreach ($errors as $err): ?><li><?= e($err); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']); ?>">
            <input type="hidden" name="id" id="eId" value="<?= $old_update ? $old('id') : ''; ?>">
            <div class="form-group">
                <label><?= txt('book_title'); ?></label>
                <input type="text" name="title" id="eTitle" required autocomplete="off" value="<?= $old_update ? $old('title') : ''; ?>">
            </div>
            <div class="form-group">
                <label><?= txt('author'); ?></label>
                <input type="text" name="author" id="eAuthor" autocomplete="off" value="<?= $old_update ? $old('author') : ''; ?>">
            </div>
            <div class="form-group">
                <label><?= txt('publisher'); ?></label>
                <input type="text" name="publisher" id="ePublisher" autocomplete="off" value="<?= $old_update ? $old('publisher') : ''; ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= txt('year'); ?></label>
                    <input type="number" name="year" id="eYear" required value="<?= $old_update ? $old('year') : ''; ?>">
                </div>
                <div class="form-group">
                    <label><?= txt('stock'); ?></label>
                    <input type="number" name="stock" id="eStock" min="0" required value="<?= $old_update ? $old('stock') : ''; ?>">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn ghost" data-close><?= txt('cancel'); ?></button>
                <button type="submit" class="btn"><?= txt('update'); ?></button>
            </div>
        </form>
    </div>
</dialog>

<!-- DELETE CONFIRM MODAL -->
<dialog id="deleteDialog">
    <div class="modal-head">
        <h3><?= txt('delete_title'); ?></h3>
        <button type="button" class="x" data-close>&times;</button>
    </div>
    <div class="modal-body">
        <p style="margin:0 0 6px;font-size:14px;"><strong id="delName"></strong></p>
        <p style="margin:0;font-size:13px;color:var(--muted);"><?= txt('delete_confirm'); ?></p>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']); ?>">
            <input type="hidden" name="id" id="delId">
            <div class="modal-actions" style="margin-top:20px;">
                <button type="button" class="btn ghost" data-close><?= txt('cancel'); ?></button>
                <button type="submit" class="btn danger"><?= txt('delete'); ?></button>
            </div>
        </form>
    </div>
</dialog>

<!-- BOOK DETAIL MODAL -->
<dialog id="detailDialog">
    <div class="modal-head">
        <h3><?= txt('details'); ?></h3>
        <button type="button" class="x" data-close>&times;</button>
    </div>
    <div class="modal-body">
        <div class="detail-top">
            <div class="cover lg" id="dCover"><span class="c-title" id="dCoverTitle"></span><span class="c-author" id="dCoverAuthor"></span></div>
            <dl class="detail-list">
                <dt><?= txt('book_title'); ?></dt><dd id="dTitle"></dd>
                <dt><?= txt('author'); ?></dt><dd id="dAuthor"></dd>
                <dt><?= txt('publisher'); ?></dt><dd id="dPublisher"></dd>
                <dt><?= txt('year'); ?></dt><dd id="dYear"></dd>
                <dt><?= txt('stock'); ?></dt><dd id="dStock"></dd>
            </dl>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn ghost" data-close><?= txt('close'); ?></button>
        </div>
    </div>
</dialog>

<script>
(function () {
    const addDialog = document.getElementById('addDialog');
    const editDialog = document.getElementById('editDialog');
    const deleteDialog = document.getElementById('deleteDialog');
    const detailDialog = document.getElementById('detailDialog');

    // Open "add book" modal (sidebar + header buttons)
    ['openAdd', 'openAdd2'].forEach(id => {
        document.getElementById(id).addEventListener('click', () => addDialog.showModal());
    });

    // Close buttons + click on backdrop
    document.querySelectorAll('dialog').forEach(d => {
        d.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => d.close()));
        d.addEventListener('click', e => { if (e.target === d) d.close(); });
    });

    // Reopen the right form automatically if the server returned validation errors
    <?php if ($failed_action === 'create'): ?>addDialog.showModal();<?php endif; ?>
    <?php if ($failed_action === 'update'): ?>editDialog.showModal();<?php endif; ?>

    document.querySelectorAll('.card').forEach(card => {
        const d = card.dataset;

        // Read More -> details modal
        card.querySelector('.read-more').addEventListener('click', () => {
            document.getElementById('dTitle').textContent = d.title;
            document.getElementById('dAuthor').textContent = d.author;
            document.getElementById('dPublisher').textContent = d.publisher;
            document.getElementById('dYear').textContent = d.year;
            document.getElementById('dStock').textContent = d.stockqty;
            document.getElementById('dCoverTitle').textContent = d.title;
            document.getElementById('dCoverAuthor').textContent = d.author;
            document.getElementById('dCover').setAttribute('style', d.cover);
            detailDialog.showModal();
        });

        // Edit -> prefill form
        card.querySelector('.edit-btn').addEventListener('click', () => {
            document.getElementById('eId').value = d.id;
            document.getElementById('eTitle').value = d.title;
            document.getElementById('eAuthor').value = d.rawauthor;
            document.getElementById('ePublisher').value = d.rawpublisher;
            document.getElementById('eYear').value = d.year;
            document.getElementById('eStock').value = d.stockqty;
            editDialog.showModal();
        });

        // Delete -> confirmation
        card.querySelector('.delete-btn').addEventListener('click', () => {
            document.getElementById('delId').value = d.id;
            document.getElementById('delName').textContent = d.title;
            deleteDialog.showModal();
        });
    });

    // Live search + stock filter
    const input = document.getElementById('searchInput');
    const filter = document.getElementById('stockFilter');
    const cards = document.querySelectorAll('.card');
    const noResults = document.getElementById('noResults');

    function applyFilters() {
        const q = input.value.trim().toLowerCase();
        const s = filter.value;
        let visible = 0;
        cards.forEach(c => {
            const ok = c.dataset.search.includes(q) && (s === 'all' || c.dataset.stock === s);
            c.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        if (noResults) noResults.hidden = visible !== 0;
    }
    input.addEventListener('input', applyFilters);
    filter.addEventListener('change', applyFilters);
})();
</script>

</body>
</html>