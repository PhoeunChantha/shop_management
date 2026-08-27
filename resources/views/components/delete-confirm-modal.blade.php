@props([
    'id' => 'deleteConfirmModal',
    'title' => 'Delete this item?',
    'messageBefore' => 'This action will permanently remove',
    'messageAfter' => 'from the system. This cannot be undone.',
    'cancelText' => 'Keep it',
    'confirmText' => 'Yes, delete',
])

<div id="{{ $id }}" class="modal-backdrop-premium" data-delete-modal hidden>
    <div class="delete-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">

        {{-- Danger icon zone --}}
        <div class="delete-modal__icon-zone">
            <div class="delete-modal__icon-ring">
                <i class="fa-solid fa-trash-can"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="delete-modal__body">
            <h3 id="{{ $id }}-title">{{ $title }}</h3>
            <p>{{ $messageBefore }} <strong data-delete-name></strong> {{ $messageAfter }}</p>
        </div>

        {{-- Warning strip --}}
        <div class="delete-modal__warning">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ __('This action cannot be undone') }}</span>
        </div>

        {{-- Actions --}}
        <div class="delete-modal__actions">
            <button type="button" class="delete-modal__cancel" data-delete-cancel>
                {{ $cancelText }}
            </button>
            <form method="POST" class="mb-0 flex-1" data-delete-form>
                @csrf
                @method('DELETE')
                <button type="submit" class="delete-modal__confirm">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>{{ $confirmText }}</span>
                </button>
            </form>
        </div>
    </div>
</div>

@once
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-delete-modal]').forEach((modal) => {
                    const form = modal.querySelector('[data-delete-form]');
                    const itemName = modal.querySelector('[data-delete-name]');
                    const cancelButton = modal.querySelector('[data-delete-cancel]');
                    const triggers = document.querySelectorAll(`[data-delete-modal-target="${modal.id}"]`);

                    const closeModal = () => {
                        modal.hidden = true;
                        form.action = '';
                        itemName.textContent = '';
                    };

                    triggers.forEach((button) => {
                        button.addEventListener('click', () => {
                            form.action = button.dataset.deleteAction;
                            itemName.textContent = button.dataset.deleteName;
                            modal.hidden = false;
                            cancelButton.focus();
                        });
                    });

                    cancelButton.addEventListener('click', closeModal);

                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) closeModal();
                    });

                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && !modal.hidden) closeModal();
                    });
                });
            });
        </script>
    @endpush
@endonce
