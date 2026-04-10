<?php $__env->startSection('title', 'Sites - WP Docker Manager'); ?>
<?php $__env->startSection('heading', 'Sites WordPress'); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('sites.create')); ?>" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus"></i> Novo Site
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php if($sites->isEmpty()): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-16 text-center">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fab fa-wordpress text-4xl text-blue-600"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Nenhum site WordPress</h3>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">Crie seu primeiro site WordPress. O processo leva menos de um minuto e já vem configurado com pt-BR, suporte a SVG/WebP e Mailpit.</p>
            <a href="<?php echo e(route('sites.create')); ?>" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700">
                <i class="fas fa-plus"></i> Criar Primeiro Site
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php $__currentLoopData = $sites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $site): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center
                                    <?php echo e($site->status === 'active' ? 'bg-blue-100' : 'bg-gray-100'); ?>">
                                    <i class="fab fa-wordpress text-2xl
                                        <?php echo e($site->status === 'active' ? 'text-blue-600' : 'text-gray-400'); ?>"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900"><?php echo e($site->name); ?></h3>
                                    <span class="inline-flex items-center gap-1 text-xs
                                        <?php echo e($site->status === 'active' ? 'text-green-600' : 'text-gray-500'); ?>">
                                        <span class="w-1.5 h-1.5 rounded-full
                                            <?php echo e($site->status === 'active' ? 'bg-green-500' : 'bg-gray-400'); ?>"></span>
                                        <?php echo e($site->status === 'active' ? 'Ativo' : 'Inativo'); ?>

                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-link w-4 text-gray-400 text-xs"></i>
                                <a href="<?php echo e($site->url); ?>" target="_blank" class="text-blue-600 hover:underline truncate"><?php echo e($site->url); ?></a>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-code-branch w-4 text-gray-400 text-xs"></i>
                                WordPress <?php echo e($site->wp_version); ?>

                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-database w-4 text-gray-400 text-xs"></i>
                                <?php echo e($site->db_name); ?>

                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-hdd w-4 text-gray-400 text-xs"></i>
                                <?php echo e($site->formatted_disk_usage); ?>

                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 rounded-b-xl flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <a href="<?php echo e($site->url); ?>/wp-admin" target="_blank"
                               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                                <i class="fas fa-cog"></i> Admin
                            </a>
                            <a href="<?php echo e(route('sites.show', $site)); ?>"
                               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                <i class="fas fa-info-circle"></i> Detalhes
                            </a>
                        </div>

                        <form id="delete-site-<?php echo e($site->id); ?>" action="<?php echo e(route('sites.destroy', $site)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="button"
                                    @click="$dispatch('confirm-action', {
                                        title: 'Remover site',
                                        message: 'Tem certeza que deseja remover o site <?php echo e($site->name); ?>?',
                                        action: 'delete-site-<?php echo e($site->id); ?>'
                                    })"
                                    class="text-red-400 hover:text-red-600 p-1.5" title="Remover site">
                                <i class="fas fa-trash-alt text-sm"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/manager/resources/views/sites/index.blade.php ENDPATH**/ ?>