<?php

// Сервис каталога: читает JSON-файлы, создаёт объекты, фильтрует и считает.
class CatalogService
{
    private string $productsFile;
    private string $categoriesFile;
    // Сюда складываем сообщения о плохих строках, которые пришлось пропустить
    private array $errors = [];

    public function __construct(string $productsFile, string $categoriesFile)
    {
        $this->productsFile = $productsFile;
        $this->categoriesFile = $categoriesFile;
    }

    // Читает JSON-файл и возвращает массив.
    // Если файла нет или JSON сломан, бросает RuntimeException.
    private function readJson(string $file): array
    {
        if (!file_exists($file)) {
            throw new RuntimeException('Файл не найден: ' . basename($file));
        }

        $text = file_get_contents($file);
        $data = json_decode($text, true);

        if (!is_array($data)) {
            throw new RuntimeException('Файл повреждён или это не JSON: ' . basename($file));
        }

        return $data;
    }

    // Читает categories.json. Возвращает массив [id => объект Category].
    public function loadCategories(): array
    {
        $result = [];
        $number = 0;

        foreach ($this->readJson($this->categoriesFile) as $row) {
            $number++;

            if (!is_array($row) || !isset($row['id'], $row['name'])) {
                $this->errors[] = 'Категория №' . $number . ' пропущена: не хватает полей id или name';
                continue;
            }

            try {
                $category = new Category((int)$row['id'], (string)$row['name']);
                $result[$category->getId()] = $category;
            } catch (InvalidArgumentException $e) {
                $this->errors[] = 'Категория №' . $number . ' пропущена: ' . $e->getMessage();
            }
        }

        return $result;
    }

    // Читает products.json. Возвращает массив объектов Product.
    public function loadProducts(): array
    {
        $result = [];
        $number = 0;

        foreach ($this->readJson($this->productsFile) as $row) {
            $number++;

            if (!is_array($row) || !isset($row['id'], $row['name'], $row['price'], $row['category_id'])) {
                $this->errors[] = 'Товар №' . $number . ' пропущен: не хватает обязательных полей';
                continue;
            }

            // "active" необязательное поле: если его нет, считаем товар доступным
            $active = isset($row['active']) ? (bool)$row['active'] : true;

            try {
                $result[] = new Product(
                    (int)$row['id'],
                    (string)$row['name'],
                    (float)$row['price'],
                    (int)$row['category_id'],
                    $active
                );
            } catch (InvalidArgumentException $e) {
                $this->errors[] = 'Товар №' . $number . ' пропущен: ' . $e->getMessage();
            }
        }

        return $result;
    }

    // Фильтр: оставляем только товары нужной категории
    public function filterByCategory(array $products, int $categoryId): array
    {
        $result = [];

        foreach ($products as $product) {
            if ($product->belongsToCategory($categoryId)) {
                $result[] = $product;
            }
        }

        return $result;
    }

    // Средняя цена. Для пустого списка вернёт 0, чтобы не было деления на ноль.
    public function averagePrice(array $products): float
    {
        if (count($products) === 0) {
            return 0;
        }

        $sum = 0;
        foreach ($products as $product) {
            $sum = $sum + $product->getPrice();
        }

        return $sum / count($products);
    }

    // Сколько товаров доступно
    public function countAvailable(array $products): int
    {
        $count = 0;

        foreach ($products as $product) {
            if ($product->isAvailable()) {
                $count++;
            }
        }

        return $count;
    }

    // Название категории по id. Если такой категории нет, возвращаем запасной текст.
    public function getCategoryName(array $categories, int $categoryId): string
    {
        if (isset($categories[$categoryId])) {
            return $categories[$categoryId]->getName();
        }
        return 'Без категории';
    }

    // Сообщения о пропущенных строках
    public function getErrors(): array
    {
        return $this->errors;
    }
}
