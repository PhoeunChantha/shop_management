@props([
    'id' => 'deleteConfirmModal',
    'title' => 'Delete this item?',
    'messageBefore' => 'This action will permanently remove',
    'messageAfter' => 'from the system. This cannot be undone.',
    'cancelText' => 'Cancel',
    'confirmText' => 'Confirm Delete',
])

<div id="{{ $id }}" class="modal-backdrop-premium" data-delete-modal hidden>
    <div class="delete-modal">
        <div class="modal-warning-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div>
            <h3>{{ $title }}</h3>
            <p>
                {{ $messageBefore }}
                <strong data-delete-name></strong>
                {{ $messageAfter }}
            </p>
        </div>

        <div class="modal-actions">
            <button type="button" class="modal-cancel" data-delete-cancel>{{ $cancelText }}</button>
            <form method="POST" class="mb-0" data-delete-form>
                @csrf
                @method('DELETE')
                <button type="submit" class="modal-delete">
                    <i class="fa-solid fa-trash"></i>
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

                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            closeModal();
                        }
                    });

                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape' && !modal.hidden) {
                            closeModal();
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
