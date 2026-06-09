<?php
require '../lib/db.php';
require '../vendor/autoload.php';
require '../admin/auth.php';

// checkLogin();
header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['product_ids']) || !is_array($data['product_ids']) || empty($data['product_ids'])) {
    http_response_code(400);
    echo json_encode(['error' => 'product_ids không hợp lệ hoặc rỗng']);
    exit;
}

$ids = array_filter(
    array_map('intval', $data['product_ids']),
    fn($id) => $id > 0
);
if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'Không có product_id hợp lệ']);
    exit;
}

if (count($ids) > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Chỉ được export tối đa 100 thiết kế mỗi lần']);
    exit;
}
use Dompdf\Dompdf;
$tmpFiles = [];

function webpToJpg(string $path): string|false {
    $fullPath = ".." . $path;

    if (!file_exists($fullPath)) {
        return false;
    }

    $realPath    = realpath($fullPath);
    $uploadsDir  = realpath(__DIR__ . '/../uploads');
    if (!$realPath || !str_starts_with($realPath, $uploadsDir)) {
        return false;
    }
    $img = @imagecreatefromwebp($realPath);
    if (!$img) {
        return false;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'pdf_') . '.jpg';
    imagejpeg($img, $tmp, 85);
    imagedestroy($img);
    return $tmp;
}
$html  = '<h2 style="text-align:center;">TỔNG HỢP SẢN PHẨM</h2>';
$html .= '<div style="display:flex; flex-wrap:wrap;">';
foreach ($ids as $id) {

    $stmt = $pdo->prepare("
        SELECT image_path FROM product_images WHERE product_id=?
    ");
    $stmt->execute([$id]);
    while ($img = $stmt->fetch()) {
        $jpg = webpToJpg($img['image_path']);

        if (!$jpg) continue;
        $tmpFiles[] = $jpg;

        $html .= '<img src="' . htmlspecialchars($jpg) . '" style="width:180px;margin:5px;">';
    }
}
$html .= '</div>';

header('Content-Type: application/pdf');
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
$pdf = $dompdf->output();

foreach ($tmpFiles as $f) {
    if (file_exists($f)) unlink($f);
}
echo $pdf;