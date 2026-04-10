<?php $__env->startSection('title', $site->name . ' - WP Docker Manager'); ?>
<?php $__env->startSection('heading', 'Site: ' . $site->name); ?>

<?php $__env->startSection('actions'); ?>
    <div class="flex items-center gap-2">
        <a href="<?php echo e($site->url); ?>" target="_blank"
           class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
            <i class="fas fa-external-link-alt"></i> Abrir Site
        </a>
        <a href="<?php echo e($site->url); ?>/wp-admin" target="_blank"
           class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            <i class="fas fa-cog"></i> WP Admin
        </a>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Site Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Informações do Site</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">URL</p>
                            <a href="<?php echo e($site->url); ?>" target="_blank" class="text-sm text-blue-600 hover:underline"><?php echo e($site->url); ?></a>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Status</p>
                            <span class="inline-flex items-center gap-1 text-sm
                                <?php echo e($site->status === 'active' ? 'text-green-600' : 'text-gray-500'); ?>">
                                <span class="w-2 h-2 rounded-full <?php echo e($site->status === 'active' ? 'bg-green-500' : 'bg-gray-400'); ?>"></span>
                                <?php echo e($site->status === 'active' ? 'Ativo' : 'Inativo'); ?>

                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">WordPress</p>
                            <p class="text-sm text-gray-900"><?php echo e($site->wp_version); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">PHP</p>
                            <p class="text-sm text-gray-900"><?php echo e($site->php_version); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Banco de Dados</p>
                            <p class="text-sm text-gray-900"><?php echo e($site->db_name); ?> (<?php echo e($site->formatted_db_size); ?>)</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Uso de Disco</p>
                            <p class="text-sm text-gray-900"><?php echo e($site->formatted_disk_usage); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Admin</p>
                            <p class="text-sm text-gray-900"><?php echo e($site->admin_user); ?> (<?php echo e($site->admin_email); ?>)</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Criado em</p>
                            <p class="text-sm text-gray-900"><?php echo e($site->created_at->format('d/m/Y H:i')); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plugins -->
            <?php if(!empty($info['plugins'])): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Plugins (<?php echo e(count($info['plugins'])); ?>)</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php $__currentLoopData = $info['plugins']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plugin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="px-6 py-3 flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-medium text-gray-900"><?php echo e($plugin['name'] ?? 'N/A'); ?></span>
                                    <span class="text-xs text-gray-400 ml-2">v<?php echo e($plugin['version'] ?? '?'); ?></span>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded
                                    <?php echo e(($plugin['status'] ?? '') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'); ?>">
                                    <?php echo e($plugin['status'] ?? 'inactive'); ?>

                                </span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Ações</h3>
                <div class="space-y-2">
                    <form action="<?php echo e(route('sites.backup', $site)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-download w-5 text-center text-gray-400"></i>
                            Fazer Backup
                        </button>
                    </form>

                    <div x-data="{ showClone: false }">
                        <button @click="showClone = !showClone" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-clone w-5 text-center text-gray-400"></i>
                            Clonar Site
                        </button>
                        <form x-show="showClone" x-cloak action="<?php echo e(route('sites.clone', $site)); ?>" method="POST" class="mt-2 space-y-2">
                            <?php echo csrf_field(); ?>
                            <input type="text" name="target_name" placeholder="nome-do-clone" required
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm hover:bg-blue-700">
                                Clonar
                            </button>
                        </form>
                    </div>

                    <a href="<?php echo e(route('sites.export', $site)); ?>"
                       class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-file-archive w-5 text-center"></i>
                        Exportar para Produção
                    </a>

                    <a href="http://localhost:8080/index.php?db=<?php echo e($site->db_name); ?>" target="_blank"
                       class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-database w-5 text-center text-gray-400"></i>
                        phpMyAdmin
                        <i class="fas fa-external-link-alt text-xs ml-auto text-gray-300"></i>
                    </a>

                    <hr class="my-3">

                    <form id="delete-site-<?php echo e($site->id); ?>" action="<?php echo e(route('sites.destroy', $site)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="button"
                                @click="$dispatch('confirm-action', {
                                    title: 'Remover site e banco de dados',
                                    message: 'ATENÇÃO: Isso vai remover o site <?php echo e($site->name); ?> e seu banco de dados. Essa ação não pode ser desfeita.',
                                    action: 'delete-site-<?php echo e($site->id); ?>'
                                })"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                            <i class="fas fa-trash-alt w-5 text-center"></i>
                            Remover Site
                        </button>
                    </form>
                </div>
            </div>

            <!-- Backups -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Backups</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $backups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $backup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="px-6 py-3">
                            <p class="text-sm text-gray-700"><?php echo e($backup->created_at->format('d/m/Y H:i')); ?></p>
                            <p class="text-xs text-gray-400"><?php echo e($backup->type); ?></p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="px-6 py-6 text-center text-gray-400 text-sm">
                            Nenhum backup registrado.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Histórico</h3>
                </div>
                <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                    <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="px-6 py-3">
                            <p class="text-sm text-gray-700"><?php echo e($log->description); ?></p>
                            <p class="text-xs text-gray-400"><?php echo e($log->created_at->diffForHumans()); ?></p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/manager/resources/views/sites/show.blade.php ENDPATH**/ ?>