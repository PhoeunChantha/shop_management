
import 'bootstrap'; 
import $ from 'jquery';
import Alpine from 'alpinejs';

window.$ = window.jQuery = $;
window.Alpine = Alpine;

$(function () {
    let searchTimer;

    $('[data-auto-search]').on('input', function () {
        const $input = $(this);
        const query = $.trim($input.val());

        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {
            if (query.length === 0 || query.length >= 2) {
                $input.closest('form').trigger('submit');
            }
        }, 500);
    });

    $('[data-avatar-input]').on('change', function () {
        const file = this.files && this.files[0];
        const $field = $(this).closest('.avatar-upload-field');
        const $preview = $field.find('[data-avatar-preview]');
        const $initial = $field.find('[data-avatar-initial]');
        const $filename = $field.find('[data-avatar-filename]');

        if (!file) {
            $filename.text('No file selected');
            return;
        }

        $filename.text(file.name);

        if (file.type && file.type.startsWith('image/')) {
            const previewUrl = URL.createObjectURL(file);

            $preview.attr('src', previewUrl).prop('hidden', false);
            $initial.prop('hidden', true);

            $preview.one('load', function () {
                URL.revokeObjectURL(previewUrl);
            });
        }
    });

    $('[data-permission-group-select]').each(function () {
        const groupToggle = this;
        const groupName = groupToggle.dataset.permissionGroupSelect;
        const $form = $(groupToggle).closest('form');
        const $permissions = $form.find(`[data-permission-group="${groupName}"]`);

        const syncGroupState = function () {
            const checkedCount = $permissions.filter(':checked').length;

            groupToggle.checked = checkedCount > 0 && checkedCount === $permissions.length;
            groupToggle.indeterminate = checkedCount > 0 && checkedCount < $permissions.length;
        };

        $(groupToggle).on('change', function () {
            $permissions.prop('checked', this.checked);
            this.indeterminate = false;
        });

        $permissions.on('change', syncGroupState);
        syncGroupState();
    });

    $('[data-add-permission-input]').on('click', function () {
        const $list = $('[data-permission-input-list]');
        const inputRow = `
            <div class="dynamic-input-row">
                <input type="text" name="names[]" class="form-input" placeholder="e.g. view users">
                <button type="button" class="dynamic-remove-button" data-remove-permission-input aria-label="Remove permission input">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;

        $list.append(inputRow);
        $list.find('input').last().trigger('focus');
    });

    $(document).on('click', '[data-remove-permission-input]', function () {
        const $rows = $('[data-permission-input-list] .dynamic-input-row');

        if ($rows.length === 1) {
            $(this).closest('.dynamic-input-row').find('input').val('').trigger('focus');
            return;
        }

        $(this).closest('.dynamic-input-row').remove();
    });
});

Alpine.start();
