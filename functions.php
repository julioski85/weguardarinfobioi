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
        (store_id, report_date, client_new, recurrent, entered_total, buyers, info_count, channel33_count, youtube_count, created_by_user_id, created_at, updated_at)
        VALUES
        (:store_id, :report_date, :client_new, :recurrent, :entered_total, :buyers, :info_count, :channel33_count, :youtube_count, :created_by_user_id, :created_at, :updated_at)';

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
            COALESCE(SUM(dr.youtube_count), 0) AS total_youtube
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
                COALESCE(SUM(dr.info_count), 0) AS information_total,
                COALESCE(SUM(dr.channel33_count), 0) AS channel33_total,
                COALESCE(SUM(dr.youtube_count), 0) AS youtube_total
            FROM daily_reports dr
            ' . $where;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch() ?: [
        'information_total' => 0,
        'channel33_total' => 0,
        'youtube_total' => 0,
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
