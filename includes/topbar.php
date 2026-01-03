<?php
// includes/topbar.php
?>
<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    
    <header class="h-16 bg-dark-card border-b border-slate-700 flex items-center justify-between px-4 md:px-8 shrink-0 z-10">
        
        <button class="md:hidden text-slate-400 hover:text-white p-2">
            <i class="fas fa-bars text-xl"></i>
        </button>

        <h1 class="hidden md:block text-lg font-bold text-white tracking-wide">
            <?php echo $pageTitle ?? 'BudgetPro'; ?>
        </h1>

        <div class="flex items-center gap-4">
            <div class="hidden md:flex flex-col items-end mr-2">
                <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Dzisiaj jest</span>
                <span class="text-sm font-bold text-white"><?php echo date('d.m.Y'); ?></span>
            </div>
            
            <a href="settings.php" class="w-10 h-10 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition border border-slate-700 shadow-sm">
                <i class="fas fa-cog"></i>
            </a>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar">