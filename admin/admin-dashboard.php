<?php
/**
 * WMSU ARL Hub: Admin Dashboard — Premium Stitch Design System
 */
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/paths.php';
}
require_once '../config/auth.php';
checkAuth('admin');

try {
    $totalUsers        = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalMaterials    = $pdo->query("SELECT COUNT(*) FROM materials WHERE status='approved'")->fetchColumn();
    $pendingModeration = $pdo->query("SELECT COUNT(*) FROM materials WHERE status = 'pending'")->fetchColumn();
    $totalDownloads    = $pdo->query("SELECT COALESCE(SUM(downloads_count),0) FROM materials")->fetchColumn();
    $studentsCount     = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    $facultyCount      = $pdo->query("SELECT COUNT(*) FROM users WHERE role='faculty'")->fetchColumn();
    $totalReviews      = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

    // New users this month
    $newUsersThisMonth = $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

    // Recent Activity Logs
    $recentLogs = $pdo->query("
        SELECT a.*, u.full_name as user_name 
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.timestamp DESC 
        LIMIT 8
    ")->fetchAll();

    // Top Downloaded Materials (with category + avg rating)
    $topMaterials = $pdo->query("
        SELECT m.id, m.title, m.downloads_count, m.category, m.views_count,
               u.full_name as author,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(r.id) as review_count
        FROM materials m 
        LEFT JOIN users u ON m.contributor_id = u.id
        LEFT JOIN reviews r ON r.material_id = m.id
        WHERE m.status = 'approved'
        GROUP BY m.id
        ORDER BY m.downloads_count DESC 
        LIMIT 5
    ")->fetchAll();

    // Category distribution for donut chart
    $categoryStats = $pdo->query("
        SELECT category, COUNT(*) as count 
        FROM materials WHERE status='approved'
        GROUP BY category ORDER BY count DESC
    ")->fetchAll();

    // Weekly user growth for last 6 months (actual monthly data)
    $monthlyUsers = $pdo->query("
        SELECT DATE_FORMAT(created_at,'%b') as month_label,
               DATE_FORMAT(created_at,'%Y-%m') as month_key,
               COUNT(*) as count
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month_key, month_label
        ORDER BY month_key ASC
    ")->fetchAll();

    // Monthly downloads for chart
    $monthlyDownloads = $pdo->query("
        SELECT DATE_FORMAT(downloaded_at,'%b') as month_label,
               DATE_FORMAT(downloaded_at,'%Y-%m') as month_key,
               COUNT(*) as count
        FROM downloads
        WHERE downloaded_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month_key, month_label
        ORDER BY month_key ASC
    ")->fetchAll();

    // Role distribution
    $roleDist = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll();

} catch (PDOException $e) {
    $totalUsers = $totalMaterials = $pendingModeration = $totalDownloads = $studentsCount = $facultyCount = $totalReviews = $newUsersThisMonth = 0;
    $recentLogs = $topMaterials = $categoryStats = $monthlyUsers = $monthlyDownloads = $roleDist = [];
}

// Prepare chart data as JSON
$chartMonthLabels   = array_column($monthlyUsers, 'month_label');
$chartUserCounts    = array_column($monthlyUsers, 'count');
$chartDlLabels      = array_column($monthlyDownloads, 'month_label');
$chartDlCounts      = array_column($monthlyDownloads, 'count');
$catLabels          = array_column($categoryStats, 'category');
$catCounts          = array_column($categoryStats, 'count');

// If no data, use placeholder labels
if (empty($chartMonthLabels)) {
    $chartMonthLabels = ['Jan','Feb','Mar','Apr','May','Jun'];
    $chartUserCounts  = [0,0,0,0,0,0];
}
if (empty($chartDlLabels)) {
    $chartDlLabels = ['Jan','Feb','Mar','Apr','May','Jun'];
    $chartDlCounts = [0,0,0,0,0,0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - WMSU ARL Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#B81C2E' } } }
        }
    </script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #F4F4F6; color: #1A1A2E; }
        h1, h2, h3, h4, .headline { font-family: 'Plus Jakarta Sans', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }

        .kpi-card {
            background: white;
            border-radius: 14px;
            border: 1px solid rgba(0,0,0,0.06);
            padding: 22px 24px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .kpi-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.08); transform: translateY(-2px); }

        .icon-badge {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .chart-card {
            background: white;
            border-radius: 14px;
            border: 1px solid rgba(0,0,0,0.06);
            padding: 24px;
        }

        .rank-badge {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 13px;
            flex-shrink: 0;
        }

        .activity-row:hover { background: #FDF5F6 !important; }

        .status-pill {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
        }

        .progress-bar-track {
            height: 5px; background: #F4F4F6; border-radius: 99px; overflow: hidden; margin-top: 10px;
        }
        .progress-bar-fill {
            height: 100%; border-radius: 99px;
            background: linear-gradient(90deg, #B81C2E, #E83354);
            transition: width 1s ease;
        }
    </style>
</head>
<body>

<?php require_once '../includes/dashboard-nav.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>

<div class="flex">
    <main class="flex-1 ml-[240px] pt-16 flex flex-col min-h-screen bg-[#F4F4F6]">
        <div class="max-w-[1320px] w-full mx-auto p-8 flex-1">

            <!-- Page Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="headline text-[26px] font-bold text-[#1A1A2E] leading-tight">System Dashboard</h1>
                    <p class="text-[#848494] text-sm mt-1 font-medium"><?php echo date('l, F j, Y'); ?> &nbsp;·&nbsp; Welcome back, Administrator</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="admin-moderation.php" class="flex items-center gap-2 bg-white border border-black/10 text-[#1A1A2E] px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-colors">
                        <span class="material-symbols-outlined text-[17px]">shield</span> Moderation
                    </a>
                    <a href="admin-analytics.php" class="flex items-center gap-2 bg-[#B81C2E] text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-[#9A1624] transition-colors">
                        <span class="material-symbols-outlined text-[17px]">analytics</span> Full Analytics
                    </a>
                </div>
            </div>

            <!-- Warning Banner -->
            <?php if ($pendingModeration > 0): ?>
            <div class="bg-amber-50 border border-amber-200 border-l-4 border-l-amber-400 p-4 mb-8 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-amber-600 text-[20px]">warning</span>
                    <span class="text-sm text-amber-800 font-semibold"><?php echo $pendingModeration; ?> material<?php echo $pendingModeration > 1 ? 's' : ''; ?> pending moderation review.</span>
                </div>
                <a href="admin-moderation.php" class="text-amber-700 hover:text-amber-900 text-sm font-bold flex items-center gap-1">
                    Review Now <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- KPI Cards Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

                <!-- Total Users -->
                <div class="kpi-card">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#848494]">Total Users</span>
                        <div class="icon-badge bg-indigo-50">
                            <span class="material-symbols-outlined text-indigo-600 text-[20px]">group</span>
                        </div>
                    </div>
                    <div class="headline text-[28px] font-bold text-[#1A1A2E]"><?php echo number_format($totalUsers); ?></div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">+<?php echo $newUsersThisMonth; ?> this month</span>
                    </div>
                    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:100%; background: linear-gradient(90deg,#6366F1,#818CF8)"></div></div>
                </div>

                <!-- Approved Materials -->
                <div class="kpi-card">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#848494]">Materials</span>
                        <div class="icon-badge bg-[#F9E8EA]">
                            <span class="material-symbols-outlined text-[#B81C2E] text-[20px]">description</span>
                        </div>
                    </div>
                    <div class="headline text-[28px] font-bold text-[#1A1A2E]"><?php echo number_format($totalMaterials); ?></div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="text-[11px] font-semibold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded"><?php echo $pendingModeration; ?> pending</span>
                    </div>
                    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:<?php echo $totalMaterials > 0 ? min(100, round(($totalMaterials/($totalMaterials+$pendingModeration))*100)) : 0; ?>%"></div></div>
                </div>

                <!-- Total Downloads -->
                <div class="kpi-card">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#848494]">Total Downloads</span>
                        <div class="icon-badge bg-emerald-50">
                            <span class="material-symbols-outlined text-emerald-600 text-[20px]">download</span>
                        </div>
                    </div>
                    <div class="headline text-[28px] font-bold text-[#1A1A2E]"><?php echo number_format($totalDownloads); ?></div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="text-[11px] text-[#848494] font-medium">Across all materials</span>
                    </div>
                    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:100%; background: linear-gradient(90deg,#059669,#34D399)"></div></div>
                </div>

                <!-- Reviews -->
                <div class="kpi-card">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#848494]">Reviews</span>
                        <div class="icon-badge bg-yellow-50">
                            <span class="material-symbols-outlined text-yellow-500 text-[20px]">star</span>
                        </div>
                    </div>
                    <div class="headline text-[28px] font-bold text-[#1A1A2E]"><?php echo number_format($totalReviews); ?></div>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="text-[11px] text-[#848494] font-medium"><?php echo $studentsCount; ?> students · <?php echo $facultyCount; ?> faculty</span>
                    </div>
                    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:100%; background: linear-gradient(90deg,#F59E0B,#FCD34D)"></div></div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-3 gap-5 mb-8">

                <!-- User Registration Chart (line) -->
                <div class="chart-card col-span-2">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="headline text-[15px] font-bold text-[#1A1A2E]">User Growth</h3>
                            <p class="text-[12px] text-[#848494] mt-0.5">New registrations over the last 6 months</p>
                        </div>
                        <div class="flex items-center gap-3 text-[12px] font-semibold text-[#848494]">
                            <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full bg-[#B81C2E]"></span>Users</span>
                            <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full bg-indigo-400"></span>Downloads</span>
                        </div>
                    </div>
                    <div style="height: 220px; position: relative;">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>

                <!-- Category Donut Chart -->
                <div class="chart-card">
                    <div class="mb-5">
                        <h3 class="headline text-[15px] font-bold text-[#1A1A2E]">By Category</h3>
                        <p class="text-[12px] text-[#848494] mt-0.5">Approved materials breakdown</p>
                    </div>
                    <div style="height: 160px; position: relative; margin: 0 auto; max-width: 160px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="mt-4 space-y-2" id="catLegend">
                        <?php 
                        $donutColors = ['#B81C2E','#6366F1','#059669','#F59E0B','#0EA5E9','#EC4899'];
                        foreach($categoryStats as $ci => $cat): 
                            $pct = $totalMaterials > 0 ? round(($cat['count']/$totalMaterials)*100) : 0;
                        ?>
                        <div class="flex items-center justify-between text-[12px]">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?php echo $donutColors[$ci % count($donutColors)]; ?>"></span>
                                <span class="text-[#4A4A5A] font-medium truncate" style="max-width:110px"><?php echo htmlspecialchars($cat['category']); ?></span>
                            </div>
                            <span class="font-bold text-[#1A1A2E]"><?php echo $pct; ?>%</span>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($categoryStats)): ?>
                        <p class="text-[12px] text-[#848494] text-center py-2">No approved materials yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Activity Table + Top Materials -->
            <div class="grid grid-cols-5 gap-5 mb-10">

                <!-- Recent Activity Table -->
                <div class="col-span-3 bg-white rounded-[14px] border border-black/[0.06] overflow-hidden">
                    <div class="px-6 py-4 border-b border-black/5 flex justify-between items-center">
                        <div>
                            <h3 class="headline text-[15px] font-bold text-[#1A1A2E]">Recent Activity</h3>
                            <p class="text-[11px] text-[#848494] mt-0.5">Latest system events</p>
                        </div>
                        <a href="admin-audit.php" class="text-[#B81C2E] text-[12px] font-bold hover:underline flex items-center gap-1">
                            View All <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                    <div class="divide-y divide-black/[0.04]">
                        <?php if (empty($recentLogs)): ?>
                            <div class="px-6 py-10 text-center text-[#848494] text-sm">No activity recorded yet.</div>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $idx => $log):
                                $actionLower = strtolower($log['action']);
                                if (str_contains($actionLower, 'flag') || str_contains($actionLower, 'report') || str_contains($actionLower, 'pending')) {
                                    $statusText = 'Pending'; $statusClass = 'bg-amber-50 text-amber-700';
                                } elseif (str_contains($actionLower, 'reject') || str_contains($actionLower, 'delete') || str_contains($actionLower, 'ban')) {
                                    $statusText = 'Actioned'; $statusClass = 'bg-red-50 text-red-700';
                                } else {
                                    $statusText = 'Completed'; $statusClass = 'bg-emerald-50 text-emerald-700';
                                }
                                $actionIcon = match(true) {
                                    str_contains($actionLower,'login')    => 'login',
                                    str_contains($actionLower,'approve')  => 'verified',
                                    str_contains($actionLower,'reject')   => 'cancel',
                                    str_contains($actionLower,'upload')   => 'cloud_upload',
                                    str_contains($actionLower,'moderate') => 'shield',
                                    default => 'sensors'
                                };
                            ?>
                            <div class="activity-row px-6 py-3.5 flex items-center gap-4 transition-colors">
                                <div class="icon-badge bg-[#F4F4F6] flex-shrink-0 w-8 h-8 rounded-lg">
                                    <span class="material-symbols-outlined text-[#4A4A5A] text-[16px]"><?php echo $actionIcon; ?></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[13px] font-semibold text-[#1A1A2E] capitalize"><?php echo htmlspecialchars($log['action']); ?></div>
                                    <div class="text-[11px] text-[#848494] truncate mt-0.5"><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?> · <?php echo htmlspecialchars(mb_strimwidth($log['details'] ?? '', 0, 50, '…')); ?></div>
                                </div>
                                <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                                    <span class="status-pill <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    <span class="text-[10px] text-[#848494] font-medium"><?php echo date('M j, g:i A', strtotime($log['timestamp'])); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Downloaded Materials -->
                <div class="col-span-2 bg-white rounded-[14px] border border-black/[0.06] overflow-hidden">
                    <div class="px-6 py-4 border-b border-black/5">
                        <h3 class="headline text-[15px] font-bold text-[#1A1A2E]">Top Downloads</h3>
                        <p class="text-[11px] text-[#848494] mt-0.5">Most accessed materials</p>
                    </div>
                    <div class="p-4 space-y-1">
                        <?php if (empty($topMaterials)): ?>
                            <div class="py-10 text-center text-[#848494] text-sm">No downloads recorded yet.</div>
                        <?php else: ?>
                            <?php foreach ($topMaterials as $i => $mat):
                                $rankColors = ['#B81C2E','#D4AF37','#836F52','#6366F1','#059669'];
                                $rankBg     = ['#F9E8EA','#FFF9E6','#F5F0EA','#EEF2FF','#ECFDF5'];
                                $maxDl      = $topMaterials[0]['downloads_count'] > 0 ? $topMaterials[0]['downloads_count'] : 1;
                                $fillPct    = max(6, round(($mat['downloads_count'] / $maxDl) * 100));
                                $stars      = round($mat['avg_rating']);
                            ?>
                            <a href="<?php echo BASE_URL; ?>core/material-details.php?id=<?php echo $mat['id']; ?>" 
                               class="flex items-center gap-3 p-3 rounded-xl hover:bg-[#F9F9FB] transition-colors group">
                                <div class="rank-badge" style="background:<?php echo $rankBg[$i] ?? '#F4F4F6'; ?>; color:<?php echo $rankColors[$i] ?? '#848494'; ?>">
                                    <?php echo str_pad($i+1, 2, '0', STR_PAD_LEFT); ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[12px] font-semibold text-[#1A1A2E] truncate group-hover:text-[#B81C2E] transition-colors">
                                        <?php echo htmlspecialchars($mat['title']); ?>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="flex-1 h-1.5 bg-[#F4F4F6] rounded-full overflow-hidden" style="max-width:80px">
                                            <div class="h-full rounded-full transition-all" style="width:<?php echo $fillPct; ?>%; background:<?php echo $rankColors[$i] ?? '#848494'; ?>"></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-[#848494]"><?php echo number_format($mat['downloads_count']); ?> dl</span>
                                    </div>
                                    <div class="text-[10px] text-[#848494] mt-0.5 truncate"><?php echo htmlspecialchars($mat['author'] ?? 'Unknown'); ?></div>
                                </div>
                                <?php if ($stars > 0): ?>
                                <div class="flex items-center gap-0.5 flex-shrink-0">
                                    <span class="material-symbols-outlined text-[12px] text-yellow-400" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">star</span>
                                    <span class="text-[11px] font-bold text-[#1A1A2E]"><?php echo number_format($mat['avg_rating'],1); ?></span>
                                </div>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="px-6 py-3 border-t border-black/5">
                        <a href="admin-analytics.php" class="text-[12px] font-bold text-[#B81C2E] hover:underline flex items-center gap-1">
                            View Full Analytics <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                        </a>
                    </div>
                </div>

            </div><!-- /bottom row -->

        </div>

        <?php require_once '../includes/dashboard-footer.php'; ?>
    </main>
</div>

<script>
// ── Chart.js: Growth Chart (Line) ──
const growthCtx = document.getElementById('growthChart').getContext('2d');

const userLabels = <?php echo json_encode($chartMonthLabels); ?>;
const userCounts = <?php echo json_encode(array_map('intval', $chartUserCounts)); ?>;
const dlLabels   = <?php echo json_encode($chartDlLabels); ?>;
const dlCounts   = <?php echo json_encode(array_map('intval', $chartDlCounts)); ?>;

// Merge labels
const allLabels = [...new Set([...userLabels, ...dlLabels])];

// Map counts to aligned labels
function alignData(labels, dataLabels, dataCounts) {
    return labels.map(l => {
        const idx = dataLabels.indexOf(l);
        return idx >= 0 ? dataCounts[idx] : 0;
    });
}
const alignedUsers = alignData(allLabels, userLabels, userCounts);
const alignedDl    = alignData(allLabels, dlLabels, dlCounts);

new Chart(growthCtx, {
    type: 'line',
    data: {
        labels: allLabels.length ? allLabels : ['Jan','Feb','Mar','Apr','May','Jun'],
        datasets: [
            {
                label: 'New Users',
                data: alignedUsers,
                borderColor: '#B81C2E',
                backgroundColor: 'rgba(184,28,46,0.08)',
                borderWidth: 2.5,
                pointBackgroundColor: '#B81C2E',
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4,
                fill: true,
            },
            {
                label: 'Downloads',
                data: alignedDl,
                borderColor: '#6366F1',
                backgroundColor: 'rgba(99,102,241,0.06)',
                borderWidth: 2.5,
                pointBackgroundColor: '#6366F1',
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4,
                fill: true,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1A1A2E',
                titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: '700' },
                bodyFont: { family: "'Inter', sans-serif", size: 12 },
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y}`
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { family: "'Inter', sans-serif", size: 11 }, color: '#9CA3AF' }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)' },
                ticks: {
                    font: { family: "'Inter', sans-serif", size: 11 },
                    color: '#9CA3AF',
                    stepSize: 1,
                    precision: 0
                }
            }
        }
    }
});

// ── Chart.js: Category Donut ──
const catCtx = document.getElementById('categoryChart').getContext('2d');
const catLabels = <?php echo json_encode($catLabels ?: ['No Data']); ?>;
const catData   = <?php echo json_encode(array_map('intval', $catCounts ?: [1])); ?>;
const catColors = ['#B81C2E','#6366F1','#059669','#F59E0B','#0EA5E9','#EC4899'];

new Chart(catCtx, {
    type: 'doughnut',
    data: {
        labels: catLabels,
        datasets: [{
            data: catData,
            backgroundColor: catColors.slice(0, catLabels.length),
            borderWidth: 3,
            borderColor: '#fff',
            hoverBorderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1A1A2E',
                titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: '700' },
                bodyFont: { family: "'Inter', sans-serif", size: 12 },
                padding: 10,
                cornerRadius: 8,
            }
        }
    }
});
</script>
</body>
</html>
