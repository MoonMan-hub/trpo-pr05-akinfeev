<?php
// Страница каталога. Здесь: подключение классов, получение данных и вывод HTML.

require_once __DIR__ . '/../src/Model/Product.php';
require_once __DIR__ . '/../src/Model/Category.php';
require_once __DIR__ . '/../src/Service/CatalogService.php';

// Экранирование текста перед выводом в HTML
function e($text): string
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// ---------- 1. Загрузка данных из dataset ----------
$service = new CatalogService(
    __DIR__ . '/../data/products.json',
    __DIR__ . '/../data/categories.json'
);

$categories = [];
$products = [];
$loadError = '';

try {
    $categories = $service->loadCategories();
    $products = $service->loadProducts();
} catch (RuntimeException $e) {
    $loadError = $e->getMessage();
}

// ---------- 2. Обработка формы фильтра (GET-параметр category) ----------
$selectedCategory = 0; // 0 значит "все категории"
$filterError = '';

if (isset($_GET['category']) && is_string($_GET['category'])) {
    $selectedCategory = (int)$_GET['category'];
}

if ($selectedCategory !== 0 && !isset($categories[$selectedCategory])) {
    $filterError = 'Такой категории нет, показаны все товары.';
    $selectedCategory = 0;
}

if ($selectedCategory !== 0) {
    $shownProducts = $service->filterByCategory($products, $selectedCategory);
} else {
    $shownProducts = $products;
}

// ---------- 3. Цифры для карточек статистики ----------
$totalShown = count($shownProducts);
$averagePrice = $service->averagePrice($shownProducts);
$availableCount = $service->countAvailable($shownProducts);

// ---------- 4. Проверка некорректного конструктора ----------
// Пробуем создать плохие товары и показываем, какую ошибку выбросил конструктор.
$badCases = [
    'Пустое название'     => [1, '   ', 100, 1],
    'Отрицательная цена'  => [2, 'Тестовый товар', -50, 1],
    'Нулевой id категории' => [3, 'Тестовый товар', 100, 0],
];
$constructorChecks = [];

foreach ($badCases as $title => $args) {
    try {
        new Product(...$args);
        $constructorChecks[$title] = 'Ошибки нет (так быть не должно)';
    } catch (InvalidArgumentException $e) {
        $constructorChecks[$title] = 'InvalidArgumentException: ' . $e->getMessage();
    }
}

$skippedRows = $service->getErrors();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Каталог товаров</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="header">
    <div class="brand">Практическая работа 5</div>
    <span>ТРПО</span>
</header>

<main class="wrap">
    <section class="hero">
        <span class="pill">PHP ООП</span>
        <h1>Каталог товаров</h1>
        <p class="muted">Данные берутся из products.json и categories.json, товары и категории создаются как объекты.</p>
    </section>

    <?php if ($loadError !== ''): ?>
        <section class="box error">
            <h2>Ошибка загрузки данных</h2>
            <p><?= e($loadError) ?></p>
        </section>
    <?php else: ?>

        <?php if (count($skippedRows) > 0): ?>
            <section class="box warning">
                <h2>Некоторые строки пропущены</h2>
                <ul>
                    <?php foreach ($skippedRows as $message): ?>
                        <li><?= e($message) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="box" id="filter">
            <h2>Фильтр</h2>
            <form method="get" action="index.php" class="filter-form">
                <label for="category">Категория:</label>
                <select name="category" id="category">
                    <option value="0">Все категории</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category->getId()) ?>"
                            <?= $category->getId() === $selectedCategory ? 'selected' : '' ?>>
                            <?= e($category->getName()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Показать</button>
                <a href="index.php" class="reset">Сбросить</a>
            </form>
            <?php if ($filterError !== ''): ?>
                <p class="error-text"><?= e($filterError) ?></p>
            <?php endif; ?>
        </section>

        <section class="grid" id="stats">
            <div class="card">
                <b>Товаров показано</b>
                <span class="big"><?= e($totalShown) ?></span>
            </div>
            <div class="card">
                <b>Средняя цена</b>
                <span class="big"><?= e(number_format($averagePrice, 2, ',', ' ')) ?> ₽</span>
            </div>
            <div class="card">
                <b>Доступно к покупке</b>
                <span class="big"><?= e($availableCount) ?></span>
            </div>
        </section>

        <section id="catalog">
            <h2>Товары</h2>
            <?php if ($totalShown === 0): ?>
                <p class="box muted">Товары не найдены.</p>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($shownProducts as $product): ?>
                        <article class="card">
                            <span class="pill"><?= e($service->getCategoryName($categories, $product->getCategoryId())) ?></span>
                            <h3><?= e($product->getName()) ?></h3>
                            <p class="price"><?= e($product->getFormattedPrice()) ?></p>
                            <p class="muted">Со скидкой 10%: <?= e(number_format($product->getPriceWithDiscount(10), 2, ',', ' ')) ?> ₽</p>
                            <p>
                                <?php if ($product->isAvailable()): ?>
                                    <span class="status ok">доступен</span>
                                <?php else: ?>
                                    <span class="status no">нет в наличии</span>
                                <?php endif; ?>
                                <?php if ($product->isExpensive()): ?>
                                    <span class="status dear">дорогой</span>
                                <?php endif; ?>
                            </p>
                            <p class="muted">id: <?= e($product->getId()) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    <?php endif; ?>

    <section class="box" id="check">
        <h2>Проверка конструктора Product</h2>
        <table class="table">
            <tr>
                <th>Что передали</th>
                <th>Результат</th>
            </tr>
            <?php foreach ($constructorChecks as $title => $result): ?>
                <tr>
                    <td><?= e($title) ?></td>
                    <td><?= e($result) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
</main>

<footer class="footer">
    <p class="muted">Практическая работа №5. Технологии разработки программного обеспечения.</p>
</footer>
</body>
</html>
