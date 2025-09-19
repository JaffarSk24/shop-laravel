<?php
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../config.php';

// === Функция отправки писем через Mailgun API ===
function sendMailgun($to, $subject, $text) {
    $apiKey = $config['mailgun']['api_key'];
    $domain = $config['mailgun']['domain'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "api:$apiKey");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $data = [
        'from'    => 'Balík PRO <order@balikpro.sk>',
        'to'      => $to,
        'subject' => $subject,
        'text'    => $text,
    ];

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_URL, "https://api.eu.mailgun.net/v3/$domain/messages");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

// === Читаем JSON из запроса ===
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
  exit;
}

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Invalid JSON']);
  exit;
}

// === Валидация полей ===
foreach (['name','email','address','cart','total','lang'] as $field) {
  if (empty($data[$field])) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>"Missing $field"]);
    exit;
  }
}

$orderUid = time().'-'.bin2hex(random_bytes(4));
$total = floatval($data['total']);
$lang = $data['lang']; // "sk" или "ru"

// === Сохраняем в базу данных ===
try {
  $pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
    $config['db']['user'],
    $config['db']['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  $stmt = $pdo->prepare("INSERT INTO orders (order_uid, name, email, address, total, cart) VALUES (?,?,?,?,?,?)");
  $stmt->execute([
    $orderUid,
    $data['name'],
    $data['email'],
    $data['address'],
    $total,
    json_encode($data['cart'], JSON_UNESCAPED_UNICODE)
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  exit;
}

// === Тексты писем по языкам ===
if ($lang === "sk") {
    // Словацкая версия
    $clientSubject = "Vaša objednávka č. $orderUid";
    $clientText = "Dobrý deň, {$data['name']}!\n\nVaša objednávka ($orderUid) bola úspešne prijatá.\n".
                  "Celková suma: €$total\n\nĎakujeme, že ste si vybrali Balík PRO!\n\nTím Balík PRO";

    $adminSubject = "Nová objednávka č. $orderUid";
    $adminText = "🔥 Nová objednávka!\n\nČíslo objednávky: $orderUid\nMeno: {$data['name']}\n".
                 "Email: {$data['email']}\nAdresa: {$data['address']}\n".
                 "Suma: €$total\n\nKošík:\n".json_encode($data['cart'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

} else {
    // Русская версия (дефолт)
    $clientSubject = "Ваш заказ №$orderUid";
    $clientText = "Здравствуйте, {$data['name']}!\n\nВаш заказ ($orderUid) успешно оформлен.\n".
                  "Сумма заказа: €$total\n\nСпасибо, что выбрали Balík PRO!\n\nКоманда Balík PRO";

    $adminSubject = "Новый заказ №$orderUid";
    $adminText = "🔥 Новый заказ!\n\nID: $orderUid\nИмя: {$data['name']}\n".
                 "Email: {$data['email']}\nАдрес: {$data['address']}\n".
                 "Сумма: €$total\n\nКорзина:\n".json_encode($data['cart'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
}

// === Отправляем письма через Mailgun ===
try {
    // клиенту
    sendMailgun($data['email'], $clientSubject, $clientText);

    // админу (тебе)
    sendMailgun("mosinkir@icloud.com", $adminSubject, $adminText);

} catch (Exception $e) {
    // Не роняем заказ, если письмо не ушло
}

// === Ответ фронту ===
echo json_encode(['ok'=>true,'order_id'=>$orderUid]);