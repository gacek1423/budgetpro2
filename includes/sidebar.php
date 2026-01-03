<?php
// includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-dark-card border-r border-slate-700 flex flex-col hidden md:flex shrink-0 transition-all duration-300 z-20" id="sidebar">
    
    <div class="h-16 flex items-center px-6 border-b border-slate-700">
        <i class="fas fa-wallet text-brand-green text-2xl mr-3"></i>
        <span class="text-xl font-bold tracking-tight text-white">Budget<span class="text-brand-green">Pro</span></span>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 custom-scrollbar">
        
        <?php if ($currentUser['role'] === 'admin'): ?>
            <div class="mb-4 px-2">
                <a href="../admin/index.php" class="flex items-center w-full px-3 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white rounded-xl shadow-lg shadow-red-900/50 transition transform hover:scale-[1.02] group">
                    <div class="bg-white/20 p-1.5 rounded-lg mr-3 group-hover:rotate-12 transition">
                        <i class="fas fa-shield-halved text-sm"></i>
                    </div>
                    <span class="font-bold tracking-wide text-sm"><?php echo __('menu_admin'); ?></span>
                </a>
            </div>
            <div class="px-3 mb-2 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php echo __('menu_main'); ?></div>
        <?php endif; ?>

        <a href="dashboard.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_dashboard'); ?></span>
        </a>
        <a href="transactions.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'transactions.php' ? 'active' : ''; ?>">
            <i class="fas fa-exchange-alt w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_transactions'); ?></span>
        </a>
        <a href="categories.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_categories'); ?></span>
        </a>
        
        <div class="mt-4 px-3 mb-2 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php echo __('menu_planning'); ?></div>
        
        <a href="budgets.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'budgets.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_budgets'); ?></span>
        </a>
        <a href="goals.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'goals.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullseye w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_goals'); ?></span>
        </a>
        <a href="debts.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'debts.php' ? 'active' : ''; ?>">
            <i class="fas fa-hand-holding-usd w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_debts'); ?></span>
        </a>
        <a href="networth.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'net_worth.php' ? 'active' : ''; ?>">
            <i class="fas fa-sack-dollar w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_networth'); ?></span>
        </a>
        <a href="planner.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'planner.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_planner'); ?></span>
        </a>

        <div class="mt-4 px-3 mb-2 text-xs font-bold text-slate-500 uppercase tracking-wider"><?php echo __('menu_tools'); ?></div>

        <a href="reports.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_reports'); ?></span>
        </a>
        <a href="family.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'family.php' ? 'active' : ''; ?>">
            <i class="fas fa-users w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_family'); ?></span>
        </a>
        <a href="import.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'import.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-import w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_import'); ?></span>
        </a>
         <a href="settings.php" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition group <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog w-6 text-center group-hover:scale-110 transition"></i> <span class="ml-3 font-medium"><?php echo __('menu_settings'); ?></span>
        </a>
    </nav>

    <div class="p-4 border-t border-slate-700">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-brand-green to-blue-500 flex items-center justify-center text-white font-bold shadow-lg">
                <?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                <p class="text-xs text-slate-400 truncate"><?php echo htmlspecialchars($currentUser['email']); ?></p>
            </div>
            <a href="../logout.php" class="text-slate-400 hover:text-red-400 transition" title="<?php echo __('menu_logout'); ?>">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>