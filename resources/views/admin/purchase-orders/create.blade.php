@php
    // Seed the line editor from old() after a validation round-trip, otherwise a
    // single empty line. Empty rows are dropped server-side, so extra blanks are safe.
    $initialLines = collect(old('items', []))
        ->map(fn ($row) => [
            'stockable' => (string) ($row['stockable'] ?? ''),
            'qty' => $row['quantity_ordered'] ?? '',
            'cost' => $row['unit_cost'] ?? '',
        ])
        ->values()
        ->all();

    $supplierNames = $suppliers->pluck('name', 'id')->map(fn ($n) => (string) $n)->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Restock') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('New Purchase Order') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page po-create">
        <form method="POST" action="{{ route('admin.purchase-orders.store') }}" class="po-form"
              x-data="poForm({
                  lines: @js($initialLines),
                  meta: @js($stockableMeta),
                  suppliers: @js($supplierNames),
                  supplier: @js(old('supplier_id', '')),
                  status: @js(old('status', 'draft')),
              })"
              @submit="onSubmit($event)">
            @csrf

            @if (session('error'))
                <div class="po-alert po-alert--error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="po-layout">
                {{-- ============================ MAIN COLUMN ============================ --}}
                <div class="po-main">

                    {{-- Order details --}}
                    <section class="po-card">
                        <header class="po-card__head">
                            <h3>{{ __('Order details') }}</h3>
                            <p>{{ __('Who you are buying from and when it should land.') }}</p>
                        </header>
                        <div class="po-card__body">
                            <div class="po-grid po-grid--2">
                                <div class="po-field">
                                    <x-select name="supplier_id" label="{{ __('Supplier') }}" size="sm"
                                              :options="$suppliers" optionValue="id" optionLabel="name"
                                              :value="old('supplier_id')" placeholder="{{ __('Select supplier') }}"
                                              searchable required x-model="supplier" />
                                </div>
                                <div class="po-field">
                                    <label for="expected_at" class="po-label">{{ __('Expected arrival') }} <span class="po-label__opt">{{ __('optional') }}</span></label>
                                    <div class="po-input-wrap">
                                        <i class="fa-regular fa-calendar"></i>
                                        <input type="date" name="expected_at" id="expected_at" class="form-input po-input po-input--icon"
                                               value="{{ old('expected_at') }}" min="{{ now()->toDateString() }}">
                                    </div>
                                    @error('expected_at')<p class="po-error">{{ $message }}</p>@enderror
                                </div>
                                <div class="po-field po-field--full">
                                    <label for="notes" class="po-label">{{ __('Notes') }} <span class="po-label__opt">{{ __('optional') }}</span></label>
                                    <textarea name="notes" id="notes" class="form-input po-input po-textarea" rows="2"
                                              placeholder="{{ __('Internal note for the receiving team, supplier reference, payment terms…') }}">{{ old('notes') }}</textarea>
                                    @error('notes')<p class="po-error">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Line items --}}
                    <section class="po-card">
                        <header class="po-card__head po-card__head--row">
                            <div>
                                <h3>{{ __('Line items') }}</h3>
                                <p>{{ __('Pick a product or variant, set the quantity and what you pay per unit.') }}</p>
                            </div>
                            <button type="button" class="btn-flat" @click="addLine()">
                                <i class="fa-solid fa-plus"></i><span>{{ __('Add line') }}</span>
                            </button>
                        </header>

                        <div class="po-lines-wrap">
                            <table class="po-lines">
                                <colgroup>
                                    <col class="po-col-n">
                                    <col class="po-col-product">
                                    <col class="po-col-qty">
                                    <col class="po-col-cost">
                                    <col class="po-col-total">
                                    <col class="po-col-x">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Product / variant') }}</th>
                                        <th class="ta-r">{{ __('Qty') }}</th>
                                        <th class="ta-r">{{ __('Unit cost') }}</th>
                                        <th class="ta-r">{{ __('Line total') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(line, i) in lines" :key="line.key">
                                        <tr class="po-line" :class="{ 'is-empty': !line.stockable }">
                                            <td class="po-line__n" x-text="i + 1"></td>
                                            <td class="po-line__product">
                                                <x-select :options="$stockables" size="sm" searchable
                                                          placeholder="{{ __('Search product or SKU…') }}"
                                                          x-model="line.stockable" ::name="`items[${i}][stockable]`"
                                                          @change="onPick(line)" />
                                                <p class="po-line__meta" x-show="line.stockable" x-cloak>
                                                    <span x-text="metaFor(line).sku ? 'SKU ' + metaFor(line).sku : @js(__('No SKU'))"></span>
                                                    <span>{{ __('On hand') }} <b x-text="metaFor(line).stock ?? 0"></b></span>
                                                    <span x-show="metaFor(line).cost !== null">{{ __('Last cost') }} <b x-text="money(metaFor(line).cost)"></b></span>
                                                </p>
                                            </td>
                                            <td class="po-line__qty" data-label="{{ __('Qty') }}">
                                                <input type="number" class="form-input po-input ta-r" min="1" step="1" inputmode="numeric"
                                                       placeholder="0" x-model="line.qty" :name="`items[${i}][quantity_ordered]`">
                                            </td>
                                            <td class="po-line__cost" data-label="{{ __('Unit cost') }}">
                                                <div class="po-money">
                                                    <span>$</span>
                                                    <input type="number" class="form-input po-input ta-r" min="0" step="0.01" inputmode="decimal"
                                                           placeholder="0.00" x-model="line.cost" :name="`items[${i}][unit_cost]`">
                                                </div>
                                            </td>
                                            <td class="po-line__total ta-r" data-label="{{ __('Line total') }}" x-text="lineTotal(line) > 0 ? money(lineTotal(line)) : '—'"></td>
                                            <td class="po-line__x">
                                                <button type="button" class="po-remove" @click="removeLine(i)" :disabled="lines.length === 1 && !line.stockable"
                                                        title="{{ __('Remove line') }}" aria-label="{{ __('Remove line') }}">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">
                                            <button type="button" class="po-addlink" @click="addLine()">
                                                <i class="fa-solid fa-plus"></i>{{ __('Add another line') }}
                                            </button>
                                        </td>
                                        <td class="ta-r po-foot-k">{{ __('Units') }}</td>
                                        <td class="ta-r po-foot-v" x-text="totalUnits().toLocaleString()"></td>
                                        <td class="ta-r po-foot-v" x-text="money(subtotal())"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @error('items')<p class="po-error po-error--block">{{ $message }}</p>@enderror
                        <p class="po-error po-error--block" x-show="submitted && filledLines().length === 0" x-cloak>
                            {{ __('Add at least one product or variant to the purchase order.') }}
                        </p>
                    </section>
                </div>

                {{-- ============================ SIDEBAR ============================ --}}
                <aside class="po-side">
                    <section class="po-card po-summary">
                        <header class="po-card__head">
                            <h3>{{ __('Summary') }}</h3>
                        </header>
                        <div class="po-card__body">
                            {{-- Status as a visible choice rather than a dropdown --}}
                            <fieldset class="po-status">
                                <legend class="po-label">{{ __('Create as') }}</legend>
                                <label class="po-status__opt" :class="{ 'is-on': status === 'draft' }">
                                    <input type="radio" name="status" value="draft" x-model="status">
                                    <span class="po-status__dot"></span>
                                    <span class="po-status__text">
                                        <b>{{ __('Draft') }}</b>
                                        <small>{{ __('Editable. Not sent, stock untouched.') }}</small>
                                    </span>
                                </label>
                                <label class="po-status__opt" :class="{ 'is-on': status === 'ordered' }">
                                    <input type="radio" name="status" value="ordered" x-model="status">
                                    <span class="po-status__dot"></span>
                                    <span class="po-status__text">
                                        <b>{{ __('Ordered') }}</b>
                                        <small>{{ __('Placed with the supplier, expected incoming.') }}</small>
                                    </span>
                                </label>
                            </fieldset>
                            @error('status')<p class="po-error">{{ $message }}</p>@enderror

                            <dl class="po-facts">
                                <div>
                                    <dt>{{ __('Supplier') }}</dt>
                                    <dd x-text="supplier && suppliers[supplier] ? suppliers[supplier] : '—'" :class="{ 'is-muted': !supplier }"></dd>
                                </div>
                                <div>
                                    <dt>{{ __('Lines') }}</dt>
                                    <dd x-text="filledLines().length"></dd>
                                </div>
                                <div>
                                    <dt>{{ __('Units') }}</dt>
                                    <dd x-text="totalUnits().toLocaleString()"></dd>
                                </div>
                                <div class="po-facts__total">
                                    <dt>{{ __('Order total') }}</dt>
                                    <dd x-text="money(subtotal())"></dd>
                                </div>
                            </dl>

                            <div class="po-actions">
                                <button type="submit" class="btn-flat btn-flat--primary po-submit" :disabled="saving">
                                    <i class="fa-solid" :class="saving ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                    <span x-text="status === 'ordered' ? @js(__('Create & mark ordered')) : @js(__('Save draft'))"></span>
                                </button>
                                <a href="{{ route('admin.purchase-orders.index') }}" class="btn-flat po-cancel">{{ __('Cancel') }}</a>
                            </div>
                            <p class="po-hint">{{ __('A PO number is assigned automatically on save.') }}</p>
                        </div>
                    </section>
                </aside>
            </div>
        </form>
    </div>

    @push('js')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('poForm', (cfg) => ({
                lines: [],
                meta: cfg.meta || {},
                suppliers: cfg.suppliers || {},
                supplier: cfg.supplier || '',
                status: cfg.status || 'draft',
                submitted: false,
                saving: false,
                seq: 0,

                init() {
                    const seed = Array.isArray(cfg.lines) ? cfg.lines : [];
                    seed.forEach((l) => this.lines.push(this.blank(l)));
                    if (this.lines.length === 0) this.addLine();
                },

                blank(from = {}) {
                    return { key: ++this.seq, stockable: from.stockable || '', qty: from.qty ?? '', cost: from.cost ?? '' };
                },
                addLine() {
                    this.lines.push(this.blank());
                    this.$nextTick(() => {
                        const rows = this.$root.querySelectorAll('.po-line');
                        rows[rows.length - 1]?.querySelector('.x-select__control')?.focus();
                    });
                },
                removeLine(i) {
                    this.lines.splice(i, 1);
                    if (this.lines.length === 0) this.addLine();
                },
                metaFor(line) {
                    return this.meta[line.stockable] || { sku: '', stock: null, cost: null };
                },
                // Pre-fill unit cost with the last known cost when a product is picked
                // (only if the admin hasn't typed a cost yet), and default qty to 1.
                onPick(line) {
                    const m = this.metaFor(line);
                    if (line.stockable && (line.cost === '' || line.cost === null) && m.cost !== null) line.cost = m.cost;
                    if (line.stockable && (line.qty === '' || line.qty === null)) line.qty = 1;
                },
                lineTotal(line) {
                    const q = parseFloat(line.qty), c = parseFloat(line.cost);
                    return (isFinite(q) && isFinite(c)) ? Math.max(0, q * c) : 0;
                },
                filledLines() { return this.lines.filter((l) => l.stockable); },
                totalUnits() { return this.filledLines().reduce((a, l) => a + (parseInt(l.qty, 10) || 0), 0); },
                subtotal() { return this.filledLines().reduce((a, l) => a + this.lineTotal(l), 0); },
                money(v) { return '$' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

                onSubmit(e) {
                    this.submitted = true;
                    if (this.filledLines().length === 0) {
                        e.preventDefault();
                        this.$root.querySelector('.po-lines')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }
                    this.saving = true;
                },
            }));
        });
    </script>
    @endpush
</x-app-layout>
