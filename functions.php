<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function appDateToday(): string
{
    return date('Y-m-d');
}

function appDateTimeNow(): string
{
    return date('Y-m-d H:i:s');
}

function formatDateMx(?string $date): string
{
    if (!$date) {
        return '';
    }

    return date('d/m/Y', strtotime($date));
}

function getStores(): array
{
    $stmt = db()->query('SELECT * FROM stores WHERE is_active = 1 ORDER BY id ASC');
    return $stmt->fetchAll();
}

function getStoreById(int $storeId): ?array
{
    $stmt = db()->prepare('SELECT * FROM stores WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $storeId]);
    $store = $stmt->fetch();

    return $store ?: null;
}

function getUserById(int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function getUserByUsername(string $username): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function getTodayReportForStore(int $storeId): ?array
{
    $stmt = db()->prepare('SELECT dr.*, s.name AS store_name
        FROM daily_reports dr
        INNER JOIN stores s ON s.id = dr.store_id
        WHERE dr.store_id = :store_id AND dr.report_date = :report_date
        LIMIT 1');
    $stmt->execute([
        'store_id' => $storeId,
        'report_date' => appDateToday(),
    ]);
    $report = $stmt->fetch();

    return $report ?: null;
}

function createDailyReport(array $data): bool
{
    $sql = 'INSERT INTO daily_reports
        (store_id, report_date, client_new, recurrent, entered_total, buyers, info_count, channel33_count, youtube_count, izzi_count, totalplay_count, created_by_user_id, created_at, updated_at)
        VALUES
        (:store_id, :report_date, :client_new, :recurrent, :entered_total, :buyers, :info_count, :channel33_count, :youtube_count, :izzi_count, :totalplay_count, :created_by_user_id, :created_at, :updated_at)';

    $stmt = db()->prepare($sql);

    return $stmt->execute($data);
}

function updateDailyReport(int $reportId, array $data): bool
{
    $data['id'] = $reportId;

    $sql = 'UPDATE daily_reports SET
        client_new = :client_new,
        recurrent = :recurrent,
        entered_total = :entered_total,
        buyers = :buyers,
        info_count = :info_count,
        channel33_count = :channel33_count,
        youtube_count = :youtube_count,
        izzi_count = :izzi_count,
        totalplay_count = :totalplay_count,
        updated_at = :updated_at
        WHERE id = :id';

    $stmt = db()->prepare($sql);

    return $stmt->execute($data);
}

function deleteDailyReport(int $reportId): bool
{
    $stmt = db()->prepare('DELETE FROM daily_reports WHERE id = :id');
    return $stmt->execute(['id' => $reportId]);
}

function getReportById(int $reportId): ?array
{
    $stmt = db()->prepare('SELECT dr.*, s.name AS store_name
        FROM daily_reports dr
        INNER JOIN stores s ON s.id = dr.store_id
        WHERE dr.id = :id
        LIMIT 1');
    $stmt->execute(['id' => $reportId]);

    $report = $stmt->fetch();
    return $report ?: null;
}

function getDashboardFilters(): array
{
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? appDateToday();
    $storeId = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? (int) $_GET['store_id'] : null;

    return [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'store_id' => $storeId,
    ];
}

function buildDashboardWhere(array $filters, array &$params): string
{
    $where = ' WHERE dr.report_date BETWEEN :date_from AND :date_to ';
    $params['date_from'] = $filters['date_from'];
    $params['date_to'] = $filters['date_to'];

    if (!empty($filters['store_id'])) {
        $where .= ' AND dr.store_id = :store_id ';
        $params['store_id'] = $filters['store_id'];
    }

    return $where;
}

function getDashboardMetrics(array $filters): array
{
    $params = [];
    $where = buildDashboardWhere($filters, $params);

    $sql = 'SELECT
            COUNT(*) AS total_records,
            COALESCE(SUM(dr.entered_total), 0) AS total_entered,
            COALESCE(SUM(dr.buyers), 0) AS total_buyers,
            COALESCE(SUM(dr.info_count), 0) AS total_information,
            COALESCE(SUM(dr.channel33_count), 0) AS total_channel33,
            COALESCE(SUM(dr.youtube_count), 0) AS total_youtube,
            COALESCE(SUM(dr.izzi_count), 0) AS total_izzi,
            COALESCE(SUM(dr.totalplay_count), 0) AS total_totalplay
        FROM daily_reports dr' . $where;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $metrics = $stmt->fetch() ?: [];

    $metrics['conversion_rate'] = ((int) $metrics['total_entered'] > 0)
        ? round(((int) $metrics['total_buyers'] / (int) $metrics['total_entered']) * 100, 1)
        : 0;

    return $metrics;
}

function getDashboardReports(array $filters): array
{
    $params = [];
    $where = buildDashboardWhere($filters, $params);

    $sql = 'SELECT dr.*, s.name AS store_name, u.username AS created_by_username
        FROM daily_reports dr
        INNER JOIN stores s ON s.id = dr.store_id
        LEFT JOIN users u ON u.id = dr.created_by_user_id
        ' . $where . '
        ORDER BY dr.report_date DESC, s.name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getChartDataByStore(array $filters): array
{
    $params = [];
    $where = buildDashboardWhere($filters, $params);

    $sql = 'SELECT s.name,
                COALESCE(SUM(dr.entered_total), 0) AS total_entered,
                COALESCE(SUM(dr.buyers), 0) AS total_buyers
            FROM stores s
            LEFT JOIN daily_reports dr ON dr.store_id = s.id
            AND dr.report_date BETWEEN :date_from AND :date_to';

    if (!empty($filters['store_id'])) {
        $sql .= ' WHERE s.id = :store_id ';
        $params = [
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'store_id' => $filters['store_id'],
        ];
    } else {
        $params = [
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ];
    }

    $sql .= ' GROUP BY s.id, s.name ORDER BY s.id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getChartDataByDate(array $filters): array
{
    $params = [];
    $where = buildDashboardWhere($filters, $params);

    $sql = 'SELECT dr.report_date,
                COALESCE(SUM(dr.entered_total), 0) AS total_entered,
                COALESCE(SUM(dr.buyers), 0) AS total_buyers
            FROM daily_reports dr
            ' . $where . '
            GROUP BY dr.report_date
            ORDER BY dr.report_date ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getSourceChartTotals(array $filters): array
{
    $params = [];
    $where = buildDashboardWhere($filters, $params);

    $sql = 'SELECT
                COALESCE(SUM(dr.channel33_count), 0) AS channel33_total,
                COALESCE(SUM(dr.youtube_count), 0) AS youtube_total,
                COALESCE(SUM(dr.izzi_count), 0) AS izzi_total,
                COALESCE(SUM(dr.totalplay_count), 0) AS totalplay_total
            FROM daily_reports dr
            ' . $where;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch() ?: [
        'channel33_total' => 0,
        'youtube_total' => 0,
        'izzi_total' => 0,
        'totalplay_total' => 0,
    ];
}

function getStoreUsers(): array
{
    $stmt = db()->query("SELECT u.id, u.username, u.display_name, u.role, u.is_active, u.updated_at, s.name AS store_name
        FROM users u
        LEFT JOIN stores s ON s.id = u.store_id
        WHERE u.role = 'store'
        ORDER BY s.id ASC");
    return $stmt->fetchAll();
}

function updateUserPassword(int $userId, string $plainPassword): bool
{
    $stmt = db()->prepare('UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id');
    return $stmt->execute([
        'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
        'updated_at' => appDateTimeNow(),
        'id' => $userId,
    ]);
}

function validateNonNegativeInt($value): int
{
    $value = trim((string) $value);

    if ($value === '' || !preg_match('/^\d+$/', $value)) {
        throw new InvalidArgumentException('Todos los campos deben ser números enteros iguales o mayores a 0.');
    }

    return (int) $value;
}

function flashSet(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flashGet(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function appSettingsTableExists(): bool
{
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    try {
        $stmt = db()->query("SHOW TABLES LIKE 'app_settings'");
        $exists = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $exists = false;
    }

    return $exists;
}

function getAppSetting(string $key, ?string $default = null): ?string
{
    if (!appSettingsTableExists()) {
        return $default;
    }

    $stmt = db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :setting_key LIMIT 1');
    $stmt->execute(['setting_key' => $key]);
    $value = $stmt->fetchColumn();

    if ($value === false || $value === null) {
        return $default;
    }

    return (string) $value;
}

function setAppSetting(string $key, string $value): bool
{
    if (!appSettingsTableExists()) {
        throw new RuntimeException('Falta la tabla app_settings. Ejecuta el SQL de configuración visual para habilitar esta función.');
    }

    $sql = 'INSERT INTO app_settings (setting_key, setting_value, updated_at)
        VALUES (:setting_key, :setting_value, :updated_at)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_at = VALUES(updated_at)';

    $stmt = db()->prepare($sql);
    return $stmt->execute([
        'setting_key' => $key,
        'setting_value' => $value,
        'updated_at' => appDateTimeNow(),
    ]);
}

function getDefaultLoginBackground(): ?string
{
    $candidates = [
        'assets/1.jpg',
        '1.jpg',
    ];

    foreach ($candidates as $candidate) {
        if (is_file(__DIR__ . '/' . $candidate)) {
            return $candidate;
        }
    }

    return null;
}

function getLoginBackgroundPath(): ?string
{
    $customPath = getAppSetting('login_background_image', '');

    if ($customPath && is_file(__DIR__ . '/' . $customPath)) {
        return $customPath;
    }

    return getDefaultLoginBackground();
}

function getLoginBackgroundUrl(): string
{
    $path = getLoginBackgroundPath();
    if (!$path) {
        return '';
    }

    return $path . '?v=' . rawurlencode((string) @filemtime(__DIR__ . '/' . $path));
}

function uploadLoginBackground(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Selecciona una imagen válida para subir.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('No se recibió un archivo subido correctamente.');
    }

    $maxSizeBytes = 4 * 1024 * 1024;
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxSizeBytes) {
        throw new InvalidArgumentException('La imagen debe pesar máximo 4 MB.');
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new InvalidArgumentException('Formato no permitido. Usa JPG, JPEG, PNG o WEBP.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $finfo->file($file['tmp_name']);
    $allowedMime = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    if (($allowedMime[$extension] ?? '') !== $mimeType) {
        throw new InvalidArgumentException('El tipo MIME del archivo no coincide con una imagen permitida.');
    }

    $directoryRelative = 'uploads/login_backgrounds';
    $directoryAbsolute = __DIR__ . '/' . $directoryRelative;
    if (!is_dir($directoryAbsolute) && !mkdir($directoryAbsolute, 0755, true) && !is_dir($directoryAbsolute)) {
        throw new RuntimeException('No se pudo crear el directorio para subir imágenes.');
    }

    $newFilename = 'login_bg_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destinationRelative = $directoryRelative . '/' . $newFilename;
    $destinationAbsolute = __DIR__ . '/' . $destinationRelative;

    if (!move_uploaded_file($file['tmp_name'], $destinationAbsolute)) {
        throw new RuntimeException('No se pudo guardar la imagen subida.');
    }

    $previousPath = getAppSetting('login_background_image', '');
    setAppSetting('login_background_image', $destinationRelative);

    if ($previousPath && str_starts_with($previousPath, $directoryRelative . '/') && is_file(__DIR__ . '/' . $previousPath)) {
        @unlink(__DIR__ . '/' . $previousPath);
    }

    return $destinationRelative;
}
