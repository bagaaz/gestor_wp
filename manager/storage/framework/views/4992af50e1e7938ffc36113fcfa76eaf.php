<?php $__env->startSection('title', 'Configurações - WP Docker Manager'); ?>
<?php $__env->startSection('heading', 'Configurações'); ?>

<?php $__env->startSection('content'); ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Credenciais WordPress -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Credenciais WordPress</h3>
                <p class="text-sm text-gray-500 mt-1">Credenciais padrão usadas ao criar novos sites.</p>
            </div>
            <form action="<?php echo e(route('settings.update')); ?>" method="POST" class="p-6 space-y-4">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuário Admin</label>
                    <input type="text" name="default_admin_user"
                           value="<?php echo e($settings['default_admin_user'] ?? 'devconecta'); ?>"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha Admin</label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'" name="default_admin_password"
                               value="<?php echo e($settings['default_admin_password'] ?? 'Ga96911431@'); ?>"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none pr-10">
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Admin</label>
                    <input type="email" name="default_admin_email"
                           value="<?php echo e($settings['default_admin_email'] ?? 'admin@localhost.test'); ?>"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-1"></i> Salvar Credenciais WordPress
                </button>
            </form>
        </div>

        <!-- Credenciais MySQL -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Credenciais MySQL</h3>
                <p class="text-sm text-gray-500 mt-1">Credenciais do banco de dados local.</p>
            </div>
            <form action="<?php echo e(route('settings.update')); ?>" method="POST" class="p-6 space-y-4">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha Root</label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'" name="mysql_root_password"
                               value="<?php echo e($settings['mysql_root_password'] ?? 'root'); ?>"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none pr-10">
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuário WordPress</label>
                    <input type="text" name="mysql_user"
                           value="<?php echo e($settings['mysql_user'] ?? 'wordpress'); ?>"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha WordPress</label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'" name="mysql_password"
                               value="<?php echo e($settings['mysql_password'] ?? 'wordpress'); ?>"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none pr-10">
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-1"></i> Salvar Credenciais MySQL
                </button>
            </form>

            <!-- Info Box -->
            <div class="px-6 pb-6">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <p class="text-xs text-amber-700">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Alterar credenciais MySQL aqui <strong>não</strong> altera o container em execução.
                        Estas são apenas referências visuais e valores padrão para novos sites.
                    </p>
                </div>
            </div>
        </div>

        <!-- Logo do Login -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Logo do Login WordPress</h3>
                <p class="text-sm text-gray-500 mt-1">Imagem exibida na tela de login (/wp-admin) de todos os sites.</p>
            </div>
            <div class="p-6">
                <!-- Preview da logo atual -->
                <div class="mb-6 p-6 bg-gray-100 rounded-xl flex flex-col items-center">
                    <p class="text-xs text-gray-500 mb-3 uppercase tracking-wider font-medium">Logo Atual</p>
                    <?php if($hasLogo): ?>
                        <div class="bg-white rounded-lg p-4 shadow-sm w-full max-w-xs">
                            <img src="data:image/svg+xml;base64,<?php echo e(base64_encode(file_get_contents(base_path('../docker/assets/login-logo.svg')))); ?>"
                                 alt="Login Logo" class="w-full h-20 object-contain">
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg p-8 shadow-sm text-center">
                            <i class="fas fa-image text-4xl text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-400">Nenhuma logo definida</p>
                        </div>
                    <?php endif; ?>
                </div>

                <form action="<?php echo e(route('settings.logo')); ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Enviar nova logo</label>
                        <div class="flex items-center gap-3">
                            <label class="flex-1 flex items-center justify-center px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                                <div class="text-center">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-lg mb-1"></i>
                                    <p class="text-sm text-gray-600">Clique para selecionar</p>
                                    <p class="text-xs text-gray-400">SVG, PNG, JPG ou WebP (max 2MB)</p>
                                </div>
                                <input type="file" name="login_logo" accept=".svg,.png,.jpg,.jpeg,.webp" class="hidden"
                                       onchange="document.getElementById('logo-filename').textContent = this.files[0]?.name || ''">
                            </label>
                        </div>
                        <p id="logo-filename" class="mt-1 text-sm text-blue-600 font-medium"></p>
                        <?php $__errorArgs = ['login_logo'];
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
                    <button type="submit" class="w-full bg-green-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                        <i class="fas fa-upload mr-1"></i> Atualizar Logo em Todos os Sites
                    </button>
                </form>
            </div>
        </div>

        <!-- Referência Rápida -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Referência Rápida</h3>
                <p class="text-sm text-gray-500 mt-1">Todas as credenciais e acessos do ambiente.</p>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2"><i class="fab fa-wordpress mr-1"></i> WordPress</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <span class="text-gray-500">Usuário:</span>
                            <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs"><?php echo e($settings['default_admin_user'] ?? 'devconecta'); ?></code>
                            <span class="text-gray-500">Senha:</span>
                            <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs"><?php echo e($settings['default_admin_password'] ?? 'Ga96911431@'); ?></code>
                            <span class="text-gray-500">Email:</span>
                            <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs"><?php echo e($settings['default_admin_email'] ?? 'admin@localhost.test'); ?></code>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-database mr-1"></i> MySQL</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <span class="text-gray-500">Host:</span>
                            <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs">mysql (localhost:3309)</code>
                            <span class="text-gray-500">Root:</span>
                            <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs">root / <?php echo e($settings['mysql_root_password'] ?? 'root'); ?></code>
                            <span class="text-gray-500">WP User:</span>
                            <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs"><?php echo e($settings['mysql_user'] ?? 'wordpress'); ?> / <?php echo e($settings['mysql_password'] ?? 'wordpress'); ?></code>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-link mr-1"></i> URLs</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <span class="text-gray-500">Painel:</span>
                            <a href="http://manager.localhost" class="text-blue-600 hover:underline text-xs">manager.localhost</a>
                            <span class="text-gray-500">phpMyAdmin:</span>
                            <a href="http://localhost:8080" target="_blank" class="text-blue-600 hover:underline text-xs">localhost:8080</a>
                            <span class="text-gray-500">Mailpit:</span>
                            <a href="http://localhost:8025" target="_blank" class="text-blue-600 hover:underline text-xs">localhost:8025</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/manager/resources/views/settings/index.blade.php ENDPATH**/ ?>