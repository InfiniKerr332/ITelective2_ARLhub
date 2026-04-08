<?php
/**
 * WMSU ARL Hub: Administrative Help Center
 * Full content version with categorized guides, FAQs, quick actions
 */
require_once '../config/auth.php';
checkAuth('admin');

$helpTopics = [
    [
        'id'    => 'getting-started',
        'icon'  => 'rocket_launch',
        'color' => '#6366F1',
        'bg'    => '#EEF2FF',
        'title' => 'Getting Started',
        'desc'  => 'Overview of the admin panel and your core responsibilities.',
        'faqs'  => [
            ['q' => 'What is the WMSU ARL Hub Admin Panel?',
             'a' => 'The Admin Panel is your central command center for managing the Academic Resource Library. Here you can moderate submitted materials, manage user accounts, view system analytics, review audit logs, and configure system settings. Only users with the Administrator role can access this panel.'],
            ['q' => 'How do I navigate the system?',
             'a' => 'Use the sidebar on the left to access every major section: Dashboard (overview), Browse Materials (view all repository content), User Management, Content Moderation, System Analytics, System Logs, and My Profile. The top navigation bar provides quick access to notifications and your profile.'],
            ['q' => 'What should I do first as a new admin?',
             'a' => "Follow this checklist: 1) Review any pending materials in Content Moderation. 2) Check System Logs for any unusual activity. 3) Review the Dashboard statistics to understand the current state of the platform. 4) Ensure there are no banned or flagged users requiring attention in User Management."],
            ['q' => 'How do I change my admin password?',
             'a' => 'Go to My Profile from the sidebar, then scroll to the Security section. Enter your current password and your new password (minimum 8 characters containing letters and numbers is recommended). Click "Update Password" to save.'],
        ]
    ],
    [
        'id'    => 'moderation',
        'icon'  => 'shield_check',
        'color' => '#B81C2E',
        'bg'    => '#FEF2F2',
        'title' => 'Content Moderation',
        'desc'  => 'Review, approve, and reject submitted research materials.',
        'faqs'  => [
            ['q' => 'How do I approve or reject a submitted material?',
             'a' => 'Navigate to "Content Moderation" in the sidebar. You will see a queue of all pending submissions. Click on any material title to view its full details. At the bottom, click "Approve" to publish it to the repository, or "Reject" to decline it. Both actions send an automatic notification to the contributor.'],
            ['q' => 'What criteria should I use to approve materials?',
             'a' => 'Verify: (1) The title and description are clear and accurate. (2) The file is accessible and not corrupted. (3) The content category is correctly assigned. (4) The material does not violate copyright or contain inappropriate content. (5) The material is relevant to WMSU academic programs.'],
            ['q' => 'What happens when I reject a material?',
             'a' => 'The contributor receives an in-app notification that their material was rejected. The material remains in their "My Uploads" list with a "Rejected" status badge. They may edit and re-submit the material. Rejected materials are never visible to other users.'],
            ['q' => 'Can I undo an approval or rejection?',
             'a' => 'Currently, once a material is approved or rejected, there is no one-click undo. However, you can manually update a material\'s status from the Content Moderation section by re-reviewing flagged or reported items that may have been approved in error.'],
            ['q' => 'What are "Flagged" materials?',
             'a' => 'When a user reports a published material (e.g., for plagiarism or incorrect information), it creates a report log visible in Content Moderation and System Logs. Admins are expected to review the report and decide whether to keep or reject the material.'],
        ]
    ],
    [
        'id'    => 'users',
        'icon'  => 'manage_accounts',
        'color' => '#059669',
        'bg'    => '#ECFDF5',
        'title' => 'User Management',
        'desc'  => 'Manage student, faculty, and admin accounts across the platform.',
        'faqs'  => [
            ['q' => 'How do I view all registered users?',
             'a' => 'Go to "User Management" in the sidebar. You will see a table listing all registered users with their name, email, role, registration date, and status. Use the search bar to find a specific user, or filter by role using the dropdown.'],
            ['q' => 'How do I ban or suspend a user account?',
             'a' => 'In User Management, find the user and click the "Ban" button in their row. This immediately prevents them from logging in while preserving all their uploaded content and history. To reinstate the account, click "Unban". Banned users see a clear error message on the login page.'],
            ['q' => 'Can admins reset a user\'s password?',
             'a' => 'For security reasons, admins cannot view or manually reset passwords. Users must click "Forgot Password" on the login page, which sends a 6-digit verification code to their @wmsu.edu.ph email. If a user cannot access their email, contact the WMSU IT Services helpdesk.'],
            ['q' => 'How do I change a user\'s role (e.g., student to faculty)?',
             'a' => 'Role changes require a direct database update via phpMyAdmin or MySQL. Navigate to the users table, find the user record, and update the "role" column to \'faculty\' or \'admin\'. Alternatively, contact technical support to perform this change safely.'],
            ['q' => 'Who can register as a WMSU ARL Hub user?',
             'a' => 'Registration is restricted to users with a valid @wmsu.edu.ph institutional email address. The system validates this during registration and sends an email verification code. Users without a WMSU email cannot register.'],
        ]
    ],
    [
        'id'    => 'analytics',
        'icon'  => 'analytics',
        'color' => '#F59E0B',
        'bg'    => '#FFFBEB',
        'title' => 'System Analytics',
        'desc'  => 'Understand platform engagement, download trends, and user activity.',
        'faqs'  => [
            ['q' => 'What statistics does the Dashboard show?',
             'a' => 'The Dashboard provides at-a-glance metrics: Total Users (all registered accounts), Approved Materials (published in the repository), Total Downloads (sum of all file downloads), Total Reviews (community feedback count), and new user registrations this month. Charts visualize growth trends over the last 6 months.'],
            ['q' => 'How is the "Top Downloads" list calculated?',
             'a' => 'The Top Downloads list ranks materials by their "downloads_count" value, which increments each time a logged-in user downloads a file. It reflects the cumulative total since the material was published and is updated in real-time.'],
            ['q' => 'What does the User Growth chart display?',
             'a' => 'The User Growth chart is a line chart plotting the number of new user registrations per month over the past 6 months. The Downloads line shows total download counts per month. Both lines use real database data and update automatically.'],
            ['q' => 'Where can I find more detailed analytics?',
             'a' => 'Click "Full Analytics" on the Dashboard or navigate to "System Analytics" in the sidebar. This page shows category-by-category breakdowns, top contributors, user role distribution, and a more detailed download history chart.'],
            ['q' => 'Can I export analytics data?',
             'a' => 'Currently, manual export requires using phpMyAdmin to run SQL queries. A formal export/report feature is planned for a future update. For now, System Logs (Admin Audit) can be used as an activity reference report.'],
        ]
    ],
    [
        'id'    => 'audit-logs',
        'icon'  => 'receipt_long',
        'color' => '#0EA5E9',
        'bg'    => '#F0F9FF',
        'title' => 'System Logs & Audit',
        'desc'  => 'Track all admin and user actions for accountability and security.',
        'faqs'  => [
            ['q' => 'What is logged in the System Logs?',
             'a' => 'The audit log records all significant actions: user logins, material uploads, approvals and rejections, downloads, profile updates, bans, report submissions, and admin actions. Every entry includes a timestamp, the user who performed the action, and a description.'],
            ['q' => 'How long are logs retained?',
             'a' => 'Logs are stored indefinitely in the database until manually cleared by a database administrator via phpMyAdmin. There is no automatic purge schedule — all historical activity is preserved for accountability.'],
            ['q' => 'Can I filter logs by user or action type?',
             'a' => 'Yes. The System Logs page (Admin Audit) provides search and filter controls. You can filter by date range, action keyword (e.g. "login", "approve"), or search by user name to isolate specific activity.'],
            ['q' => 'What does "Completed" vs "Actioned" vs "Pending" mean?',
             'a' => '"Completed" means a successful, positive action (login, approval). "Actioned" means a corrective or critical action was taken (rejection, ban, deletion). "Pending" indicates a flag or report that has been submitted but not yet reviewed by an admin.'],
        ]
    ],
    [
        'id'    => 'troubleshooting',
        'icon'  => 'build_circle',
        'color' => '#EC4899',
        'bg'    => '#FDF2F8',
        'title' => 'Troubleshooting',
        'desc'  => 'Common issues and how to resolve them quickly.',
        'faqs'  => [
            ['q' => 'Users report they cannot log in — what should I check?',
             'a' => 'First, check User Management to confirm the account is not banned. Second, confirm the user is using their @wmsu.edu.ph email. Third, ask if they have verified their email after registration. If the account is verified and not banned but still failing, ask them to use "Forgot Password" to reset credentials.'],
            ['q' => 'A material file is not downloading — what could be wrong?',
             'a' => 'Check that the file exists in the /uploads/ directory on the server. The file_path stored in the database must match the exact filename in the uploads folder. If the file is missing, the uploader must re-submit. Also verify that the XAMPP Apache server has read permissions on the uploads folder.'],
            ['q' => 'The dashboard shows a database connection error — what do I do?',
             'a' => 'This means the MySQL service is not running. Open the XAMPP Control Panel and click "Start" next to MySQL. If MySQL fails to start, it may be that port 3306 is occupied by another service. Check the XAMPP error logs at C:\\xampp\\mysql\\data\\mysql_error.log for details.'],
            ['q' => 'Notifications are not being sent after material approval — why?',
             'a' => 'Notifications are stored in the "notifications" database table and displayed in the user\'s notification bell icon. If they are not appearing, check that the approval action in admin-moderation.php is correctly inserting into the notifications table. Email notifications require PHPMailer and SMTP to be configured in config/mailer.php.'],
            ['q' => 'File uploads are failing for faculty/students — how do I fix it?',
             'a' => 'Check that the /uploads/ directory exists in your XAMPP htdocs folder and has write permissions. In php.ini (C:\\xampp\\php\\php.ini), confirm upload_max_filesize and post_max_size are set large enough (e.g., 50M). Restart Apache in XAMPP after any php.ini changes.'],
        ]
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - WMSU ARL Hub Admin</title>
    <meta name="description" content="Admin Help Center for WMSU ARL Hub — find guides, FAQs, and troubleshooting resources.">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>tailwind.config = { theme: { extend: { colors: { primary: '#B81C2E' } } } }</script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #F4F4F6; }
        h1, h2, h3, h4, .headline { font-family: 'Plus Jakarta Sans', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }

        /* Accordion */
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s cubic-bezier(0.4,0,0.2,1), padding 0.2s;
        }
        .faq-answer.open {
            max-height: 400px;
        }
        .faq-icon {
            transition: transform 0.3s ease;
            flex-shrink: 0;
        }
        .faq-item.open .faq-icon {
            transform: rotate(45deg);
        }
        .faq-item.open > button {
            color: #B81C2E;
        }

        /* Topic card */
        .topic-card {
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .topic-card:hover {
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        /* Category sidebar button */
        .cat-btn.active {
            background: #FEF2F2;
            color: #B81C2E;
            border-color: #FECACA;
            font-weight: 700;
        }

        /* Highlight search matches */
        mark { background: #FFF3CD; color: inherit; border-radius: 2px; }

        /* Smooth scroll */
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="text-[#1A1A2E]">

<?php require_once '../includes/dashboard-nav.php'; ?>
<div class="flex">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="flex-1 ml-[240px] pt-16 min-h-screen bg-[#F4F4F6]">
        <div class="max-w-[1200px] mx-auto px-8 py-8">

            <!-- ── Hero Header ── -->
            <div class="bg-[#1A1A2E] rounded-2xl px-10 py-10 mb-8 flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-symbols-outlined text-[#B81C2E] text-[22px]">support_agent</span>
                        <span class="text-white/60 text-sm font-semibold uppercase tracking-wider">Admin Help Center</span>
                    </div>
                    <h1 class="headline text-[30px] font-bold text-white leading-tight">How can we help you?</h1>
                    <p class="text-white/60 mt-2 text-sm max-w-md">Search guides, FAQs, and troubleshooting resources for the WMSU ARL Hub admin panel.</p>
                </div>
                <!-- Search -->
                <div class="relative w-full max-w-sm z-10 flex-shrink-0">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#848494] text-[20px]">search</span>
                    <input type="text" id="helpSearch" placeholder="Search topics, questions..."
                           class="w-full pl-12 pr-4 py-3.5 bg-white border border-transparent rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#B81C2E] transition-all shadow-lg"
                           autocomplete="off">
                    <div id="searchEmpty" class="hidden absolute left-0 top-full mt-2 bg-white rounded-xl border border-black/10 px-5 py-4 w-full shadow-xl z-50">
                        <p class="text-sm text-[#848494]">No results found. Try a different keyword.</p>
                    </div>
                </div>
                <!-- Background deco -->
                <span class="material-symbols-outlined text-[200px] text-white/[0.03] absolute -right-8 -bottom-10 select-none">help_center</span>
            </div>

            <!-- ── Quick Stats ── -->
            <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-xl border border-black/[0.06] p-5 flex items-center gap-4">
                    <div class="w-10 h-10 bg-[#F9E8EA] rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[#B81C2E] text-[20px]">menu_book</span>
                    </div>
                    <div>
                        <div class="headline text-xl font-bold"><?php echo count($helpTopics); ?></div>
                        <div class="text-[11px] text-[#848494] font-medium uppercase tracking-wide">Guide Categories</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-black/[0.06] p-5 flex items-center gap-4">
                    <div class="w-10 h-10 bg-[#EEF2FF] rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-indigo-600 text-[20px]">quiz</span>
                    </div>
                    <div>
                        <div class="headline text-xl font-bold"><?php echo array_sum(array_map(fn($t)=>count($t['faqs']),$helpTopics)); ?></div>
                        <div class="text-[11px] text-[#848494] font-medium uppercase tracking-wide">FAQ Answers</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-black/[0.06] p-5 flex items-center gap-4">
                    <div class="w-10 h-10 bg-[#ECFDF5] rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-emerald-600 text-[20px]">support</span>
                    </div>
                    <div>
                        <div class="headline text-xl font-bold">24/7</div>
                        <div class="text-[11px] text-[#848494] font-medium uppercase tracking-wide">Email Support Available</div>
                    </div>
                </div>
            </div>

            <!-- ── Category Nav Pills ── -->
            <div class="flex flex-wrap gap-2 mb-8" id="catNav">
                <button onclick="filterCat('all')" data-cat="all" class="cat-btn active px-4 py-2 rounded-xl text-sm font-semibold border border-black/10 bg-white text-[#1A1A2E] transition-all">
                    All Topics
                </button>
                <?php foreach($helpTopics as $t): ?>
                <button onclick="filterCat('<?php echo $t['id']; ?>')" data-cat="<?php echo $t['id']; ?>"
                        class="cat-btn px-4 py-2 rounded-xl text-sm font-semibold border border-black/10 bg-white text-[#4A4A5A] hover:border-[#B81C2E] hover:text-[#B81C2E] transition-all">
                    <?php echo htmlspecialchars($t['title']); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- ── Topics Grid ── -->
            <div id="topicsGrid" class="space-y-5 mb-12">
                <?php foreach($helpTopics as $t): ?>
                <div class="topic-card bg-white rounded-2xl border border-black/[0.06]" data-category="<?php echo $t['id']; ?>" id="section-<?php echo $t['id']; ?>">

                    <!-- Topic Header -->
                    <div class="flex items-center gap-5 px-8 py-6 border-b border-black/[0.05]">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?php echo $t['bg']; ?>">
                            <span class="material-symbols-outlined text-[22px]" style="color:<?php echo $t['color']; ?>"><?php echo $t['icon']; ?></span>
                        </div>
                        <div class="flex-1">
                            <h2 class="headline text-[17px] font-bold text-[#1A1A2E]"><?php echo htmlspecialchars($t['title']); ?></h2>
                            <p class="text-[12px] text-[#848494] mt-0.5"><?php echo htmlspecialchars($t['desc']); ?></p>
                        </div>
                        <span class="text-[11px] font-bold text-[#848494] bg-[#F4F4F6] px-3 py-1 rounded-full"><?php echo count($t['faqs']); ?> articles</span>
                    </div>

                    <!-- FAQ Items -->
                    <div class="divide-y divide-black/[0.04]" id="faqs-<?php echo $t['id']; ?>">
                        <?php foreach($t['faqs'] as $fi => $faq):
                            $faqId = $t['id'] . '-' . $fi;
                        ?>
                        <div class="faq-item px-8" data-question="<?php echo strtolower(htmlspecialchars($faq['q'] . ' ' . $faq['a'])); ?>">
                            <button onclick="toggleFaq('<?php echo $faqId; ?>')"
                                    class="w-full flex items-center justify-between gap-4 py-4 text-left text-[14px] font-semibold text-[#1A1A2E] hover:text-[#B81C2E] transition-colors">
                                <span class="faq-q-text"><?php echo htmlspecialchars($faq['q']); ?></span>
                                <span class="material-symbols-outlined faq-icon text-[#C7C7D1] text-[20px]">add</span>
                            </button>
                            <div id="faq-<?php echo $faqId; ?>" class="faq-answer pb-0">
                                <p class="text-[13px] text-[#4A4A5A] leading-relaxed pb-5 faq-a-text"><?php echo htmlspecialchars($faq['a']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- No Results State -->
                <div id="noResults" class="hidden bg-white rounded-2xl border border-black/[0.06] p-16 text-center">
                    <span class="material-symbols-outlined text-[56px] text-[#D1D1D9] block mb-4">search_off</span>
                    <h3 class="headline text-lg font-bold text-[#1A1A2E] mb-2">No results found</h3>
                    <p class="text-[#848494] text-sm">Try a different search term, or browse all topics above.</p>
                </div>
            </div>

            <!-- ── Quick Actions Row ── -->
            <div class="grid grid-cols-3 gap-5 mb-8">
                <a href="admin-moderation.php" class="bg-white rounded-xl border border-black/[0.06] p-6 flex items-center gap-4 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 bg-[#FEF2F2] rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[#B81C2E] text-[20px]">shield</span>
                    </div>
                    <div>
                        <div class="text-[13px] font-bold text-[#1A1A2E] group-hover:text-[#B81C2E] transition-colors">Content Moderation</div>
                        <div class="text-[11px] text-[#848494]">Review pending materials</div>
                    </div>
                    <span class="material-symbols-outlined text-[#C7C7D1] text-[18px] ml-auto">arrow_forward</span>
                </a>
                <a href="admin-users.php" class="bg-white rounded-xl border border-black/[0.06] p-6 flex items-center gap-4 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 bg-[#ECFDF5] rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-emerald-600 text-[20px]">manage_accounts</span>
                    </div>
                    <div>
                        <div class="text-[13px] font-bold text-[#1A1A2E] group-hover:text-[#B81C2E] transition-colors">User Management</div>
                        <div class="text-[11px] text-[#848494]">View and manage accounts</div>
                    </div>
                    <span class="material-symbols-outlined text-[#C7C7D1] text-[18px] ml-auto">arrow_forward</span>
                </a>
                <a href="admin-audit.php" class="bg-white rounded-xl border border-black/[0.06] p-6 flex items-center gap-4 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 bg-[#F0F9FF] rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-sky-500 text-[20px]">receipt_long</span>
                    </div>
                    <div>
                        <div class="text-[13px] font-bold text-[#1A1A2E] group-hover:text-[#B81C2E] transition-colors">System Audit Logs</div>
                        <div class="text-[11px] text-[#848494]">Track all system events</div>
                    </div>
                    <span class="material-symbols-outlined text-[#C7C7D1] text-[18px] ml-auto">arrow_forward</span>
                </a>
            </div>

            <!-- ── Still Need Help Banner ── -->
            <div class="bg-[#1A1A2E] rounded-2xl p-10 flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="headline text-[22px] font-bold text-white mb-2">Still need help?</h3>
                    <p class="text-white/60 text-sm max-w-[440px]">
                        Contact the WMSU IT Services helpdesk for critical system issues, database requests, or feature support. Available Monday–Friday, 8 AM–5 PM.
                    </p>
                    <div class="flex flex-wrap items-center gap-3 mt-5">
                        <div class="flex items-center gap-2 bg-white/10 rounded-lg px-4 py-2">
                            <span class="material-symbols-outlined text-white/70 text-[16px]">email</span>
                            <span class="text-white text-[13px] font-medium">itsupport@wmsu.edu.ph</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white/10 rounded-lg px-4 py-2">
                            <span class="material-symbols-outlined text-white/70 text-[16px]">phone</span>
                            <span class="text-white text-[13px] font-medium">WMSU IT Office: Ext. 100</span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 relative z-10 flex-shrink-0">
                    <a href="mailto:itsupport@wmsu.edu.ph"
                       class="bg-[#B81C2E] text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-[#9A1624] transition-colors inline-flex items-center gap-2 shadow-lg">
                        <span class="material-symbols-outlined text-[18px]">mail</span>
                        Email Support
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/admin-audit.php"
                       class="bg-white/10 text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-white/20 transition-colors inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                        View Logs
                    </a>
                </div>
                <span class="material-symbols-outlined text-[200px] text-white/[0.03] absolute -right-8 -bottom-10 select-none">headset_mic</span>
            </div>

        </div>
        <?php require_once '../includes/dashboard-footer.php'; ?>
    </main>
</div>

<script>
// ── FAQ Toggle ──
function toggleFaq(id) {
    const answer = document.getElementById('faq-' + id);
    const faqItem = answer.closest('.faq-item');

    // Close all others in same section
    const allFaqs = faqItem.closest('.divide-y').querySelectorAll('.faq-item');
    allFaqs.forEach(item => {
        if (item !== faqItem) {
            item.classList.remove('open');
            item.querySelector('.faq-answer').classList.remove('open');
        }
    });

    faqItem.classList.toggle('open');
    answer.classList.toggle('open');
}

// ── Category Filter ──
function filterCat(cat) {
    // Update active button
    document.querySelectorAll('.cat-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.cat === cat);
    });

    // Show/hide topic cards
    document.querySelectorAll('[data-category]').forEach(card => {
        if (cat === 'all' || card.dataset.category === cat) {
            card.style.display = '';
            card.style.opacity = '0';
            card.style.transform = 'translateY(8px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.25s, transform 0.25s';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 10);
        } else {
            card.style.display = 'none';
        }
    });

    // Scroll to section
    if (cat !== 'all') {
        const target = document.getElementById('section-' + cat);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// ── Live Search ──
const searchInput = document.getElementById('helpSearch');
searchInput.addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();
    const cards = document.querySelectorAll('[data-category]');
    let anyVisible = false;

    // Reset cat nav
    document.querySelectorAll('.cat-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector('.cat-btn[data-cat="all"]').classList.add('active');

    cards.forEach(card => {
        if (!query) {
            card.style.display = '';
            const faqItems = card.querySelectorAll('.faq-item');
            faqItems.forEach(fi => fi.style.display = '');
            restoreText(card);
            anyVisible = true;
            return;
        }

        const faqItems = card.querySelectorAll('.faq-item');
        let cardHasMatch = false;

        faqItems.forEach(item => {
            const question = item.querySelector('.faq-q-text');
            const answer   = item.querySelector('.faq-a-text');
            const qText = question.textContent.toLowerCase();
            const aText = answer.textContent.toLowerCase();

            if (qText.includes(query) || aText.includes(query)) {
                item.style.display = '';
                cardHasMatch = true;
                anyVisible = true;

                // Open relevant FAQ
                const faqId = item.querySelector('.faq-answer').id.replace('faq-','');
                item.classList.add('open');
                item.querySelector('.faq-answer').classList.add('open');

                // Highlight
                highlightText(question, query);
                highlightText(answer, query);
            } else {
                item.style.display = 'none';
                item.classList.remove('open');
                item.querySelector('.faq-answer').classList.remove('open');
            }
        });

        card.style.display = cardHasMatch ? '' : 'none';
    });

    document.getElementById('noResults').style.display = anyVisible ? 'none' : '';
});

function highlightText(el, query) {
    const orig = el.dataset.orig || el.textContent;
    el.dataset.orig = orig;
    if (!query) { el.innerHTML = orig; return; }
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
    el.innerHTML = orig.replace(new RegExp(`(${escaped})`, 'gi'), '<mark>$1</mark>');
}

function restoreText(card) {
    card.querySelectorAll('.faq-q-text[data-orig], .faq-a-text[data-orig]').forEach(el => {
        el.innerHTML = el.dataset.orig;
    });
}

// Clear search on Escape
searchInput.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
    }
});
</script>
</body>
</html>
