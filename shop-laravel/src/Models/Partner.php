<?php
namespace App\Models;

use PDO;

class Partner {
    public $id;
    public $name;
    public $email;
    public $created_at;
    public $updated_at;

    public function __construct($data = []) {
        $this->id         = $data['id'] ?? null;
        $this->name       = $data['name'] ?? null;
        $this->email      = $data['email'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    /**
     * Возвращает bundle партнёра: main service, bonus services, savings.
     * Если передан $lang — добавляет локализованные метки.
     */
    public function getBundle(?string $lang = null): array {
        $services = $this->loadServices();

        $main = null;
        $bonuses = [];

        foreach ($services as $s) {
            if (isset($s['type']) && $s['type'] === 'main' && $main === null) {
                $main = $s;
            } elseif (isset($s['type']) && $s['type'] === 'bonus') {
                $bonuses[] = $s;
            }
        }

        if ($main === null && !empty($services)) {
            $main = $services[0];
            $bonuses = array_slice($services, 1);
        }

        $savings = 0.0;
        foreach ($bonuses as $b) {
            $savings += floatval($b['price'] ?? 0);
        }

        $result = [
            'partner_id' => $this->id,
            'main' => $main,
            'bonuses' => $bonuses,
            'savings' => round($savings, 2),
        ];

        if ($lang) {
            $translations = $this->loadTranslations($lang);
            $result['labels'] = [
                'bundle_title'    => $translations['bundle.title']    ?? 'Bundle',
                'main_service'    => $translations['bundle.main_service'] ?? 'Main service',
                'bonus_service'   => $translations['bundle.bonus_service'] ?? 'Bonus service',
                'savings_label'   => $translations['bundle.save']     ?? 'You save',
                'total_label'     => $translations['products.total']  ?? 'Total',
            ];
        } else {
            $result['labels'] = [
                'bundle_title'    => 'bundle.title',
                'main_service'    => 'bundle.main_service',
                'bonus_service'   => 'bundle.bonus_service',
                'savings_label'   => 'bundle.save',
                'total_label'     => 'products.total',
            ];
        }

        return $result;
    }

    protected function loadServices(): array {
        $config = require __DIR__ . '/../../config.php';

        $host = $_ENV['DB_HOST'] ?? $config['db']['host'] ?? 'localhost';
        $db   = $_ENV['DB_NAME'] ?? $config['db']['name'] ?? 'shop_db';
        $user = $_ENV['DB_USER'] ?? $config['db']['user'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? $config['db']['pass'] ?? '';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $stmt = $pdo->prepare("SELECT id, partner_id, name, type, price FROM services WHERE partner_id = ?");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("DB error in Partner::loadServices(): " . $e->getMessage());
            return [];
        }
    }

    protected function loadTranslations(string $lang): array {
        $file = __DIR__ . '/../../assets/translations.json';
        if (!file_exists($file)) return [];
        $json = json_decode(file_get_contents($file), true);
        return $json[$lang] ?? [];
    }
}