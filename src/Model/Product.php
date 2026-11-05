<?php

// Модель одного товара.
// Здесь только данные и поведение. HTML в этом классе нет.
class Product
{
    private int $id;
    private string $name;
    private float $price;
    private int $categoryId;
    private bool $active;

    // Конструктор проверяет данные: плохой объект создать нельзя.
    // $active необязательный: если его нет в данных, товар считается доступным.
    public function __construct(int $id, string $name, float $price, int $categoryId, bool $active = true)
    {
        $name = trim($name);

        if ($id <= 0) {
            throw new InvalidArgumentException('id товара должен быть больше 0');
        }
        if ($name === '') {
            throw new InvalidArgumentException('название товара не может быть пустым');
        }
        if ($price < 0) {
            throw new InvalidArgumentException('цена товара не может быть отрицательной');
        }
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('id категории должен быть больше 0');
        }

        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->categoryId = $categoryId;
        $this->active = $active;
    }

    // ----- методы чтения -----

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    // ----- методы предметной области -----

    // 1. Можно ли купить товар сейчас
    public function isAvailable(): bool
    {
        return $this->active;
    }

    // 2. Принадлежит ли товар категории
    public function belongsToCategory(int $categoryId): bool
    {
        return $this->categoryId === $categoryId;
    }

    // 3. Цена в удобном виде: 1 124,00 ₽
    public function getFormattedPrice(): string
    {
        return number_format($this->price, 2, ',', ' ') . ' ₽';
    }

    // 4. Дорогой ли товар (по умолчанию дороже 1500)
    public function isExpensive(float $limit = 1500): bool
    {
        return $this->price > $limit;
    }

    // 5. Цена со скидкой в процентах (от 0 до 100)
    public function getPriceWithDiscount(int $percent): float
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('скидка должна быть от 0 до 100 процентов');
        }
        return round($this->price * (100 - $percent) / 100, 2);
    }
}
