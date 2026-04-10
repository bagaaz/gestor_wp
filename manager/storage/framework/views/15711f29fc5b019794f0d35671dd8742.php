<?php $__env->startSection('title', 'Logs - WP Docker Manager'); ?>
<?php $__env->startSection('heading', 'Logs do Sistema'); ?>

<?php $__env->startSection('content'); ?>
    <!-- Tabs -->
    <div class="mb-6">
        <nav class="flex gap-1 bg-white rounded-lg shadow-sm border border-gray-200 p-1 inline-flex">
            <a href="<?php echo e(route('logs.index', ['tab' => 'activity'])); ?>"
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                      <?php echo e($tab === 'activity' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'); ?>">
                <i class="fas fa-list-ul mr-1"></i> Atividades
            </a>
            <a href="<?php echo e(route('logs.index', ['tab' => 'laravel'])); ?>"
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                      <?php echo e($tab === 'laravel' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'); ?>">
                <i class="fas fa-file-alt mr-1"></i> Log do Laravel
            </a>
        </nav>
    </div>

    <?php if($tab === 'activity'): ?>
        <!-- Activity Logs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Registro de Atividades</h3>
                <span class="text-sm text-gray-500"><?php echo e($activityLogs->total()); ?> registros</span>
            </div>

            <?php if($activityLogs->isEmpty()): ?>
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                    <p>Nenhuma atividade registrada.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3 text-left">Data</th>
                                <th class="px-6 py-3 text-left">Acao</th>
                                <th class="px-6 py-3 text-left">Site</th>
                                <th class="px-6 py-3 text-left">Descricao</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php $__currentLoopData = $activityLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-gray-500 whitespace-nowrap">
                                        <?php echo e($log->created_at->format('d/m/Y H:i:s')); ?>

                                    </td>
                                    <td class="px-6 py-3">
                                        <?php
                                            $actionColors = [
                                                'created' => 'bg-green-100 text-green-700',
                                                'removed' => 'bg-red-100 text-red-700',
                                                'error' => 'bg-red-100 text-red-700',
                                                'backup' => 'bg-blue-100 text-blue-700',
                                                'cloned' => 'bg-purple-100 text-purple-700',
                                            ];
                                            $actionIcons = [
                                                'created' => 'fa-plus-circle',
                                                'removed' => 'fa-minus-circle',
                                                'error' => 'fa-exclamation-triangle',
                                                'backup' => 'fa-download',
                                                'cloned' => 'fa-clone',
                                            ];
                                            $color = $actionColors[$log->action] ?? 'bg-gray-100 text-gray-700';
                                            $icon = $actionIcons[$log->action] ?? 'fa-info-circle';
                                        ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium <?php echo e($color); ?>">
                                            <i class="fas <?php echo e($icon); ?>"></i>
                                            <?php echo e($log->action); ?>

                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">
                                        <?php echo e($log->site?->name ?? '-'); ?>

                                    </td>
                                    <td class="px-6 py-3 text-gray-600 max-w-md truncate" title="<?php echo e($log->description); ?>">
                                        <?php echo e($log->description); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200">
                    <?php echo e($activityLogs->appends(['tab' => 'activity'])->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Laravel Log -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Log do Laravel</h3>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400">storage/logs/laravel.log</span>
                    <form id="clear-laravel-log" action="<?php echo e(route('logs.clear-laravel')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="button"
                                @click="$dispatch('confirm-action', {
                                    title: 'Limpar log do Laravel',
                                    message: 'Tem certeza que deseja limpar o log? Todo o conteúdo será apagado.',
                                    action: 'clear-laravel-log',
                                    variant: 'warning'
                                })"
                                class="text-xs text-red-600 hover:text-red-800 font-medium">
                            <i class="fas fa-trash-alt mr-1"></i> Limpar
                        </button>
                    </form>
                </div>
            </div>

            <?php if(empty($laravelLog)): ?>
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-file-alt text-4xl mb-3"></i>
                    <p>Log vazio.</p>
                </div>
            <?php else: ?>
                <div class="p-4">
                    <pre class="bg-gray-900 text-green-400 text-xs p-4 rounded-lg overflow-x-auto max-h-[600px] overflow-y-auto leading-relaxed whitespace-pre-wrap break-words"><?php echo e($laravelLog); ?></pre>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/manager/resources/views/logs/index.blade.php ENDPATH**/ ?>