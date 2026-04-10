



<div x-data="{
        open: false,
        title: '',
        message: '',
        formId: '',
        variant: 'danger',
        show(e) {
            this.title = e.detail.title || 'Confirmar';
            this.message = e.detail.message || 'Tem certeza?';
            this.formId = e.detail.action || '';
            this.variant = e.detail.variant || 'danger';
            this.open = true;
        },
        proceed() {
            const form = document.getElementById(this.formId);
            if (form) form.submit();
            this.open = false;
        }
     }"
     x-on:confirm-action.window="show($event)"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-[100] overflow-y-auto"
     x-transition:enter="ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false"></div>

    
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div @click.outside="open = false"
             x-show="open"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden">

            <div class="p-6">
                <div class="flex items-start gap-4">
                    
                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
                         :class="variant === 'danger' ? 'bg-red-100' : 'bg-yellow-100'">
                        <i class="fas text-xl"
                           :class="variant === 'danger' ? 'fa-trash-alt text-red-600' : 'fa-exclamation-triangle text-yellow-600'"></i>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900" x-text="title"></h3>
                        <p class="mt-2 text-sm text-gray-600 leading-relaxed" x-text="message"></p>
                    </div>
                </div>
            </div>

            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                <button @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button @click="proceed()"
                        class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                        :class="variant === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-yellow-600 hover:bg-yellow-700'">
                    <span x-text="variant === 'danger' ? 'Remover' : 'Confirmar'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /var/www/manager/resources/views/components/confirm-modal.blade.php ENDPATH**/ ?>