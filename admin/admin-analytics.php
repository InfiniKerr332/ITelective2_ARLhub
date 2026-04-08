<?php
/**
 * WMSU ARL Hub: Admin System Analytics — Stitch Design System
 * Features: Date Range Filtering, Chart.js Integration, Bento Grid Layout
 */
require_once '../config/auth.php';
checkAuth('admin');

// ── Date Range Handling ──
$today = date('Y-m-d');
$firstOfMonth = date('Y-m-01');
$lastOfLastMonth = date('Y-m-t', strtotime('last month'));

$startDate = $_GET['start_date'] ?? $firstOfMonth;
$endDate   = $_GET['end_date'] ?? $today;

// Validation: flip if start > end
if ($startDate > $endDate) {
    list($startDate, $endDate) = [$endDate, $startDate];
}

try {
    // 1. Global Totals (Context)
    $globalUsers     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $globalMaterials = $pdo->query("SELECT COUNT(*) FROM materials WHERE status='approved'")->fetchColumn();
    $globalDownloads = $pdo->query("SELECT SUM(downloads_count) FROM materials")->fetchColumn() ?? 0;

    // 2. Period Statistics
    $newUsersStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
    $newUsersStmt->execute([$startDate, $endDate]);
    $periodNewUsers = $newUsersStmt->fetchColumn();

    $newMatsStmt = $pdo->prepare("SELECT COUNT(*) FROM materials WHERE DATE(created_at) BETWEEN ? AND ?");
    $newMatsStmt->execute([$startDate, $endDate]);
    $periodNewMats = $newMatsStmt->fetchColumn();

    $periodDlStmt = $pdo->prepare("SELECT COUNT(*) FROM downloads WHERE DATE(downloaded_at) BETWEEN ? AND ?");
    $periodDlStmt->execute([$startDate, $endDate]);
    $periodDownloads = $periodDlStmt->fetchColumn();

    $periodReviewsStmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE DATE(created_at) BETWEEN ? AND ?");
    $periodReviewsStmt->execute([$startDate, $endDate]);
    $periodReviews = $periodReviewsStmt->fetchColumn();

    // 3. Downloads Trend Data (Chart.js)
    // If range <= 62 days, show daily. Else show monthly.
    $diff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
    if ($diff <= 62) {
        $trendStmt = $pdo->prepare("
            SELECT DATE_FORMAT(downloaded_at, '%Y-%m-%d') as label, COUNT(*) as count 
            FROM downloads 
            WHERE DATE(downloaded_at) BETWEEN ? AND ? 
            GROUP BY label ORDER BY label ASC
        ");
        $trendType = 'day';
    } else {
        $trendStmt = $pdo->prepare("
            SELECT DATE_FORMAT(downloaded_at, '%b %Y') as label, COUNT(*) as count 
            FROM downloads 
            WHERE DATE(downloaded_at) BETWEEN ? AND ? 
            GROUP BY DATE_FORMAT(downloaded_at, '%Y-%m') ORDER BY downloaded_at ASC
        ");
        $trendType = 'month';
    }
    $trendStmt->execute([$startDate, $endDate]);
    $trendData = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Category Distribution
    $catStmt = $pdo->prepare("
        SELECT category, COUNT(*) as count 
        FROM materials 
        WHERE status='approved' AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY category ORDER BY count DESC
    ");
    $catStmt->execute([$startDate, $endDate]);
    $catData = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no data in period, maybe show global category distribution as fallback?
    // Let's stick to period for true analytics.

    // 5. Top Contributors (in period)
    $contributorStmt = $pdo->prepare("
        SELECT u.full_name, u.role, COUNT(m.id) as mat_count
        FROM users u 
        JOIN materials m ON m.contributor_id = u.id
        WHERE m.status = 'approved' AND DATE(m.created_at) BETWEEN ? AND ?
        GROUP BY u.id ORDER BY mat_count DESC LIMIT 5
    ");
    $contributorStmt->execute([$startDate, $endDate]);
    $topContributors = $contributorStmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Role Distribution (Global)
    $roleDist = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
    $totalRoles = array_sum(array_column($roleDist, 'count'));

} catch (PDOException $e) {
    // Fail gracefully
    $periodNewUsers = $periodNewMats = $periodDownloads = $periodReviews = 0;
    $trendData = $catData = $topContributors = $roleDist = [];
}

// Prepare Chart Data for JS
$trendLabels = array_column($trendData, 'label');
$trendValues = array_column($trendData, 'count');
$catLabels   = array_column($catData, 'category');
$catValues   = array_column($catData, 'count');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Analytics - WMSU ARL Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#B81C2E',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; color: #1E293B; }
        h1, h2, h3, .headline { font-family: 'Plus Jakarta Sans', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(15%) sepia(85%) saturate(5432%) hue-rotate(345deg) brightness(95%) contrast(90%);
            cursor: pointer;
        }
    </style>
</head>
<body class="antialiased">

<?php require_once '../includes/dashboard-nav.php'; ?>

<div class="flex">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="flex-1 ml-[240px] pt-16 min-h-screen">
        <div class="p-8 max-w-7xl mx-auto">
            
            <!-- ── Page Header & Filters ── -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
                <div>
                    <h1 class="text-[32px] font-extrabold tracking-tight text-[#1A1A2E] flex items-center gap-3">
                        System Analytics
                        <span class="bg-red-50 text-red-600 text-xs px-2.5 py-1 rounded-full border border-red-100 uppercase tracking-widest font-bold">Admin</span>
                    </h1>
                    <p class="text-slate-500 mt-1 font-medium italic">Comprehensive performance insights and usage trends.</p>
                </div>

                <form method="GET" class="flex flex-wrap items-center gap-3 bg-white p-2.5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex flex-col px-3 border-r border-slate-100">
                        <label class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $startDate; ?>" 
                               class="border-none p-0 text-sm font-bold focus:ring-0 text-[#1A1A2E]">
                    </div>
                    <div class="flex flex-col px-3">
                        <label class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">End Date</label>
                        <input type="date" name="end_date" value="<?php echo $endDate; ?>" 
                               class="border-none p-0 text-sm font-bold focus:ring-0 text-[#1A1A2E]">
                    </div>
                    <button type="submit" class="bg-[#B81C2E] hover:bg-[#9A1624] text-white p-3 rounded-xl transition-all shadow-lg shadow-red-100 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">filter_list</span>
                    </button>
                    <?php if(isset($_GET['start_date'])): ?>
                    <a href="admin-analytics.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 p-3 rounded-xl transition-all flex items-center justify-center" title="Reset">
                        <span class="material-symbols-outlined text-[20px]">restart_alt</span>
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ── Period KPI Highlights ── -->
            <div class="mb-4">
                <span class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4 block">Period Overview: <?php echo date('M d', strtotime($startDate)); ?> – <?php echo date('M d, Y', strtotime($endDate)); ?></span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <?php 
                $kpis = [
                    ['New Users', 'person_add', $periodNewUsers, 'Registrations', 'border-indigo-100 text-indigo-600', 'bg-indigo-50'],
                    ['Submissions', 'upload_file', $periodNewMats, 'New materials', 'border-red-100 text-[#B81C2E]', 'bg-red-50/50'],
                    ['Downloads', 'download', $periodDownloads, 'Total actions', 'border-emerald-100 text-emerald-600', 'bg-emerald-50'],
                    ['Feedback', 'reviews', $periodReviews, 'New reviews', 'border-amber-100 text-amber-600', 'bg-amber-50'],
                ];
                foreach ($kpis as $k): ?>
                <div class="bg-white rounded-2xl border <?php echo $k[4]; ?> p-6 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 <?php echo $k[5]; ?> rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[24px]"><?php echo $k[1]; ?></span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?php echo $k[0]; ?></span>
                    </div>
                    <div class="text-3xl font-black text-[#1A1A2E] leading-none mb-1"><?php echo number_format($k[2]); ?></div>
                    <div class="text-xs text-slate-500 font-semibold"><?php echo $k[3]; ?> in selected range</div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Charts Grid ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                
                <!-- Main Trend Chart -->
                <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="headline text-lg font-bold text-[#1A1A2E]">Download Activity Trend</h3>
                            <p class="text-sm text-slate-500 font-medium">Tracking platform engagement frequency</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-[#B81C2E]"></span>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Downloads</span>
                        </div>
                    </div>
                    <div class="h-[320px] relative">
                        <?php if(empty($trendData)): ?>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
                                <span class="material-symbols-outlined text-[48px] mb-2 opacity-20">analytics</span>
                                <p class="text-sm font-medium">No activity data for this period</p>
                            </div>
                        <?php endif; ?>
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm flex flex-col">
                    <h3 class="headline text-lg font-bold text-[#1A1A2E] mb-1">Content Distribution</h3>
                    <p class="text-sm text-slate-500 font-medium mb-8">Submission focus by category</p>
                    <div class="flex-1 relative min-h-[220px]">
                         <?php if(empty($catData)): ?>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
                                <span class="material-symbols-outlined text-[48px] mb-2 opacity-20">category</span>
                                <p class="text-sm font-medium">No submission data</p>
                            </div>
                        <?php endif; ?>
                        <canvas id="catChart"></canvas>
                    </div>
                    <div class="mt-6 space-y-3 max-h-[140px] overflow-y-auto pr-2">
                        <?php foreach($catData as $i => $c): 
                            $colors = ['#B81C2E', '#F59E0B', '#10B981', '#6366F1', '#EC4899', '#8B5CF6'];
                            $color = $colors[$i % count($colors)];
                        ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" style="background:<?php echo $color; ?>"></span>
                                <span class="text-xs font-bold text-[#1A1A2E]"><?php echo htmlspecialchars($c['category']); ?></span>
                            </div>
                            <span class="text-xs font-black text-slate-400"><?php echo $c['count']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ── Bottom Bento Grid ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Top Contributors -->
                <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="headline text-lg font-bold text-[#1A1A2E]">Active Contributors</h3>
                        <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-1 rounded-lg uppercase">Leaderboard</span>
                    </div>
                    <?php if (empty($topContributors)): ?>
                    <div class="py-12 text-center text-slate-400">
                        <p class="text-sm">No submissions in this period</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($topContributors as $i => $c): 
                             $bg = ['bg-amber-100 text-amber-700', 'bg-slate-100 text-slate-600', 'bg-orange-100 text-orange-700'][$i] ?? 'bg-slate-50 text-slate-400';
                        ?>
                        <div class="flex items-center gap-4 group">
                            <div class="w-10 h-10 rounded-2xl <?php echo $bg; ?> flex items-center justify-center font-black text-sm transition-transform group-hover:scale-110">
                                <?php echo $i + 1; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-[#1A1A2E] truncate"><?php echo htmlspecialchars($c['full_name']); ?></div>
                                <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider"><?php echo $c['role']; ?></div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-black text-[#1A1A2E]"><?php echo $c['mat_count']; ?></div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase">Files</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Role Distribution -->
                <div class="bg-[#1A1A2E] rounded-3xl p-8 shadow-xl relative overflow-hidden">
                    <h3 class="headline text-lg font-bold text-white mb-6 relative z-10">User Ecosystem</h3>
                    <div class="space-y-6 relative z-10">
                        <?php 
                        $roleColors = ['student' => '#6366F1', 'faculty' => '#10B981', 'admin' => '#B81C2E'];
                        foreach ($roleDist as $r): 
                            $pct = $totalRoles > 0 ? ($r['count'] / $totalRoles) * 100 : 0;
                            $color = $roleColors[$r['role']] ?? '#94A3B8';
                        ?>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-bold text-white/60 uppercase tracking-widest"><?php echo ucfirst($r['role']); ?>s</span>
                                <span class="text-sm font-black text-white"><?php echo number_format($r['count']); ?></span>
                            </div>
                            <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-1000" style="width:<?php echo $pct; ?>%; background:<?php echo $color; ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-10 pt-6 border-t border-white/10 relative z-10 flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Global Base</div>
                            <div class="text-2xl font-black text-white"><?php echo number_format($globalUsers); ?></div>
                        </div>
                        <div class="text-right">
                             <div class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Total Downloads</div>
                            <div class="text-2xl font-black text-white"><?php echo number_format($globalDownloads); ?></div>
                        </div>
                    </div>
                    <!-- Deco Icon -->
                    <span class="material-symbols-outlined absolute -bottom-8 -right-8 text-white/[0.03] text-[180px] pointer-events-none">hub</span>
                </div>

                <!-- Quick Insight / Summary -->
                <div class="bg-red-600 rounded-3xl p-8 shadow-lg shadow-red-100 flex flex-col justify-between text-white border-4 border-white/10">
                    <div>
                        <span class="material-symbols-outlined text-[40px] mb-4">bolt</span>
                        <h3 class="headline text-xl font-black mb-3">Instant Summary</h3>
                        <p class="text-white/80 text-sm leading-relaxed font-medium">
                            Over the selected period, you've seen <strong class="text-white"><?php echo $periodNewUsers; ?></strong> new registrations 
                            and <strong class="text-white"><?php echo $periodDownloads; ?></strong> downloads. 
                            The most active category continues to be <strong class="text-white"><?php echo $catData[0]['category'] ?? 'N/A'; ?></strong>.
                        </p>
                    </div>
                    <div class="mt-8">
                        <a href="admin-audit.php" class="inline-flex items-center gap-2 bg-white text-[#B81C2E] px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest hover:scale-105 transition-transform shadow-xl">
                            View Audit Logs
                            <span class="material-symbols-outlined text-[18px]">arrow_right_alt</span>
                        </a>
                    </div>
                </div>

            </div>

        </div>
        
        <?php require_once '../includes/dashboard-footer.php'; ?>
    </main>
</div>

<!-- ── Chart Initialization ── -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Trend Line Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendGradient = trendCtx.createLinearGradient(0, 0, 0, 400);
    trendGradient.addColorStop(0, 'rgba(184, 28, 46, 0.2)');
    trendGradient.addColorStop(1, 'rgba(184, 28, 46, 0)');

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trendLabels); ?>,
            datasets: [{
                label: 'Downloads',
                data: <?php echo json_encode($trendValues); ?>,
                borderColor: '#B81C2E',
                borderWidth: 4,
                backgroundColor: trendGradient,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#B81C2E',
                pointBorderWidth: 2,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1A1A2E',
                    padding: 12,
                    titleFont: { family: 'Plus Jakarta Sans', size: 13, weight: 'bold' },
                    bodyFont: { family: 'Inter', size: 12 },
                    displayColors: false,
                    callbacks: {
                        label: (ctx) => `Downloads: ${ctx.raw}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F1F5F9', drawBorder: false },
                    ticks: { font: { family: 'Inter', weight: 'bold', size: 10 }, color: '#94A3B8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', weight: 'bold', size: 10 }, color: '#94A3B8' }
                }
            }
        }
    });

    // 2. Category Distribution Chart (Doughnut)
    const catCtx = document.getElementById('catChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($catLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($catValues); ?>,
                backgroundColor: ['#B81C2E', '#F59E0B', '#10B981', '#6366F1', '#EC4899', '#8B5CF6'],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1A1A2E',
                    padding: 12,
                    titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                    callbacks: {
                        label: (ctx) => ` Submissions: ${ctx.raw}`
                    }
                }
            }
        }
    });
});
</script>

</body>
</html>
