
import 'bootstrap';
import $ from 'jquery';
import Alpine from 'alpinejs';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

window.$ = window.jQuery = $;
window.Alpine = Alpine;

toastr.options = {
    closeButton: true,
    progressBar: true,
    newestOnTop: true,
    positionClass: 'toast-top-right',
    timeOut: 4000,
};
window.toastr = toastr;

$(function () {
    let searchTimer;

    $('[data-auto-search]').on('input', function () {
        const $input = $(this);
        const query = $.trim($input.val());

        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {
            if (query.length === 0 || query.length >= 2) {
                const form = $input.closest('form').get(0);
                if (!form) return;
                // requestSubmit() actually navigates AND fires the `submit`
                // event, so <x-table-loader> can show its overlay.
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        }, 500);
    });

    // Date-range filter — daterangepicker + moment come from the CDN <script> tags
    // in the admin layout. They may finish loading just after DOMContentLoaded, so
    // wait until both are ready before initializing.
    function initDateRanges() {
        const moment = window.moment;
        const format = 'MMMM D, YYYY';
        const label = (s, e) => s.format(format) + ' - ' + e.format(format);

        $('[data-daterange]').each(function (index) {
            const $input = $(this);
            const $form = $input.closest('form');
            const fromName = $input.data('daterangeFrom') || 'date_from';
            const toName = $input.data('daterangeTo') || 'date_to';
            const $from = $form.find(`input[name="${fromName}"]`);
            const $to = $form.find(`input[name="${toName}"]`);
            const start = $from.val() ? moment($from.val(), 'YYYY-MM-DD') : null;
            const end = $to.val() ? moment($to.val(), 'YYYY-MM-DD') : null;
            const namespace = `.daterange${index}`;
            let scrollFrame = null;

            $input.daterangepicker({
                autoUpdateInput: false,
                opens: 'left',
                locale: { format: format, cancelLabel: 'Clear', separator: ' - ' },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                },
                ...(start && end ? { startDate: start, endDate: end } : {}),
            });

            // Show the picked range in the input (autoUpdateInput is off so it stays
            // empty until the admin actually chooses a range).
            if (start && end) {
                $input.val(label(start, end));
            }

            $input.on('apply.daterangepicker', function (ev, picker) {
                $input.val(label(picker.startDate, picker.endDate));
                $from.val(picker.startDate.format('YYYY-MM-DD'));
                $to.val(picker.endDate.format('YYYY-MM-DD'));
            });

            $input.on('cancel.daterangepicker', function () {
                $input.val('');
                $from.val('');
                $to.val('');
            });

            const syncPickerPosition = function () {
                if (scrollFrame) return;

                scrollFrame = window.requestAnimationFrame(function () {
                    scrollFrame = null;

                    const picker = $input.data('daterangepicker');
                    if (!picker || !picker.isShowing) return;

                    const rect = $input.get(0).getBoundingClientRect();
                    const hiddenAbove = rect.bottom < 80;
                    const hiddenBelow = rect.top > window.innerHeight - 40;

                    if (hiddenAbove || hiddenBelow) {
                        picker.hide();
                        return;
                    }

                    picker.move();
                });
            };

            $input.on('show.daterangepicker', function () {
                $('.admin-workspace').on(`scroll${namespace}`, syncPickerPosition);
                $(window).on(`resize${namespace}`, syncPickerPosition);
            });

            $input.on('hide.daterangepicker', function () {
                $('.admin-workspace').off(namespace);
                $(window).off(namespace);
            });
        });
    }

    if ($('[data-daterange]').length) {
        (function whenReady(tries) {
            if (window.moment && $.fn.daterangepicker) {
                initDateRanges();
            } else if (tries < 60) {
                setTimeout(function () { whenReady(tries + 1); }, 100);
            } else {
                console.error('Date-range picker failed to load from CDN.');
            }
        })(0);
    }

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

// Row-selection state for admin tables with bulk actions.
// Row checkboxes carry `data-row-check` and `value="{id}"`; the header
// select-all and the <x-bulk-bar> read/write this shared `selected` array.
Alpine.data('bulkSelect', () => ({
    selected: [],
    confirmingDelete: false,

    rowIds() {
        return Array.from(this.$root.querySelectorAll('input[data-row-check]')).map((b) => b.value);
    },
    get count() {
        return this.selected.length;
    },
    get allChecked() {
        const ids = this.rowIds();
        return ids.length > 0 && ids.every((id) => this.selected.includes(id));
    },
    get someChecked() {
        return this.count > 0 && !this.allChecked;
    },
    toggleAll(event) {
        this.selected = event.target.checked ? this.rowIds() : [];
    },
    clear() {
        this.selected = [];
        this.confirmingDelete = false;
    },
}));

Alpine.start();
