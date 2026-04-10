<?php $__env->startSection('title', 'Novo Site - WP Docker Manager'); ?>
<?php $__env->startSection('heading', 'Criar Novo Site WordPress'); ?>

<?php $__env->startSection('content'); ?>
    <div class="max-w-2xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Configurações do Site</h3>
                <p class="text-sm text-gray-500 mt-1">Preencha os dados para criar um novo WordPress local.</p>
            </div>

            <form action="<?php echo e(route('sites.store')); ?>" method="POST" class="p-6 space-y-6" x-data="{ loading: false }" @submit="loading = true">
                <?php echo csrf_field(); ?>

                <!-- Nome do Site -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nome do site <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="name" id="name" required
                               pattern="[a-z0-9][a-z0-9-]*[a-z0-9]|[a-z0-9]"
                               value="<?php echo e(old('name')); ?>"
                               placeholder="meusite"
                               class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <span class="text-sm text-gray-500">.localhost</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Apenas letras minúsculas, números e hífens. Ex: meu-site, loja01</p>
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- Título -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Título do site</label>
                    <input type="text" name="title" id="title"
                           value="<?php echo e(old('title')); ?>"
                           placeholder="Meu Site WordPress"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <p class="mt-1 text-xs text-gray-400">Se não informado, será usado o nome do site.</p>
                </div>

                <!-- Versão do WordPress -->
                <div>
                    <label for="version" class="block text-sm font-medium text-gray-700 mb-1">Versão do WordPress</label>
                    <select name="version" id="version"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <option value="latest" selected>Mais recente (latest)</option>
                        <option value="6.7">6.7</option>
                        <option value="6.6">6.6</option>
                        <option value="6.5">6.5</option>
                        <option value="6.4">6.4</option>
                        <option value="6.3">6.3</option>
                        <option value="6.2">6.2</option>
                    </select>
                </div>

                <!-- Opções extras -->
                <div class="space-y-3">
                    <p class="text-sm font-medium text-gray-700">Opções adicionais</p>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="woocommerce" value="1"
                               class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm text-gray-700 font-medium">Instalar WooCommerce</span>
                            <p class="text-xs text-gray-400">Adiciona o plugin WooCommerce já ativado</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="multisite" value="1"
                               class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm text-gray-700 font-medium">WordPress Multisite</span>
                            <p class="text-xs text-gray-400">Instalar como rede multisite</p>
                        </div>
                    </label>
                </div>

                <!-- Info box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-blue-800 flex items-center gap-2">
                        <i class="fas fa-info-circle"></i> O que será configurado automaticamente
                    </h4>
                    <ul class="mt-2 text-xs text-blue-700 space-y-1">
                        <li>Idioma: Português (Brasil)</li>
                        <li>Timezone: America/Sao_Paulo</li>
                        <li>Upload máximo: 256MB</li>
                        <li>Suporte a SVG e WebP habilitado</li>
                        <li>SMTP configurado via Mailpit</li>
                        <li>WP_DEBUG ativado</li>
                        <li>Permalinks: /%postname%/</li>
                        <li>Admin: admin / admin123</li>
                    </ul>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                    <button type="submit"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!loading">
                            <span class="flex items-center gap-2"><i class="fas fa-plus"></i> Criar Site</span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Criando... (pode levar alguns minutos)
                            </span>
                        </template>
                    </button>

                    <a href="<?php echo e(route('sites.index')); ?>" class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-800">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/manager/resources/views/sites/create.blade.php ENDPATH**/ ?>