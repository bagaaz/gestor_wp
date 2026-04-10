<?php $__env->startSection('title', 'Dashboard - WP Docker Manager'); ?>
<?php $__env->startSection('heading', 'Dashboard'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('sites.create')); ?>" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus"></i> Novo Site
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total de Sites</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo e($stats['total_sites']); ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-globe text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Sites Ativos</p>
                    <p class="text-3xl font-bold text-green-600 mt-1"><?php echo e($stats['active_sites']); ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Uso de Disco</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">
                        <?php echo e(number_format($stats['total_disk'] / 1024 / 1024, 0)); ?> MB
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-hdd text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Docker Status</p>
                    <p class="text-xl font-bold mt-1 <?php echo e($stats['is_running'] ? 'text-green-600' : 'text-red-600'); ?>">
                        <?php echo e($stats['is_running'] ? 'Rodando' : 'Parado'); ?>

                    </p>
                </div>
                <div class="w-12 h-12 <?php echo e($stats['is_running'] ? 'bg-green-100' : 'bg-red-100'); ?> rounded-xl flex items-center justify-center">
                    <i class="fab fa-docker <?php echo e($stats['is_running'] ? 'text-green-600' : 'text-red-600'); ?> text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sites List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Sites WordPress</h3>
                    <span class="text-sm text-gray-500"><?php echo e($sites->count()); ?> sites</span>
                </div>

                <?php if($sites->isEmpty()): ?>
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fab fa-wordpress text-3xl text-gray-400"></i>
                        </div>
                        <h4 class="text-gray-600 font-medium mb-2">Nenhum site ainda</h4>
                        <p class="text-gray-400 text-sm mb-4">Crie seu primeiro site WordPress para começar.</p>
                        <a href="<?php echo e(route('sites.create')); ?>" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                            <i class="fas fa-plus"></i> Criar Site
                        </a>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php $__currentLoopData = $sites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $site): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                            <?php echo e($site->status === 'active' ? 'bg-green-100' : 'bg-gray-100'); ?>">
                                            <i class="fab fa-wordpress text-lg
                                                <?php echo e($site->status === 'active' ? 'text-green-600' : 'text-gray-400'); ?>"></i>
                                        </div>
                                        <div>
                                            <a href="<?php echo e(route('sites.show', $site)); ?>" class="text-sm font-semibold text-gray-900 hover:text-blue-600">
                                                <?php echo e($site->name); ?>

                                            </a>
                                            <div class="flex items-center gap-3 mt-0.5">
                                                <a href="<?php echo e($site->url); ?>" target="_blank" class="text-xs text-blue-500 hover:underline">
                                                    <?php echo e($site->url); ?> <i class="fas fa-external-link-alt text-[10px]"></i>
                                                </a>
                                                <span class="text-xs text-gray-400">WP <?php echo e($site->wp_version); ?></span>
                                                <span class="text-xs text-gray-400"><?php echo e($site->formatted_disk_usage); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                                            <?php echo e($site->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'); ?>">
                                            <span class="w-1.5 h-1.5 rounded-full
                                                <?php echo e($site->status === 'active' ? 'bg-green-500' : 'bg-gray-400'); ?>"></span>
                                            <?php echo e($site->status === 'active' ? 'Ativo' : 'Inativo'); ?>

                                        </span>
                                        <a href="<?php echo e($site->url); ?>/wp-admin" target="_blank"
                                           class="text-gray-400 hover:text-blue-600 p-1" title="WP Admin">
                                            <i class="fas fa-cog text-sm"></i>
                                        </a>
                                        <a href="<?php echo e(route('sites.show', $site)); ?>"
                                           class="text-gray-400 hover:text-blue-600 p-1" title="Detalhes">
                                            <i class="fas fa-arrow-right text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar: Containers + Activity + Quick Links -->
        <div class="space-y-6">
            <!-- Quick Links -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Acesso Rápido</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="http://localhost:8080" target="_blank"
                       class="flex flex-col items-center gap-2 p-3 rounded-lg bg-orange-50 hover:bg-orange-100 transition-colors">
                        <i class="fas fa-database text-orange-600 text-lg"></i>
                        <span class="text-xs font-medium text-orange-700">phpMyAdmin</span>
                    </a>
                    <a href="http://localhost:8025" target="_blank"
                       class="flex flex-col items-center gap-2 p-3 rounded-lg bg-blue-50 hover:bg-blue-100 transition-colors">
                        <i class="fas fa-envelope text-blue-600 text-lg"></i>
                        <span class="text-xs font-medium text-blue-700">Mailpit</span>
                    </a>
                </div>
            </div>

            <!-- Containers Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Containers Docker</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php $__currentLoopData = $containers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $container): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="px-6 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full <?php echo e($container['running'] ? 'bg-green-500' : 'bg-red-500'); ?>"></span>
                                <span class="text-sm text-gray-700"><?php echo e(str_replace('wp-', '', $container['name'])); ?></span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded
                                <?php echo e($container['running'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                                <?php echo e($container['running'] ? 'running' : 'stopped'); ?>

                            </span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Atividade Recente</h3>
                </div>
                <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                    <?php $__empty_1 = true; $__currentLoopData = $recentLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="px-6 py-3">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-<?php echo e($log->action === 'created' ? 'plus-circle text-green-500' : ($log->action === 'removed' ? 'minus-circle text-red-500' : 'info-circle text-blue-500')); ?> mt-0.5"></i>
                                <div>
                                    <p class="text-sm text-gray-700"><?php echo e($log->description); ?></p>
                                    <p class="text-xs text-gray-400 mt-0.5"><?php echo e($log->created_at->diffForHumans()); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="px-6 py-8 text-center text-gray-400 text-sm">
                            Nenhuma atividade registrada.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/manager/resources/views/dashboard/index.blade.php ENDPATH**/ ?>